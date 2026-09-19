<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('accion', 100);
            $table->string('modulo', 50);
            $table->text('descripcion');
            $table->dateTime('fecha');

            $table->index(['usuario_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
    }
};
