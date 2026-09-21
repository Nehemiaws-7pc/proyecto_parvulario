<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Calificacion extends Model
{
    use HasFactory;

    protected $table = 'calificaciones';

    protected $fillable = [
        'actividad_id',
        'asignacion_id',
        'estudiante_id',
        'escala_id',
        'nota',
        'estado',
        'observacion',
        'calificado_por',
        'calificado_at',
    ];

    protected function casts(): array
    {
        return ['nota' => 'decimal:2', 'calificado_at' => 'datetime'];
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class, 'actividad_id');
    }

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(AsignacionEscolar::class, 'asignacion_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function escala(): BelongsTo
    {
        return $this->belongsTo(EscalaEvaluacion::class, 'escala_id');
    }

    public function calificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calificado_por');
    }
}
