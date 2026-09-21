<?php

namespace Database\Factories;

use App\Models\Asistencia;
use App\Models\JustificacionInasistencia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JustificacionInasistenciaFactory extends Factory
{
    protected $model = JustificacionInasistencia::class;

    public function definition(): array
    {
        return [
            'asistencia_id' => Asistencia::factory(),
            'solicitado_por' => User::factory(),
            'motivo' => fake()->sentence(),
            'estado' => JustificacionInasistencia::PENDIENTE,
            'respuesta' => null,
            'resuelto_por' => null,
            'resuelto_at' => null,
        ];
    }
}
