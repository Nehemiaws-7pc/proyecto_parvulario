@extends('layouts.app')
@section('title', 'Cambiar contraseña')
@section('content')
<main class="container py-5" style="max-width: 640px">
    <h1 class="h3">Cambiar contraseña</h1>
    <p>Antes de continuar, reemplaza la contraseña inicial por una personal de al menos 12 caracteres.</p>
    <form method="POST" action="{{ route('password.update') }}">
        @csrf @method('PUT')
        @foreach (['current_password' => 'Contraseña actual', 'password' => 'Nueva contraseña', 'password_confirmation' => 'Confirmar nueva contraseña'] as $field => $label)
            <div class="mb-3">
                <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                <div class="input-group"><input id="{{ $field }}" name="{{ $field }}" type="password" class="form-control" autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}" required><button type="button" class="btn btn-outline-secondary password-toggle" data-password-toggle="{{ $field }}" aria-label="Mostrar contraseña" aria-controls="{{ $field }}" aria-pressed="false" title="Mostrar contraseña"><x-line-icon name="eye" class="eye-open" /><x-line-icon name="eye-off" class="eye-off" /><span class="visually-hidden" data-password-label>Mostrar contraseña</span></button></div>
                @error($field)<div class="text-danger">{{ $message }}</div>@enderror
            </div>
        @endforeach
        <button class="btn btn-school">Guardar contraseña</button>
    </form>
</main>
<script>
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const field = document.getElementById(button.dataset.passwordToggle);
        const visible = field.type === 'text';
        field.type = visible ? 'password' : 'text';
        button.classList.toggle('is-visible', !visible);
        button.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
        button.setAttribute('title', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
        button.setAttribute('aria-pressed', String(!visible));
        button.querySelector('[data-password-label]').textContent = visible ? 'Mostrar contraseña' : 'Ocultar contraseña';
    });
});
</script>
@endsection
