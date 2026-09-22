<?php

namespace Tests\Feature;

use App\Models\PasswordRecoveryRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'rol_id' => Role::firstOrCreate(['nombre' => $role], ['activo' => true])->id,
            'codigo_usuario' => 'USR-'.fake()->unique()->numerify('####'),
            'nombre' => 'Persona Ficticia',
            'telefono' => '5550-1111',
        ], $attributes));
    }

    public function test_request_is_generic_and_does_not_change_a_valid_password(): void
    {
        RateLimiter::clear('password-recovery:'.sha1('127.0.0.1'));
        $teacher = $this->user(Role::DOCENTE, ['codigo_usuario' => 'DOC-REC-001', 'nombre' => 'Docente Ficticia', 'password' => 'Existing-Password-2026!']);
        $before = $teacher->password;
        $valid = ['codigo_usuario' => 'doc-rec-001', 'nombre' => 'docente ficticia', 'telefono' => '5550-1111'];
        $invalid = ['codigo_usuario' => 'NO-EXISTE', 'nombre' => 'Persona Inexistente', 'telefono' => '0000'];
        $first = $this->post(route('password-recovery.store'), $valid)->assertRedirect();
        $first->assertSessionHas('status', 'Si los datos coinciden con una cuenta autorizada, Dirección revisará la solicitud.');
        $this->assertDatabaseHas('password_recovery_requests', ['usuario_id' => $teacher->id, 'estado' => PasswordRecoveryRequest::PENDING]);
        $this->assertSame($before, $teacher->fresh()->password);
        $this->post(route('password-recovery.store'), $invalid)->assertRedirect()->assertSessionHas('status', 'Si los datos coinciden con una cuenta autorizada, Dirección revisará la solicitud.');
        $this->assertSame(1, PasswordRecoveryRequest::count());
    }

    public function test_only_direction_and_administration_can_review_and_reset_once(): void
    {
        $teacher = $this->user(Role::DOCENTE, ['codigo_usuario' => 'DOC-REC-002', 'nombre' => 'Docente Temporal', 'password' => 'Old-Password-2026!']);
        $recovery = PasswordRecoveryRequest::create(['usuario_id' => $teacher->id, 'codigo_usuario' => $teacher->codigo_usuario, 'nombre' => $teacher->nombre, 'telefono' => $teacher->telefono]);
        $family = $this->user(Role::ENCARGADO);
        $this->actingAs($family)->get(route('password-recovery.index'))->assertForbidden();
        $this->actingAs($teacher)->post(route('password-recovery.reset', $recovery))->assertForbidden();
        $admin = $this->user(Role::ADMINISTRATIVO);
        $this->actingAs($admin)->get(route('password-recovery.index'))->assertOk()->assertSee($teacher->codigo_usuario);
        $resetResponse = $this->actingAs($admin)->withSession(['_token' => csrf_token()])->post(route('password-recovery.reset', $recovery));
        $resetResponse->assertOk()->assertViewHas('temporary');
        $recovery->refresh();
        $this->assertSame(PasswordRecoveryRequest::RESOLVED, $recovery->estado);
        $temporary = $resetResponse->viewData('temporary');
        $this->assertIsString($temporary);
        $this->assertTrue(Hash::check($temporary, $teacher->fresh()->password));
        $this->assertTrue($teacher->fresh()->cambiar_password);
        $this->assertDatabaseHas('bitacora', ['usuario_id' => $admin->id, 'accion' => 'restablecer_password']);
        $this->actingAs($admin)->post(route('password-recovery.reset', $recovery))->assertNotFound();
        $this->post('/cerrar-sesion');
        $this->post(route('login.store'), ['codigo_usuario' => $teacher->codigo_usuario, 'password' => $temporary])->assertRedirect(route('password.edit'));
        $this->actingAs($teacher->fresh());
        $this->put(route('password.update'), ['current_password' => $temporary, 'password' => 'Personal-Password-2026!', 'password_confirmation' => 'Personal-Password-2026!'])->assertRedirect('/panel');
        $this->post('/cerrar-sesion');
        $this->post(route('login.store'), ['codigo_usuario' => $teacher->codigo_usuario, 'password' => 'Personal-Password-2026!'])->assertRedirect('/panel');
        $this->assertAuthenticatedAs($teacher->fresh());
    }

    public function test_only_authorized_roles_see_the_recovery_link_and_empty_state(): void
    {
        $direction = $this->user(Role::DIRECCION, ['codigo_usuario' => 'DIR-NAV-001']);
        $admin = $this->user(Role::ADMINISTRATIVO, ['codigo_usuario' => 'ADM-NAV-001']);
        $teacher = $this->user(Role::DOCENTE, ['codigo_usuario' => 'DOC-NAV-001']);
        $family = $this->user(Role::ENCARGADO, ['codigo_usuario' => 'ENC-NAV-001']);
        foreach ([$direction, $admin] as $authorized) {
            $dashboard = $this->actingAs($authorized)->get(route('dashboard'))->assertOk();
            $dashboard->assertSee('Solicitudes de recuperación')->assertSee('No hay solicitudes pendientes.');
            $dashboard->assertSee(route('password-recovery.index'));
            $this->get(route('password-recovery.index'))->assertOk()->assertSee('No hay solicitudes pendientes.');
        }
        foreach ([$teacher, $family] as $restricted) {
            $this->actingAs($restricted)->get(route('dashboard'))->assertOk()->assertDontSee('Solicitudes de recuperación');
            $this->get(route('password-recovery.index'))->assertForbidden();
        }
    }

    public function test_recovery_requests_are_rate_limited_and_do_not_accept_other_roles(): void
    {
        $direction = $this->user(Role::DIRECCION, ['codigo_usuario' => 'DIR-REC-001']);
        $payload = ['codigo_usuario' => $direction->codigo_usuario, 'nombre' => $direction->nombre, 'telefono' => $direction->telefono];
        $this->post(route('password-recovery.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('password_recovery_requests', 0);
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password-recovery.store'), ['codigo_usuario' => 'NO-'.$i, 'nombre' => 'X', 'telefono' => '0'])->assertRedirect();
        }
        $this->assertDatabaseCount('password_recovery_requests', 0);
    }
}
