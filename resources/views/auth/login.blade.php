@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
<main class="login-shell d-flex align-items-center py-5">
    <div class="container"><div class="row justify-content-center"><div class="col-12 col-md-8 col-lg-5 col-xl-4">
        <div class="text-center mb-4"><span class="brand-mark mb-3" aria-hidden="true">AB</span><h1 class="h3 fw-bold mb-1">Sistema de gestión escolar</h1><p class="text-secondary mb-0">Escuelita Parvularia Arévalo Barrios</p></div>
        <div class="card login-card rounded-4"><div class="card-body p-4 p-lg-5">
            <h2 class="h5 fw-bold mb-4">Iniciar sesión</h2>
            @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
            <form method="POST" action="{{ route('login.store') }}" novalidate>
                @csrf
                <div class="mb-3"><label for="codigo_usuario" class="form-label">Código identificador</label><input id="codigo_usuario" name="codigo_usuario" type="text" maxlength="30" value="{{ old('codigo_usuario') }}" class="form-control form-control-lg text-uppercase @error('codigo_usuario') is-invalid @enderror" autocomplete="username" autofocus required>@error('codigo_usuario')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-4"><label for="password" class="form-label">Contraseña</label><div class="input-group input-group-lg"><input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="current-password" required><button type="button" class="btn btn-outline-secondary password-toggle" id="toggle-password" aria-label="Mostrar contraseña" aria-controls="password" aria-pressed="false" title="Mostrar contraseña"><x-line-icon name="eye" class="eye-open" /><x-line-icon name="eye-off" class="eye-off" /><span class="visually-hidden" data-password-label>Mostrar contraseña</span></button></div>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <button type="submit" class="btn btn-school btn-lg w-100">Ingresar</button>
                <div class="text-center mt-3"><a href="{{ route('password-recovery.create') }}">¿Olvidaste tu contraseña?</a></div>
            </form>
        </div></div>
        <p class="small text-secondary text-center mt-4 mb-0">Si no puedes ingresar, comunícate con Dirección.</p>
    </div></div></div>
</main>
<script>
document.getElementById('toggle-password')?.addEventListener('click', function () {
    const field = document.getElementById('password');
    const visible = field.type === 'text';
    field.type = visible ? 'password' : 'text';
    this.classList.toggle('is-visible', !visible);
    this.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
    this.setAttribute('title', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
    this.querySelector('[data-password-label]').textContent = visible ? 'Mostrar contraseña' : 'Ocultar contraseña';
    this.setAttribute('aria-pressed', String(!visible));
});
</script>
@endsection
