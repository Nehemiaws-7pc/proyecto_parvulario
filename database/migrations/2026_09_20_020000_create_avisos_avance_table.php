<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avisos_avance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('autor_id')->constrained('users');
            $table->foreignId('estudiante_id')->nullable()->constrained('estudiantes')->nullOnDelete();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();
            $table->string('asunto', 180);
            $table->text('mensaje');
            $table->timestamp('fecha_publicacion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('consultado_at')->nullable();
            $table->timestamps();
            $table->index(['activo', 'fecha_publicacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos_avance');
    }
};
