<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ciclos_escolares', function (Blueprint $table) {
            $table->id();
            $table->year('anio')->unique();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('estado', 20)->default('planificado');
            $table->timestamps();
        });

        Schema::create('grados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('secciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 30)->unique();
            $table->unsignedInteger('capacidad')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ciclo_id')->constrained('ciclos_escolares')->restrictOnDelete();
            $table->foreignId('grado_id')->constrained('grados')->restrictOnDelete();
            $table->foreignId('seccion_id')->constrained('secciones')->restrictOnDelete();
            $table->foreignId('docente_id')->constrained('users')->restrictOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['ciclo_id', 'grado_id', 'seccion_id']);
        });

        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->date('fecha_nacimiento');
            $table->string('sexo', 20)->nullable();
            $table->text('direccion')->nullable();
            $table->text('informacion_medica')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
        });

        Schema::create('encargados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('nombre', 150);
            $table->string('telefono', 20);
            $table->string('correo', 150)->nullable();
            $table->text('direccion')->nullable();
            $table->timestamps();
        });

        Schema::create('estudiante_encargado', function (Blueprint $table) {
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('encargado_id')->constrained('encargados')->restrictOnDelete();
            $table->string('parentesco', 50);
            $table->boolean('contacto_principal')->default(false);
            $table->boolean('contacto_emergencia')->default(false);
            $table->boolean('autorizado_recoger')->default(false);
            $table->timestamps();

            $table->primary(['estudiante_id', 'encargado_id']);
        });

        Schema::create('personas_autorizadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->string('nombre', 150);
            $table->string('parentesco', 50);
            $table->string('telefono', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('asignaciones_escolares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->restrictOnDelete();
            $table->foreignId('grupo_id')->constrained('grupos')->restrictOnDelete();
            $table->date('fecha_asignacion');
            $table->string('estado', 20)->default('activa');
            $table->timestamps();

            $table->index(['estudiante_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_escolares');
        Schema::dropIfExists('personas_autorizadas');
        Schema::dropIfExists('estudiante_encargado');
        Schema::dropIfExists('encargados');
        Schema::dropIfExists('estudiantes');
        Schema::dropIfExists('grupos');
        Schema::dropIfExists('secciones');
        Schema::dropIfExists('grados');
        Schema::dropIfExists('ciclos_escolares');
    }
};
