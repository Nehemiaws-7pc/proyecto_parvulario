@extends('layouts.app')
@section('title', 'Solicitudes de recuperación')
@section('content')
<main class="container py-5">
    <h1 class="h3">Solicitudes pendientes</h1>
    <p class="text-secondary">Verifica la identidad por el procedimiento de la escuela antes de restablecer una cuenta.</p>
    @forelse($requests as $item)
        <div class="card panel-card mb-3"><div class="card-body d-flex flex-wrap justify-content-between gap-3 align-items-center">
            <div><strong>{{ $item->codigo_usuario }} · {{ $item->nombre }}</strong><div>{{ $item->telefono }} · {{ $item->created_at->format('d/m/Y H:i') }}</div></div>
            <form method="POST" action="{{ route('password-recovery.reset', $item) }}" data-confirm="Confirma que verificaste la identidad por el procedimiento de la escuela.">@csrf<button class="btn btn-school">Restablecer cuenta</button></form>
        </div></div>
    @empty
        <p>No hay solicitudes pendientes.</p>
    @endforelse
</main>
@endsection
