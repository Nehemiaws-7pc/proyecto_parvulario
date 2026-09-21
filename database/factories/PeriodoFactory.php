<?php

namespace Database\Factories;

use App\Models\CicloEscolar;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeriodoFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('2026-01-15', '2026-06-01');

        return [
            'ciclo_id' => CicloEscolar::factory(),
            'nombre' => 'Período '.fake()->unique()->numberBetween(1, 10000),
            'fecha_inicio' => $start,
            'fecha_fin' => (clone $start)->modify('+2 months'),
            'activo' => true,
        ];
    }
}
