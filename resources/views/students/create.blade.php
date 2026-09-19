@extends('layouts.app')

@section('title', 'Nuevo expediente')

@section('content')
<main class="container py-5">
    <a href="{{ route('estudiantes.index') }}" class="btn btn-link px-0 mb-3">← Volver a estudiantes</a>
    <div class="card panel-card rounded-4">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h3 fw-bold mb-1">Nuevo expediente estudiantil</h1>
            <p class="text-secondary mb-4">Registra los datos básicos y su ubicación escolar actual.</p>
            <form method="POST" action="{{ route('estudiantes.store') }}">
                @csrf
                @include('students._form')
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('estudiantes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button class="btn btn-school">Guardar expediente</button>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
