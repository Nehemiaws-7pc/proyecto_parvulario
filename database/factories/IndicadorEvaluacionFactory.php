<?php

namespace Database\Factories;

use App\Models\AreaAprendizaje;
use App\Models\Grado;
use Illuminate\Database\Eloquent\Factories\Factory;

class IndicadorEvaluacionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'area_id' => AreaAprendizaje::factory(),
            'grado_id' => Grado::factory(),
            'nombre' => fake()->unique()->sentence(6),
            'descripcion' => fake()->optional()->sentence(),
            'activo' => true,
        ];
    }
}
