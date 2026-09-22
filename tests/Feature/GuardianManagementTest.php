<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\AvisoAvance;
use App\Models\Encargado;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GuardianManagementTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['rol_id' => Role::firstOrCreate(['nombre' => $role], ['activo' => true])->id]);
    }

    private function data(): array
    {
        return ['codigo_usuario' => 'enc-test-100', 'nombre' => 'Encargada Ficticia', 'telefono' => '5550-9988',
            'correo' => 'guardian@example.test', 'parentesco' => 'Madre',
            'password' => 'Initial-Only-2026!', 'password_confirmation' => 'Initial-Only-2026!'];
    }

    public function test_admin_creates_account_once_and_first_login_requires_password_change(): void
    {
        $admin = $this->user(Role::ADMINISTRATIVO);
        $this->user(Role::ENCARGADO);
        $student = Estudiante::factory()->create();
        $this->actingAs($admin)->get(route('encargados.index', $student))->assertOk()->assertSee('Crear cuenta si no existe');
        $this->post(route('encargados.accounts.store', $student), $this->data())->assertSessionHasNoErrors()->assertRedirect();
        $user = User::where('codigo_usuario', 'ENC-TEST-100')->firstOrFail();
        $this->assertTrue(Hash::check($this->data()['password'], $user->password));
        $this->assertTrue($user->cambiar_password);
        $this->assertDatabaseHas('bitacora', ['usuario_id' => $admin->id, 'accion' => 'crear_cuenta_encargado']);
        $this->post(route('encargados.accounts.store', $student), $this->data())->assertSessionHasErrors('codigo_usuario');
        $this->post(route('encargados.accounts.store', $student), [...$this->data(), 'codigo_usuario' => 'ENC-OTHER'])->assertSessionHasErrors('correo');
        $this->assertSame(1, User::where('codigo_usuario', 'ENC-TEST-100')->count());
        $this->post('/cerrar-sesion');
        $this->post('/iniciar-sesion', ['codigo_usuario' => $user->codigo_usuario, 'password' => $this->data()['password']])->assertRedirect(route('password.edit'));
        $this->get('/panel')->assertRedirect(route('password.edit'));
        $this->get('/estudiantes/'.$student->id)->assertRedirect(route('password.edit'));
        $this->post('/avisos', [])->assertRedirect(route('password.edit'));
        $this->get(route('password.edit'))->assertOk();
        $this->put(route('password.update'), ['current_password' => 'wrong', 'password' => 'New-Private-2026!', 'password_confirmation' => 'New-Private-2026!'])
            ->assertSessionHasErrors('current_password')->assertSessionDoesntHaveErrors('password');
        $this->put(route('password.update'), ['current_password' => $this->data()['password'], 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors(['password' => 'La nueva contraseña debe tener al menos 12 caracteres.']);
        $this->put(route('password.update'), ['current_password' => $this->data()['password'], 'password' => 'New-Private-2026!', 'password_confirmation' => 'Different-Private-2026!'])
            ->assertSessionHasErrors(['password' => 'La confirmación de la nueva contraseña no coincide.']);
        $this->put(route('password.update'), ['current_password' => $this->data()['password'], 'password' => 'New-Private-2026!', 'password_confirmation' => 'New-Private-2026!'])->assertSessionHasNoErrors()->assertRedirect('/panel');
        $this->assertFalse($user->fresh()->cambiar_password);
        $this->assertTrue(Hash::check('New-Private-2026!', $user->fresh()->password));
        $this->post('/cerrar-sesion')->assertRedirect('/iniciar-sesion');
        $this->post('/iniciar-sesion', ['codigo_usuario' => $user->codigo_usuario, 'password' => 'New-Private-2026!'])
            ->assertRedirect('/panel');
        $this->assertAuthenticatedAs($user->fresh());
        $this->get('/estudiantes/'.$student->id)->assertOk();
    }

    public function test_direction_searches_links_many_to_many_and_unlink_revokes_access_without_deleting_accounts(): void
    {
        $admin = $this->user(Role::DIRECCION);
        $first = $this->user(Role::ENCARGADO);
        $second = $this->user(Role::ENCARGADO);
        $students = Estudiante::factory()->count(2)->create();
        $this->actingAs($admin)->get(route('encargados.index', [$students[0], 'q' => $first->codigo_usuario]))->assertOk()->assertSee($first->codigo_usuario)->assertDontSee($second->codigo_usuario);
        foreach ([$first, $second] as $parent) {
            foreach ($students as $student) {
                $payload = ['usuario_id' => $parent->id, 'nombre' => $parent->nombre, 'telefono' => '55555555', 'parentesco' => 'Encargado'];
                $this->post(route('estudiantes.encargados.store', $student), $payload)->assertSessionHasNoErrors();
                $this->post(route('estudiantes.encargados.store', $student), $payload)->assertSessionHasNoErrors();
            }
        }
        $this->assertDatabaseCount('encargados', 2);
        $this->assertDatabaseCount('estudiante_encargado', 4);
        $guardian = $first->encargado;
        $password = $first->password;
        $this->actingAs($first)->get('/estudiantes/'.$students[0]->id)->assertOk();
        $this->actingAs($admin)->delete(route('estudiantes.encargados.destroy', [$students[0], $guardian]))->assertRedirect();
        $this->assertDatabaseHas('bitacora', ['usuario_id' => $admin->id, 'accion' => 'desvincular_encargado']);
        $this->assertSame($password, $first->fresh()->password);
        $this->assertDatabaseCount('encargados', 2);
        $this->assertDatabaseCount('estudiante_encargado', 3);
        $this->actingAs($first)->get('/estudiantes/'.$students[0]->id)->assertForbidden();
        $this->get('/estudiantes/'.$students[1]->id)->assertOk();
        $this->get('/estudiantes')->assertDontSee($students[0]->nombre_completo)->assertSee($students[1]->nombre_completo);
        $this->actingAs($second)->get('/estudiantes/'.$students[0]->id)->assertOk();
    }

    public function test_existing_identity_and_contact_data_are_preserved_and_wrong_roles_are_rejected(): void
    {
        $admin = $this->user(Role::ADMINISTRATIVO);
        $parent = $this->user(Role::ENCARGADO);
        $other = $this->user(Role::ENCARGADO);
        $teacher = $this->user(Role::DOCENTE);
        $student = Estudiante::factory()->create();
        $guardian = Encargado::create(['usuario_id' => $parent->id, 'nombre' => 'Contacto conservado', 'telefono' => '1111']);
        $this->actingAs($admin);
        $data = ['usuario_id' => $parent->id, 'nombre' => 'No sobrescribir', 'telefono' => '2222', 'parentesco' => 'Madre', 'autorizado_recoger' => true];
        $this->post(route('estudiantes.encargados.store', $student), $data)->assertSessionHasNoErrors();
        $this->post(route('estudiantes.encargados.store', $student), [...$data, 'autorizado_recoger' => false])->assertSessionHasNoErrors();
        $this->assertSame('Contacto conservado', $guardian->fresh()->nombre);
        $this->assertDatabaseHas('estudiante_encargado', ['estudiante_id' => $student->id, 'encargado_id' => $guardian->id, 'autorizado_recoger' => true]);
        $this->put(route('estudiantes.encargados.update', [$student, $guardian]), [...$data, 'usuario_id' => $other->id])->assertSessionHasErrors('usuario_id');
        $this->assertSame($parent->id, $guardian->fresh()->usuario_id);
        $this->post(route('estudiantes.encargados.store', $student), [...$data, 'usuario_id' => $teacher->id])->assertSessionHasErrors('usuario_id');
        $other->update(['activo' => false]);
        $this->post(route('estudiantes.encargados.store', $student), [...$data, 'usuario_id' => $other->id])->assertSessionHasErrors('usuario_id');
    }

    public function test_unlink_removes_academic_and_notice_access_on_the_server(): void
    {
        config(['demo.enabled' => true, 'demo.reset_passwords' => true]);
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('codigo_usuario', 'DIR-001')->firstOrFail();
        $parent = User::where('codigo_usuario', 'ENC-0001')->firstOrFail();
        $group = Grupo::firstOrFail();
        $guardian = $parent->encargado;
        $students = $group->asignaciones()->with('estudiante')->get()->pluck('estudiante');
        $activity = Actividad::where('grupo_id', $group->id)->where('publicada', true)->firstOrFail();
        $notice = AvisoAvance::create(['autor_id' => $admin->id, 'grupo_id' => $group->id,
            'asunto' => 'Announcement before unlink', 'mensaje' => 'Test only', 'activo' => true, 'fecha_publicacion' => now()]);
        $this->actingAs($parent)->get('/actividades/'.$activity->id)->assertOk();
        $this->get('/avisos')->assertSee($notice->asunto);
        foreach ($students as $student) {
            $this->actingAs($admin)->delete(route('estudiantes.encargados.destroy', [$student, $guardian]))->assertRedirect();
        }
        $this->actingAs($parent);
        foreach (['asistencia', 'actividades', 'evaluaciones'] as $module) {
            $this->get('/'.$module.'?grupo_id='.$group->id)->assertForbidden();
        }
        $this->get('/actividades/'.$activity->id)->assertForbidden();
        $this->get('/asistencia/resumen-mensual?grupo_id='.$group->id.'&mes=2026-09')->assertForbidden();
        $this->get('/avisos')->assertDontSee($notice->asunto);
        foreach ($students as $student) {
            $this->get('/estudiantes/'.$student->id)->assertForbidden();
            $this->get('/justificaciones')->assertDontSee($student->nombre_completo);
        }
    }

    public function test_teacher_and_family_cannot_search_create_link_update_or_unlink_even_by_direct_url(): void
    {
        $student = Estudiante::factory()->create();
        $parent = $this->user(Role::ENCARGADO);
        $guardian = Encargado::create(['usuario_id' => $parent->id, 'nombre' => $parent->nombre, 'telefono' => '5555']);
        $student->encargados()->attach($guardian, ['parentesco' => 'Madre']);
        foreach ([$parent, $this->user(Role::DOCENTE)] as $user) {
            $this->actingAs($user)->get(route('encargados.index', $student))->assertForbidden();
            $this->post(route('encargados.accounts.store', $student), $this->data())->assertForbidden();
            $this->post(route('estudiantes.encargados.store', $student), [])->assertForbidden();
            $this->put(route('estudiantes.encargados.update', [$student, $guardian]), [])->assertForbidden();
            $this->delete(route('estudiantes.encargados.destroy', [$student, $guardian]))->assertForbidden();
        }
        $this->assertDatabaseCount('estudiante_encargado', 1);
    }
}
