<?php

namespace Tests\Feature;

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
            ->assertSee('Ocultar contraseña', false);
    }

    public function test_guardian_navigation_hides_restricted_sections_and_routes_stay_forbidden(): void
    {
        Config::set('demo.enabled', true);
        $this->seed(DatabaseSeeder::class);
        $guardian = User::where('codigo_usuario', 'ENC-0001')->firstOrFail();

        $dashboard = $this->actingAs($guardian)->get(route('dashboard'))->assertOk();
        $dashboard->assertSee('Estudiantes')->assertSee('Avisos de avances')
            ->assertDontSee('Estructura escolar')->assertDontSee('Dirección')->assertDontSee('Administración')->assertDontSee('Docencia');
        $this->actingAs($guardian)->get(route('estructura.index'))->assertForbidden();
        $this->actingAs($guardian)->get(route('direccion.index'))->assertForbidden();
        $this->actingAs($guardian)->get(route('docencia.index'))->assertForbidden();
    }
}
