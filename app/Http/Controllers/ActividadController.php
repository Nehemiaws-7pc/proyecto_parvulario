<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Bitacora;
use App\Models\Calificacion;
use App\Models\EscalaEvaluacion;
use App\Models\Grupo;
use App\Models\Periodo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ActividadController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Actividad::class);
        $validated = $request->validate(['grupo_id' => ['nullable', 'integer']]);
        $groups = $this->availableGroups($request->user());
        $group = $this->selectedGroup($groups, isset($validated['grupo_id']) ? (int) $validated['grupo_id'] : null);
        $periods = $group
            ? Periodo::query()->where('ciclo_id', $group->ciclo_id)->orderBy('fecha_inicio')->get()
            : collect();
        $activities = collect();

        if ($group) {
            $activities = Actividad::query()
                ->where('grupo_id', $group->id)
                ->when($request->user()->hasRole(Role::DOCENTE), fn (Builder $query) => $query
                    ->where(fn (Builder $type) => $type->where('tipo_docente', $this->teachingType($request->user(), $group))
                        ->when($this->teachingType($request->user(), $group) === 'titular', fn (Builder $legacy) => $legacy->orWhereNull('tipo_docente'))))
                ->when($request->user()->hasRole(Role::ENCARGADO), fn (Builder $query) => $query
                    ->where('publicada', true)
                    ->whereHas('calificaciones', fn (Builder $grades) => $grades
                        ->where(function (Builder $state) {
                            $state->where('estado', 'calificada')->orWhere(function (Builder $legacy) {
                                $legacy->where(fn (Builder $value) => $value->whereNotNull('nota')->orWhereNotNull('escala_id'));
                            });
                        })
                        ->whereHas('estudiante.encargados', fn (Builder $guardians) => $guardians
                            ->where('usuario_id', $request->user()->id))))
                ->with(['periodo', 'creadoPor'])
                ->withCount('calificaciones')
                ->orderByDesc('fecha')
                ->orderByDesc('hora_inicio')
                ->orderByDesc('id')
                ->get();
        }

        $canCreate = $group
            && $request->user()->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO, Role::DOCENTE])
            && $group->activo
            && ($request->user()->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO]) || $group->tieneDocente($request->user()));

        return view('activities.index', compact('activities', 'canCreate', 'group', 'groups', 'periods'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Actividad::class);
        $validated = $request->validate([
            'grupo_id' => ['required', 'integer', 'exists:grupos,id'],
            'periodo_id' => ['required', 'integer', 'exists:periodos,id'],
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:3000'],
            'area_aprendizaje' => ['nullable', 'string', 'max:120'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'hora_fin' => ['nullable', 'date_format:H:i', 'after:hora_inicio'],
            'tipo' => ['required', Rule::in(array_keys(Actividad::tipos()))],
            'punteo_maximo' => ['nullable', 'numeric', 'gt:0', 'max:9999.99'],
        ]);

        $group = $this->teacherGroup($request->user(), (int) $validated['grupo_id']);
        $period = Periodo::query()
            ->whereKey($validated['periodo_id'])
            ->where('ciclo_id', $group->ciclo_id)
            ->where('activo', true)
            ->first();
        abort_unless($period, 403);

        if ($validated['fecha'] < $period->fecha_inicio->toDateString()
            || $validated['fecha'] > $period->fecha_fin->toDateString()) {
            throw ValidationException::withMessages([
                'fecha' => 'La fecha debe pertenecer al período seleccionado.',
            ]);
        }

        $duplicate = Actividad::query()
            ->where('grupo_id', $group->id)
            ->where('periodo_id', $period->id)
            ->where('titulo', $validated['titulo'])
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'titulo' => 'Ya existe una actividad con este título en el período y grupo.',
            ]);
        }

        if ($this->teachingType($request->user(), $group) === 'educacion_fisica') {
            $validated['area_aprendizaje'] = 'Educación Física';
        }
        $activity = Actividad::create([
            ...$validated,
            'punteo_maximo' => $validated['punteo_maximo'] ?? 100,
            'creado_por' => $request->user()->id,
            'tipo_docente' => $this->teachingType($request->user(), $group),
            'publicada' => false,
        ]);

        return redirect()->route('actividades.show', $activity)
            ->with('status', 'Actividad creada. Ahora registra los resultados del grupo.');
    }

    public function show(Request $request, Actividad $actividad): View
    {
        Gate::authorize('view', $actividad);
        $actividad->load(['grupo.ciclo', 'grupo.grado', 'grupo.seccion', 'periodo']);

        $grades = $actividad->calificaciones()
            ->when($request->user()->hasRole(Role::ENCARGADO), fn (Builder $query) => $query
                ->where(function (Builder $state) {
                    $state->where('estado', 'calificada')->orWhere(function (Builder $legacy) {
                        $legacy->where(fn (Builder $value) => $value->whereNotNull('nota')->orWhereNotNull('escala_id'));
                    });
                })
                ->whereHas('estudiante.encargados', fn (Builder $guardians) => $guardians
                    ->where('usuario_id', $request->user()->id)))
            ->with(['estudiante', 'escala'])
            ->get()
            ->sortBy(fn (Calificacion $grade) => $grade->estudiante->nombre_completo)
            ->values();

        $assignments = collect();
        $canGrade = Gate::allows('update', $actividad);

        if ($canGrade) {
            $assignments = $actividad->grupo->asignaciones()
                ->where('estado', 'activa')
                ->whereHas('estudiante', fn (Builder $query) => $query->where('estado', 'activo'))
                ->with('estudiante')
                ->get()
                ->sortBy(fn ($assignment) => $assignment->estudiante->nombre_completo)
                ->values();
        }

        $gradesByAssignment = $grades->keyBy('asignacion_id');
        $scales = EscalaEvaluacion::query()->where('activo', true)->orderBy('orden')->get();
        $canPublish = $canGrade
            && ! $actividad->publicada
            && $assignments->isNotEmpty()
            && $grades->count() === $assignments->count();

        return view('activities.show', compact(
            'actividad',
            'assignments',
            'canGrade',
            'canPublish',
            'grades',
            'gradesByAssignment',
            'scales',
        ));
    }

    public function storeGrades(Request $request, Actividad $actividad): RedirectResponse
    {
        Gate::authorize('update', $actividad);
        $validated = $request->validate([
            'resultados' => ['required', 'array'],
            'resultados.*' => ['required'],
            'observaciones' => ['nullable', 'array'],
            'observaciones.*' => ['nullable', 'string', 'max:3000'],
        ]);
        $assignments = $actividad->grupo->asignaciones()
            ->where('estado', 'activa')
            ->whereHas('estudiante', fn (Builder $query) => $query->where('estado', 'activo'))
            ->get();
        $expectedIds = $assignments->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
        $submittedIds = collect(array_keys($validated['resultados']))->map(fn ($id) => (int) $id)->sort()->values();

        if ($expectedIds->isEmpty() || $expectedIds->all() !== $submittedIds->all()) {
            throw ValidationException::withMessages([
                'resultados' => 'Debes registrar un resultado para cada estudiante activo del grupo.',
            ]);
        }

        $scaleIds = EscalaEvaluacion::query()->where('activo', true)->pluck('id')->map(fn ($id) => (int) $id);

        foreach ($validated['resultados'] as $result) {
            if ($actividad->tipo === Actividad::DESCRIPTIVA && ! $scaleIds->contains((int) $result)) {
                throw ValidationException::withMessages(['resultados' => 'Selecciona una escala descriptiva válida.']);
            }

            if ($actividad->tipo === Actividad::NUMERICA
                && (! is_numeric($result) || (float) $result < 0 || (float) $result > (float) $actividad->punteo_maximo)) {
                throw ValidationException::withMessages(['resultados' => 'Las notas deben estar entre 0 y el punteo máximo.']);
            }
        }

        DB::transaction(function () use ($actividad, $assignments, $request, $validated) {
            foreach ($assignments as $assignment) {
                $result = $validated['resultados'][$assignment->id];
                Calificacion::updateOrCreate(
                    [
                        'actividad_id' => $actividad->id,
                        'estudiante_id' => $assignment->estudiante_id,
                    ],
                    [
                        'asignacion_id' => $assignment->id,
                        'escala_id' => $actividad->tipo === Actividad::DESCRIPTIVA ? (int) $result : null,
                        'nota' => $actividad->tipo === Actividad::NUMERICA ? (float) $result : null,
                        'estado' => 'calificada',
                        'observacion' => $validated['observaciones'][$assignment->id] ?? null,
                        'calificado_por' => $request->user()->id,
                        'calificado_at' => now(),
                    ],
                );
            }

            Bitacora::create([
                'usuario_id' => $request->user()->id,
                'accion' => 'registrar_calificaciones',
                'modulo' => 'actividades',
                'descripcion' => "Resultados de la actividad {$actividad->id} actualizados.",
                'fecha' => now(),
            ]);
        });

        return redirect()->route('actividades.show', $actividad)
            ->with('status', 'Calificaciones actualizadas correctamente.');
    }

    public function publish(Request $request, Actividad $actividad): RedirectResponse
    {
        Gate::authorize('update', $actividad);
        $expected = $actividad->grupo->asignaciones()
            ->where('estado', 'activa')
            ->whereHas('estudiante', fn (Builder $query) => $query->where('estado', 'activo'))
            ->count();

        if ($expected === 0 || $actividad->calificaciones()->count() !== $expected) {
            throw ValidationException::withMessages([
                'actividad' => 'Registra las calificaciones de todo el grupo antes de publicar.',
            ]);
        }

        DB::transaction(function () use ($actividad, $request) {
            $actividad->update([
                'publicada' => true,
                'publicada_at' => now(),
                'publicada_por' => $request->user()->id,
            ]);
            Bitacora::create([
                'usuario_id' => $request->user()->id,
                'accion' => 'publicar_actividad',
                'modulo' => 'actividades',
                'descripcion' => "Actividad {$actividad->id} publicada para encargados.",
                'fecha' => now(),
            ]);
        });

        return redirect()->route('actividades.show', $actividad)
            ->with('status', 'Actividad publicada para los encargados.');
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
                            ->where('grupo_docente.activo', true)
                            ->whereIn('grupo_docente.tipo', ['titular', 'educacion_fisica']));
                }))
            ->when($user->hasRole(Role::ENCARGADO), fn (Builder $query) => $query
                ->whereHas('asignaciones.estudiante.encargados', fn (Builder $guardians) => $guardians
                    ->where('usuario_id', $user->id)))
            ->get()
            ->sortBy(fn (Grupo $group) => [$group->grado->nombre, $group->seccion->nombre])
            ->values();
    }

    private function selectedGroup(Collection $groups, ?int $groupId): ?Grupo
    {
        if ($groupId !== null) {
            $group = $groups->firstWhere('id', $groupId);
            abort_unless($group, 403);

            return $group;
        }

        return $groups->first();
    }

    private function teacherGroup(User $user, int $groupId): Grupo
    {
        if ($user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO])) {
            return Grupo::query()->whereKey($groupId)->where('activo', true)->firstOrFail();
        }
        $group = Grupo::query()
            ->whereKey($groupId)
            ->where('activo', true)
            ->where(function (Builder $group) use ($user) {
                $group->where('docente_id', $user->id)
                    ->orWhereHas('docentes', fn (Builder $teacher) => $teacher
                        ->whereKey($user->id)
                        ->where('grupo_docente.activo', true)
                        ->whereIn('grupo_docente.tipo', ['titular', 'educacion_fisica']));
            })
            ->first();
        abort_unless($group, 403);

        return $group;
    }

    private function teachingType(User $user, Grupo $group): string
    {
        return $group->docentes()->whereKey($user->id)->wherePivot('activo', true)->value('grupo_docente.tipo') ?: 'titular';
    }
}
