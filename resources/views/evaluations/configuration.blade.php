@extends('layouts.app')

@section('title', 'Configurar evaluaciones')

@section('content')
<main class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
        <div>
            <a href="{{ route('evaluaciones.index') }}" class="btn btn-link px-0">← Volver a evaluaciones</a>
            <h1 class="h2 fw-bold mb-1">Configuración de evaluaciones</h1>
            <p class="text-secondary mb-0">Administra períodos, áreas, indicadores y la escala descriptiva sin modificar código.</p>
        </div>
    </div>

    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-4">
        <section class="col-12" id="periodos">
            <div class="card panel-card rounded-4">
                <div class="card-body p-4">
                    <h2 class="h4 fw-bold">Períodos</h2>
                    <form method="POST" action="{{ route('evaluaciones.periodos.store') }}" class="row g-3 align-items-end border-bottom pb-4 mb-4">
                        @csrf
                        <div class="col-md-3"><label class="form-label">Ciclo</label><select name="ciclo_id" class="form-select" required>@foreach ($ciclos as $ciclo)<option value="{{ $ciclo->id }}">{{ $ciclo->anio }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label">Nombre</label><input name="nombre" class="form-control" maxlength="80" required></div>
                        <div class="col-sm-5 col-md-2"><label class="form-label">Inicio</label><input type="date" name="fecha_inicio" class="form-control" required></div>
                        <div class="col-sm-5 col-md-2"><label class="form-label">Fin</label><input type="date" name="fecha_fin" class="form-control" required></div>
                        <div class="col-sm-2 col-md-1"><div class="form-check mb-2"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" class="form-check-input" id="periodo_activo" checked><label class="form-check-label" for="periodo_activo">Activo</label></div></div>
                        <div class="col-md-1"><button class="btn btn-school w-100">Crear</button></div>
                    </form>
                    <div class="row g-3">
                        @foreach ($periodos as $item)
                            <div class="col-lg-6"><details class="border rounded-3 p-3"><summary class="fw-semibold">{{ $item->ciclo->anio }} · {{ $item->nombre }} · {{ $item->activo ? 'Activo' : 'Inactivo' }}</summary>
                                <form method="POST" action="{{ route('evaluaciones.periodos.update', $item) }}" class="row g-2 mt-2">@csrf @method('PUT')
                                    <div class="col-sm-6"><label class="form-label small">Ciclo</label><select name="ciclo_id" class="form-select">@foreach ($ciclos as $ciclo)<option value="{{ $ciclo->id }}" @selected($item->ciclo_id === $ciclo->id)>{{ $ciclo->anio }}</option>@endforeach</select></div>
                                    <div class="col-sm-6"><label class="form-label small">Nombre</label><input name="nombre" value="{{ $item->nombre }}" class="form-control" required></div>
                                    <div class="col-sm-6"><label class="form-label small">Inicio</label><input type="date" name="fecha_inicio" value="{{ $item->fecha_inicio->toDateString() }}" class="form-control" required></div>
                                    <div class="col-sm-6"><label class="form-label small">Fin</label><input type="date" name="fecha_fin" value="{{ $item->fecha_fin->toDateString() }}" class="form-control" required></div>
                                    <div class="col-12 d-flex justify-content-between align-items-center"><label class="form-check"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" class="form-check-input" @checked($item->activo)> Activo</label><button class="btn btn-sm btn-outline-primary">Actualizar</button></div>
                                </form>
                            </details></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="col-lg-6" id="areas">
            <div class="card panel-card rounded-4 h-100"><div class="card-body p-4">
                <h2 class="h4 fw-bold">Áreas de aprendizaje</h2>
                <form method="POST" action="{{ route('evaluaciones.areas.store') }}" class="row g-3 border-bottom pb-4 mb-4">@csrf
                    <div class="col-12"><label class="form-label">Nombre</label><input name="nombre" class="form-control" maxlength="100" required></div>
                    <div class="col-12"><label class="form-label">Descripción</label><input name="descripcion" class="form-control" maxlength="255"></div>
                    <div class="col-6"><label class="form-check"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" class="form-check-input" checked> Activa</label></div>
                    <div class="col-6 text-end"><button class="btn btn-school">Crear área</button></div>
                </form>
                @foreach ($areas as $item)
                    <details class="border rounded-3 p-3 mb-2"><summary class="fw-semibold">{{ $item->nombre }} · {{ $item->activo ? 'Activa' : 'Inactiva' }}</summary>
                        <form method="POST" action="{{ route('evaluaciones.areas.update', $item) }}" class="row g-2 mt-2">@csrf @method('PUT')
                            <div class="col-12"><input name="nombre" value="{{ $item->nombre }}" class="form-control" required></div>
                            <div class="col-12"><input name="descripcion" value="{{ $item->descripcion }}" class="form-control" maxlength="255"></div>
                            <div class="col-6"><label class="form-check"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" class="form-check-input" @checked($item->activo)> Activa</label></div>
                            <div class="col-6 text-end"><button class="btn btn-sm btn-outline-primary">Actualizar</button></div>
                        </form>
                    </details>
                @endforeach
            </div></div>
        </section>

        <section class="col-lg-6" id="escalas">
            <div class="card panel-card rounded-4 h-100"><div class="card-body p-4">
                <h2 class="h4 fw-bold">Escala descriptiva</h2>
                <p class="small text-secondary">Los cambios se aplican a los formularios sin modificar el código.</p>
                <form method="POST" action="{{ route('evaluaciones.escalas.store') }}" class="row g-3 border-bottom pb-4 mb-4">@csrf
                    <div class="col-sm-3"><label class="form-label">Código</label><input name="codigo" class="form-control" maxlength="30" required></div>
                    <div class="col-sm-6"><label class="form-label">Nombre</label><input name="nombre" class="form-control" maxlength="80" required></div>
                    <div class="col-sm-3"><label class="form-label">Orden</label><input type="number" name="orden" value="1" min="1" max="999" class="form-control" required></div>
                    <div class="col-6"><label class="form-check"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" class="form-check-input" checked> Activo</label></div>
                    <div class="col-6 text-end"><button class="btn btn-school">Crear resultado</button></div>
                </form>
                @foreach ($escalas as $item)
                    <details class="border rounded-3 p-3 mb-2"><summary class="fw-semibold">{{ $item->orden }} · {{ $item->nombre }} ({{ $item->codigo }}) · {{ $item->activo ? 'Activo' : 'Inactivo' }}</summary>
                        <form method="POST" action="{{ route('evaluaciones.escalas.update', $item) }}" class="row g-2 mt-2">@csrf @method('PUT')
                            <div class="col-sm-3"><input name="codigo" value="{{ $item->codigo }}" class="form-control" required></div>
                            <div class="col-sm-6"><input name="nombre" value="{{ $item->nombre }}" class="form-control" required></div>
                            <div class="col-sm-3"><input type="number" name="orden" value="{{ $item->orden }}" min="1" max="999" class="form-control" required></div>
                            <div class="col-6"><label class="form-check"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" class="form-check-input" @checked($item->activo)> Activo</label></div>
                            <div class="col-6 text-end"><button class="btn btn-sm btn-outline-primary">Actualizar</button></div>
                        </form>
                    </details>
                @endforeach
            </div></div>
        </section>

        <section class="col-12" id="indicadores">
            <div class="card panel-card rounded-4"><div class="card-body p-4">
                <h2 class="h4 fw-bold">Criterios o indicadores</h2>
                <form method="POST" action="{{ route('evaluaciones.indicadores.store') }}" class="row g-3 align-items-end border-bottom pb-4 mb-4">@csrf
                    <div class="col-md-3"><label class="form-label">Área</label><select name="area_id" class="form-select" required>@foreach ($areas as $areaItem)<option value="{{ $areaItem->id }}">{{ $areaItem->nombre }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Grado</label><select name="grado_id" class="form-select" required>@foreach ($grados as $grado)<option value="{{ $grado->id }}">{{ $grado->nombre }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Indicador</label><input name="nombre" class="form-control" maxlength="180" required></div>
                    <div class="col-md-2"><label class="form-check mb-2"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" class="form-check-input" checked> Activo</label></div>
                    <div class="col-md-10"><label class="form-label">Descripción opcional</label><input name="descripcion" class="form-control" maxlength="500"></div>
                    <div class="col-md-2"><button class="btn btn-school w-100">Crear indicador</button></div>
                </form>
                <div class="row g-3">
                    @foreach ($indicadores as $item)
                        <div class="col-lg-6"><details class="border rounded-3 p-3"><summary class="fw-semibold">{{ $item->area->nombre }} · {{ $item->grado->nombre }} · {{ $item->nombre }}</summary>
                            <form method="POST" action="{{ route('evaluaciones.indicadores.update', $item) }}" class="row g-2 mt-2">@csrf @method('PUT')
                                <div class="col-sm-6"><select name="area_id" class="form-select">@foreach ($areas as $areaItem)<option value="{{ $areaItem->id }}" @selected($item->area_id === $areaItem->id)>{{ $areaItem->nombre }}</option>@endforeach</select></div>
                                <div class="col-sm-6"><select name="grado_id" class="form-select">@foreach ($grados as $grado)<option value="{{ $grado->id }}" @selected($item->grado_id === $grado->id)>{{ $grado->nombre }}</option>@endforeach</select></div>
                                <div class="col-12"><input name="nombre" value="{{ $item->nombre }}" class="form-control" required></div>
                                <div class="col-12"><input name="descripcion" value="{{ $item->descripcion }}" class="form-control" maxlength="500"></div>
                                <div class="col-6"><label class="form-check"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" class="form-check-input" @checked($item->activo)> Activo</label></div>
                                <div class="col-6 text-end"><button class="btn btn-sm btn-outline-primary">Actualizar</button></div>
                            </form>
                        </details></div>
                    @endforeach
                </div>
            </div></div>
        </section>
    </div>
</main>
@endsection
