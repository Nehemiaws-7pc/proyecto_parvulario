<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupo_docente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('docente_id')->constrained('users')->restrictOnDelete();
            $table->string('tipo', 30);
            $table->boolean('activo')->default(true);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();

            $table->unique(['grupo_id', 'docente_id', 'tipo', 'fecha_inicio'], 'grupo_docente_historial_unique');
            $table->index(['docente_id', 'tipo', 'activo']);
        });

        $now = now();
        DB::table('grupos')->orderBy('id')->each(function (object $group) use ($now): void {
            DB::table('grupo_docente')->insert([
                'grupo_id' => $group->id,
                'docente_id' => $group->docente_id,
                'tipo' => 'titular',
                'activo' => $group->activo,
                'fecha_inicio' => null,
                'fecha_fin' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupo_docente');
    }
};
