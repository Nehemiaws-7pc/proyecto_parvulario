<?php

use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EncargadoController;
use App\Http\Controllers\EstructuraEscolarController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\PersonaAutorizadaController;
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
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/panel', fn () => view('dashboard'))->name('dashboard');
    Route::post('/cerrar-sesion', [AuthController::class, 'destroy'])->name('logout');

    Route::resource('estudiantes', EstudianteController::class)->except('destroy');

    Route::get('asistencia', [AsistenciaController::class, 'index'])->name('asistencia.index');
    Route::post('asistencia', [AsistenciaController::class, 'store'])->name('asistencia.store');
    Route::get('asistencia/resumen-mensual', [AsistenciaController::class, 'monthly'])->name('asistencia.monthly');
    Route::get('asistencia/{asistencia}/editar', [AsistenciaController::class, 'edit'])->name('asistencia.edit');
    Route::put('asistencia/{asistencia}', [AsistenciaController::class, 'update'])->name('asistencia.update');
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
