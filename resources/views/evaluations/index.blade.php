@extends('layouts.app')

@section('title', 'Evaluaciones')

@section('content')
<main class="container py-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Seguimiento del aprendizaje</p>
            <h1 class="h2 fw-bold mb-1">Evaluaciones descriptivas</h1>
            <p class="text-secondary mb-0">Resultados por período e indicador según los permisos de tu cuenta.</p>
        </div>
        @can('configure', \App\Models\Evaluacion::class)
            <a href="{{ route('evaluaciones.configuracion') }}" class="btn btn-outline-primary align-self-start">Configurar evaluaciones</a>
        @endcan
    </div>

    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="GET" action="{{ route('evaluaciones.index') }}" class="card panel-card rounded-4 mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-sm-6 col-xl-3">
                <label class="form-label" for="grupo_id">Grupo</label>
                <select id="grupo_id" name="grupo_id" class="form-select" required onchange="this.form.periodo_id.value=''; this.form.area_id.value=''; this.form.indicador_id.value=''; this.form.submit()">
                    @foreach ($groups as $option)
                        <option value="{{ $option->id }}" @selected($group?->id === $option->id)>
                            {{ $option->nombre_completo }}{{ $option->activo ? '' : ' · Inactivo' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-xl-3">
                <label class="form-label" for="periodo_id">Período</label>
                <select id="periodo_id" name="periodo_id" class="form-select" required>
                    @foreach ($periods as $option)
                        <option value="{{ $option->id }}" @selected($period?->id === $option->id)>{{ $option->nombre }}{{ $option->activo ? '' : ' · Inactivo' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-xl-2">
                <label class="form-label" for="area_id">Área</label>
                <select id="area_id" name="area_id" class="form-select" required onchange="this.form.indicador_id.value=''; this.form.submit()">
                    @foreach ($areas as $option)
                        <option value="{{ $option->id }}" @selected($area?->id === $option->id)>{{ $option->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-xl-3">
                <label class="form-label" for="indicador_id">Indicador</label>
                <select id="indicador_id" name="indicador_id" class="form-select" required>
                    @foreach ($indicators as $option)
                        <option value="{{ $option->id }}" @selected($indicator?->id === $option->id)>{{ $option->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-1"><button class="btn btn-school w-100">Ver</button></div>
        </div>
    </form>

    @if (! $group)
        <div class="alert alert-info">No hay grupos disponibles para tu cuenta.</div>
    @elseif (! $period || ! $area || ! $indicator)
        <div class="alert alert-info">Configura y selecciona un período, un área y un indicador para continuar.</div>
    @else
        <div class="card panel-card rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white p-4 d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">{{ $indicator->nombre }}</h2>
                    <p class="text-secondary mb-0">{{ $group->nombre_completo }} · {{ $period->nombre }} · {{ $area->nombre }}</p>
                </div>
                @if ($canPublish)
                    <form method="POST" action="{{ route('evaluaciones.publish') }}">
                        @csrf
                        <input type="hidden" name="grupo_id" value="{{ $group->id }}">
                        <input type="hidden" name="periodo_id" value="{{ $period->id }}">
                        <input type="hidden" name="indicador_id" value="{{ $indicator->id }}">
                        <button class="btn btn-success">Publicar para encargados</button>
                    </form>
                @endif
            </div>

            @if ($evaluations->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Estudiante</th><th>Resultado</th><th>Observación docente</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($evaluations as $evaluation)
                                <tr>
                                    <td>{{ $evaluation->asignacion->estudiante->nombre_completo }}</td>
                                    <td class="fw-semibold">{{ $evaluation->escala->nombre }}</td>
                                    <td>{{ $evaluation->observacion ?: '—' }}</td>
                                    <td>
                                        <span class="badge {{ $evaluation->publicado ? 'text-bg-success' : 'text-bg-secondary' }}">
                                            {{ $evaluation->publicado ? 'Publicado' : 'Borrador' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @can('update', $evaluation)
                                            <a href="{{ route('evaluaciones.edit', $evaluation) }}" class="btn btn-sm btn-outline-primary">Corregir</a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif (! $canRegister)
                <div class="card-body p-5 text-center text-secondary">No hay resultados disponibles para esta selección.</div>
            @endif
        </div>

        @if ($canRegister)
            <form method="POST" action="{{ route('evaluaciones.store') }}" class="card panel-card rounded-4 overflow-hidden">
                @csrf
                <input type="hidden" name="grupo_id" value="{{ $group->id }}">
                <input type="hidden" name="periodo_id" value="{{ $period->id }}">
                <input type="hidden" name="indicador_id" value="{{ $indicator->id }}">
                <div class="card-header bg-white p-4">
                    <h2 class="h5 fw-bold mb-1">Registrar resultados</h2>
                    <p class="text-secondary mb-0">Completa a todos los estudiantes. Se guardarán como borrador hasta publicarlos.</p>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>Estudiante</th><th>Resultado descriptivo</th><th>Observación docente</th></tr></thead>
                        <tbody>
                            @foreach ($assignments as $assignment)
                                <tr>
                                    <td>{{ $assignment->estudiante->nombre_completo }}</td>
                                    <td>
                                        <select name="resultados[{{ $assignment->id }}]" class="form-select" required>
                                            <option value="">Selecciona</option>
                                            @foreach ($scales as $scale)
                                                <option value="{{ $scale->id }}" @selected((int) old("resultados.{$assignment->id}") === $scale->id)>{{ $scale->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><textarea name="observaciones[{{ $assignment->id }}]" class="form-control" rows="2" maxlength="3000">{{ old("observaciones.{$assignment->id}") }}</textarea></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white p-4 text-end"><button class="btn btn-school">Guardar borrador del grupo</button></div>
            </form>
        @endif
    @endif
</main>
@endsection
