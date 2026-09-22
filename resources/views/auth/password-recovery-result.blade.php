@extends('layouts.app')
@section('title', 'Contraseña temporal')
@section('content')
<main class="container py-5" style="max-width: 700px">
    <div class="alert alert-warning"><h1 class="h4">Entrega privada requerida</h1><p>Esta contraseña temporal se muestra una sola vez. Entrégala directamente a {{ $user->nombre }} y no la registres ni la compartas por canales inseguros.</p><p class="mb-1"><strong>Cuenta:</strong> {{ $user->codigo_usuario }}</p><p class="mb-0"><strong>Contraseña temporal:</strong> <code>{{ $temporary }}</code></p></div>
    <a href="{{ route('password-recovery.index') }}" class="btn btn-school">Volver a solicitudes</a>
</main>
@endsection
