<?php

use App\Http\Controllers\ActividadController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvisoAvanceController;
use App\Http\Controllers\ConfiguracionEvaluacionController;
use App\Http\Controllers\EncargadoController;
use App\Http\Controllers\EstructuraEscolarController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\EvaluacionController;
use App\Http\Controllers\JustificacionInasistenciaController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PasswordRecoveryController;
use App\Http\Controllers\PersonaAutorizadaController;
use App\Http\Middleware\RequirePasswordChange;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/iniciar-sesion', [AuthController::class, 'create'])->name('login');
    Route::post('/iniciar-sesion', [AuthController::class, 'store'])->name('login.store');
    Route::get('/recuperar-contrasena', [PasswordRecoveryController::class, 'create'])->name('password-recovery.create');
    Route::post('/recuperar-contrasena', [PasswordRecoveryController::class, 'store'])->name('password-recovery.store');
});

Route::middleware(['auth', 'active', RequirePasswordChange::class])->group(function () {
    Route::get('/cambiar-contrasena', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/cambiar-contrasena', [PasswordController::class, 'update'])->name('password.update');
    Route::get('estudiantes/{estudiante}/gestionar-encargados', [EncargadoController::class, 'index'])->name('encargados.index');
    Route::post('estudiantes/{estudiante}/cuentas-encargados', [EncargadoController::class, 'createAccount'])->name('encargados.accounts.store');
    Route::delete('estudiantes/{estudiante}/encargados/{encargado}', [EncargadoController::class, 'destroy'])->name('estudiantes.encargados.destroy');
    Route::get('/solicitudes-recuperacion', [PasswordRecoveryController::class, 'index'])->name('password-recovery.index');
    Route::post('/solicitudes-recuperacion/{recovery}/restablecer', [PasswordRecoveryController::class, 'reset'])->name('password-recovery.reset');
    Route::get('/panel', fn () => view('dashboard'))->name('dashboard');
    Route::post('/cerrar-sesion', [AuthController::class, 'destroy'])->name('logout');

    Route::resource('estudiantes', EstudianteController::class)->except('destroy');

    Route::get('asistencia', [AsistenciaController::class, 'index'])->name('asistencia.index');
    Route::post('asistencia', [AsistenciaController::class, 'store'])->name('asistencia.store');
    Route::get('asistencia/resumen-mensual', [AsistenciaController::class, 'monthly'])->name('asistencia.monthly');
    Route::get('asistencia/{asistencia}/editar', [AsistenciaController::class, 'edit'])->name('asistencia.edit');
    Route::put('asistencia/{asistencia}', [AsistenciaController::class, 'update'])->name('asistencia.update');

    Route::get('justificaciones', [JustificacionInasistenciaController::class, 'index'])->name('justificaciones.index');
    Route::post('justificaciones', [JustificacionInasistenciaController::class, 'store'])->name('justificaciones.store');
    Route::put('justificaciones/{justificacion}/resolver', [JustificacionInasistenciaController::class, 'resolve'])
        ->name('justificaciones.resolve');

    Route::get('avisos', [AvisoAvanceController::class, 'index'])->name('avisos.index');
    Route::post('avisos', [AvisoAvanceController::class, 'store'])->name('avisos.store');

    Route::get('actividades', [ActividadController::class, 'index'])->name('actividades.index');
    Route::post('actividades', [ActividadController::class, 'store'])->name('actividades.store');
    Route::get('actividades/{actividad}', [ActividadController::class, 'show'])->name('actividades.show');
    Route::post('actividades/{actividad}/calificaciones', [ActividadController::class, 'storeGrades'])
        ->name('actividades.calificaciones.store');
    Route::post('actividades/{actividad}/publicar', [ActividadController::class, 'publish'])->name('actividades.publish');

    Route::get('evaluaciones', [EvaluacionController::class, 'index'])->name('evaluaciones.index');
    Route::post('evaluaciones', [EvaluacionController::class, 'store'])->name('evaluaciones.store');
    Route::post('evaluaciones/publicar', [EvaluacionController::class, 'publish'])->name('evaluaciones.publish');
    Route::get('evaluaciones/configuracion', [ConfiguracionEvaluacionController::class, 'index'])
        ->name('evaluaciones.configuracion');
    Route::post('evaluaciones/configuracion/periodos', [ConfiguracionEvaluacionController::class, 'storePeriod'])
        ->name('evaluaciones.periodos.store');
    Route::put('evaluaciones/configuracion/periodos/{periodo}', [ConfiguracionEvaluacionController::class, 'updatePeriod'])
        ->name('evaluaciones.periodos.update');
    Route::post('evaluaciones/configuracion/areas', [ConfiguracionEvaluacionController::class, 'storeArea'])
        ->name('evaluaciones.areas.store');
    Route::put('evaluaciones/configuracion/areas/{area}', [ConfiguracionEvaluacionController::class, 'updateArea'])
        ->name('evaluaciones.areas.update');
    Route::post('evaluaciones/configuracion/indicadores', [ConfiguracionEvaluacionController::class, 'storeIndicator'])
        ->name('evaluaciones.indicadores.store');
    Route::put('evaluaciones/configuracion/indicadores/{indicador}', [ConfiguracionEvaluacionController::class, 'updateIndicator'])
        ->name('evaluaciones.indicadores.update');
    Route::post('evaluaciones/configuracion/escalas', [ConfiguracionEvaluacionController::class, 'storeScale'])
        ->name('evaluaciones.escalas.store');
    Route::put('evaluaciones/configuracion/escalas/{escala}', [ConfiguracionEvaluacionController::class, 'updateScale'])
        ->name('evaluaciones.escalas.update');
    Route::get('evaluaciones/{evaluacion}/editar', [EvaluacionController::class, 'edit'])->name('evaluaciones.edit');
    Route::put('evaluaciones/{evaluacion}', [EvaluacionController::class, 'update'])->name('evaluaciones.update');

    Route::post('estudiantes/{estudiante}/encargados', [EncargadoController::class, 'store'])
        ->name('estudiantes.encargados.store');
    Route::put('estudiantes/{estudiante}/encargados/{encargado}', [EncargadoController::class, 'update'])
        ->name('estudiantes.encargados.update');
    Route::post('estudiantes/{estudiante}/personas-autorizadas', [PersonaAutorizadaController::class, 'store'])
        ->name('estudiantes.personas.store');
    Route::put('estudiantes/{estudiante}/personas-autorizadas/{persona}', [PersonaAutorizadaController::class, 'update'])
        ->name('estudiantes.personas.update');

    Route::prefix('estructura')->name('estructura.')
        ->middleware('role:'.Role::DIRECCION.','.Role::ADMINISTRATIVO)
        ->group(function () {
            Route::get('/', [EstructuraEscolarController::class, 'index'])->name('index');
            Route::post('/ciclos', [EstructuraEscolarController::class, 'storeCycle'])->name('ciclos.store');
            Route::put('/ciclos/{ciclo}', [EstructuraEscolarController::class, 'updateCycle'])->name('ciclos.update');
            Route::post('/grados', [EstructuraEscolarController::class, 'storeGrade'])->name('grados.store');
            Route::put('/grados/{grado}', [EstructuraEscolarController::class, 'updateGrade'])->name('grados.update');
            Route::post('/secciones', [EstructuraEscolarController::class, 'storeSection'])->name('secciones.store');
            Route::put('/secciones/{seccion}', [EstructuraEscolarController::class, 'updateSection'])->name('secciones.update');
            Route::post('/grupos', [EstructuraEscolarController::class, 'storeGroup'])->name('grupos.store');
            Route::put('/grupos/{grupo}', [EstructuraEscolarController::class, 'updateGroup'])->name('grupos.update');
        });

    Route::view('/panel/direccion', 'role-area', [
        'titulo' => 'Área de Dirección',
        'descripcion' => 'Administración general, usuarios y seguridad del sistema.',
    ])->middleware('role:'.Role::DIRECCION)->name('direccion.index');

    Route::view('/panel/administracion', 'role-area', [
        'titulo' => 'Área administrativa',
        'descripcion' => 'Espacio reservado para la gestión administrativa autorizada.',
    ])->middleware('role:'.Role::DIRECCION.','.Role::ADMINISTRATIVO)->name('administracion.index');

    Route::view('/panel/docencia', 'role-area', [
        'titulo' => 'Área docente',
        'descripcion' => 'Espacio reservado para las funciones académicas autorizadas.',
    ])->middleware('role:'.Role::DIRECCION.','.Role::DOCENTE)->name('docencia.index');

    Route::view('/panel/familia', 'role-area', [
        'titulo' => 'Área de padres y encargados',
        'descripcion' => 'Consulta de información autorizada para la familia.',
    ])->middleware('role:'.Role::DIRECCION.','.Role::ENCARGADO)->name('familia.index');
});
