@extends('layouts.app')

@section('title', $estudiante->nombre_completo)

@section('content')
<main class="container py-5">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a href="{{ route('estudiantes.index') }}" class="btn btn-link px-0 mb-2">← Volver a estudiantes</a>
            <h1 class="h2 fw-bold mb-1">{{ $estudiante->nombre_completo }}</h1>
            <p class="text-secondary mb-0">Expediente {{ $estudiante->codigo }}</p>
        </div>
        @if ($canManage)
            <a href="{{ route('estudiantes.edit', $estudiante) }}" class="btn btn-school">Editar expediente</a>
        @endif
    </div>

    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger"><strong>Revisa los datos ingresados.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <section class="card panel-card rounded-4 mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Datos del expediente</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Nacimiento</dt><dd class="col-sm-8">{{ $estudiante->fecha_nacimiento->format('d/m/Y') }}</dd>
                        <dt class="col-sm-4">Sexo</dt><dd class="col-sm-8">{{ $estudiante->sexo ? ucfirst($estudiante->sexo) : 'No indicado' }}</dd>
                        <dt class="col-sm-4">Estado</dt><dd class="col-sm-8">{{ ucfirst($estudiante->estado) }}</dd>
                        <dt class="col-sm-4">Dirección</dt><dd class="col-sm-8">{{ $estudiante->direccion ?: 'No registrada' }}</dd>
                        <dt class="col-sm-4">Información médica</dt><dd class="col-sm-8">{{ $estudiante->informacion_medica ?: 'No registrada' }}</dd>
                        <dt class="col-sm-4">Observaciones</dt><dd class="col-sm-8">{{ $estudiante->observaciones ?: 'Sin observaciones' }}</dd>
                    </dl>
                </div>
            </section>

            <section class="card panel-card rounded-4 mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Historial de grado y sección</h2>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Ciclo</th><th>Grado y sección</th><th>Docente</th><th>Estado</th></tr></thead>
                            <tbody>
                                @forelse ($estudiante->asignaciones->sortByDesc(fn ($item) => $item->grupo->ciclo->anio) as $asignacion)
                                    <tr>
                                        <td>{{ $asignacion->grupo->ciclo->anio }}</td>
                                        <td>{{ $asignacion->grupo->grado->nombre }} · {{ $asignacion->grupo->seccion->nombre }}</td>
                                        <td>{{ $asignacion->grupo->docente->nombre }}</td>
                                        <td>{{ ucfirst($asignacion->estado) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-secondary">Sin historial escolar.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-5">
            <section class="card panel-card rounded-4 mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Encargados y contactos de emergencia</h2>
                    @forelse ($estudiante->encargados as $encargado)
                        <div class="border rounded-3 p-3 mb-3">
                            @if ($canManage)
                                <form method="POST" action="{{ route('estudiantes.encargados.update', [$estudiante, $encargado]) }}">
                                    @csrf @method('PUT')
                                    @include('students._guardian-form', ['guardian' => $encargado])
                                    <button class="btn btn-sm btn-outline-primary mt-2">Actualizar contacto</button>
                                </form>
                            @else
                                <div class="fw-semibold">{{ $encargado->nombre }}</div>
                                <div>{{ $encargado->pivot->parentesco }} · {{ $encargado->telefono }}</div>
                                <div class="small text-secondary">
                                    @if ($encargado->pivot->contacto_principal) Contacto principal · @endif
                                    @if ($encargado->pivot->contacto_emergencia) Emergencia · @endif
                                    @if ($encargado->pivot->autorizado_recoger) Puede recoger @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-secondary">No hay encargados registrados.</p>
                    @endforelse

                    @if ($canManage)
                        <hr>
                        <a class="btn btn-outline-primary mb-3" href="{{ route('encargados.index', $estudiante) }}">Buscar, crear o desvincular encargados</a>
                        <h3 class="h6 fw-bold">Agregar contacto</h3>
                        <form method="POST" action="{{ route('estudiantes.encargados.store', $estudiante) }}">
                            @csrf
                            @include('students._guardian-form', ['guardian' => null])
                            <button class="btn btn-school btn-sm mt-2">Agregar contacto</button>
                        </form>
                    @endif
                </div>
            </section>

            <section class="card panel-card rounded-4">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Personas autorizadas para recoger</h2>
                    @forelse ($estudiante->personasAutorizadas as $persona)
                        <div class="border rounded-3 p-3 mb-3">
                            @if ($canManage)
                                <form method="POST" action="{{ route('estudiantes.personas.update', [$estudiante, $persona]) }}">
                                    @csrf @method('PUT')
                                    @include('students._authorized-form', ['authorized' => $persona])
                                    <button class="btn btn-sm btn-outline-primary mt-2">Actualizar autorización</button>
                                </form>
                            @else
                                <div class="fw-semibold">{{ $persona->nombre }}</div>
                                <div>{{ $persona->parentesco }} · {{ $persona->telefono ?: 'Sin teléfono' }}</div>
                                <span class="badge {{ $persona->activo ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $persona->activo ? 'Autorización activa' : 'Inactiva' }}</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-secondary">No hay personas autorizadas registradas.</p>
                    @endforelse

                    @if ($canManage)
                        <hr>
                        <h3 class="h6 fw-bold">Agregar persona autorizada</h3>
                        <form method="POST" action="{{ route('estudiantes.personas.store', $estudiante) }}">
                            @csrf
                            @include('students._authorized-form', ['authorized' => null])
                            <button class="btn btn-school btn-sm mt-2">Agregar autorización</button>
                        </form>
                    @endif
                </div>
            </section>
        </div>
    </div>
</main>
@endsection
