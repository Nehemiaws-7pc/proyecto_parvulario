@extends('layouts.app')

@section('title', 'Corregir asistencia')

@section('content')
<main class="container py-5">
    <a href="{{ route('asistencia.index', ['fecha' => $asistencia->fecha->toDateString(), 'grupo_id' => $asistencia->asignacion->grupo_id]) }}" class="btn btn-link px-0 mb-3">← Volver a asistencia</a>
    <div class="card panel-card rounded-4">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h3 fw-bold">Corrección autorizada</h1>
            <p class="text-secondary">{{ $asistencia->asignacion->estudiante->nombre_completo }} · {{ $asistencia->asignacion->grupo->nombre_completo }} · {{ $asistencia->fecha->format('d/m/Y') }}</p>
            <div class="alert alert-warning">La corrección quedará registrada en la bitácora con el usuario responsable y el motivo.</div>
            <form method="POST" action="{{ route('asistencia.update', $asistencia) }}">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label" for="estado">Estado</label>
                    <select id="estado" name="estado" class="form-select" required>
                        @foreach (\App\Models\Asistencia::estados() as $value => $label)
                            <option value="{{ $value }}" @selected(old('estado', $asistencia->estado) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="observacion">Observación</label>
                    <textarea id="observacion" name="observacion" class="form-control" rows="3">{{ old('observacion', $asistencia->observacion) }}</textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="motivo_correccion">Motivo de la corrección</label>
                    <textarea id="motivo_correccion" name="motivo_correccion" class="form-control @error('motivo_correccion') is-invalid @enderror" rows="3" required>{{ old('motivo_correccion') }}</textarea>
                    @error('motivo_correccion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-school">Guardar corrección</button>
            </form>
        </div>
    </div>
</main>
@endsection
