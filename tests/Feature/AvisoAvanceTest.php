<?php

namespace Tests\Feature;

use App\Models\Grupo;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AvisoAvanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_special_education_teacher_can_publish_and_family_sees_only_linked_notice(): void
    {
        Config::set('demo.enabled', true);
        $this->seed(DatabaseSeeder::class);
        // Estos escenarios verifican permisos después del cambio inicial de contraseña.
        User::query()->update(['cambiar_password' => false]);
        $special = User::where('codigo_usuario', 'DOC-007')->firstOrFail();
        $guardian = User::where('codigo_usuario', 'ENC-0001')->firstOrFail();
        $student = $guardian->encargado->estudiantes()->firstOrFail();

        $this->actingAs($special)->post(route('avisos.store'), [
            'estudiante_id' => $student->id,
            'asunto' => 'Aviso de prueba',
            'mensaje' => 'Mensaje ficticio.',
        ])->assertRedirect();

        $this->actingAs($guardian)->get(route('avisos.index'))->assertOk()->assertSee('Aviso de prueba');
        $this->assertDatabaseHas('avisos_avance', ['asunto' => 'Aviso de prueba', 'autor_id' => $special->id]);
    }

    public function test_teacher_publishes_group_announcement_and_guardian_sees_only_linked_groups(): void
    {
        Config::set('demo.enabled', true);
        $this->seed(DatabaseSeeder::class);
        // Estos escenarios verifican permisos después del cambio inicial de contraseña.
        User::query()->update(['cambiar_password' => false]);
        $teacher = User::where('codigo_usuario', 'DOC-001')->firstOrFail();
        $guardian = User::where('codigo_usuario', 'ENC-0001')->firstOrFail();
        $group = $teacher->gruposAsignados()->wherePivot('tipo', 'titular')->firstOrFail();

        $this->actingAs($teacher)->post(route('avisos.store'), [
            'grupo_id' => $group->id,
            'asunto' => 'Anuncio para familias',
            'mensaje' => 'Reunión ficticia del grupo.',
        ])->assertRedirect();

        $this->actingAs($guardian)->get(route('avisos.index'))->assertOk()
            ->assertSee('Anuncio para familias')->assertSee('Anuncio al grupo');
        $this->assertTrue($guardian->encargado->estudiantes()->whereHas('asignaciones', fn ($q) => $q->where('grupo_id', $group->id))->exists());
    }

    public function test_teacher_cannot_publish_to_an_unassigned_group(): void
    {
        Config::set('demo.enabled', true);
        $this->seed(DatabaseSeeder::class);
        // Estos escenarios verifican permisos después del cambio inicial de contraseña.
        User::query()->update(['cambiar_password' => false]);
        $teacher = User::where('codigo_usuario', 'DOC-001')->firstOrFail();
        $otherGroup = Grupo::where('docente_id', '!=', $teacher->id)->firstOrFail();

        $this->actingAs($teacher)->post(route('avisos.store'), [
            'grupo_id' => $otherGroup->id,
            'asunto' => 'No autorizado',
            'mensaje' => 'No debe publicarse.',
        ])->assertForbidden();
    }

    public function test_direction_can_publish_and_consult_announcements_from_all_teachers(): void
    {
        Config::set('demo.enabled', true);
        $this->seed(DatabaseSeeder::class);
        // Estos escenarios verifican permisos después del cambio inicial de contraseña.
        User::query()->update(['cambiar_password' => false]);
        $direction = User::where('codigo_usuario', 'DIR-001')->firstOrFail();
        $group = Grupo::where('activo', true)->firstOrFail();

        $this->actingAs($direction)->post(route('avisos.store'), [
            'grupo_id' => $group->id,
            'asunto' => 'Anuncio de Dirección',
            'mensaje' => 'Información general para la familia.',
        ])->assertRedirect();

        $this->actingAs($direction)->get(route('avisos.index'))->assertOk()
            ->assertSee('Anuncio de Dirección')->assertSee('Anuncio al grupo');
    }
}
