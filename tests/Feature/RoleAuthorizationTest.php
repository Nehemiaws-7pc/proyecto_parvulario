<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_direction_can_access_all_role_areas(): void
    {
        $user = $this->userWithRole(Role::DIRECCION);

        foreach (['direccion.index', 'administracion.index', 'docencia.index', 'familia.index'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }

    public function test_each_role_only_accesses_its_authorized_area(): void
    {
        $administrative = $this->userWithRole(Role::ADMINISTRATIVO);
        $teacher = $this->userWithRole(Role::DOCENTE);
        $guardian = $this->userWithRole(Role::ENCARGADO);

        $this->actingAs($administrative)->get(route('administracion.index'))->assertOk();
        $this->actingAs($administrative)->get(route('docencia.index'))->assertForbidden();

        $this->actingAs($teacher)->get(route('docencia.index'))->assertOk();
        $this->actingAs($teacher)->get(route('administracion.index'))->assertForbidden();

        $this->actingAs($guardian)->get(route('familia.index'))->assertOk();
        $this->actingAs($guardian)->get(route('direccion.index'))->assertForbidden();
    }

    public function test_deactivated_authenticated_user_is_logged_out(): void
    {
        $user = $this->userWithRole(Role::DOCENTE);
        $user->update(['activo' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::factory()->create(['nombre' => $roleName]);

        return User::factory()->create(['rol_id' => $role->id]);
    }
}
