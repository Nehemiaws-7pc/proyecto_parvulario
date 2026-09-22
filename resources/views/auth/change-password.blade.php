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
                <input id="{{ $field }}" name="{{ $field }}" type="password" class="form-control" autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}" required>
                @error($field)<div class="text-danger">{{ $message }}</div>@enderror
            </div>
        @endforeach
        <button class="btn btn-school">Guardar contraseña</button>
    </form>
</main>
@endsection
