<?php

namespace App\Providers;

use App\Models\Actividad;
use App\Models\Asistencia;
use App\Models\AvisoAvance;
use App\Models\Estudiante;
use App\Models\Evaluacion;
use App\Models\JustificacionInasistencia;
use App\Policies\ActividadPolicy;
use App\Policies\AsistenciaPolicy;
use App\Policies\AvisoAvancePolicy;
use App\Policies\EstudiantePolicy;
use App\Policies\EvaluacionPolicy;
use App\Policies\JustificacionInasistenciaPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Estudiante::class, EstudiantePolicy::class);
        Gate::policy(Asistencia::class, AsistenciaPolicy::class);
        Gate::policy(Evaluacion::class, EvaluacionPolicy::class);
        Gate::policy(Actividad::class, ActividadPolicy::class);
        Gate::policy(AvisoAvance::class, AvisoAvancePolicy::class);
        Gate::policy(JustificacionInasistencia::class, JustificacionInasistenciaPolicy::class);
        Paginator::useBootstrapFive();
    }
}
