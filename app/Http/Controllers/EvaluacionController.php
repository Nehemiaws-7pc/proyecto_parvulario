<?php

namespace App\Http\Controllers;

use App\Models\AreaAprendizaje;
use App\Models\Bitacora;
use App\Models\EscalaEvaluacion;
use App\Models\Evaluacion;
use App\Models\Grupo;
use App\Models\IndicadorEvaluacion;
use App\Models\Periodo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EvaluacionController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Evaluacion::class);
        $validated = $request->validate([
            'grupo_id' => ['nullable', 'integer'],
            'periodo_id' => ['nullable', 'integer'],
            'area_id' => ['nullable', 'integer'],
            'indicador_id' => ['nullable', 'integer'],
        ]);
        $groups = $this->availableGroups($request->user());
        $group = $this->selected($groups, $validated['grupo_id'] ?? null, 'grupo');
        $periods = $group
            ? Periodo::query()->where('ciclo_id', $group->ciclo_id)->orderBy('fecha_inicio')->get()
            : collect();
        $period = $this->selected($periods, $validated['periodo_id'] ?? null, 'período');
        $areas = $group
            ? AreaAprendizaje::query()
                ->whereHas('indicadores', fn (Builder $query) => $query->where('grado_id', $group->grado_id))
                ->orderBy('nombre')->get()
            : collect();
        $area = $this->selected($areas, $validated['area_id'] ?? null, 'área');
        $indicators = $group && $area
            ? IndicadorEvaluacion::query()
                ->where('area_id', $area->id)
                ->where('grado_id', $group->grado_id)
                ->orderBy('nombre')->get()
            : collect();
        $indicator = $this->selected($indicators, $validated['indicador_id'] ?? null, 'indicador');
        $evaluations = collect();
        $assignments = collect();

        if ($group && $period && $indicator) {
            $evaluations = Evaluacion::query()
                ->where('periodo_id', $period->id)
                ->where('indicador_id', $indicator->id)
                ->whereHas('asignacion', fn (Builder $query) => $query->where('grupo_id', $group->id))
                ->when($request->user()->hasRole(Role::ENCARGADO), fn (Builder $query) => $query
                    ->where('publicado', true)
                    ->whereHas('asignacion.estudiante.encargados', fn (Builder $guardians) => $guardians
                        ->where('usuario_id', $request->user()->id)))
                ->with(['asignacion.estudiante', 'escala', 'evaluadoPor'])
                ->get()
                ->sortBy(fn (Evaluacion $evaluation) => $evaluation->asignacion->estudiante->nombre_completo)
                ->values();

            if ($request->user()->hasRole(Role::DOCENTE)) {
                $assignments = $group->asignaciones()
                    ->where('estado', 'activa')
                    ->whereHas('estudiante', fn (Builder $query) => $query->where('estado', 'activo'))
                    ->with('estudiante')->get()
                    ->sortBy(fn ($assignment) => $assignment->estudiante->nombre_completo)
                    ->values();
            }
        }

        $scales = EscalaEvaluacion::query()->where('activo', true)->orderBy('orden')->orderBy('nombre')->get();
        $canRegister = $group && $period && $indicator
            && $request->user()->hasRole(Role::DOCENTE)
            && $group->activo
            && $group->tieneDocente($request->user())
            && $period->activo
            && $indicator->activo
            && $area->activo
            && $assignments->isNotEmpty()
            && $evaluations->isEmpty()
            && $scales->isNotEmpty();
        $canPublish = $group && $period && $indicator
            && $request->user()->hasRole(Role::DOCENTE)
            && $group->activo
            && $group->tieneDocente($request->user())
            && $evaluations->isNotEmpty()
            && $evaluations->contains(fn (Evaluacion $evaluation) => ! $evaluation->publicado);

        return view('evaluations.index', compact(
            'area',
            'areas',
            'assignments',
            'canPublish',
            'canRegister',
            'evaluations',
            'group',
            'groups',
            'indicator',
            'indicators',
            'period',
            'periods',
            'scales',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Evaluacion::class);
        $validated = $request->validate([
            'grupo_id' => ['required', 'integer', 'exists:grupos,id'],
            'periodo_id' => ['required', 'integer', 'exists:periodos,id'],
            'indicador_id' => ['required', 'integer', 'exists:indicadores_evaluacion,id'],
            'resultados' => ['required', 'array'],
            'resultados.*' => ['required', 'integer'],
            'observaciones' => ['nullable', 'array'],
            'observaciones.*' => ['nullable', 'string', 'max:3000'],
        ]);
        $group = $this->teacherGroup($request->user(), (int) $validated['grupo_id']);
        $period = $this->groupPeriod($group, (int) $validated['periodo_id'], true);
        $indicator = $this->groupIndicator($group, (int) $validated['indicador_id'], true);
        $assignments = $group->asignaciones()
            ->where('estado', 'activa')
            ->whereHas('estudiante', fn (Builder $query) => $query->where('estado', 'activo'))
            ->get();
        $expectedIds = $assignments->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
        $submittedIds = collect(array_keys($validated['resultados']))->map(fn ($id) => (int) $id)->sort()->values();

        if ($expectedIds->isEmpty() || $expectedIds->all() !== $submittedIds->all()) {
            throw ValidationException::withMessages([
                'resultados' => 'Debes registrar el resultado de todos los estudiantes activos del grupo.',
            ]);
        }

        $activeScaleIds = EscalaEvaluacion::query()->where('activo', true)->pluck('id')->map(fn ($id) => (int) $id);
        $invalidScale = collect($validated['resultados'])
            ->contains(fn ($scaleId) => ! $activeScaleIds->contains((int) $scaleId));
        if ($invalidScale) {
            throw ValidationException::withMessages(['resultados' => 'Selecciona únicamente resultados activos.']);
        }

        $duplicate = Evaluacion::query()
            ->where('periodo_id', $period->id)
            ->where('indicador_id', $indicator->id)
            ->whereIn('estudiante_id', $assignments->pluck('estudiante_id'))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages([
                'resultados' => 'Ya existen resultados para este indicador y período. Usa la corrección autorizada.',
            ]);
        }

        try {
            DB::transaction(function () use ($assignments, $indicator, $period, $request, $validated) {
                foreach ($assignments as $assignment) {
                    Evaluacion::create([
                        'asignacion_id' => $assignment->id,
                        'estudiante_id' => $assignment->estudiante_id,
                        'periodo_id' => $period->id,
                        'indicador_id' => $indicator->id,
                        'escala_id' => $validated['resultados'][$assignment->id],
                        'evaluado_por' => $request->user()->id,
                        'observacion' => $validated['observaciones'][$assignment->id] ?? null,
                        'fecha' => today(),
                    ]);
                }

                Bitacora::create([
                    'usuario_id' => $request->user()->id,
                    'accion' => 'registrar_evaluaciones',
                    'modulo' => 'evaluaciones',
                    'descripcion' => "Resultados del indicador {$indicator->id} y período {$period->id}.",
                    'fecha' => now(),
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'resultados' => 'Uno o más estudiantes ya tienen resultado para este indicador y período.',
            ]);
        }

        return $this->redirectToSelection($group, $period, $indicator)
            ->with('status', 'Evaluaciones guardadas como borrador.');
    }

    public function publish(Request $request): RedirectResponse
    {
        Gate::authorize('publish', Evaluacion::class);
        $validated = $request->validate([
            'grupo_id' => ['required', 'integer', 'exists:grupos,id'],
            'periodo_id' => ['required', 'integer', 'exists:periodos,id'],
            'indicador_id' => ['required', 'integer', 'exists:indicadores_evaluacion,id'],
        ]);
        $group = $this->teacherGroup($request->user(), (int) $validated['grupo_id']);
        $period = $this->groupPeriod($group, (int) $validated['periodo_id']);
        $indicator = $this->groupIndicator($group, (int) $validated['indicador_id']);
        $assignmentIds = $group->asignaciones()
            ->where('estado', 'activa')
            ->whereHas('estudiante', fn (Builder $query) => $query->where('estado', 'activo'))
            ->pluck('id');
        $evaluations = Evaluacion::query()
            ->where('periodo_id', $period->id)
            ->where('indicador_id', $indicator->id)
            ->whereIn('asignacion_id', $assignmentIds)
            ->get();

        if ($assignmentIds->isEmpty() || $evaluations->pluck('asignacion_id')->sort()->values()->all()
            !== $assignmentIds->sort()->values()->all()) {
            throw ValidationException::withMessages([
                'publicacion' => 'Completa los resultados de todos los estudiantes antes de publicar.',
            ]);
        }
        if ($evaluations->every(fn (Evaluacion $evaluation) => $evaluation->publicado)) {
            throw ValidationException::withMessages(['publicacion' => 'Estos resultados ya están publicados.']);
        }

        DB::transaction(function () use ($evaluations, $indicator, $period, $request) {
            Evaluacion::query()->whereKey($evaluations->pluck('id'))->update([
                'publicado' => true,
                'publicado_at' => now(),
                'publicado_por' => $request->user()->id,
            ]);
            Bitacora::create([
                'usuario_id' => $request->user()->id,
                'accion' => 'publicar_evaluaciones',
                'modulo' => 'evaluaciones',
                'descripcion' => "Publicación del indicador {$indicator->id} y período {$period->id}.",
                'fecha' => now(),
            ]);
        });

        return $this->redirectToSelection($group, $period, $indicator)
            ->with('status', 'Evaluaciones publicadas para las familias autorizadas.');
    }

    public function edit(Evaluacion $evaluacion): View
    {
        Gate::authorize('update', $evaluacion);
        $evaluacion->load([
            'asignacion.estudiante',
            'asignacion.grupo.ciclo',
            'asignacion.grupo.grado',
            'asignacion.grupo.seccion',
            'indicador.area',
            'periodo',
            'escala',
        ]);
        $scales = EscalaEvaluacion::query()->where('activo', true)->orWhereKey($evaluacion->escala_id)
            ->orderBy('orden')->orderBy('nombre')->get();

        return view('evaluations.edit', compact('evaluacion', 'scales'));
    }

    public function update(Request $request, Evaluacion $evaluacion): RedirectResponse
    {
        Gate::authorize('update', $evaluacion);
        $validated = $request->validate([
            'escala_id' => ['required', 'integer', 'exists:escalas_evaluacion,id'],
            'observacion' => ['nullable', 'string', 'max:3000'],
            'motivo_correccion' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        $scale = EscalaEvaluacion::query()->whereKey($validated['escala_id'])->where('activo', true)->first();
        if (! $scale && (int) $validated['escala_id'] !== $evaluacion->escala_id) {
            throw ValidationException::withMessages(['escala_id' => 'Selecciona un resultado activo.']);
        }
        $evaluacion->loadMissing(['escala', 'indicador.area', 'periodo', 'asignacion.grupo']);
        $oldScale = $evaluacion->escala->nombre;
        $oldObservation = $evaluacion->observacion ?: 'sin observación';
        $newObservation = $validated['observacion'] ?? null;
        $newScale = $scale?->nombre ?? $evaluacion->escala->nombre;

        DB::transaction(function () use ($evaluacion, $newObservation, $newScale, $oldObservation, $oldScale, $request, $validated) {
            $evaluacion->update([
                'escala_id' => $validated['escala_id'],
                'observacion' => $newObservation,
            ]);
            Bitacora::create([
                'usuario_id' => $request->user()->id,
                'accion' => 'corregir_evaluacion',
                'modulo' => 'evaluaciones',
                'descripcion' => "Evaluación {$evaluacion->id}: resultado {$oldScale} → {$newScale}; observación {$oldObservation} → ".($newObservation ?: 'sin observación').". Motivo: {$validated['motivo_correccion']}",
                'fecha' => now(),
            ]);
        });

        return $this->redirectToSelection(
            $evaluacion->asignacion->grupo,
            $evaluacion->periodo,
            $evaluacion->indicador,
        )->with('status', 'Evaluación corregida y registrada en bitácora.');
    }

    private function availableGroups(User $user): Collection
    {
        return Grupo::query()
            ->with(['ciclo', 'grado', 'seccion', 'docente'])
            ->when($user->hasRole(Role::DOCENTE), fn (Builder $query) => $query
                ->where('activo', true)
                ->where(function (Builder $group) use ($user) {
                    $group->where('docente_id', $user->id)
                        ->orWhereHas('docentes', fn (Builder $teacher) => $teacher
                            ->whereKey($user->id)
                            ->wherePivot('activo', true)
                            ->whereIn('grupo_docente.tipo', ['titular', 'educacion_fisica']));
                }))
            ->when($user->hasRole(Role::ENCARGADO), fn (Builder $query) => $query
                ->whereHas('asignaciones.estudiante.encargados', fn (Builder $guardians) => $guardians
                    ->where('usuario_id', $user->id)))
            ->get()->sortByDesc(fn (Grupo $group) => $group->ciclo->anio)->values();
    }

    private function selected(Collection $items, mixed $requestedId, string $resource): mixed
    {
        if ($requestedId !== null) {
            $item = $items->firstWhere('id', (int) $requestedId);
            abort_unless($item, 403, "No tienes autorización para consultar este {$resource}.");

            return $item;
        }

        return $items->first();
    }

    private function teacherGroup(User $user, int $groupId): Grupo
    {
        $group = Grupo::query()->whereKey($groupId)->where('activo', true)
            ->where(function (Builder $group) use ($user) {
                $group->where('docente_id', $user->id)
                    ->orWhereHas('docentes', fn (Builder $teacher) => $teacher
                        ->whereKey($user->id)
                        ->wherePivot('activo', true)
                        ->whereIn('grupo_docente.tipo', ['titular', 'educacion_fisica']));
            })->first();
        abort_unless($group, 403);

        return $group;
    }

    private function groupPeriod(Grupo $group, int $periodId, bool $active = false): Periodo
    {
        $period = Periodo::query()->whereKey($periodId)->where('ciclo_id', $group->ciclo_id)
            ->when($active, fn (Builder $query) => $query->where('activo', true))->first();
        abort_unless($period, 403);

        return $period;
    }

    private function groupIndicator(Grupo $group, int $indicatorId, bool $active = false): IndicadorEvaluacion
    {
        $indicator = IndicadorEvaluacion::query()->whereKey($indicatorId)
            ->where('grado_id', $group->grado_id)
            ->when($active, fn (Builder $query) => $query
                ->where('activo', true)
                ->whereHas('area', fn (Builder $area) => $area->where('activo', true)))
            ->with('area')->first();
        abort_unless($indicator, 403);

        return $indicator;
    }

    private function redirectToSelection(Grupo $group, Periodo $period, IndicadorEvaluacion $indicator): RedirectResponse
    {
        return redirect()->route('evaluaciones.index', [
            'grupo_id' => $group->id,
            'periodo_id' => $period->id,
            'area_id' => $indicator->area_id,
            'indicador_id' => $indicator->id,
        ]);
    }
}
