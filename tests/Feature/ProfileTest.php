<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_can_view_and_update_only_contact_profile_fields(): void
    {
        foreach ([Role::DIRECCION, Role::ADMINISTRATIVO, Role::DOCENTE, Role::ENCARGADO] as $roleName) {
            $role = Role::firstOrCreate(['nombre' => $roleName], ['activo' => true]);
            $user = User::factory()->create(['rol_id' => $role->id, 'nombre' => 'Nombre Inicial']);
            $this->actingAs($user)->get(route('profile.edit'))->assertOk()
                ->assertSee('Mi perfil')->assertSee($user->codigo_usuario)->assertSee($role->etiqueta)
                ->assertSee('Este es tu código para iniciar sesión. Guárdalo en un lugar seguro.');
            $this->actingAs($user)->put(route('profile.update'), [
                'nombre' => 'Nombre Actualizado', 'telefono' => '5550-2026', 'correo' => 'perfil-'.$user->id.'@example.test',
                'codigo_usuario' => 'MANIPULADO', 'rol_id' => Role::DIRECCION, 'activo' => false,
                'cambiar_password' => false, 'estudiantes' => [999],
            ])->assertSessionHasNoErrors()->assertRedirect()->assertSessionHas('status', 'Mi perfil fue actualizado correctamente.');
            $fresh = $user->fresh();
            $this->assertSame('Nombre Actualizado', $fresh->nombre);
            $this->assertSame('5550-2026', $fresh->telefono);
            $this->assertSame('perfil-'.$user->id.'@example.test', $fresh->correo);
            $this->assertSame($user->codigo_usuario, $fresh->codigo_usuario);
            $this->assertSame($role->id, $fresh->rol_id);
            $this->assertTrue($fresh->activo);
            $this->assertSame($user->cambiar_password, $fresh->cambiar_password);
            $this->actingAs($fresh)->get(route('dashboard'))->assertOk()->assertSee('Nombre Actualizado')->assertSee('Mi perfil');
            $this->assertDatabaseHas('bitacora', ['usuario_id' => $user->id, 'accion' => 'actualizar_perfil']);
        }
    }

    public function test_profile_rejects_invalid_or_duplicate_email_and_other_accounts(): void
    {
        $role = Role::firstOrCreate(['nombre' => Role::DOCENTE], ['activo' => true]);
        $user = User::factory()->create(['rol_id' => $role->id]);
        $other = User::factory()->create(['rol_id' => $role->id, 'correo' => 'already@example.test']);
        $this->actingAs($user)->put(route('profile.update'), ['nombre' => 'A', 'correo' => 'not-an-email'])->assertSessionHasErrors('correo');
        $this->actingAs($user)->put(route('profile.update'), ['nombre' => 'A', 'correo' => $other->correo])->assertSessionHasErrors('correo');
        $this->get('/mi-perfil/'.$other->id)->assertNotFound();
        $this->put('/mi-perfil/'.$other->id, ['nombre' => 'Intruso'])->assertNotFound();
    }
}
