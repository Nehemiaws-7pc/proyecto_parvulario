<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Calificacion;
use App\Models\Grupo;
use App\Models\Periodo;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_manage_results_only_for_the_assigned_group_without_duplicates(): void
    {
        $this->seedDemoData();
        $teacher = User::where('codigo_usuario', 'DOC-001')->firstOrFail();
        $ownGroup = Grupo::where('docente_id', $teacher->id)->firstOrFail();
        $otherGroup = Grupo::where('docente_id', '!=', $teacher->id)->firstOrFail();
        $period = Periodo::where('ciclo_id', $ownGroup->ciclo_id)->orderByDesc('fecha_inicio')->firstOrFail();
        $duplicate = Actividad::where('grupo_id', $ownGroup->id)->firstOrFail();
        $activityCount = Actividad::count();

        $this->actingAs($teacher)->get(route('actividades.index', ['grupo_id' => $otherGroup->id]))
            ->assertForbidden();
        $this->actingAs($teacher)->get(route('actividades.show', Actividad::where('grupo_id', $otherGroup->id)->firstOrFail()))
            ->assertForbidden();

        $this->actingAs($teacher)->post(route('actividades.store'), [
            'grupo_id' => $ownGroup->id,
            'periodo_id' => $duplicate->periodo_id,
            'titulo' => $duplicate->titulo,
            'descripcion' => 'Intento duplicado ficticio.',
            'fecha' => $duplicate->fecha->toDateString(),
            'tipo' => Actividad::NUMERICA,
        ])->assertSessionHasErrors('titulo');
        $this->assertSame($activityCount, Actividad::count());

        $this->actingAs($teacher)->post(route('actividades.store'), [
            'grupo_id' => $ownGroup->id,
            'periodo_id' => $period->id,
            'titulo' => 'Actividad numérica adicional ficticia',
            'descripcion' => 'Actividad creada por la prueba automatizada.',
            'fecha' => '2026-09-20',
            'tipo' => Actividad::NUMERICA,
        ])->assertRedirect();

        $activity = Actividad::where('titulo', 'Actividad numérica adicional ficticia')->firstOrFail();
        $assignments = $ownGroup->asignaciones()->where('estado', 'activa')->get();
        $results = $assignments->mapWithKeys(fn ($assignment, $index) => [$assignment->id => 80 + $index])->all();
        $observations = $assignments->mapWithKeys(fn ($assignment) => [$assignment->id => 'Observación individual ficticia.'])->all();

        $this->actingAs($teacher)->post(route('actividades.calificaciones.store', $activity), [
            'resultados' => $results,
            'observaciones' => $observations,
        ])->assertRedirect(route('actividades.show', $activity));
        $this->assertSame(4, $activity->calificaciones()->count());

        $results[$assignments->first()->id] = 95;
        $this->actingAs($teacher)->post(route('actividades.calificaciones.store', $activity), [
            'resultados' => $results,
            'observaciones' => $observations,
        ])->assertRedirect(route('actividades.show', $activity));

        $this->assertSame(4, $activity->calificaciones()->count());
        $this->assertSame('95.00', Calificacion::where('actividad_id', $activity->id)
            ->where('asignacion_id', $assignments->first()->id)->value('nota'));
    }

    public function test_direction_sees_every_group_and_guardian_only_sees_published_linked_results(): void
    {
        $this->seedDemoData();
        $direction = User::where('codigo_usuario', 'DIR-001')->firstOrFail();
        $guardian = User::where('codigo_usuario', 'ENC-0001')->firstOrFail();
        $published = Actividad::where('publicada', true)->firstOrFail();
        $draft = Actividad::where('publicada', false)->firstOrFail();

        foreach (Grupo::all() as $group) {
            $this->actingAs($direction)->get(route('actividades.index', ['grupo_id' => $group->id]))->assertOk();
        }
        $this->actingAs($direction)->get(route('actividades.show', $draft))->assertOk();

        $this->actingAs($guardian)->get(route('actividades.show', $published))
            ->assertOk()
            ->assertSee($published->titulo)
            ->assertSee('Observación descriptiva ficticia.');
        $this->actingAs($guardian)->get(route('actividades.show', $draft))->assertForbidden();
        $this->actingAs($guardian)->post(route('actividades.calificaciones.store', $published), [
            'resultados' => [],
        ])->assertForbidden();

        $groupResponse = $this->actingAs($guardian)->get(route('actividades.index', ['grupo_id' => $draft->grupo_id]));
        $groupResponse->assertOk()->assertDontSee($draft->titulo);
    }

    public function test_carmen_creates_physical_education_activities_for_all_groups_and_josefa_is_denied(): void
    {
        $this->seedDemoData();
        $carmen = User::where('codigo_usuario', 'DOC-006')->firstOrFail();
        $josefa = User::where('codigo_usuario', 'DOC-007')->firstOrFail();
        foreach (Grupo::where('activo', true)->get() as $group) {
            $period = Periodo::query()->where('ciclo_id', $group->ciclo_id)->where('activo', true)->firstOrFail();
            $this->actingAs($carmen)->post(route('actividades.store'), [
                'grupo_id' => $group->id,
                'periodo_id' => $period->id,
                'titulo' => 'Educación Física '.$group->id,
                'descripcion' => 'Actividad física ficticia.',
                'area_aprendizaje' => 'Educación Física',
                'fecha' => $period->fecha_inicio->toDateString(),
                'tipo' => Actividad::NUMERICA,
                'punteo_maximo' => 20,
            ])->assertRedirect();
        }

        $this->assertSame(6, Actividad::where('tipo_docente', 'educacion_fisica')->count());
        $this->actingAs($josefa)->post(route('actividades.store'), [
            'grupo_id' => Grupo::first()->id,
            'periodo_id' => $period->id,
            'titulo' => 'Actividad no permitida',
            'area_aprendizaje' => 'Especial',
            'fecha' => $period->fecha_inicio->toDateString(),
            'tipo' => Actividad::NUMERICA,
            'punteo_maximo' => 10,
        ])->assertForbidden();
    }

    public function test_score_cannot_exceed_activity_maximum(): void
    {
        $this->seedDemoData();
        $teacher = User::where('codigo_usuario', 'DOC-001')->firstOrFail();
        $activity = Actividad::where('tipo', Actividad::NUMERICA)->firstOrFail();
        $assignment = $activity->grupo->asignaciones()->where('estado', 'activa')->firstOrFail();

        $this->actingAs($teacher)->post(route('actividades.calificaciones.store', $activity), [
            'resultados' => [$assignment->id => (float) $activity->punteo_maximo + 1],
        ])->assertSessionHasErrors('resultados');
    }

    public function test_same_group_and_date_supports_activities_at_different_times(): void
    {
        $this->seedDemoData();
        $teacher = User::where('codigo_usuario', 'DOC-001')->firstOrFail();
        $group = Grupo::where('docente_id', $teacher->id)->firstOrFail();
        $period = Periodo::where('ciclo_id', $group->ciclo_id)->where('activo', true)->firstOrFail();
        foreach ([['Primera hora', '08:00'], ['Segunda hora', '10:30']] as [$title, $time]) {
            $this->actingAs($teacher)->post(route('actividades.store'), [
                'grupo_id' => $group->id, 'periodo_id' => $period->id, 'titulo' => $title,
                'descripcion' => 'Actividad horaria ficticia.', 'area_aprendizaje' => 'Comunicación y Lenguaje',
                'fecha' => $period->fecha_inicio->toDateString(), 'hora_inicio' => $time,
                'tipo' => Actividad::NUMERICA, 'punteo_maximo' => 10,
            ])->assertRedirect();
        }
        $first = Actividad::where('grupo_id', $group->id)->where('titulo', 'Primera hora')->firstOrFail();
        $second = Actividad::where('grupo_id', $group->id)->where('titulo', 'Segunda hora')->firstOrFail();
        $this->assertSame('08:00', $first->hora_inicio->format('H:i'));
        $this->assertSame('10:30', $second->hora_inicio->format('H:i'));
        $this->assertSame($first->fecha->toDateString(), $second->fecha->toDateString());
    }

    private function seedDemoData(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.user_password', Str::password(16));
        $this->seed(DatabaseSeeder::class);
        // Estos escenarios verifican permisos después del cambio inicial de contraseña.
        User::query()->update(['cambiar_password' => false]);
    }
}
