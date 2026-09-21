@extends('layouts.app')
@section('title', 'Panel')
@section('content')
<main class="container py-5">
<div class="d-flex justify-content-between align-items-center mb-4"><div><p class="text-uppercase text-secondary small fw-semibold mb-1">Panel principal</p><h1 class="h2 fw-bold mb-1">Bienvenido, {{ auth()->user()->nombre }}</h1><p class="text-secondary mb-0">Selecciona una sección habilitada para tu rol.</p></div><span class="badge role-badge rounded-pill px-3 py-2">{{ auth()->user()->role->etiqueta }}</span></div>
<div class="row g-4">
<div class="col-md-6 col-xl-4"><x-panel-card icon="E" title="Estudiantes" description="Expedientes y consulta autorizada." route="estudiantes.index" /></div>
@if (!auth()->user()->hasRole(\App\Models\Role::DOCENTE) || !auth()->user()->gruposAsignados()->wherePivot('tipo', 'educacion_especial')->exists())
<div class="col-md-6 col-xl-4"><x-panel-card icon="A" title="Asistencia" description="Registro diario y consulta autorizada." route="asistencia.index" /></div><div class="col-md-6 col-xl-4"><x-panel-card icon="V" title="Evaluaciones" description="Resultados y publicación autorizada." route="evaluaciones.index" /></div><div class="col-md-6 col-xl-4"><x-panel-card icon="T" title="Actividades" description="Actividades y calificaciones por grupo." route="actividades.index" /></div>
@endif
<div class="col-md-6 col-xl-4"><x-panel-card icon="J" title="Justificaciones" description="Consulta y gestión de justificaciones." route="justificaciones.index" /></div><div class="col-md-6 col-xl-4"><x-panel-card icon="N" title="Avisos de avances" description="Seguimiento y comunicación de avances." route="avisos.index" /></div>
@if (auth()->user()->hasRole([\App\Models\Role::DIRECCION, \App\Models\Role::ADMINISTRATIVO]))<div class="col-md-6 col-xl-4"><x-panel-card icon="G" title="Estructura escolar" description="Ciclos, grados, secciones y grupos docentes." route="estructura.index" /></div>@endif
@if (auth()->user()->hasRole(\App\Models\Role::DOCENTE))<div class="col-12"><div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h5">Grupos asignados</h2><p class="mb-0">{{ auth()->user()->gruposAsignados()->with(['grado','seccion'])->get()->map(fn($g) => $g->grado->nombre.' '.$g->seccion->nombre)->unique()->join(', ') }}</p></div></div></div>@endif
</div></main>
@endsection
