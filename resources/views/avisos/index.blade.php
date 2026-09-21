@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h3 mb-4">Avisos de avances</h1>
    @forelse($avisos as $aviso)
        <article class="card mb-3"><div class="card-body">
            <h2 class="h5">{{ $aviso->asunto }}</h2>
            <p class="mb-1">{{ $aviso->mensaje }}</p>
            <small class="text-muted">{{ $aviso->fecha_publicacion?->format('d/m/Y H:i') }} · {{ $aviso->autor?->nombre }}</small>
        </div></article>
    @empty
        <p class="text-muted">No hay avisos publicados.</p>
    @endforelse
</div>
@endsection
