<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ciclo_id')->constrained('ciclos_escolares')->restrictOnDelete();
            $table->string('nombre', 80);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['ciclo_id', 'nombre']);
        });

        Schema::create('areas_aprendizaje', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('indicadores_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas_aprendizaje')->restrictOnDelete();
            $table->foreignId('grado_id')->constrained('grados')->restrictOnDelete();
            $table->string('nombre', 180);
            $table->string('descripcion', 500)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['area_id', 'grado_id', 'nombre']);
        });

        Schema::create('escalas_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 80)->unique();
            $table->unsignedSmallInteger('orden')->default(1);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_id')->constrained('asignaciones_escolares')->restrictOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->restrictOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->restrictOnDelete();
            $table->foreignId('indicador_id')->constrained('indicadores_evaluacion')->restrictOnDelete();
            $table->foreignId('escala_id')->constrained('escalas_evaluacion')->restrictOnDelete();
            $table->foreignId('evaluado_por')->constrained('users')->restrictOnDelete();
            $table->text('observacion')->nullable();
            $table->date('fecha');
            $table->boolean('publicado')->default(false);
            $table->dateTime('publicado_at')->nullable();
            $table->foreignId('publicado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['estudiante_id', 'periodo_id', 'indicador_id']);
            $table->index(['periodo_id', 'indicador_id', 'publicado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones');
        Schema::dropIfExists('escalas_evaluacion');
        Schema::dropIfExists('indicadores_evaluacion');
        Schema::dropIfExists('areas_aprendizaje');
        Schema::dropIfExists('periodos');
    }
};
