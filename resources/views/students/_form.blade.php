@php($student = $estudiante ?? null)
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="codigo_sufijo">Código</label>
        @php($studentSuffix = $student && str_starts_with($student->codigo, 'EST-') ? substr($student->codigo, 4) : '')
        <div class="input-group"><span class="input-group-text fw-semibold">EST-</span><input id="codigo_sufijo" name="codigo_sufijo" value="{{ old('codigo_sufijo', $studentSuffix) }}" maxlength="26" pattern="[A-Za-z0-9-]+" class="form-control text-uppercase @error('codigo') is-invalid @enderror" required></div>
        <input type="hidden" name="codigo_prefijo" value="EST-">
        @error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="nombres">Nombres</label>
        <input id="nombres" name="nombres" value="{{ old('nombres', $student?->nombres) }}" class="form-control @error('nombres') is-invalid @enderror" required>
        @error('nombres')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="apellidos">Apellidos</label>
        <input id="apellidos" name="apellidos" value="{{ old('apellidos', $student?->apellidos) }}" class="form-control @error('apellidos') is-invalid @enderror" required>
        @error('apellidos')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="fecha_nacimiento">Fecha de nacimiento</label>
        <input id="fecha_nacimiento" type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento', $student?->fecha_nacimiento?->format('Y-m-d')) }}" class="form-control @error('fecha_nacimiento') is-invalid @enderror" required>
        @error('fecha_nacimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="sexo">Sexo</label>
        <select id="sexo" name="sexo" class="form-select">
            <option value="">Sin especificar</option>
            @foreach (['femenino' => 'Femenino', 'masculino' => 'Masculino', 'otro' => 'Otro'] as $value => $label)
                <option value="{{ $value }}" @selected(old('sexo', $student?->sexo) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="estado">Estado</label>
        <select id="estado" name="estado" class="form-select" required>
            @foreach (['activo' => 'Activo', 'retirado' => 'Retirado', 'inactivo' => 'Inactivo'] as $value => $label)
                <option value="{{ $value }}" @selected(old('estado', $student?->estado ?? 'activo') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label" for="grupo_id">Ciclo, grado y sección</label>
        <select id="grupo_id" name="grupo_id" class="form-select @error('grupo_id') is-invalid @enderror" required>
            <option value="">Selecciona un grupo</option>
            @foreach ($grupos as $grupo)
                <option value="{{ $grupo->id }}" @selected((string) old('grupo_id', $currentGroupId ?? null) === (string) $grupo->id)>
                    {{ $grupo->nombre_completo }} · Docente: {{ $grupo->docente->nombre }}
                </option>
            @endforeach
        </select>
        @error('grupo_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Este dato actualiza el historial escolar; no crea una inscripción.</div>
    </div>
    <div class="col-12">
        <label class="form-label" for="direccion">Dirección</label>
        <textarea id="direccion" name="direccion" class="form-control" rows="2">{{ old('direccion', $student?->direccion) }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="informacion_medica">Información médica indispensable</label>
        <textarea id="informacion_medica" name="informacion_medica" class="form-control" rows="3">{{ old('informacion_medica', $student?->informacion_medica) }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="observaciones">Observaciones</label>
        <textarea id="observaciones" name="observaciones" class="form-control" rows="3">{{ old('observaciones', $student?->observaciones) }}</textarea>
    </div>
</div>
