@extends('layouts.app')

@section('title', 'Editar expediente')

@section('content')
<main class="container py-5">
    <a href="{{ route('estudiantes.show', $estudiante) }}" class="btn btn-link px-0 mb-3">← Volver al expediente</a>
    <div class="card panel-card rounded-4">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h3 fw-bold mb-1">Editar expediente</h1>
            <p class="text-secondary mb-4">{{ $estudiante->codigo }} · {{ $estudiante->nombre_completo }}</p>
            <form method="POST" action="{{ route('estudiantes.update', $estudiante) }}">
                @csrf
                @method('PUT')
                @include('students._form')
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('estudiantes.show', $estudiante) }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button class="btn btn-school">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
