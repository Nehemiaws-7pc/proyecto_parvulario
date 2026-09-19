<?php

namespace Database\Factories;

use App\Models\AsignacionEscolar;
use App\Models\Asistencia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AsistenciaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'asignacion_id' => AsignacionEscolar::factory(),
            'registrado_por' => User::factory(),
            'fecha' => today(),
            'estado' => Asistencia::PRESENTE,
            'observacion' => null,
        ];
    }
}
