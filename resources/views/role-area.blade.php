@extends('layouts.app')

@section('title', $titulo)

@section('content')
<main class="container py-5">
    <a href="{{ route('dashboard') }}" class="btn btn-link px-0 mb-3">← Volver al panel</a>
    <div class="card panel-card rounded-4">
        <div class="card-body p-4 p-lg-5">
            <span class="feature-icon mb-3" aria-hidden="true">AB</span>
            <h1 class="h2 fw-bold">{{ $titulo }}</h1>
            <p class="text-secondary mb-0">{{ $descripcion }}</p>
        </div>
    </div>
</main>
@endsection
