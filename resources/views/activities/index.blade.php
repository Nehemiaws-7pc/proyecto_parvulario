@extends('layouts.app')

@section('title', 'Actividades y calificaciones')

@section('content')
<main class="container py-5">
    <div class="mb-4">
        <p class="text-uppercase text-secondary small fw-semibold mb-1">Seguimiento académico</p>
        <h1 class="h2 fw-bold mb-1">Actividades y calificaciones</h1>
        <p class="text-secondary mb-0">Actividades descriptivas o numéricas según los grupos autorizados.</p>
    </div>

    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="GET" action="{{ route('actividades.index') }}" class="card panel-card rounded-4 mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-10">
                <label class="form-label" for="grupo_id">Grupo</label>
                <select id="grupo_id" name="grupo_id" class="form-select" required>
                    @foreach ($groups as $option)
                        <option value="{{ $option->id }}" @selected($group?->id === $option->id)>
                            {{ $option->nombre_completo }} · {{ $option->docente->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-school w-100">Consultar</button></div>
        </div>
    </form>

    @if (! $group)
        <div class="alert alert-info">No hay grupos disponibles para tu cuenta.</div>
    @else
        <div class="card panel-card rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white p-4"><h2 class="h5 fw-bold mb-0">{{ $group->nombre_completo }}</h2></div>
            @if ($activities->isEmpty())
                <div class="card-body text-secondary">No hay actividades visibles para este grupo.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Actividad</th><th>Período</th><th>Fecha</th><th>Tipo</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($activities as $activity)
                                <tr>
                                    <td><strong>{{ $activity->titulo }}</strong><div class="small text-secondary">{{ $activity->calificaciones_count }} resultados</div></td>
                                    <td>{{ $activity->periodo->nombre }}</td>
                                    <td>{{ $activity->fecha->format('d/m/Y') }}<div class="small text-secondary">{{ $activity->hora_inicio?->format('H:i') }}{{ $activity->hora_fin ? ' - '.$activity->hora_fin->format('H:i') : '' }}</div></td>
                                    <td>{{ \App\Models\Actividad::tipos()[$activity->tipo] }}</td>
                                    <td><span class="badge {{ $activity->publicada ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $activity->publicada ? 'Publicada' : 'Borrador' }}</span></td>
                                    <td class="text-end"><a href="{{ route('actividades.show', $activity) }}" class="btn btn-sm btn-outline-primary">Ver resumen</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if ($canCreate)
            <form method="POST" action="{{ route('actividades.store') }}" class="card panel-card rounded-4">
                @csrf
                <input type="hidden" name="grupo_id" value="{{ $group->id }}">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold">Crear actividad para el grupo</h2>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="titulo">Título</label><input id="titulo" name="titulo" value="{{ old('titulo') }}" class="form-control" maxlength="150" required></div>
                        <div class="col-md-3"><label class="form-label" for="periodo_id">Período</label><select id="periodo_id" name="periodo_id" class="form-select" required>@foreach ($periods as $period)<option value="{{ $period->id }}">{{ $period->nombre }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label" for="fecha">Fecha</label><input id="fecha" type="date" name="fecha" value="{{ old('fecha', today()->toDateString()) }}" class="form-control" required></div>
                        <div class="col-md-2"><label class="form-label" for="hora_inicio">Hora inicio</label><input id="hora_inicio" type="time" name="hora_inicio" value="{{ old('hora_inicio') }}" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label" for="hora_fin">Hora fin</label><input id="hora_fin" type="time" name="hora_fin" value="{{ old('hora_fin') }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label" for="tipo">Evaluación</label><select id="tipo" name="tipo" class="form-select" required>@foreach (\App\Models\Actividad::tipos() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                        <div class="col-md-8"><label class="form-label" for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" class="form-control" rows="2" maxlength="3000">{{ old('descripcion') }}</textarea></div>
                    </div>
                </div>
                <div class="card-footer bg-white p-4 text-end"><button class="btn btn-school">Crear actividad</button></div>
            </form>
        @endif
    @endif
</main>
@endsection
