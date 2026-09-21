<?php

namespace Database\Factories;

use App\Models\AsignacionEscolar;
use App\Models\EscalaEvaluacion;
use App\Models\IndicadorEvaluacion;
use App\Models\Periodo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluacionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'asignacion_id' => AsignacionEscolar::factory(),
            'estudiante_id' => fn (array $attributes) => AsignacionEscolar::findOrFail($attributes['asignacion_id'])->estudiante_id,
            'periodo_id' => Periodo::factory(),
            'indicador_id' => IndicadorEvaluacion::factory(),
            'escala_id' => EscalaEvaluacion::factory(),
            'evaluado_por' => User::factory(),
            'observacion' => fake()->optional()->sentence(),
            'fecha' => today(),
            'publicado' => false,
            'publicado_at' => null,
            'publicado_por' => null,
        ];
    }
}
