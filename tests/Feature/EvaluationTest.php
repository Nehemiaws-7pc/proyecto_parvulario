<?php

namespace Tests\Feature;

use App\Models\AreaAprendizaje;
use App\Models\AsignacionEscolar;
use App\Models\Bitacora;
use App\Models\Encargado;
use App\Models\EscalaEvaluacion;
use App\Models\Estudiante;
use App\Models\Evaluacion;
use App\Models\Grupo;
use App\Models\IndicadorEvaluacion;
use App\Models\Periodo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_registers_complete_descriptive_results_for_own_active_group(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $group = $this->group($teacher);
        [$period, $area, $indicator, $achieved, $process] = $this->catalog($group);
        $first = $this->assignment($group, 'Andrea', 'Ficticia');
        $second = $this->assignment($group, 'Mateo', 'Ficticio');

        $this->actingAs($teacher)->post(route('evaluaciones.store'), [
            'grupo_id' => $group->id,
            'periodo_id' => $period->id,
            'indicador_id' => $indicator->id,
            'resultados' => [$first->id => $achieved->id, $second->id => $process->id],
            'observaciones' => [$second->id => 'Continúa practicando con material ficticio.'],
        ])->assertRedirect(route('evaluaciones.index', [
            'grupo_id' => $group->id,
            'periodo_id' => $period->id,
            'area_id' => $area->id,
            'indicador_id' => $indicator->id,
        ]));

        $this->assertDatabaseCount('evaluaciones', 2);
        $this->assertDatabaseHas('evaluaciones', [
            'asignacion_id' => $first->id,
            'periodo_id' => $period->id,
            'indicador_id' => $indicator->id,
            'escala_id' => $achieved->id,
            'evaluado_por' => $teacher->id,
            'publicado' => false,
        ]);
        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $teacher->id,
            'accion' => 'registrar_evaluaciones',
        ]);
    }

    public function test_teacher_must_submit_every_student_and_cannot_register_other_or_inactive_groups(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $otherTeacher = $this->userWithRole(Role::DOCENTE);
        $group = $this->group($teacher);
        [$period, , $indicator, $scale] = $this->catalog($group);
        $first = $this->assignment($group, 'Primera', 'Estudiante');
        $this->assignment($group, 'Segunda', 'Estudiante');

        $this->actingAs($teacher)->post(route('evaluaciones.store'), [
            'grupo_id' => $group->id,
            'periodo_id' => $period->id,
            'indicador_id' => $indicator->id,
            'resultados' => [$first->id => $scale->id],
        ])->assertSessionHasErrors('resultados');

        $otherGroup = $this->group($otherTeacher);
        [$otherPeriod, , $otherIndicator, $otherScale] = $this->catalog($otherGroup);
        $otherAssignment = $this->assignment($otherGroup, 'Grupo', 'Ajeno');
        $this->actingAs($teacher)->post(route('evaluaciones.store'), [
            'grupo_id' => $otherGroup->id,
            'periodo_id' => $otherPeriod->id,
            'indicador_id' => $otherIndicator->id,
            'resultados' => [$otherAssignment->id => $otherScale->id],
        ])->assertForbidden();

        $inactiveGroup = $this->group($teacher, false);
        [$inactivePeriod, , $inactiveIndicator, $inactiveScale] = $this->catalog($inactiveGroup);
        $inactiveAssignment = $this->assignment($inactiveGroup, 'Grupo', 'Inactivo');
        $this->actingAs($teacher)->post(route('evaluaciones.store'), [
            'grupo_id' => $inactiveGroup->id,
            'periodo_id' => $inactivePeriod->id,
            'indicador_id' => $inactiveIndicator->id,
            'resultados' => [$inactiveAssignment->id => $inactiveScale->id],
        ])->assertForbidden();

        $this->assertDatabaseCount('evaluaciones', 0);
    }

    public function test_duplicate_result_for_student_indicator_and_period_is_rejected(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $group = $this->group($teacher);
        [$period, , $indicator, $achieved, $process] = $this->catalog($group);
        $assignment = $this->assignment($group, 'Resultado', 'Único');
        $this->evaluation($assignment, $period, $indicator, $achieved, $teacher);
        $assignment->update(['estado' => 'inactiva']);
        $newGroup = Grupo::factory()->create([
            'ciclo_id' => $group->ciclo_id,
            'grado_id' => $group->grado_id,
            'docente_id' => $teacher->id,
            'activo' => true,
        ]);
        $newAssignment = AsignacionEscolar::factory()->create([
            'estudiante_id' => $assignment->estudiante_id,
            'grupo_id' => $newGroup->id,
            'estado' => 'activa',
        ]);

        $this->actingAs($teacher)->post(route('evaluaciones.store'), [
            'grupo_id' => $newGroup->id,
            'periodo_id' => $period->id,
            'indicador_id' => $indicator->id,
            'resultados' => [$newAssignment->id => $process->id],
        ])->assertSessionHasErrors('resultados');

        $this->assertDatabaseCount('evaluaciones', 1);
        $this->assertDatabaseHas('evaluaciones', [
            'asignacion_id' => $assignment->id,
            'estudiante_id' => $assignment->estudiante_id,
            'periodo_id' => $period->id,
            'indicador_id' => $indicator->id,
            'escala_id' => $achieved->id,
        ]);
    }

    public function test_only_assigned_teacher_corrects_results_and_change_is_audited(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $otherTeacher = $this->userWithRole(Role::DOCENTE);
        $administrative = $this->userWithRole(Role::ADMINISTRATIVO);
        $group = $this->group($teacher);
        [$period, , $indicator, $achieved, $process] = $this->catalog($group);
        $assignment = $this->assignment($group, 'Corrección', 'Ficticia');
        $evaluation = $this->evaluation($assignment, $period, $indicator, $achieved, $teacher, true);

        $payload = [
            'escala_id' => $process->id,
            'observacion' => 'Observación corregida.',
            'motivo_correccion' => 'Se revisó la evidencia del aula.',
        ];
        $this->actingAs($otherTeacher)->put(route('evaluaciones.update', $evaluation), $payload)->assertForbidden();
        $this->actingAs($administrative)->put(route('evaluaciones.update', $evaluation), $payload)->assertForbidden();
        $this->actingAs($teacher)->put(route('evaluaciones.update', $evaluation), $payload)->assertRedirect();

        $this->assertDatabaseHas('evaluaciones', [
            'id' => $evaluation->id,
            'escala_id' => $process->id,
            'observacion' => 'Observación corregida.',
            'publicado' => true,
        ]);
        $audit = Bitacora::where('accion', 'corregir_evaluacion')->firstOrFail();
        $this->assertSame($teacher->id, $audit->usuario_id);
        $this->assertStringContainsString('Logro alcanzado → En proceso', $audit->descripcion);
        $this->assertStringContainsString('Se revisó la evidencia', $audit->descripcion);
    }

    public function test_direction_and_administration_consult_all_groups_while_teacher_only_sees_active_own_groups(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $otherTeacher = $this->userWithRole(Role::DOCENTE);
        $direction = $this->userWithRole(Role::DIRECCION);
        $administrative = $this->userWithRole(Role::ADMINISTRATIVO);
        $group = $this->group($otherTeacher);
        [$period, $area, $indicator, $scale] = $this->catalog($group);
        $assignment = $this->assignment($group, 'Consulta', 'Global');
        $this->evaluation($assignment, $period, $indicator, $scale, $otherTeacher);
        $query = [
            'grupo_id' => $group->id,
            'periodo_id' => $period->id,
            'area_id' => $area->id,
            'indicador_id' => $indicator->id,
        ];

        $this->actingAs($direction)->get(route('evaluaciones.index', $query))->assertOk()->assertSee('Consulta');
        $this->actingAs($administrative)->get(route('evaluaciones.index', $query))->assertOk()->assertSee('Consulta');
        $this->actingAs($teacher)->get(route('evaluaciones.index', $query))->assertForbidden();

        $inactiveGroup = $this->group($teacher, false);
        [$inactivePeriod, $inactiveArea, $inactiveIndicator] = $this->catalog($inactiveGroup);
        $this->actingAs($teacher)->get(route('evaluaciones.index', [
            'grupo_id' => $inactiveGroup->id,
            'periodo_id' => $inactivePeriod->id,
            'area_id' => $inactiveArea->id,
            'indicador_id' => $inactiveIndicator->id,
        ]))->assertForbidden();
    }

    public function test_guardian_only_sees_published_results_for_linked_students(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $otherTeacher = $this->userWithRole(Role::DOCENTE);
        $parent = $this->userWithRole(Role::ENCARGADO);
        $group = $this->group($teacher);
        [$period, $area, $indicator, $scale] = $this->catalog($group);
        $linked = $this->assignment($group, 'Vinculada', 'Familia');
        $unlinked = $this->assignment($group, 'Reservada', 'Docente');
        $guardian = Encargado::factory()->create(['usuario_id' => $parent->id]);
        $linked->estudiante->encargados()->attach($guardian->id, [
            'parentesco' => 'Madre',
            'contacto_principal' => true,
            'contacto_emergencia' => true,
            'autorizado_recoger' => true,
        ]);
        $this->evaluation($linked, $period, $indicator, $scale, $teacher);
        $this->evaluation($unlinked, $period, $indicator, $scale, $teacher);
        $query = [
            'grupo_id' => $group->id,
            'periodo_id' => $period->id,
            'area_id' => $area->id,
            'indicador_id' => $indicator->id,
        ];

        $this->actingAs($parent)->get(route('evaluaciones.index', $query))
            ->assertOk()->assertDontSee('Vinculada')->assertDontSee('Reservada');
        $this->actingAs($otherTeacher)->post(route('evaluaciones.publish'), [
            'grupo_id' => $group->id,
            'periodo_id' => $period->id,
            'indicador_id' => $indicator->id,
        ])->assertForbidden();
        $this->actingAs($teacher)->post(route('evaluaciones.publish'), [
            'grupo_id' => $group->id,
            'periodo_id' => $period->id,
            'indicador_id' => $indicator->id,
        ])->assertRedirect();

        $this->assertSame(2, Evaluacion::where('publicado', true)->count());
        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $teacher->id,
            'accion' => 'publicar_evaluaciones',
        ]);
        $this->actingAs($parent)->get(route('evaluaciones.index', $query))
            ->assertOk()->assertSee('Vinculada')->assertDontSee('Reservada')->assertSee('Logro alcanzado');
    }

    public function test_scale_can_be_configured_without_code_and_used_by_teacher(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $administrative = $this->userWithRole(Role::ADMINISTRATIVO);
        $direction = $this->userWithRole(Role::DIRECCION);
        $group = $this->group($teacher);
        [$period, , $indicator] = $this->catalog($group);
        $assignment = $this->assignment($group, 'Escala', 'Configurable');

        $this->actingAs($teacher)->get(route('evaluaciones.configuracion'))->assertForbidden();
        $this->actingAs($direction)->get(route('evaluaciones.configuracion'))->assertOk();
        $this->actingAs($administrative)->post(route('evaluaciones.escalas.store'), [
            'codigo' => 'av',
            'nombre' => 'Avance destacado',
            'orden' => 5,
            'activo' => true,
        ])->assertRedirect();
        $customScale = EscalaEvaluacion::where('codigo', 'AV')->firstOrFail();

        $this->actingAs($teacher)->post(route('evaluaciones.store'), [
            'grupo_id' => $group->id,
            'periodo_id' => $period->id,
            'indicador_id' => $indicator->id,
            'resultados' => [$assignment->id => $customScale->id],
        ])->assertRedirect();
        $this->assertDatabaseHas('evaluaciones', [
            'asignacion_id' => $assignment->id,
            'escala_id' => $customScale->id,
        ]);
    }

    public function test_period_and_indicator_must_belong_to_group_structure(): void
    {
        $teacher = $this->userWithRole(Role::DOCENTE);
        $group = $this->group($teacher);
        [, , , $scale] = $this->catalog($group);
        $otherGroup = $this->group($teacher);
        [$otherPeriod, , $otherIndicator] = $this->catalog($otherGroup);
        $assignment = $this->assignment($group, 'Estructura', 'Validada');

        $this->actingAs($teacher)->post(route('evaluaciones.store'), [
            'grupo_id' => $group->id,
            'periodo_id' => $otherPeriod->id,
            'indicador_id' => $otherIndicator->id,
            'resultados' => [$assignment->id => $scale->id],
        ])->assertForbidden();
        $this->assertDatabaseCount('evaluaciones', 0);
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

    private function catalog(Grupo $group): array
    {
        $period = Periodo::factory()->create([
            'ciclo_id' => $group->ciclo_id,
            'fecha_inicio' => "{$group->ciclo->anio}-01-15",
            'fecha_fin' => "{$group->ciclo->anio}-04-30",
        ]);
        $area = AreaAprendizaje::factory()->create();
        $indicator = IndicadorEvaluacion::factory()->create([
            'area_id' => $area->id,
            'grado_id' => $group->grado_id,
        ]);
        $achieved = EscalaEvaluacion::firstOrCreate(
            ['codigo' => 'LA'],
            ['nombre' => 'Logro alcanzado', 'orden' => 1, 'activo' => true],
        );
        $process = EscalaEvaluacion::firstOrCreate(
            ['codigo' => 'EP'],
            ['nombre' => 'En proceso', 'orden' => 2, 'activo' => true],
        );

        return [$period, $area, $indicator, $achieved, $process];
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

    private function evaluation(
        AsignacionEscolar $assignment,
        Periodo $period,
        IndicadorEvaluacion $indicator,
        EscalaEvaluacion $scale,
        User $teacher,
        bool $published = false,
    ): Evaluacion {
        return Evaluacion::factory()->create([
            'asignacion_id' => $assignment->id,
            'estudiante_id' => $assignment->estudiante_id,
            'periodo_id' => $period->id,
            'indicador_id' => $indicator->id,
            'escala_id' => $scale->id,
            'evaluado_por' => $teacher->id,
            'observacion' => 'Observación ficticia.',
            'publicado' => $published,
            'publicado_at' => $published ? now() : null,
            'publicado_por' => $published ? $teacher->id : null,
        ]);
    }
}
