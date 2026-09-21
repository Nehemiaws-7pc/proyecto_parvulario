<?php

namespace Database\Factories;

use App\Models\Actividad;
use App\Models\Grupo;
use App\Models\Periodo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActividadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'grupo_id' => Grupo::factory(),
            'periodo_id' => Periodo::factory(),
            'creado_por' => User::factory(),
            'titulo' => fake()->unique()->sentence(3),
            'descripcion' => fake()->sentence(),
            'fecha' => today(),
            'tipo' => Actividad::DESCRIPTIVA,
            'publicada' => false,
            'publicada_at' => null,
            'publicada_por' => null,
        ];
    }
}
