@extends('layouts.app')

@section('title', 'Panel')

@section('content')
<main class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Panel principal</p>
            <h1 class="h2 fw-bold mb-1">Bienvenido, {{ auth()->user()->nombre }}</h1>
            <p class="text-secondary mb-0">Selecciona una sección habilitada para tu rol.</p>
        </div>
        <span class="badge role-badge rounded-pill px-3 py-2 align-self-start">
            {{ auth()->user()->role->etiqueta }}
        </span>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-xl-4">
            <x-panel-card icon="E" title="Estudiantes" description="Expedientes y consulta según los permisos de tu rol." route="estudiantes.index" />
        </div>

        <div class="col-md-6 col-xl-4">
            <x-panel-card icon="A" title="Asistencia" description="Registro diario, consulta y resumen mensual autorizado." route="asistencia.index" />
        </div>

        <div class="col-md-6 col-xl-4">
            <x-panel-card icon="V" title="Evaluaciones" description="Resultados descriptivos, observaciones y publicación autorizada." route="evaluaciones.index" />
        </div>

        @if (auth()->user()->hasRole([\App\Models\Role::DIRECCION, \App\Models\Role::ADMINISTRATIVO]))
            <div class="col-md-6 col-xl-4">
                <x-panel-card icon="G" title="Estructura escolar" description="Ciclos, grados, secciones y grupos docentes." route="estructura.index" />
            </div>
        @endif

        @if (auth()->user()->hasRole(\App\Models\Role::DIRECCION))
            <div class="col-md-6 col-xl-4">
                <x-panel-card icon="D" title="Dirección" description="Usuarios, seguridad y administración general." route="direccion.index" />
            </div>
        @endif

        @if (auth()->user()->hasRole([\App\Models\Role::DIRECCION, \App\Models\Role::ADMINISTRATIVO]))
            <div class="col-md-6 col-xl-4">
                <x-panel-card icon="A" title="Administración" description="Funciones administrativas autorizadas." route="administracion.index" />
            </div>
        @endif

        @if (auth()->user()->hasRole([\App\Models\Role::DIRECCION, \App\Models\Role::DOCENTE]))
            <div class="col-md-6 col-xl-4">
                <x-panel-card icon="D" title="Docencia" description="Funciones académicas autorizadas." route="docencia.index" />
            </div>
        @endif

        @if (auth()->user()->hasRole([\App\Models\Role::DIRECCION, \App\Models\Role::ENCARGADO]))
            <div class="col-md-6 col-xl-4">
                <x-panel-card icon="F" title="Familia" description="Consulta de información autorizada." route="familia.index" />
            </div>
        @endif
    </div>
</main>
@endsection
