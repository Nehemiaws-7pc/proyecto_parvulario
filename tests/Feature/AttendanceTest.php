<?php

namespace Tests\Feature;

use App\Models\AsignacionEscolar;
use App\Models\Asistencia;
use App\Models\Bitacora;
use App\Models\Encargado;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_register_complete_daily_attendance_for_own_active_group(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $group = $this->group($teacher);
        $first = $this->assignment($group, 'Ana', 'Ficticia');
        $second = $this->assignment($group, 'Luis', 'Ficticio');
        $date = '2026-09-10';

        $this->actingAs($teacher)->post(route('asistencia.store'), [
            'fecha' => $date,
            'grupo_id' => $group->id,
            'asistencias' => [
                $first->id => Asistencia::PRESENTE,
                $second->id => Asistencia::TARDE,
            ],
            'observaciones' => [$second->id => 'Llegó después de la hora de entrada.'],
        ])->assertRedirect(route('asistencia.index', ['fecha' => $date, 'grupo_id' => $group->id]));

        $this->assertDatabaseHas('asistencias', [
            'asignacion_id' => $first->id,
            'registrado_por' => $teacher->id,
            'estado' => Asistencia::PRESENTE,
        ]);
        $this->assertDatabaseHas('asistencias', [
            'asignacion_id' => $second->id,
            'estado' => Asistencia::TARDE,
        ]);
        $this->assertTrue(
            Asistencia::where('asignacion_id', $first->id)->firstOrFail()->fecha->isSameDay($date),
        );
        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $teacher->id,
            'accion' => 'registrar_asistencia',
        ]);
    }

    public function test_teacher_must_submit_every_active_student(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $group = $this->group($teacher);
        $first = $this->assignment($group, 'Ana', 'Ficticia');
        $this->assignment($group, 'Luis', 'Ficticio');

        $this->actingAs($teacher)->post(route('asistencia.store'), [
            'fecha' => '2026-09-10',
            'grupo_id' => $group->id,
            'asistencias' => [$first->id => Asistencia::PRESENTE],
        ])->assertSessionHasErrors('asistencias');

        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_teacher_cannot_register_attendance_for_other_or_inactive_group(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $otherTeacher = $this->userWithRole(Role::DOCENTE);
        $otherGroup = $this->group($otherTeacher);
        $otherAssignment = $this->assignment($otherGroup, 'Otro', 'Grupo');
        $inactiveGroup = $this->group($teacher, false);
        $inactiveAssignment = $this->assignment($inactiveGroup, 'Grupo', 'Inactivo');

        $this->actingAs($teacher)->post(route('asistencia.store'), [
            'fecha' => '2026-09-10',
            'grupo_id' => $otherGroup->id,
            'asistencias' => [$otherAssignment->id => Asistencia::PRESENTE],
        ])->assertForbidden();

        $this->actingAs($teacher)->post(route('asistencia.store'), [
            'fecha' => '2026-09-10',
            'grupo_id' => $inactiveGroup->id,
            'asistencias' => [$inactiveAssignment->id => Asistencia::PRESENTE],
        ])->assertForbidden();

        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_duplicate_attendance_for_student_group_and_date_is_rejected(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $group = $this->group($teacher);
        $assignment = $this->assignment($group, 'Ana', 'Ficticia');
        $this->attendance($assignment, $teacher, '2026-09-10', Asistencia::PRESENTE);

        $this->actingAs($teacher)->post(route('asistencia.store'), [
            'fecha' => '2026-09-10',
            'grupo_id' => $group->id,
            'asistencias' => [$assignment->id => Asistencia::AUSENTE],
        ])->assertSessionHasErrors('fecha');

        $this->assertDatabaseCount('asistencias', 1);
        $this->assertDatabaseHas('asistencias', [
            'asignacion_id' => $assignment->id,
            'estado' => Asistencia::PRESENTE,
        ]);
        $this->assertTrue(
            Asistencia::where('asignacion_id', $assignment->id)->firstOrFail()->fecha->isSameDay('2026-09-10'),
        );
    }

    public function test_only_direction_or_administration_can_correct_and_change_is_audited(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $administrative = $this->userWithRole(Role::ADMINISTRATIVO);
        $direction = $this->userWithRole(Role::DIRECCION);
        $group = $this->group($teacher);
        $assignment = $this->assignment($group, 'Ana', 'Ficticia');
        $attendance = $this->attendance($assignment, $teacher, '2026-09-10', Asistencia::AUSENTE);
        $directionAttendance = $this->attendance($assignment, $teacher, '2026-09-11', Asistencia::PRESENTE);

        $this->actingAs($teacher)->get(route('asistencia.edit', $attendance))->assertForbidden();
        $this->actingAs($teacher)->put(route('asistencia.update', $attendance), [
            'estado' => Asistencia::JUSTIFICADO,
            'motivo_correccion' => 'Se recibió la constancia.',
        ])->assertForbidden();

        $this->actingAs($administrative)->put(route('asistencia.update', $attendance), [
            'estado' => Asistencia::JUSTIFICADO,
            'observacion' => 'Ausencia respaldada.',
            'motivo_correccion' => 'Se recibió una constancia de la familia.',
        ])->assertRedirect();
        $this->actingAs($direction)->put(route('asistencia.update', $directionAttendance), [
            'estado' => Asistencia::TARDE,
            'observacion' => 'Hora rectificada.',
            'motivo_correccion' => 'Dirección verificó la hora de entrada.',
        ])->assertRedirect();

        $this->assertDatabaseHas('asistencias', [
            'id' => $attendance->id,
            'registrado_por' => $teacher->id,
            'estado' => Asistencia::JUSTIFICADO,
        ]);
        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $administrative->id,
            'accion' => 'corregir_asistencia',
            'modulo' => 'asistencia',
        ]);
        $this->assertDatabaseHas('asistencias', [
            'id' => $directionAttendance->id,
            'estado' => Asistencia::TARDE,
        ]);
        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $direction->id,
            'accion' => 'corregir_asistencia',
        ]);
        $this->assertStringContainsString(
            'Se recibió una constancia',
            (string) Bitacora::where('accion', 'corregir_asistencia')->value('descripcion'),
        );
        $this->assertStringContainsString(
            'observación sin observación → Ausencia respaldada.',
            (string) Bitacora::where('usuario_id', $administrative->id)->where('accion', 'corregir_asistencia')->value('descripcion'),
        );
    }

    public function test_direction_and_administration_can_consult_any_group_but_teacher_cannot_consult_another_group(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $otherTeacher = $this->userWithRole(Role::DOCENTE);
        $administrative = $this->userWithRole(Role::ADMINISTRATIVO);
        $direction = $this->userWithRole(Role::DIRECCION);
        $group = $this->group($otherTeacher);
        $assignment = $this->assignment($group, 'Visible', 'Administración');
        $this->attendance($assignment, $otherTeacher, '2026-09-10', Asistencia::PRESENTE);

        $this->actingAs($administrative)->get(route('asistencia.index', [
            'fecha' => '2026-09-10',
            'grupo_id' => $group->id,
        ]))->assertOk()->assertSee('Visible');
        $this->actingAs($direction)->get(route('asistencia.index', [
            'fecha' => '2026-09-10',
            'grupo_id' => $group->id,
        ]))->assertOk()->assertSee('Visible');

        $this->actingAs($teacher)->get(route('asistencia.index', [
            'fecha' => '2026-09-10',
            'grupo_id' => $group->id,
        ]))->assertForbidden();
    }

    public function test_guardian_daily_and_monthly_queries_only_include_linked_students(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $parent = $this->userWithRole(Role::ENCARGADO);
        $group = $this->group($teacher);
        $linked = $this->assignment($group, 'Vinculada', 'Familia');
        $other = $this->assignment($group, 'NoVisible', 'Familia');
        $guardian = Encargado::factory()->create(['usuario_id' => $parent->id]);
        $linked->estudiante->encargados()->attach($guardian->id, [
            'parentesco' => 'Padre',
            'contacto_principal' => true,
            'contacto_emergencia' => true,
            'autorizado_recoger' => true,
        ]);
        $this->attendance($linked, $teacher, '2026-09-10', Asistencia::PRESENTE);
        $this->attendance($other, $teacher, '2026-09-10', Asistencia::AUSENTE);

        $this->actingAs($parent)->get(route('asistencia.index', [
            'fecha' => '2026-09-10',
            'grupo_id' => $group->id,
        ]))->assertOk()->assertSee('Vinculada')->assertDontSee('NoVisible');

        $this->actingAs($parent)->get(route('asistencia.monthly', [
            'mes' => '2026-09',
            'grupo_id' => $group->id,
        ]))->assertOk()->assertSee('Vinculada')->assertDontSee('NoVisible');
    }

    public function test_monthly_summary_is_limited_to_authorized_group(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $otherTeacher = $this->userWithRole(Role::DOCENTE);
        $ownGroup = $this->group($teacher);
        $otherGroup = $this->group($otherTeacher);
        $assignment = $this->assignment($ownGroup, 'Resumen', 'Mensual');
        $this->attendance($assignment, $teacher, '2026-09-02', Asistencia::PRESENTE);
        $this->attendance($assignment, $teacher, '2026-09-03', Asistencia::TARDE);

        $this->actingAs($teacher)->get(route('asistencia.monthly', [
            'mes' => '2026-09',
            'grupo_id' => $ownGroup->id,
        ]))->assertOk()->assertSee('Resumen')->assertSee('Mensual');

        $this->actingAs($teacher)->get(route('asistencia.monthly', [
            'mes' => '2026-09',
            'grupo_id' => $otherGroup->id,
        ]))->assertForbidden();
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['nombre' => $roleName], ['activo' => true]);

        return User::factory()->create(['rol_id' => $role->id]);
    }

    private function group(User $teacher, bool $active = true): Grupo
    {
        return Grupo::factory()->create(['docente_id' => $teacher->id, 'activo' => $active]);
    }

    private function assignment(Grupo $group, string $firstName, string $lastName): AsignacionEscolar
    {
        $student = Estudiante::factory()->create(['nombres' => $firstName, 'apellidos' => $lastName]);

        return AsignacionEscolar::factory()->create([
            'estudiante_id' => $student->id,
            'grupo_id' => $group->id,
            'estado' => 'activa',
        ])->load('estudiante');
    }

    private function attendance(
        AsignacionEscolar $assignment,
        User $teacher,
        string $date,
        string $status,
    ): Asistencia {
        return Asistencia::factory()->create([
            'asignacion_id' => $assignment->id,
            'registrado_por' => $teacher->id,
            'fecha' => $date,
            'estado' => $status,
        ]);
    }
}
