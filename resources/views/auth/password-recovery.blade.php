@extends('layouts.app')
@section('title', 'Recuperar contraseña')
@section('content')
<main class="container py-5" style="max-width: 640px">
    <h1 class="h3">Solicitar recuperación</h1>
    <p>Ingresa tus datos. Dirección verificará tu identidad antes de restablecer la cuenta.</p>
    @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
    <form method="POST" action="{{ route('password-recovery.store') }}">
        @csrf
        @foreach(['codigo_usuario' => 'Código de usuario', 'nombre' => 'Nombre completo', 'telefono' => 'Teléfono'] as $field => $label)
            <div class="mb-3"><label for="{{ $field }}" class="form-label">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" value="{{ old($field) }}" class="form-control" required maxlength="{{ $field === 'codigo_usuario' ? 30 : ($field === 'nombre' ? 150 : 20) }}">@error($field)<div class="text-danger">{{ $message }}</div>@enderror</div>
        @endforeach
        <button class="btn btn-school">Enviar solicitud</button>
        <a class="btn btn-link" href="{{ route('login') }}">Volver al inicio de sesión</a>
    </form>
</main>
@endsection
