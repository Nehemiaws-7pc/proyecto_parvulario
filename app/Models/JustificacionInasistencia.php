<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JustificacionInasistencia extends Model
{
    use HasFactory;

    public const PENDIENTE = 'pendiente';

    public const ACEPTADA = 'aceptada';

    public const RECHAZADA = 'rechazada';

    protected $table = 'justificaciones_inasistencia';

    protected $fillable = [
        'asistencia_id',
        'solicitado_por',
        'motivo',
        'estado',
        'respuesta',
        'resuelto_por',
        'resuelto_at',
    ];

    protected function casts(): array
    {
        return ['resuelto_at' => 'datetime'];
    }

    public static function estados(): array
    {
        return [
            self::PENDIENTE => 'Pendiente',
            self::ACEPTADA => 'Aceptada',
            self::RECHAZADA => 'Rechazada',
        ];
    }

    public function asistencia(): BelongsTo
    {
        return $this->belongsTo(Asistencia::class, 'asistencia_id');
    }

    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function resueltoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }
}
