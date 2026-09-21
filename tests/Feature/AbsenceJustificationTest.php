<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Bitacora;
use App\Models\JustificacionInasistencia;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class AbsenceJustificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardian_can_only_justify_an_absence_for_a_linked_student(): void
    {
        $this->seedDemoData();
        $guardian = User::where('codigo_usuario', 'ENC-0001')->firstOrFail();
        $eligible = Asistencia::query()
            ->whereIn('estado', [Asistencia::AUSENTE, Asistencia::TARDE])
            ->whereDoesntHave('justificacion')
            ->firstOrFail();

        $this->actingAs($guardian)->post(route('justificaciones.store'), [
            'asistencia_id' => $eligible->id,
            'motivo' => 'Motivo escrito ficticio suficientemente detallado.',
        ])->assertRedirect(route('justificaciones.index'));
        $this->assertDatabaseHas('justificaciones_inasistencia', [
            'asistencia_id' => $eligible->id,
            'solicitado_por' => $guardian->id,
            'estado' => JustificacionInasistencia::PENDIENTE,
        ]);

        $guardianRole = Role::where('nombre', Role::ENCARGADO)->firstOrFail();
        $unlinkedGuardian = User::factory()->create(['rol_id' => $guardianRole->id]);
        $otherEligible = Asistencia::query()
            ->whereIn('estado', [Asistencia::AUSENTE, Asistencia::TARDE])
            ->whereDoesntHave('justificacion')
            ->whereKeyNot($eligible->id)
            ->firstOrFail();

        $this->actingAs($unlinkedGuardian)->post(route('justificaciones.store'), [
            'asistencia_id' => $otherEligible->id,
            'motivo' => 'Este usuario no tiene vínculo con el estudiante.',
        ])->assertForbidden();

        $present = Asistencia::where('estado', Asistencia::PRESENTE)->firstOrFail();
        $this->actingAs($guardian)->post(route('justificaciones.store'), [
            'asistencia_id' => $present->id,
            'motivo' => 'Una asistencia presente no puede ser justificada.',
        ])->assertForbidden();
    }

    public function test_direction_resolves_and_audits_justifications_while_teacher_only_sees_their_group(): void
    {
        $this->seedDemoData();
        $direction = User::where('codigo_usuario', 'DIR-001')->firstOrFail();
        $teacher = User::where('codigo_usuario', 'DOC-001')->firstOrFail();
        $otherTeacher = User::where('codigo_usuario', 'DOC-002')->firstOrFail();
        $guardian = User::where('codigo_usuario', 'ENC-0001')->firstOrFail();
        $pending = JustificacionInasistencia::where('estado', JustificacionInasistencia::PENDIENTE)->firstOrFail();

        $this->actingAs($teacher)->get(route('justificaciones.index'))
            ->assertOk()
            ->assertSee($pending->motivo);
        $this->actingAs($otherTeacher)->get(route('justificaciones.index'))
            ->assertOk()
            ->assertDontSee($pending->motivo);

        $this->actingAs($guardian)->put(route('justificaciones.resolve', $pending), [
            'estado' => JustificacionInasistencia::ACEPTADA,
        ])->assertForbidden();

        $this->actingAs($direction)->put(route('justificaciones.resolve', $pending), [
            'estado' => JustificacionInasistencia::ACEPTADA,
            'respuesta' => 'Resolución ficticia aceptada.',
        ])->assertRedirect(route('justificaciones.index'));

        $pending->refresh();
        $this->assertSame(JustificacionInasistencia::ACEPTADA, $pending->estado);
        $this->assertSame(Asistencia::JUSTIFICADO, $pending->asistencia->fresh()->estado);
        $this->assertTrue(Bitacora::where('accion', 'resolver_justificacion')
            ->where('usuario_id', $direction->id)->exists());
    }

    private function seedDemoData(): void
    {
        Config::set('app.demo_data_enabled', true);
        Config::set('app.demo_user_password', Str::password(16));
        $this->seed(DatabaseSeeder::class);
    }
}
