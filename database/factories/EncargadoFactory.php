<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EncargadoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'usuario_id' => null,
            'nombre' => fake()->name(),
            'telefono' => fake()->numerify('5550-####'),
            'correo' => fake()->optional()->safeEmail(),
            'direccion' => fake()->address(),
        ];
    }
}
