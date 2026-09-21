<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Bitacora;
use App\Models\JustificacionInasistencia;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JustificacionInasistenciaController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', JustificacionInasistencia::class);
        $user = $request->user();
        $justifications = JustificacionInasistencia::query()
            ->when($user->hasRole(Role::ENCARGADO), fn (Builder $query) => $query
                ->where('solicitado_por', $user->id)
                ->whereHas('asistencia.asignacion.estudiante.encargados', fn (Builder $guardians) => $guardians
                    ->where('usuario_id', $user->id)))
            ->when($user->hasRole(Role::DOCENTE), fn (Builder $query) => $query
                ->whereHas('asistencia.asignacion.grupo', fn (Builder $groups) => $groups
                    ->where('docente_id', $user->id)))
            ->with([
                'asistencia.asignacion.estudiante',
                'asistencia.asignacion.grupo.ciclo',
                'asistencia.asignacion.grupo.grado',
                'asistencia.asignacion.grupo.seccion',
                'solicitadoPor',
                'resueltoPor',
            ])
            ->latest()
            ->get();
        $eligibleAttendances = collect();

        if ($user->hasRole(Role::ENCARGADO)) {
            $eligibleAttendances = Asistencia::query()
                ->whereIn('estado', [Asistencia::AUSENTE, Asistencia::TARDE])
                ->whereDoesntHave('justificacion')
                ->whereHas('asignacion.estudiante.encargados', fn (Builder $guardians) => $guardians
                    ->where('usuario_id', $user->id))
                ->with(['asignacion.estudiante', 'asignacion.grupo.ciclo', 'asignacion.grupo.grado', 'asignacion.grupo.seccion'])
                ->orderByDesc('fecha')
                ->get();
        }

        return view('absence-justifications.index', compact('eligibleAttendances', 'justifications'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', JustificacionInasistencia::class);
        $validated = $request->validate([
            'asistencia_id' => ['required', 'integer', 'exists:asistencias,id'],
            'motivo' => ['required', 'string', 'min:10', 'max:3000'],
        ]);
        $attendance = Asistencia::query()
            ->whereKey($validated['asistencia_id'])
            ->whereIn('estado', [Asistencia::AUSENTE, Asistencia::TARDE])
            ->whereDoesntHave('justificacion')
            ->whereHas('asignacion.estudiante.encargados', fn (Builder $guardians) => $guardians
                ->where('usuario_id', $request->user()->id))
            ->first();
        abort_unless($attendance, 403);

        JustificacionInasistencia::create([
            'asistencia_id' => $attendance->id,
            'solicitado_por' => $request->user()->id,
            'motivo' => $validated['motivo'],
            'estado' => JustificacionInasistencia::PENDIENTE,
        ]);

        return redirect()->route('justificaciones.index')
            ->with('status', 'Justificación enviada para revisión.');
    }

    public function resolve(Request $request, JustificacionInasistencia $justificacion): RedirectResponse
    {
        Gate::authorize('resolve', JustificacionInasistencia::class);
        $validated = $request->validate([
            'estado' => ['required', Rule::in([
                JustificacionInasistencia::ACEPTADA,
                JustificacionInasistencia::RECHAZADA,
            ])],
            'respuesta' => ['nullable', 'string', 'max:3000'],
        ]);

        if ($justificacion->estado !== JustificacionInasistencia::PENDIENTE) {
            throw ValidationException::withMessages([
                'estado' => 'Esta justificación ya fue resuelta.',
            ]);
        }

        DB::transaction(function () use ($justificacion, $request, $validated) {
            $justificacion->update([
                'estado' => $validated['estado'],
                'respuesta' => $validated['respuesta'] ?? null,
                'resuelto_por' => $request->user()->id,
                'resuelto_at' => now(),
            ]);

            if ($validated['estado'] === JustificacionInasistencia::ACEPTADA) {
                $justificacion->asistencia->update(['estado' => Asistencia::JUSTIFICADO]);
            }

            Bitacora::create([
                'usuario_id' => $request->user()->id,
                'accion' => 'resolver_justificacion',
                'modulo' => 'asistencia',
                'descripcion' => "Justificación {$justificacion->id} resuelta como {$validated['estado']}.",
                'fecha' => now(),
            ]);
        });

        return redirect()->route('justificaciones.index')
            ->with('status', 'Justificación resuelta y registrada en bitácora.');
    }
}
