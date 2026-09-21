<?php

namespace Database\Factories;

use App\Models\Actividad;
use App\Models\AsignacionEscolar;
use App\Models\Calificacion;
use App\Models\EscalaEvaluacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CalificacionFactory extends Factory
{
    protected $model = Calificacion::class;

    public function definition(): array
    {
        return [
            'actividad_id' => Actividad::factory(),
            'asignacion_id' => AsignacionEscolar::factory(),
            'estudiante_id' => fn (array $attributes) => AsignacionEscolar::find($attributes['asignacion_id'])->estudiante_id,
            'escala_id' => EscalaEvaluacion::factory(),
            'nota' => null,
            'observacion' => fake()->optional()->sentence(),
            'calificado_por' => User::factory(),
        ];
    }
}
