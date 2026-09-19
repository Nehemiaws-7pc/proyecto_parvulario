<div class="row g-2">
    <div class="col-12">
        <label class="form-label small">Nombre completo</label>
        <input name="nombre" value="{{ old('nombre', $authorized?->nombre) }}" class="form-control form-control-sm" maxlength="150" required>
    </div>
    <div class="col-6">
        <label class="form-label small">Parentesco</label>
        <input name="parentesco" value="{{ old('parentesco', $authorized?->parentesco) }}" class="form-control form-control-sm" maxlength="50" required>
    </div>
    <div class="col-6">
        <label class="form-label small">Teléfono</label>
        <input name="telefono" value="{{ old('telefono', $authorized?->telefono) }}" class="form-control form-control-sm" maxlength="20">
    </div>
    <div class="col-12">
        <label class="form-check small">
            <input type="hidden" name="activo" value="0">
            <input type="checkbox" name="activo" value="1" class="form-check-input" @checked((bool) old('activo', $authorized?->activo ?? true))>
            <span class="form-check-label">Autorización activa</span>
        </label>
    </div>
</div>
