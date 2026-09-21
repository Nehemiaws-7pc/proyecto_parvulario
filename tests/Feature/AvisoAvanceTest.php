<?php

namespace Tests\Feature;

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
}
