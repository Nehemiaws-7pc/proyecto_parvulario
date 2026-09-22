@extends('layouts.app')
@section('title', 'Panel')
@section('content')
@php($user = auth()->user())
<main class="container py-5">
<div class="d-flex justify-content-between align-items-center mb-4"><div><p class="text-uppercase text-secondary small fw-semibold mb-1">Panel principal</p><h1 class="h2 fw-bold mb-1">Bienvenido, {{ $user->nombre }}</h1><p class="text-secondary mb-0">Selecciona una sección habilitada para tu rol.</p></div><span class="badge role-badge rounded-pill px-3 py-2">{{ $user->role->etiqueta }}</span></div>
<div class="row g-4">
@can('viewAny', \App\Models\Estudiante::class)<div class="col-md-6 col-xl-4"><x-panel-card icon="E" title="Estudiantes" description="Expedientes y consulta autorizada." route="estudiantes.index" /></div>@endcan
@php($specialist = $user->hasRole(\App\Models\Role::DOCENTE) && $user->gruposAsignados()->wherePivot('tipo', 'educacion_especial')->wherePivot('activo', true)->exists())
@if (!$specialist)
@can('viewAny', \App\Models\Asistencia::class)<div class="col-md-6 col-xl-4"><x-panel-card icon="A" title="Asistencia" description="Registro diario y consulta autorizada." route="asistencia.index" /></div>@endcan
@can('viewAny', \App\Models\Evaluacion::class)<div class="col-md-6 col-xl-4"><x-panel-card icon="V" title="Evaluaciones" description="Resultados y publicación autorizada." route="evaluaciones.index" /></div>@endcan
@can('viewAny', \App\Models\Actividad::class)<div class="col-md-6 col-xl-4"><x-panel-card icon="T" title="Actividades" description="Actividades y calificaciones por grupo." route="actividades.index" /></div>@endcan
@can('viewAny', \App\Models\JustificacionInasistencia::class)<div class="col-md-6 col-xl-4"><x-panel-card icon="J" title="Justificaciones" description="Consulta y gestión de justificaciones." route="justificaciones.index" /></div>@endcan
@endif
@can('viewAny', \App\Models\AvisoAvance::class)<div class="col-md-6 col-xl-4"><x-panel-card icon="N" title="Avisos de avances" description="Seguimiento y comunicación de avances." route="avisos.index" /></div>@endcan
@if ($user->hasRole([\App\Models\Role::DIRECCION, \App\Models\Role::ADMINISTRATIVO]))<div class="col-md-6 col-xl-4"><x-panel-card icon="G" title="Estructura escolar" description="Ciclos, grados, secciones y grupos docentes." route="estructura.index" /></div>
@php($recoveryCount = \App\Models\PasswordRecoveryRequest::where('estado', \App\Models\PasswordRecoveryRequest::PENDING)->count())
<div class="col-md-6 col-xl-4"><x-panel-card icon="R" title="Solicitudes de recuperación" description="{{ $recoveryCount }} solicitud(es) pendiente(s)." route="password-recovery.index" />@if ($recoveryCount === 0)<p class="small text-secondary mt-2 mb-0">No hay solicitudes pendientes.</p>@endif</div>@endif
@if ($user->hasRole(\App\Models\Role::DOCENTE))<div class="col-12"><div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h5">Grupos asignados</h2><p class="mb-0">{{ $user->gruposAsignados()->with(['grado','seccion'])->get()->map(fn($g) => $g->grado->nombre.' '.$g->seccion->nombre)->unique()->join(', ') }}</p></div></div></div>@endif
</div></main>
@endsection
