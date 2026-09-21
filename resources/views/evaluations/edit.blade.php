@extends('layouts.app')

@section('title', 'Corregir evaluación')

@section('content')
<main class="container py-5">
    <a href="{{ route('evaluaciones.index', ['grupo_id' => $evaluacion->asignacion->grupo_id, 'periodo_id' => $evaluacion->periodo_id, 'area_id' => $evaluacion->indicador->area_id, 'indicador_id' => $evaluacion->indicador_id]) }}" class="btn btn-link px-0 mb-3">← Volver a evaluaciones</a>
    <div class="card panel-card rounded-4 mx-auto" style="max-width: 850px">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h3 fw-bold">Corregir evaluación</h1>
            <p class="text-secondary">
                {{ $evaluacion->asignacion->estudiante->nombre_completo }} · {{ $evaluacion->periodo->nombre }}<br>
                {{ $evaluacion->indicador->area->nombre }} · {{ $evaluacion->indicador->nombre }}
            </p>
            @if ($evaluacion->publicado)
                <div class="alert alert-warning">Este resultado ya está publicado. La corrección será visible para los encargados y quedará registrada en bitácora.</div>
            @else
                <div class="alert alert-info">La corrección quedará registrada en bitácora con el usuario y el motivo.</div>
            @endif
            @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form method="POST" action="{{ route('evaluaciones.update', $evaluacion) }}">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label" for="escala_id">Resultado descriptivo</label>
                    <select id="escala_id" name="escala_id" class="form-select" required>
                        @foreach ($scales as $scale)
                            <option value="{{ $scale->id }}" @selected((int) old('escala_id', $evaluacion->escala_id) === $scale->id)>{{ $scale->nombre }}{{ $scale->activo ? '' : ' · Inactivo' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="observacion">Observación docente</label>
                    <textarea id="observacion" name="observacion" class="form-control" rows="4" maxlength="3000">{{ old('observacion', $evaluacion->observacion) }}</textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="motivo_correccion">Motivo de la corrección</label>
                    <textarea id="motivo_correccion" name="motivo_correccion" class="form-control @error('motivo_correccion') is-invalid @enderror" rows="3" maxlength="1000" required>{{ old('motivo_correccion') }}</textarea>
                    @error('motivo_correccion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-school">Guardar corrección</button>
            </form>
        </div>
    </div>
</main>
@endsection
