<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluacion extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones';

    protected $fillable = [
        'asignacion_id',
        'estudiante_id',
        'periodo_id',
        'indicador_id',
        'escala_id',
        'evaluado_por',
        'observacion',
        'fecha',
        'publicado',
        'publicado_at',
        'publicado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'publicado' => 'boolean',
            'publicado_at' => 'datetime',
        ];
    }

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(AsignacionEscolar::class, 'asignacion_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(IndicadorEvaluacion::class, 'indicador_id');
    }

    public function escala(): BelongsTo
    {
        return $this->belongsTo(EscalaEvaluacion::class, 'escala_id');
    }

    public function evaluadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluado_por');
    }

    public function publicadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publicado_por');
    }
}
