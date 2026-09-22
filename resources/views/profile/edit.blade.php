@extends('layouts.app')
@section('title', 'Mi perfil')
@section('content')
<main class="container py-5" style="max-width: 760px">
    <div class="card panel-card rounded-4"><div class="card-body p-4 p-lg-5">
        <h1 class="h3">Mi perfil</h1>
        <p class="text-secondary">Actualiza únicamente tus datos de contacto.</p>
        <dl class="row border-bottom pb-3 mb-4">
            <dt class="col-sm-4">Código identificador</dt><dd class="col-sm-8"><span class="fw-semibold">{{ $user->codigo_usuario }}</span><small class="d-block text-secondary">Este es tu código para iniciar sesión. Guárdalo en un lugar seguro.</small></dd>
            <dt class="col-sm-4">Rol</dt><dd class="col-sm-8">{{ $user->role->etiqueta }}</dd>
        </dl>
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf @method('PUT')
            @foreach(['nombre' => 'Nombre completo', 'telefono' => 'Teléfono', 'correo' => 'Correo electrónico (opcional)'] as $field => $label)
                <div class="mb-3"><label for="profile-{{ $field }}" class="form-label">{{ $label }}</label><input id="profile-{{ $field }}" name="{{ $field }}" type="{{ $field === 'correo' ? 'email' : 'text' }}" value="{{ old($field, $user->{$field}) }}" class="form-control @error($field) is-invalid @enderror" maxlength="{{ $field === 'nombre' ? 150 : ($field === 'telefono' ? 20 : 150) }}" @required($field === 'nombre')>@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @endforeach
            <button class="btn btn-school">Guardar cambios</button>
            <a class="btn btn-outline-secondary ms-2" href="{{ route('password.edit') }}">Cambiar contraseña</a>
        </form>
    </div></div>
</main>
@endsection
