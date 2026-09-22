<?php

namespace Tests\Feature;

use App\Models\Encargado;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DemoAccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_demo_users_login_and_follow_their_access_matrix(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.user_password', 'Demo2026!');
        Config::set('demo.reset_passwords', true);
        $this->seed(DatabaseSeeder::class);

        foreach (User::whereIn('codigo_usuario', ['DIR-001', 'ADM-001', 'DOC-001', 'DOC-002', 'DOC-003', 'DOC-004', 'DOC-005', 'DOC-006', 'DOC-007', 'ENC-0001'])->get() as $user) {
            $this->post(route('login.store'), ['codigo_usuario' => $user->codigo_usuario, 'password' => 'Demo2026!'])->assertRedirect(route('dashboard'));
            $this->actingAs($user)->get(route('dashboard'))->assertOk();
            $this->actingAs($user)->get(route('estudiantes.index'))->assertOk();
            $this->actingAs($user)->get(route('avisos.index'))->assertOk();

            if ($user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO])) {
                $this->actingAs($user)->get(route('estructura.index'))->assertOk();
            }
            if ($user->hasRole(Role::ENCARGADO)) {
                $this->assertSame(24, Encargado::where('usuario_id', $user->id)->firstOrFail()->estudiantes()->count());
                $this->actingAs($user)->get(route('estructura.index'))->assertForbidden();
            }
            if ($user->hasRole(Role::DOCENTE) && $user->codigo_usuario === 'DOC-007') {
                $this->actingAs($user)->get(route('asistencia.index'))->assertForbidden();
                $this->actingAs($user)->get(route('actividades.index'))->assertForbidden();
                $this->actingAs($user)->get(route('evaluaciones.index'))->assertForbidden();
            }
        }
    }
}
