<?php

namespace App\Providers;

use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Policies\AsistenciaPolicy;
use App\Policies\EstudiantePolicy;
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
        Paginator::useBootstrapFive();
    }
}
