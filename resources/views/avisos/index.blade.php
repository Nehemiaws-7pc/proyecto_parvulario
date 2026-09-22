@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h3 mb-4">Avisos de avances</h1>
    @if (auth()->user()->hasRole([\App\Models\Role::DIRECCION, \App\Models\Role::ADMINISTRATIVO, \App\Models\Role::DOCENTE]))
        <form method="POST" action="{{ route('avisos.store') }}" class="card mb-4"><div class="card-body row g-3">
            @csrf
            <div class="col-md-4"><label class="form-label" for="grupo_id">Grupo destinatario</label><select class="form-select" name="grupo_id" id="grupo_id" required><option value="">Selecciona</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->nombre_completo }}</option>@endforeach</select></div>
            <div class="col-md-8"><label class="form-label" for="asunto">Asunto</label><input class="form-control" name="asunto" id="asunto" required maxlength="180"></div>
            <div class="col-12"><label class="form-label" for="mensaje">Mensaje para las familias</label><textarea class="form-control" name="mensaje" id="mensaje" required maxlength="5000"></textarea></div>
            <div class="col-12 text-end"><button class="btn btn-school">Publicar anuncio</button></div>
        </div></form>
    @endif
    @forelse($avisos as $aviso)
        <article class="card mb-3"><div class="card-body">
            <span class="badge {{ $aviso->grupo_id ? 'text-bg-info' : 'text-bg-success' }}">{{ $aviso->grupo_id ? 'Anuncio al grupo' : 'Avance individual' }}</span>
            <h2 class="h5">{{ $aviso->asunto }}</h2>
            <p class="mb-1">{{ $aviso->mensaje }}</p>
            <small class="text-muted">{{ $aviso->fecha_publicacion?->format('d/m/Y H:i') }} · {{ $aviso->autor?->nombre }}</small>
        </div></article>
    @empty
        <p class="text-muted">No hay avisos publicados.</p>
    @endforelse
</div>
@endsection
