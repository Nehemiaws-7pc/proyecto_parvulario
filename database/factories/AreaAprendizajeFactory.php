<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AreaAprendizajeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => 'Área ficticia '.fake()->unique()->numberBetween(1, 10000),
            'descripcion' => fake()->sentence(),
            'activo' => true,
        ];
    }
}
