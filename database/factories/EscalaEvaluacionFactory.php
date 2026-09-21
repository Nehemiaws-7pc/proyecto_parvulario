<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EscalaEvaluacionFactory extends Factory
{
    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 10000);

        return [
            'codigo' => "ESC-{$number}",
            'nombre' => "Escala ficticia {$number}",
            'orden' => $number,
            'activo' => true,
        ];
    }
}
