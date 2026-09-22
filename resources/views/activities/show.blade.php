@extends('layouts.app')

@section('title', $actividad->titulo)

@section('content')
<main class="container py-5">
    <a href="{{ route('actividades.index', ['grupo_id' => $actividad->grupo_id]) }}" class="btn btn-link px-0 mb-3">← Volver a actividades</a>
    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card panel-card rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <h1 class="h3 fw-bold mb-1">{{ $actividad->titulo }}</h1>
                    <p class="text-secondary mb-2">{{ $actividad->grupo->nombre_completo }} · {{ $actividad->periodo->nombre }} · {{ $actividad->fecha->format('d/m/Y') }} · {{ $actividad->hora_inicio?->format('H:i') }}{{ $actividad->hora_fin ? ' - '.$actividad->hora_fin->format('H:i') : '' }}</p>
                    <p class="mb-0">{{ $actividad->descripcion ?: 'Sin descripción.' }}</p>
                </div>
                <div><span class="badge {{ $actividad->publicada ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $actividad->publicada ? 'Publicada' : 'Borrador' }}</span></div>
            </div>
        </div>
    </div>

    @if ($canGrade)
        <form method="POST" action="{{ route('actividades.calificaciones.store', $actividad) }}" class="card panel-card rounded-4 overflow-hidden mb-4">
            @csrf
            <div class="card-header bg-white p-4"><h2 class="h5 fw-bold mb-1">Registrar o actualizar resultados</h2><p class="text-secondary mb-0">Completa los cuatro estudiantes del grupo y agrega observaciones individuales cuando corresponda.</p></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Estudiante</th><th>Resultado</th><th>Observación individual</th></tr></thead>
                    <tbody>
                        @foreach ($assignments as $assignment)
                            @php($grade = $gradesByAssignment->get($assignment->id))
                            <tr>
                                <td>{{ $assignment->estudiante->nombre_completo }}</td>
                                <td>
                                    @if ($actividad->tipo === \App\Models\Actividad::DESCRIPTIVA)
                                        <select name="resultados[{{ $assignment->id }}]" class="form-select" required>
                                            <option value="">Selecciona</option>
                                            @foreach ($scales as $scale)<option value="{{ $scale->id }}" @selected((int) old("resultados.{$assignment->id}", $grade?->escala_id) === $scale->id)>{{ $scale->nombre }}</option>@endforeach
                                        </select>
                                    @else
                                        <input type="number" name="resultados[{{ $assignment->id }}]" value="{{ old("resultados.{$assignment->id}", $grade?->nota) }}" min="0" max="100" step="0.01" class="form-control" required>
                                    @endif
                                </td>
                                <td><textarea name="observaciones[{{ $assignment->id }}]" class="form-control" rows="2" maxlength="3000">{{ old("observaciones.{$assignment->id}", $grade?->observacion) }}</textarea></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white p-4 d-flex justify-content-end gap-2">
                <button class="btn btn-school">Guardar resultados</button>
            </div>
        </form>
        @if ($canPublish)
            <form method="POST" action="{{ route('actividades.publish', $actividad) }}" class="text-end mb-4">@csrf<button class="btn btn-success">Publicar para encargados</button></form>
        @endif
    @else
        <div class="card panel-card rounded-4 overflow-hidden">
            <div class="card-header bg-white p-4"><h2 class="h5 fw-bold mb-0">Resumen de calificaciones</h2></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Estudiante</th><th>Resultado</th><th>Observación</th></tr></thead>
                    <tbody>
                        @forelse ($grades as $grade)
                            <tr><td>{{ $grade->estudiante->nombre_completo }}</td><td class="fw-semibold">{{ $actividad->tipo === \App\Models\Actividad::DESCRIPTIVA ? $grade->escala?->nombre : number_format((float) $grade->nota, 2) }}</td><td>{{ $grade->observacion ?: '—' }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-secondary p-4">No hay calificaciones visibles.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</main>
@endsection
