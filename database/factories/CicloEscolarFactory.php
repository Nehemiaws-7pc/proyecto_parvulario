<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CicloEscolarFactory extends Factory
{
    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2020, 2100);

        return [
            'anio' => $year,
            'fecha_inicio' => "{$year}-01-15",
            'fecha_fin' => "{$year}-10-31",
            'estado' => 'activo',
        ];
    }
}
