<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class RoleNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_visibility_control_is_accessible(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('id="toggle-password"', false)
            ->assertSee('Mostrar contraseña', false)
            ->assertSee('Ocultar contraseña', false)
            ->assertSee('class="line-icon eye-open"', false)
            ->assertSee('aria-label="Mostrar contraseña"', false)
            ->assertSee('type="button"', false);
    }

    public function test_change_password_has_keyboard_accessible_eye_controls(): void
    {
        $role = Role::factory()->create(['nombre' => Role::ENCARGADO]);
        $user = User::factory()->create(['rol_id' => $role->id, 'cambiar_password' => true]);
        $response = $this->actingAs($user)->get(route('password.edit'))->assertOk();
        $response->assertSee('data-password-toggle="current_password"', false)
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('data-password-toggle="password_confirmation"', false)
            ->assertSee('aria-label="Mostrar contraseña"', false)
            ->assertSee('aria-pressed="false"', false)
            ->assertSee('class="line-icon eye-off"', false);
    }

    public function test_guardian_navigation_hides_restricted_sections_and_routes_stay_forbidden(): void
    {
        Config::set('demo.enabled', true);
        $this->seed(DatabaseSeeder::class);
        // Estos escenarios verifican permisos después del cambio inicial de contraseña.
        User::query()->update(['cambiar_password' => false]);
        $guardian = User::where('codigo_usuario', 'ENC-0001')->firstOrFail();

        $dashboard = $this->actingAs($guardian)->get(route('dashboard'))->assertOk();
        $dashboard->assertSee('Estudiantes')->assertSee('Avisos de avances')
            ->assertDontSee('Estructura escolar')->assertDontSee('Dirección')->assertDontSee('Administración')->assertDontSee('Docencia');
        $this->actingAs($guardian)->get(route('estructura.index'))->assertForbidden();
        $this->actingAs($guardian)->get(route('direccion.index'))->assertForbidden();
        $this->actingAs($guardian)->get(route('docencia.index'))->assertForbidden();
    }
}
