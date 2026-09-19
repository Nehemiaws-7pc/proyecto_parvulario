@php($pivot = $guardian?->pivot)
<div class="row g-2">
    <div class="col-12">
        <label class="form-label small">Cuenta de acceso vinculada</label>
        <select name="usuario_id" class="form-select form-select-sm">
            <option value="">Sin cuenta de acceso</option>
            @foreach ($parentUsers as $parentUser)
                <option value="{{ $parentUser->id }}" @selected((string) old('usuario_id', $guardian?->usuario_id) === (string) $parentUser->id)>
                    {{ $parentUser->codigo_usuario }} · {{ $parentUser->nombre }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label small">Nombre completo</label>
        <input name="nombre" value="{{ old('nombre', $guardian?->nombre) }}" class="form-control form-control-sm" maxlength="150" required>
    </div>
    <div class="col-6">
        <label class="form-label small">Parentesco</label>
        <input name="parentesco" value="{{ old('parentesco', $pivot?->parentesco) }}" class="form-control form-control-sm" maxlength="50" required>
    </div>
    <div class="col-6">
        <label class="form-label small">Teléfono</label>
        <input name="telefono" value="{{ old('telefono', $guardian?->telefono) }}" class="form-control form-control-sm" maxlength="20" required>
    </div>
    <div class="col-12">
        <label class="form-label small">Correo opcional</label>
        <input type="email" name="correo" value="{{ old('correo', $guardian?->correo) }}" class="form-control form-control-sm" maxlength="150">
    </div>
    <div class="col-12">
        <label class="form-label small">Dirección opcional</label>
        <textarea name="direccion" class="form-control form-control-sm" rows="2">{{ old('direccion', $guardian?->direccion) }}</textarea>
    </div>
    <div class="col-12 d-flex flex-wrap gap-3 small">
        @foreach (['contacto_principal' => 'Principal', 'contacto_emergencia' => 'Emergencia', 'autorizado_recoger' => 'Puede recoger'] as $field => $label)
            <label class="form-check">
                <input type="hidden" name="{{ $field }}" value="0">
                <input type="checkbox" name="{{ $field }}" value="1" class="form-check-input" @checked((bool) old($field, $pivot?->{$field} ?? false))>
                <span class="form-check-label">{{ $label }}</span>
            </label>
        @endforeach
    </div>
</div>
