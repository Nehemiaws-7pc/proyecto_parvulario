<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->string('area_aprendizaje', 120)->nullable()->after('descripcion');
            $table->decimal('punteo_maximo', 6, 2)->default(100)->after('fecha');
            $table->string('tipo_docente', 30)->nullable()->after('tipo');
        });
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->string('estado', 20)->default('pendiente')->after('nota');
            $table->dateTime('calificado_at')->nullable()->after('calificado_por');
        });
        DB::table('calificaciones')->where(function ($q) {
            $q->whereNotNull('nota')->orWhereNotNull('escala_id');
        })->update(['estado' => 'calificada']);
    }

    public function down(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropColumn(['estado', 'calificado_at']);
        });
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropColumn(['area_aprendizaje', 'punteo_maximo', 'tipo_docente']);
        });
    }
};
