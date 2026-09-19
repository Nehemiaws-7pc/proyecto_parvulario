@extends('layouts.app')

@section('title', 'Estructura escolar')

@section('content')
<main class="container py-5">
    <div class="mb-4">
        <a href="{{ route('dashboard') }}" class="btn btn-link px-0">← Volver al panel</a>
        <h1 class="h2 fw-bold mb-1">Estructura escolar</h1>
        <p class="text-secondary">Configura ciclos, grados, secciones y los grupos a cargo de cada docente.</p>
    </div>

    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-4">
        <div class="col-lg-6">
            <section class="card panel-card rounded-4 h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold">Ciclos escolares</h2>
                    <form method="POST" action="{{ route('estructura.ciclos.store') }}" class="row g-2 mb-4">
                        @csrf
                        <div class="col-3"><input type="number" name="anio" value="{{ now()->year }}" class="form-control" min="2020" max="2100" required></div>
                        <div class="col"><input type="date" name="fecha_inicio" class="form-control" required></div>
                        <div class="col"><input type="date" name="fecha_fin" class="form-control" required></div>
                        <div class="col-12 d-flex gap-2"><select name="estado" class="form-select"><option value="planificado">Planificado</option><option value="activo">Activo</option><option value="cerrado">Cerrado</option></select><button class="btn btn-school">Agregar</button></div>
                    </form>
                    @foreach ($ciclos as $ciclo)
                        <form method="POST" action="{{ route('estructura.ciclos.update', $ciclo) }}" class="row g-2 border-top pt-3 mt-3">
                            @csrf @method('PUT')
                            <div class="col-3"><input type="number" name="anio" value="{{ $ciclo->anio }}" class="form-control form-control-sm" required></div>
                            <div class="col"><input type="date" name="fecha_inicio" value="{{ $ciclo->fecha_inicio->format('Y-m-d') }}" class="form-control form-control-sm" required></div>
                            <div class="col"><input type="date" name="fecha_fin" value="{{ $ciclo->fecha_fin->format('Y-m-d') }}" class="form-control form-control-sm" required></div>
                            <div class="col-9"><select name="estado" class="form-select form-select-sm">@foreach (['planificado','activo','cerrado'] as $estado)<option value="{{ $estado }}" @selected($ciclo->estado === $estado)>{{ ucfirst($estado) }}</option>@endforeach</select></div>
                            <div class="col-3"><button class="btn btn-sm btn-outline-primary w-100">Guardar</button></div>
                        </form>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="col-lg-6">
            <section class="card panel-card rounded-4 h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold">Grados</h2>
                    <form method="POST" action="{{ route('estructura.grados.store') }}" class="row g-2 mb-4">
                        @csrf
                        <div class="col-5"><input name="nombre" class="form-control" placeholder="Nombre" required></div>
                        <div class="col"><input name="descripcion" class="form-control" placeholder="Descripción opcional"></div>
                        <input type="hidden" name="activo" value="1"><div class="col-auto"><button class="btn btn-school">Agregar</button></div>
                    </form>
                    @foreach ($grados as $grado)
                        <form method="POST" action="{{ route('estructura.grados.update', $grado) }}" class="row g-2 border-top pt-3 mt-3">
                            @csrf @method('PUT')
                            <div class="col-4"><input name="nombre" value="{{ $grado->nombre }}" class="form-control form-control-sm" required></div>
                            <div class="col"><input name="descripcion" value="{{ $grado->descripcion }}" class="form-control form-control-sm"></div>
                            <div class="col-auto form-check pt-1"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" class="form-check-input" @checked($grado->activo)></div>
                            <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Guardar</button></div>
                        </form>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="col-lg-6">
            <section class="card panel-card rounded-4 h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold">Secciones</h2>
                    <form method="POST" action="{{ route('estructura.secciones.store') }}" class="row g-2 mb-4">
                        @csrf
                        <div class="col"><input name="nombre" class="form-control" placeholder="Nombre" required></div>
                        <div class="col"><input type="number" name="capacidad" class="form-control" placeholder="Capacidad" min="1" max="100"></div>
                        <input type="hidden" name="activo" value="1"><div class="col-auto"><button class="btn btn-school">Agregar</button></div>
                    </form>
                    @foreach ($secciones as $seccion)
                        <form method="POST" action="{{ route('estructura.secciones.update', $seccion) }}" class="row g-2 border-top pt-3 mt-3">
                            @csrf @method('PUT')
                            <div class="col"><input name="nombre" value="{{ $seccion->nombre }}" class="form-control form-control-sm" required></div>
                            <div class="col"><input type="number" name="capacidad" value="{{ $seccion->capacidad }}" class="form-control form-control-sm"></div>
                            <div class="col-auto form-check pt-1"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" class="form-check-input" @checked($seccion->activo)></div>
                            <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Guardar</button></div>
                        </form>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="col-lg-6">
            <section class="card panel-card rounded-4 h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold">Grupos docentes</h2>
                    <form method="POST" action="{{ route('estructura.grupos.store') }}" class="row g-2 mb-4">
                        @csrf
                        <div class="col-6"><select name="ciclo_id" class="form-select" required><option value="">Ciclo</option>@foreach ($ciclos as $ciclo)<option value="{{ $ciclo->id }}">{{ $ciclo->anio }}</option>@endforeach</select></div>
                        <div class="col-6"><select name="grado_id" class="form-select" required><option value="">Grado</option>@foreach ($grados->where('activo', true) as $grado)<option value="{{ $grado->id }}">{{ $grado->nombre }}</option>@endforeach</select></div>
                        <div class="col-6"><select name="seccion_id" class="form-select" required><option value="">Sección</option>@foreach ($secciones->where('activo', true) as $seccion)<option value="{{ $seccion->id }}">{{ $seccion->nombre }}</option>@endforeach</select></div>
                        <div class="col-6"><select name="docente_id" class="form-select" required><option value="">Docente</option>@foreach ($docentes as $docente)<option value="{{ $docente->id }}">{{ $docente->nombre }}</option>@endforeach</select></div>
                        <input type="hidden" name="activo" value="1"><div class="col-12"><button class="btn btn-school w-100">Crear grupo</button></div>
                    </form>
                    @foreach ($grupos as $grupo)
                        <form method="POST" action="{{ route('estructura.grupos.update', $grupo) }}" class="border-top pt-3 mt-3">
                            @csrf @method('PUT')
                            <div class="row g-2">
                                <div class="col-4"><select name="ciclo_id" class="form-select form-select-sm">@foreach ($ciclos as $ciclo)<option value="{{ $ciclo->id }}" @selected($grupo->ciclo_id === $ciclo->id)>{{ $ciclo->anio }}</option>@endforeach</select></div>
                                <div class="col-4"><select name="grado_id" class="form-select form-select-sm">@foreach ($grados as $grado)<option value="{{ $grado->id }}" @selected($grupo->grado_id === $grado->id)>{{ $grado->nombre }}</option>@endforeach</select></div>
                                <div class="col-4"><select name="seccion_id" class="form-select form-select-sm">@foreach ($secciones as $seccion)<option value="{{ $seccion->id }}" @selected($grupo->seccion_id === $seccion->id)>{{ $seccion->nombre }}</option>@endforeach</select></div>
                                <div class="col-8"><select name="docente_id" class="form-select form-select-sm">@foreach ($docentes as $docente)<option value="{{ $docente->id }}" @selected($grupo->docente_id === $docente->id)>{{ $docente->nombre }}</option>@endforeach</select></div>
                                <div class="col-1 form-check pt-1"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" class="form-check-input" @checked($grupo->activo)></div>
                                <div class="col-3"><button class="btn btn-sm btn-outline-primary w-100">Guardar</button></div>
                            </div>
                        </form>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</main>
@endsection
