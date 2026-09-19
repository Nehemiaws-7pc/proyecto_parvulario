<?php

namespace Database\Factories;

use App\Models\CicloEscolar;
use App\Models\Grado;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GrupoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ciclo_id' => CicloEscolar::factory(),
            'grado_id' => Grado::factory(),
            'seccion_id' => Seccion::factory(),
            'docente_id' => User::factory(),
            'activo' => true,
        ];
    }
}
