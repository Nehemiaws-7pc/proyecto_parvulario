<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->restrictOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->restrictOnDelete();
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->string('titulo', 150);
            $table->text('descripcion')->nullable();
            $table->date('fecha');
            $table->string('tipo', 20);
            $table->boolean('publicada')->default(false);
            $table->dateTime('publicada_at')->nullable();
            $table->foreignId('publicada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['grupo_id', 'periodo_id', 'titulo']);
            $table->index(['grupo_id', 'publicada']);
        });

        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades')->cascadeOnDelete();
            $table->foreignId('asignacion_id')->constrained('asignaciones_escolares')->restrictOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->restrictOnDelete();
            $table->foreignId('escala_id')->nullable()->constrained('escalas_evaluacion')->restrictOnDelete();
            $table->decimal('nota', 5, 2)->nullable();
            $table->text('observacion')->nullable();
            $table->foreignId('calificado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['actividad_id', 'estudiante_id']);
            $table->index(['asignacion_id', 'actividad_id']);
        });

        Schema::create('justificaciones_inasistencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asistencia_id')->unique()->constrained('asistencias')->restrictOnDelete();
            $table->foreignId('solicitado_por')->constrained('users')->restrictOnDelete();
            $table->text('motivo');
            $table->string('estado', 20)->default('pendiente');
            $table->text('respuesta')->nullable();
            $table->foreignId('resuelto_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resuelto_at')->nullable();
            $table->timestamps();

            $table->index(['estado', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('justificaciones_inasistencia');
        Schema::dropIfExists('calificaciones');
        Schema::dropIfExists('actividades');
    }
};
