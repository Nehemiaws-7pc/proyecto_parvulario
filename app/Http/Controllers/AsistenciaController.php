<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Bitacora;
use App\Models\Grupo;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AsistenciaController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Asistencia::class);
        $validated = $request->validate([
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'grupo_id' => ['nullable', 'integer'],
        ]);
        $date = $validated['fecha'] ?? today()->toDateString();
        $groups = $this->availableGroups($request->user());
        $group = $this->selectedGroup($groups, isset($validated['grupo_id']) ? (int) $validated['grupo_id'] : null);
        $attendances = collect();
        $assignments = collect();

        if ($group) {
            $attendances = Asistencia::query()
                ->whereDate('fecha', $date)
                ->whereHas('asignacion', fn (Builder $query) => $query->where('grupo_id', $group->id))
                ->when($request->user()->hasRole(Role::ENCARGADO), fn (Builder $query) => $query
                    ->whereHas('asignacion.estudiante.encargados', fn (Builder $guardians) => $guardians
                        ->where('usuario_id', $request->user()->id)))
                ->with(['asignacion.estudiante', 'registradoPor'])
                ->get()
                ->sortBy(fn (Asistencia $attendance) => $attendance->asignacion->estudiante->nombre_completo)
                ->values();

            $assignments = $group->asignaciones()
                ->where('estado', 'activa')
                ->whereHas('estudiante', fn (Builder $query) => $query->where('estado', 'activo'))
                ->with('estudiante')
                ->get()
                ->sortBy(fn ($assignment) => $assignment->estudiante->nombre_completo)
                ->values();
        }

        $canRegister = $group
            && $request->user()->hasRole(Role::DOCENTE)
            && $group->activo
            && $group->tieneDocente($request->user())
            && CarbonImmutable::parse($date)->lte(today())
            && $attendances->isEmpty();

        return view('attendance.index', compact(
            'attendances',
            'assignments',
            'canRegister',
            'date',
            'group',
            'groups',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('viewAny', Asistencia::class);
        abort_unless($request->user()->hasRole(Role::DOCENTE), 403);

        $validated = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'grupo_id' => ['required', 'integer', 'exists:grupos,id'],
            'asistencias' => ['required', 'array'],
            'asistencias.*' => ['required', Rule::in(array_keys(Asistencia::estados()))],
            'observaciones' => ['nullable', 'array'],
            'observaciones.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $group = Grupo::query()
            ->whereKey($validated['grupo_id'])
            ->where('activo', true)
            ->where(function (Builder $group) use ($request) {
                $group->where('docente_id', $request->user()->id)
                    ->orWhereHas('docentes', fn (Builder $teacher) => $teacher
                        ->where('users.id', $request->user()->id)
                        ->wherePivot('activo', true)
                        ->whereIn('grupo_docente.tipo', ['titular', 'educacion_fisica']));
            })
            ->first();
        abort_unless($group, 403);

        $assignments = $group->asignaciones()
            ->where('estado', 'activa')
            ->whereHas('estudiante', fn (Builder $query) => $query->where('estado', 'activo'))
            ->with('estudiante')
            ->get();
        $expectedIds = $assignments->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
        $submittedIds = collect(array_keys($validated['asistencias']))->map(fn ($id) => (int) $id)->sort()->values();

        if ($expectedIds->isEmpty() || $expectedIds->all() !== $submittedIds->all()) {
            throw ValidationException::withMessages([
                'asistencias' => 'Debes registrar el estado de todos los estudiantes activos del grupo.',
            ]);
        }

        $duplicates = Asistencia::query()
            ->whereDate('fecha', $validated['fecha'])
            ->whereIn('asignacion_id', $expectedIds)
            ->exists();

        if ($duplicates) {
            throw ValidationException::withMessages([
                'fecha' => 'La asistencia de este grupo y fecha ya fue registrada. Solicita una corrección autorizada.',
            ]);
        }

        try {
            DB::transaction(function () use ($request, $validated, $assignments, $group) {
                foreach ($assignments as $assignment) {
                    Asistencia::create([
                        'asignacion_id' => $assignment->id,
                        'registrado_por' => $request->user()->id,
                        'fecha' => $validated['fecha'],
                        'estado' => $validated['asistencias'][$assignment->id],
                        'observacion' => $validated['observaciones'][$assignment->id] ?? null,
                    ]);
                }

                Bitacora::create([
                    'usuario_id' => $request->user()->id,
                    'accion' => 'registrar_asistencia',
                    'modulo' => 'asistencia',
                    'descripcion' => "Asistencia del grupo {$group->id} para {$validated['fecha']}.",
                    'fecha' => now(),
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'fecha' => 'La asistencia de uno o más estudiantes ya fue registrada para esta fecha.',
            ]);
        }

        return redirect()->route('asistencia.index', [
            'fecha' => $validated['fecha'],
            'grupo_id' => $group->id,
        ])->with('status', 'Asistencia registrada correctamente.');
    }

    public function edit(Asistencia $asistencia): View
    {
        Gate::authorize('update', $asistencia);
        $asistencia->load(['asignacion.estudiante', 'asignacion.grupo.ciclo', 'asignacion.grupo.grado', 'asignacion.grupo.seccion']);

        return view('attendance.edit', compact('asistencia'));
    }

    public function update(Request $request, Asistencia $asistencia): RedirectResponse
    {
        Gate::authorize('update', $asistencia);
        $validated = $request->validate([
            'estado' => ['required', Rule::in(array_keys(Asistencia::estados()))],
            'observacion' => ['nullable', 'string', 'max:2000'],
            'motivo_correccion' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        $oldStatus = $asistencia->estado;
        $oldObservation = $asistencia->observacion ?: 'sin observación';
        $newObservation = $validated['observacion'] ?? null;
        $newObservationDescription = $newObservation ?: 'sin observación';

        DB::transaction(function () use ($request, $validated, $asistencia, $oldStatus, $oldObservation, $newObservation, $newObservationDescription) {
            $asistencia->update([
                'estado' => $validated['estado'],
                'observacion' => $newObservation,
            ]);
            Bitacora::create([
                'usuario_id' => $request->user()->id,
                'accion' => 'corregir_asistencia',
                'modulo' => 'asistencia',
                'descripcion' => "Asistencia {$asistencia->id}: estado {$oldStatus} → {$validated['estado']}; observación {$oldObservation} → {$newObservationDescription}. Motivo: {$validated['motivo_correccion']}",
                'fecha' => now(),
            ]);
        });

        return redirect()->route('asistencia.index', [
            'fecha' => $asistencia->fecha->toDateString(),
            'grupo_id' => $asistencia->asignacion->grupo_id,
        ])->with('status', 'Asistencia corregida y registrada en bitácora.');
    }

    public function monthly(Request $request): View
    {
        Gate::authorize('viewAny', Asistencia::class);
        $validated = $request->validate([
            'mes' => ['nullable', 'date_format:Y-m'],
            'grupo_id' => ['nullable', 'integer'],
        ]);
        $month = $validated['mes'] ?? today()->format('Y-m');
        $start = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->endOfMonth();
        $groups = $this->availableGroups($request->user());
        $group = $this->selectedGroup($groups, isset($validated['grupo_id']) ? (int) $validated['grupo_id'] : null);
        $summary = collect();

        if ($group) {
            $records = Asistencia::query()
                ->whereBetween('fecha', [$start->toDateString(), $end->toDateString()])
                ->whereHas('asignacion', fn (Builder $query) => $query->where('grupo_id', $group->id))
                ->when($request->user()->hasRole(Role::ENCARGADO), fn (Builder $query) => $query
                    ->whereHas('asignacion.estudiante.encargados', fn (Builder $guardians) => $guardians
                        ->where('usuario_id', $request->user()->id)))
                ->with('asignacion.estudiante')
                ->get();

            $summary = $records->groupBy(fn (Asistencia $attendance) => $attendance->asignacion->estudiante_id)
                ->map(function (Collection $items) {
                    $counts = $items->countBy('estado');

                    return [
                        'estudiante' => $items->first()->asignacion->estudiante,
                        'presente' => $counts->get(Asistencia::PRESENTE, 0),
                        'ausente' => $counts->get(Asistencia::AUSENTE, 0),
                        'tarde' => $counts->get(Asistencia::TARDE, 0),
                        'justificada' => $counts->get(Asistencia::JUSTIFICADO, 0),
                        'total' => $items->count(),
                    ];
                })
                ->sortBy(fn (array $row) => $row['estudiante']->nombre_completo)
                ->values();
        }

        return view('attendance.monthly', compact('end', 'group', 'groups', 'month', 'start', 'summary'));
    }

    private function availableGroups(User $user): Collection
    {
        return Grupo::query()
            ->with(['ciclo', 'grado', 'seccion', 'docente'])
            ->when($user->hasRole(Role::DOCENTE), fn (Builder $query) => $query
                ->where(function (Builder $group) use ($user) {
                    $group->where('docente_id', $user->id)
                        ->orWhereHas('docentes', fn (Builder $teacher) => $teacher
                            ->where('users.id', $user->id)
                            ->wherePivot('activo', true)
                            ->whereIn('grupo_docente.tipo', ['titular', 'educacion_fisica']));
                }))
            ->when($user->hasRole(Role::ENCARGADO), fn (Builder $query) => $query
                ->whereHas('asignaciones.estudiante.encargados', fn (Builder $guardians) => $guardians
                    ->where('usuario_id', $user->id)))
            ->get()
            ->sortByDesc(fn (Grupo $group) => $group->ciclo->anio)
            ->values();
    }

    private function selectedGroup(Collection $groups, ?int $groupId): ?Grupo
    {
        if ($groupId !== null) {
            $group = $groups->firstWhere('id', $groupId);
            abort_unless($group, 403, 'No tienes autorización para consultar este grupo.');

            return $group;
        }

        return $groups->first();
    }
}
