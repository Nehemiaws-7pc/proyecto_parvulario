<?php

namespace Tests\Feature;

use App\Models\AsignacionEscolar;
use App\Models\CicloEscolar;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Role;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administration_can_create_a_valid_student_record(): void
    {
        $administrative = $this->userWithRole(Role::ADMINISTRATIVO);
        $group = $this->group();

        $response = $this->actingAs($administrative)->post(route('estudiantes.store'), $this->studentData($group, [
            'codigo' => 'est-1001',
        ]));

        $student = Estudiante::where('codigo', 'EST-1001')->firstOrFail();
        $response->assertRedirect(route('estudiantes.show', $student));
        $this->assertDatabaseHas('asignaciones_escolares', [
            'estudiante_id' => $student->id,
            'grupo_id' => $group->id,
            'estado' => 'activa',
        ]);
        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $administrative->id,
            'accion' => 'crear_expediente',
        ]);
    }

    public function test_student_creation_uses_fixed_est_prefix_and_rejects_manipulated_prefix(): void
    {
        $administrative = $this->userWithRole(Role::ADMINISTRATIVO);
        $group = $this->group();
        $payload = $this->studentData($group, ['codigo' => null, 'codigo_prefijo' => 'EST-', 'codigo_sufijo' => '0099']);
        unset($payload['codigo']);
        $this->actingAs($administrative)->post(route('estudiantes.store'), $payload)->assertRedirect();
        $this->assertDatabaseHas('estudiantes', ['codigo' => 'EST-0099']);
        $duplicate = $this->studentData($group, ['codigo' => 'EST-0099']);
        $this->actingAs($administrative)->post(route('estudiantes.store'), $duplicate)->assertSessionHasErrors('codigo');
        $payload['codigo_prefijo'] = 'DOC-';
        $payload['codigo_sufijo'] = '0100';
        $this->actingAs($administrative)->post(route('estudiantes.store'), $payload)->assertSessionHasErrors('codigo');
    }

    public function test_student_form_validates_unique_code_birth_date_and_group(): void
    {
        $administrative = $this->userWithRole(Role::ADMINISTRATIVO);
        Estudiante::factory()->create(['codigo' => 'EST-1001']);

        $this->actingAs($administrative)->post(route('estudiantes.store'), [
            'codigo' => 'EST-1001',
            'nombres' => 'Prueba',
            'apellidos' => 'Inválida',
            'fecha_nacimiento' => now()->addDay()->format('Y-m-d'),
            'estado' => 'activo',
            'grupo_id' => 99999,
        ])->assertSessionHasErrors(['codigo', 'fecha_nacimiento', 'grupo_id']);
    }

    public function test_changing_group_preserves_history_and_closes_previous_placement(): void
    {
        $administrative = $this->userWithRole(Role::ADMINISTRATIVO);
        $cycle = CicloEscolar::factory()->create();
        $teacher = $this->userWithRole(Role::DOCENTE);
        $grade = Grado::factory()->create();
        $firstGroup = Grupo::factory()->create([
            'ciclo_id' => $cycle->id,
            'grado_id' => $grade->id,
            'docente_id' => $teacher->id,
        ]);
        $secondGroup = Grupo::factory()->create([
            'ciclo_id' => $cycle->id,
            'grado_id' => $grade->id,
            'docente_id' => $teacher->id,
        ]);
        $student = Estudiante::factory()->create();
        AsignacionEscolar::factory()->create([
            'estudiante_id' => $student->id,
            'grupo_id' => $firstGroup->id,
            'estado' => 'activa',
        ]);

        $this->actingAs($administrative)->put(
            route('estudiantes.update', $student),
            $this->studentData($secondGroup, ['codigo' => $student->codigo]),
        )->assertRedirect(route('estudiantes.show', $student));

        $this->assertDatabaseHas('asignaciones_escolares', [
            'estudiante_id' => $student->id,
            'grupo_id' => $firstGroup->id,
            'estado' => 'trasladada',
        ]);
        $this->assertDatabaseHas('asignaciones_escolares', [
            'estudiante_id' => $student->id,
            'grupo_id' => $secondGroup->id,
            'estado' => 'activa',
        ]);
        $this->assertSame(2, $student->asignaciones()->count());
    }

    public function test_administration_can_add_emergency_contact_and_authorized_person(): void
    {
        $administrative = $this->userWithRole(Role::ADMINISTRATIVO);
        $parent = $this->userWithRole(Role::ENCARGADO);
        $student = Estudiante::factory()->create();

        $this->actingAs($administrative)->post(route('estudiantes.encargados.store', $student), [
            'usuario_id' => $parent->id,
            'nombre' => 'Laura Ficticia',
            'telefono' => '5550-0200',
            'parentesco' => 'Madre',
            'contacto_principal' => '1',
            'contacto_emergencia' => '1',
            'autorizado_recoger' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('estudiante_encargado', [
            'estudiante_id' => $student->id,
            'contacto_principal' => true,
            'contacto_emergencia' => true,
            'autorizado_recoger' => true,
        ]);

        $this->actingAs($administrative)->post(route('estudiantes.personas.store', $student), [
            'nombre' => 'Marta Ficticia',
            'parentesco' => 'Tía',
            'telefono' => '5550-0300',
            'activo' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('personas_autorizadas', [
            'estudiante_id' => $student->id,
            'nombre' => 'Marta Ficticia',
            'activo' => true,
        ]);
    }

    public function test_teacher_cannot_modify_student_or_contacts(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $group = $this->group($teacher);
        $student = Estudiante::factory()->create();
        AsignacionEscolar::factory()->create(['estudiante_id' => $student->id, 'grupo_id' => $group->id]);

        $this->actingAs($teacher)->put(route('estudiantes.update', $student), $this->studentData($group))
            ->assertForbidden();
        $this->actingAs($teacher)->post(route('estudiantes.encargados.store', $student), [
            'nombre' => 'Contacto', 'telefono' => '5555', 'parentesco' => 'Tío',
        ])->assertForbidden();
        $this->actingAs($teacher)->post(route('estudiantes.personas.store', $student), [
            'nombre' => 'Persona', 'parentesco' => 'Tía',
        ])->assertForbidden();
    }

    public function test_only_parent_role_accounts_can_be_linked_as_guardians(): void
    {
        $administrative = $this->userWithRole(Role::ADMINISTRATIVO);
        $teacher = $this->userWithRole(Role::DOCENTE);
        $student = Estudiante::factory()->create();

        $this->actingAs($administrative)->post(route('estudiantes.encargados.store', $student), [
            'usuario_id' => $teacher->id,
            'nombre' => 'Cuenta incorrecta',
            'telefono' => '5550-0400',
            'parentesco' => 'Otro',
        ])->assertSessionHasErrors('usuario_id');

        $this->assertDatabaseMissing('encargados', ['usuario_id' => $teacher->id]);
    }

    public function test_group_with_student_history_cannot_change_cycle_grade_or_section(): void
    {
        $administrative = $this->userWithRole(Role::ADMINISTRATIVO);
        $group = $this->group();
        $student = Estudiante::factory()->create();
        AsignacionEscolar::factory()->create(['estudiante_id' => $student->id, 'grupo_id' => $group->id]);
        $newSection = Seccion::factory()->create();

        $this->actingAs($administrative)->put(route('estructura.grupos.update', $group), [
            'ciclo_id' => $group->ciclo_id,
            'grado_id' => $group->grado_id,
            'seccion_id' => $newSection->id,
            'docente_id' => $group->docente_id,
            'activo' => '1',
        ])->assertSessionHasErrors('seccion_id');

        $this->assertSame($group->seccion_id, $group->fresh()->seccion_id);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['nombre' => $roleName], ['activo' => true]);

        return User::factory()->create(['rol_id' => $role->id]);
    }

    private function group(?User $teacher = null): Grupo
    {
        return Grupo::factory()->create(['docente_id' => ($teacher ?? $this->userWithRole(Role::DOCENTE))->id]);
    }

    private function studentData(Grupo $group, array $overrides = []): array
    {
        return array_merge([
            'codigo' => 'EST-2001',
            'nombres' => 'Lucía',
            'apellidos' => 'Pérez',
            'fecha_nacimiento' => '2021-05-10',
            'sexo' => 'femenino',
            'direccion' => 'Dirección ficticia',
            'informacion_medica' => null,
            'observaciones' => null,
            'estado' => 'activo',
            'grupo_id' => $group->id,
        ], $overrides);
    }
}
