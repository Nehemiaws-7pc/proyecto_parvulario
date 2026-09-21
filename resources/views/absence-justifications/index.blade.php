@extends('layouts.app')

@section('title', 'Justificaciones de inasistencia')

@section('content')
<main class="container py-5">
    <div class="mb-4"><p class="text-uppercase text-secondary small fw-semibold mb-1">Asistencia</p><h1 class="h2 fw-bold mb-1">Justificaciones</h1><p class="text-secondary mb-0">Solicitud, consulta y resolución de ausencias o llegadas tarde.</p></div>
    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @can('create', \App\Models\JustificacionInasistencia::class)
        <form method="POST" action="{{ route('justificaciones.store') }}" class="card panel-card rounded-4 mb-4">
            @csrf
            <div class="card-body p-4">
                <h2 class="h5 fw-bold">Enviar justificación</h2>
                <div class="row g-3">
                    <div class="col-lg-6"><label class="form-label" for="asistencia_id">Ausencia o llegada tarde</label><select id="asistencia_id" name="asistencia_id" class="form-select" required><option value="">Selecciona</option>@foreach ($eligibleAttendances as $attendance)<option value="{{ $attendance->id }}">{{ $attendance->asignacion->estudiante->nombre_completo }} · {{ $attendance->fecha->format('d/m/Y') }} · {{ \App\Models\Asistencia::estados()[$attendance->estado] }}</option>@endforeach</select></div>
                    <div class="col-lg-6"><label class="form-label" for="motivo">Motivo</label><textarea id="motivo" name="motivo" class="form-control" rows="3" minlength="10" maxlength="3000" required>{{ old('motivo') }}</textarea></div>
                </div>
                @if ($eligibleAttendances->isEmpty())<p class="text-secondary mt-3 mb-0">No hay ausencias o llegadas tarde pendientes de justificación para tus estudiantes vinculados.</p>@endif
            </div>
            <div class="card-footer bg-white p-4 text-end"><button class="btn btn-school" @disabled($eligibleAttendances->isEmpty())>Enviar justificación</button></div>
        </form>
    @endcan

    <div class="card panel-card rounded-4 overflow-hidden">
        <div class="card-header bg-white p-4"><h2 class="h5 fw-bold mb-0">Solicitudes visibles</h2></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Estudiante</th><th>Fecha</th><th>Motivo</th><th>Estado</th><th>Respuesta</th><th></th></tr></thead>
                <tbody>
                    @forelse ($justifications as $justification)
                        <tr>
                            <td>{{ $justification->asistencia->asignacion->estudiante->nombre_completo }}<div class="small text-secondary">{{ $justification->asistencia->asignacion->grupo->nombre_completo }}</div></td>
                            <td>{{ $justification->asistencia->fecha->format('d/m/Y') }}</td>
                            <td>{{ $justification->motivo }}</td>
                            <td><span class="badge {{ $justification->estado === 'aceptada' ? 'text-bg-success' : ($justification->estado === 'rechazada' ? 'text-bg-danger' : 'text-bg-warning') }}">{{ \App\Models\JustificacionInasistencia::estados()[$justification->estado] }}</span></td>
                            <td>{{ $justification->respuesta ?: '—' }}</td>
                            <td>
                                @can('resolve', \App\Models\JustificacionInasistencia::class)
                                    @if ($justification->estado === \App\Models\JustificacionInasistencia::PENDIENTE)
                                        <form method="POST" action="{{ route('justificaciones.resolve', $justification) }}" class="d-flex flex-column gap-2" style="min-width: 220px">@csrf @method('PUT')<select name="estado" class="form-select form-select-sm" required><option value="aceptada">Aceptar</option><option value="rechazada">Rechazar</option></select><input name="respuesta" class="form-control form-control-sm" maxlength="3000" placeholder="Respuesta opcional"><button class="btn btn-sm btn-outline-primary">Resolver</button></form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary p-4">No hay justificaciones visibles.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</main>
@endsection
