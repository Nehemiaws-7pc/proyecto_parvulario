<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EstudianteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('EST-####'),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'fecha_nacimiento' => fake()->dateTimeBetween('-7 years', '-3 years')->format('Y-m-d'),
            'sexo' => fake()->randomElement(['femenino', 'masculino']),
            'direccion' => fake()->address(),
            'informacion_medica' => null,
            'observaciones' => null,
            'estado' => 'activo',
        ];
    }
}
