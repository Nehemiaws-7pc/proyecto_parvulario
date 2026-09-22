<?php

namespace App\Http\Controllers;

use App\Models\AvisoAvance;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AvisoAvanceController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', AvisoAvance::class);
        $user = $request->user();
        $avisos = AvisoAvance::query()->with(['autor', 'grupo', 'estudiante'])->where('activo', true)
            ->when($user->hasRole(Role::ENCARGADO), fn (Builder $q) => $q->where(function (Builder $scope) use ($user) {
                $scope->whereHas('estudiante.encargados', fn ($e) => $e->where('usuario_id', $user->id))
                    ->orWhereHas('grupo.asignaciones.estudiante.encargados', fn ($e) => $e->where('usuario_id', $user->id));
            }))
            ->when($user->hasRole(Role::DOCENTE), fn (Builder $q) => $q->where(function (Builder $scope) use ($user) {
                $scope->whereHas('grupo', fn ($g) => $g->where('docente_id', $user->id)->orWhereHas('docentes', fn ($t) => $t->whereKey($user->id)->where('grupo_docente.activo', true)))
                    ->orWhereHas('estudiante.asignaciones', fn ($a) => $a->where('estado', 'activa')->whereHas('grupo', fn ($g) => $g->where('docente_id', $user->id)->orWhereHas('docentes', fn ($t) => $t->whereKey($user->id)->where('grupo_docente.activo', true))));
            }))
            ->latest('fecha_publicacion')->get();

        $groups = $user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO])
            ? Grupo::query()->where('activo', true)->with(['grado', 'seccion'])->get()
            : ($user->hasRole(Role::DOCENTE)
                ? $user->gruposAsignados()->wherePivot('activo', true)->with(['grado', 'seccion'])->get()
                : collect());

        return view('avisos.index', compact('avisos', 'groups'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', AvisoAvance::class);
        $data = $request->validate([
            'estudiante_id' => ['nullable', 'integer', 'exists:estudiantes,id'],
            'grupo_id' => ['nullable', 'integer', 'exists:grupos,id'],
            'asunto' => ['required', 'string', 'max:180'],
            'mensaje' => ['required', 'string', 'max:5000'],
        ]);
        abort_unless(($data['estudiante_id'] ?? null) || ($data['grupo_id'] ?? null), 422);
        $group = ($data['grupo_id'] ?? null) ? Grupo::findOrFail($data['grupo_id']) : null;
        if ($request->user()->hasRole(Role::DOCENTE)) {
            if ($group) {
                abort_unless($group->tieneDocente($request->user()), 403);
            } elseif ($data['estudiante_id'] ?? null) {
                abort_unless($this->studentBelongsToTeacher((int) $data['estudiante_id'], $request->user()), 403);
            }
        }
        if ($group && ! $group->activo) {
            abort(403);
        }
        AvisoAvance::create([...$data, 'autor_id' => $request->user()->id, 'fecha_publicacion' => now(), 'activo' => true]);

        return back()->with('status', 'Aviso publicado.');
    }

    private function studentBelongsToTeacher(int $studentId, $user): bool
    {
        return Estudiante::query()->whereKey($studentId)
            ->whereHas('asignaciones', fn (Builder $a) => $a->where('estado', 'activa')->whereHas('grupo', fn (Builder $g) => $g
                ->where('activo', true)->where(fn (Builder $scope) => $scope->where('docente_id', $user->id)
                ->orWhereHas('docentes', fn (Builder $t) => $t->whereKey($user->id)->where('grupo_docente.activo', true)))))
            ->exists();
    }
}
