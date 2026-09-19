@extends('layouts.app')

@section('title', 'Asistencia diaria')

@section('content')
<main class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Control diario</p>
            <h1 class="h2 fw-bold mb-1">Asistencia</h1>
            <p class="text-secondary mb-0">Consulta por fecha y grupo según los permisos de tu cuenta.</p>
        </div>
        <a href="{{ route('asistencia.monthly', ['grupo_id' => $group?->id, 'mes' => substr($date, 0, 7)]) }}" class="btn btn-outline-primary align-self-start">Resumen mensual</a>
    </div>

    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="GET" action="{{ route('asistencia.index') }}" class="card panel-card rounded-4 mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="fecha">Fecha</label>
                <input id="fecha" type="date" name="fecha" value="{{ $date }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="grupo_id">Grupo</label>
                <select id="grupo_id" name="grupo_id" class="form-select" required>
                    @foreach ($groups as $option)
                        <option value="{{ $option->id }}" @selected($group?->id === $option->id)>
                            {{ $option->nombre_completo }} · {{ $option->docente->nombre }}{{ $option->activo ? '' : ' · Inactivo' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-school w-100">Consultar</button></div>
        </div>
    </form>

    @if (! $group)
        <div class="alert alert-info">No hay grupos disponibles para tu cuenta.</div>
    @elseif ($attendances->isNotEmpty())
        <div class="card panel-card rounded-4 overflow-hidden">
            <div class="card-header bg-white p-4"><h2 class="h5 fw-bold mb-0">{{ $group->nombre_completo }} · {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</h2></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Estudiante</th><th>Estado</th><th>Observación</th><th>Registró</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($attendances as $attendance)
                            <tr>
                                <td>{{ $attendance->asignacion->estudiante->nombre_completo }}</td>
                                <td>{{ \App\Models\Asistencia::estados()[$attendance->estado] }}</td>
                                <td>{{ $attendance->observacion ?: '—' }}</td>
                                <td>{{ $attendance->registradoPor->nombre }}</td>
                                <td class="text-end">
                                    @can('update', $attendance)
                                        <a href="{{ route('asistencia.edit', $attendance) }}" class="btn btn-sm btn-outline-primary">Corregir</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif ($canRegister)
        <form method="POST" action="{{ route('asistencia.store') }}" class="card panel-card rounded-4 overflow-hidden">
            @csrf
            <input type="hidden" name="fecha" value="{{ $date }}">
            <input type="hidden" name="grupo_id" value="{{ $group->id }}">
            <div class="card-header bg-white p-4">
                <h2 class="h5 fw-bold mb-1">Registrar {{ $group->nombre_completo }}</h2>
                <p class="text-secondary mb-0">Debes marcar a todos los estudiantes antes de guardar.</p>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Estudiante</th><th>Estado</th><th>Observación</th></tr></thead>
                    <tbody>
                        @foreach ($assignments as $assignment)
                            <tr>
                                <td>{{ $assignment->estudiante->nombre_completo }}</td>
                                <td>
                                    <select name="asistencias[{{ $assignment->id }}]" class="form-select" required>
                                        <option value="">Selecciona</option>
                                        @foreach (\App\Models\Asistencia::estados() as $value => $label)
                                            <option value="{{ $value }}" @selected(old("asistencias.{$assignment->id}") === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input name="observaciones[{{ $assignment->id }}]" value="{{ old("observaciones.{$assignment->id}") }}" class="form-control" maxlength="2000"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white p-4 text-end"><button class="btn btn-school">Guardar asistencia del grupo</button></div>
        </form>
    @else
        <div class="alert alert-info">No hay asistencia registrada para esta fecha.</div>
    @endif
</main>
@endsection
