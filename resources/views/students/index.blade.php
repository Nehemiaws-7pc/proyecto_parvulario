@extends('layouts.app')

@section('title', 'Estudiantes')

@section('content')
<main class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Expedientes</p>
            <h1 class="h2 fw-bold mb-1">Estudiantes</h1>
            <p class="text-secondary mb-0">La lista muestra únicamente los expedientes autorizados para tu cuenta.</p>
        </div>
        @can('create', \App\Models\Estudiante::class)
            <a href="{{ route('estudiantes.create') }}" class="btn btn-school align-self-start">Nuevo expediente</a>
        @endcan
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="GET" class="card panel-card rounded-4 mb-4">
        <div class="card-body d-flex gap-2">
            <input name="q" value="{{ request('q') }}" class="form-control" placeholder="Buscar por código, nombres o apellidos">
            <button class="btn btn-outline-primary">Buscar</button>
        </div>
    </form>

    <div class="card panel-card rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Código</th><th>Estudiante</th><th>Ciclo, grado y sección</th><th>Estado</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($estudiantes as $estudiante)
                        <tr>
                            <td class="fw-semibold">{{ $estudiante->codigo }}</td>
                            <td>{{ $estudiante->nombre_completo }}</td>
                            <td>
                                @if ($estudiante->asignacionActual)
                                    {{ $estudiante->asignacionActual->grupo->nombre_completo }}
                                @else
                                    <span class="text-secondary">Sin ubicación activa</span>
                                @endif
                            </td>
                            <td><span class="badge text-bg-light">{{ ucfirst($estudiante->estado) }}</span></td>
                            <td class="text-end"><a href="{{ route('estudiantes.show', $estudiante) }}" class="btn btn-sm btn-outline-primary">Ver expediente</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-5">No hay estudiantes disponibles.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $estudiantes->links() }}</div>
</main>
@endsection
