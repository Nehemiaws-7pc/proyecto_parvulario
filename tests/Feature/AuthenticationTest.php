<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_is_available(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_user_can_login_with_identifier_code_and_logout(): void
    {
        $role = Role::factory()->create(['nombre' => Role::DIRECCION]);
        $user = User::factory()->create([
            'rol_id' => $role->id,
            'codigo_usuario' => 'DIR-001',
            'password' => 'clave-segura',
        ]);

        $this->post(route('login.store'), [
            'codigo_usuario' => 'dir-001',
            'password' => 'clave-segura',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->ultimo_acceso);
        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $user->id,
            'accion' => 'inicio_sesion',
        ]);

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame(2, Bitacora::where('usuario_id', $user->id)->count());
    }

    public function test_invalid_password_is_rejected(): void
    {
        $user = User::factory()->create([
            'codigo_usuario' => 'DOC-001',
            'password' => 'clave-correcta',
        ]);

        $this->post(route('login.store'), [
            'codigo_usuario' => $user->codigo_usuario,
            'password' => 'incorrecta',
        ])->assertSessionHasErrors('codigo_usuario');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create([
            'codigo_usuario' => 'ADM-009',
            'password' => 'clave-segura',
        ]);

        $this->post(route('login.store'), [
            'codigo_usuario' => $user->codigo_usuario,
            'password' => 'clave-segura',
        ])->assertSessionHasErrors('codigo_usuario');

        $this->assertGuest();
    }

    public function test_user_with_inactive_role_cannot_login(): void
    {
        $role = Role::factory()->create([
            'nombre' => Role::DOCENTE,
            'activo' => false,
        ]);
        $user = User::factory()->create([
            'rol_id' => $role->id,
            'codigo_usuario' => 'DOC-099',
            'password' => 'clave-segura',
        ]);

        $this->post(route('login.store'), [
            'codigo_usuario' => $user->codigo_usuario,
            'password' => 'clave-segura',
        ])->assertSessionHasErrors('codigo_usuario');

        $this->assertGuest();
        $this->assertDatabaseMissing('bitacora', ['usuario_id' => $user->id]);
    }
}
