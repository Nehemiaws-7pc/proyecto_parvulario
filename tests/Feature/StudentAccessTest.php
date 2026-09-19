<?php

namespace Tests\Feature;

use App\Models\AsignacionEscolar;
use App\Models\Encargado;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_only_sees_students_in_assigned_active_groups(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $otherTeacher = $this->userWithRole(Role::DOCENTE);
        $assigned = Estudiante::factory()->create(['nombres' => 'AlumnoVisible']);
        $other = Estudiante::factory()->create(['nombres' => 'AlumnoOculto']);
        $this->placeStudent($assigned, Grupo::factory()->create(['docente_id' => $teacher->id]));
        $this->placeStudent($other, Grupo::factory()->create(['docente_id' => $otherTeacher->id]));

        $this->actingAs($teacher)->get(route('estudiantes.index'))
            ->assertOk()
            ->assertSee('AlumnoVisible')
            ->assertDontSee('AlumnoOculto');

        $this->actingAs($teacher)->get(route('estudiantes.show', $assigned))->assertOk();
        $this->actingAs($teacher)->get(route('estudiantes.show', $other))->assertForbidden();
        $this->actingAs($teacher)->get(route('estudiantes.create'))->assertForbidden();
        $this->actingAs($teacher)->get(route('estudiantes.edit', $assigned))->assertForbidden();
    }

    public function test_guardian_only_sees_students_linked_to_their_account(): void
    {
        $parent = $this->userWithRole(Role::ENCARGADO);
        $linked = Estudiante::factory()->create(['nombres' => 'EstudianteVinculado']);
        $other = Estudiante::factory()->create(['nombres' => 'EstudianteAjeno']);
        $guardian = Encargado::factory()->create(['usuario_id' => $parent->id]);
        $linked->encargados()->attach($guardian->id, [
            'parentesco' => 'Madre',
            'contacto_principal' => true,
            'contacto_emergencia' => true,
            'autorizado_recoger' => true,
        ]);

        $this->actingAs($parent)->get(route('estudiantes.index'))
            ->assertOk()
            ->assertSee('EstudianteVinculado')
            ->assertDontSee('EstudianteAjeno');

        $this->actingAs($parent)->get(route('estudiantes.show', $linked))->assertOk();
        $this->actingAs($parent)->get(route('estudiantes.show', $other))->assertForbidden();
        $this->actingAs($parent)->get(route('estudiantes.create'))->assertForbidden();
        $this->actingAs($parent)->get(route('estudiantes.edit', $linked))->assertForbidden();
    }

    public function test_direction_and_administration_can_consult_all_students_and_manage_structure(): void
    {
        Estudiante::factory()->count(2)->create();
        $direction = $this->userWithRole(Role::DIRECCION);
        $administrative = $this->userWithRole(Role::ADMINISTRATIVO);

        $this->actingAs($direction)->get(route('estudiantes.index'))->assertOk();
        $this->actingAs($direction)->get(route('estructura.index'))->assertOk();
        $this->actingAs($administrative)->get(route('estudiantes.create'))->assertOk();
        $this->actingAs($administrative)->get(route('estructura.index'))->assertOk();
    }

    public function test_teacher_and_guardian_cannot_manage_school_structure(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $parent = $this->userWithRole(Role::ENCARGADO);

        $this->actingAs($teacher)->get(route('estructura.index'))->assertForbidden();
        $this->actingAs($parent)->get(route('estructura.index'))->assertForbidden();
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['nombre' => $roleName], ['activo' => true]);

        return User::factory()->create(['rol_id' => $role->id]);
    }

    private function placeStudent(Estudiante $student, Grupo $group): void
    {
        AsignacionEscolar::factory()->create([
            'estudiante_id' => $student->id,
            'grupo_id' => $group->id,
            'estado' => 'activa',
        ]);
    }
}
