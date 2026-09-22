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
        @php($currentUser = auth()->user())
        <nav class="navbar navbar-expand-lg navbar-dark navbar-school">
            <div class="container py-1">
                <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
                    <span class="brand-mark" aria-hidden="true">AB</span>
                    <span class="d-none d-sm-inline">Escuelita Parvularia Arévalo Barrios</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Mostrar navegación">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNavigation">
                    <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                        @can('viewAny', \App\Models\Estudiante::class)<a class="nav-link text-white" href="{{ route('estudiantes.index') }}">Estudiantes</a>@endcan
                        @if (!$currentUser->hasRole(\App\Models\Role::DOCENTE) || !$currentUser->gruposAsignados()->wherePivot('tipo', 'educacion_especial')->wherePivot('activo', true)->exists())
                            @can('viewAny', \App\Models\Asistencia::class)<a class="nav-link text-white" href="{{ route('asistencia.index') }}">Asistencia</a>@endcan
                            @can('viewAny', \App\Models\Actividad::class)<a class="nav-link text-white" href="{{ route('actividades.index') }}">Actividades</a>@endcan
                            @can('viewAny', \App\Models\Evaluacion::class)<a class="nav-link text-white" href="{{ route('evaluaciones.index') }}">Evaluaciones</a>@endcan
                            @can('viewAny', \App\Models\JustificacionInasistencia::class)<a class="nav-link text-white" href="{{ route('justificaciones.index') }}">Justificaciones</a>@endcan
                        @endif
                        @can('viewAny', \App\Models\AvisoAvance::class)<a class="nav-link text-white" href="{{ route('avisos.index') }}">Avisos</a>@endcan
                        @if ($currentUser->hasRole([\App\Models\Role::DIRECCION, \App\Models\Role::ADMINISTRATIVO]))<a class="nav-link text-white" href="{{ route('estructura.index') }}">Estructura</a>@endif
                    </div>
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 text-white ms-lg-4 py-3 py-lg-0">
                        <div class="text-lg-end small">
                            <div class="fw-semibold">{{ auth()->user()->nombre }}</div>
                            <div class="opacity-75">{{ auth()->user()->role->etiqueta }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-light btn-sm">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
    @endauth

    @yield('content')
</body>
</html>
