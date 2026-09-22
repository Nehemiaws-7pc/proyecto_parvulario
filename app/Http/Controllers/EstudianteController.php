<?php

namespace App\Http\Controllers;

use App\Http\Requests\EstudianteRequest;
use App\Models\Bitacora;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EstudianteController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Estudiante::class);

        $user = $request->user();
        $query = Estudiante::query()->with([
            'asignacionActual.grupo.ciclo',
            'asignacionActual.grupo.grado',
            'asignacionActual.grupo.seccion',
        ]);

        if ($user->hasRole(Role::DOCENTE)) {
            $query->whereHas('asignaciones', fn (Builder $assignment) => $assignment
                ->where('estado', 'activa')
                ->whereHas('grupo', fn (Builder $group) => $group
                    ->where('activo', true)
                    ->where(function (Builder $groupQuery) use ($user) {
                        $groupQuery->where('docente_id', $user->id)
                            ->orWhereHas('docentes', fn (Builder $teacher) => $teacher
                                ->whereKey($user->id)
                                ->where('grupo_docente.activo', true));
                    })));
        } elseif ($user->hasRole(Role::ENCARGADO)) {
            $query->whereHas('encargados', fn (Builder $guardian) => $guardian
                ->where('usuario_id', $user->id));
        }

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function (Builder $student) use ($search) {
                $student->where('codigo', 'like', "%{$search}%")
                    ->orWhere('nombres', 'like', "%{$search}%")
                    ->orWhere('apellidos', 'like', "%{$search}%");
            });
        }

        return view('students.index', [
            'estudiantes' => $query->orderBy('apellidos')->orderBy('nombres')->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Estudiante::class);

        return view('students.create', ['grupos' => $this->availableGroups()]);
    }

    public function store(EstudianteRequest $request): RedirectResponse
    {
        Gate::authorize('create', Estudiante::class);
        $validated = $request->validated();
        $groupId = (int) $validated['grupo_id'];
        unset($validated['grupo_id']);

        $student = DB::transaction(function () use ($validated, $groupId, $request) {
            $student = Estudiante::create($validated);
            $this->updateSchoolHistory($student, $groupId);
            $this->audit($request, 'crear_expediente', $student);

            return $student;
        });

        return redirect()->route('estudiantes.show', $student)
            ->with('status', 'Expediente creado correctamente.');
    }

    public function show(Request $request, Estudiante $estudiante): View
    {
        Gate::authorize('view', $estudiante);

        if ($request->user()->hasRole(Role::DOCENTE) && $request->user()->gruposAsignados()
            ->wherePivot('activo', true)->wherePivot('tipo', 'educacion_especial')->exists()) {
            return view('students.basic', [
                'nombre' => $estudiante->nombre_completo,
                'codigo' => $estudiante->codigo,
                'grupo' => $estudiante->asignacionActual?->grupo?->nombre_completo,
            ]);
        }

        $estudiante->load([
            'asignaciones.grupo.ciclo',
            'asignaciones.grupo.grado',
            'asignaciones.grupo.seccion',
            'asignaciones.grupo.docente',
            'encargados.usuario',
            'personasAutorizadas',
        ]);

        $canManage = $request->user()->can('update', $estudiante);

        return view('students.show', [
            'estudiante' => $estudiante,
            'canManage' => $canManage,
            'parentUsers' => $canManage
                ? User::query()->whereHas('role', fn (Builder $role) => $role->where('nombre', Role::ENCARGADO))
                    ->where('activo', true)->orderBy('nombre')->get()
                : collect(),
        ]);
    }

    public function edit(Estudiante $estudiante): View
    {
        Gate::authorize('update', $estudiante);
        $estudiante->load('asignacionActual');

        return view('students.edit', [
            'estudiante' => $estudiante,
            'grupos' => $this->availableGroups(),
            'currentGroupId' => $estudiante->asignacionActual?->grupo_id,
        ]);
    }

    public function update(EstudianteRequest $request, Estudiante $estudiante): RedirectResponse
    {
        Gate::authorize('update', $estudiante);
        $validated = $request->validated();
        $groupId = (int) $validated['grupo_id'];
        unset($validated['grupo_id']);

        DB::transaction(function () use ($validated, $groupId, $request, $estudiante) {
            $estudiante->update($validated);
            $this->updateSchoolHistory($estudiante, $groupId);
            $this->audit($request, 'actualizar_expediente', $estudiante);
        });

        return redirect()->route('estudiantes.show', $estudiante)
            ->with('status', 'Expediente actualizado correctamente.');
    }

    private function availableGroups()
    {
        return Grupo::query()->where('activo', true)
            ->with(['ciclo', 'grado', 'seccion', 'docente'])
            ->get()
            ->sortByDesc(fn (Grupo $group) => $group->ciclo->anio)
            ->values();
    }

    private function updateSchoolHistory(Estudiante $student, int $groupId): void
    {
        $group = Grupo::query()->where('activo', true)->findOrFail($groupId);
        $activeAssignments = $student->asignaciones()->where('estado', 'activa')->with('grupo')->get();
        $alreadyActive = false;

        foreach ($activeAssignments as $assignment) {
            if ($assignment->grupo_id === $group->id) {
                $alreadyActive = true;

                continue;
            }

            $assignment->update([
                'estado' => $assignment->grupo->ciclo_id === $group->ciclo_id ? 'trasladada' : 'finalizada',
            ]);
        }

        if (! $alreadyActive) {
            $student->asignaciones()->create([
                'grupo_id' => $group->id,
                'fecha_asignacion' => today(),
                'estado' => 'activa',
            ]);
        }
    }

    private function audit(Request $request, string $action, Estudiante $student): void
    {
        Bitacora::create([
            'usuario_id' => $request->user()->id,
            'accion' => $action,
            'modulo' => 'estudiantes',
            'descripcion' => "Expediente {$student->codigo}: {$student->nombre_completo}.",
            'fecha' => now(),
        ]);
    }
}
