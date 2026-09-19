<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GradoFactory extends Factory
{
    public function definition(): array
    {
        return ['nombre' => fake()->unique()->words(2, true), 'descripcion' => null, 'activo' => true];
    }
}
