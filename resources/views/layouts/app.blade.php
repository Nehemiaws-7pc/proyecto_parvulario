<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema escolar') | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @auth
        <nav class="navbar navbar-expand-lg navbar-dark navbar-school">
            <div class="container py-1">
                <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
                    <span class="brand-mark" aria-hidden="true">AB</span>
                    <span>Escuelita Parvularia Arévalo Barrios</span>
                </a>
                <a class="nav-link text-white ms-auto me-3" href="{{ route('estudiantes.index') }}">Estudiantes</a>
                <div class="d-flex align-items-center gap-3 text-white">
                    <div class="d-none d-md-block text-end small">
                        <div class="fw-semibold">{{ auth()->user()->nombre }}</div>
                        <div class="opacity-75">{{ auth()->user()->role->etiqueta }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm">Cerrar sesión</button>
                    </form>
                </div>
            </div>
        </nav>
    @endauth

    @yield('content')
</body>
</html>
