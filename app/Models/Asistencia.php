<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asistencia extends Model
{
    use HasFactory;

    public const PRESENTE = 'presente';

    public const AUSENTE = 'ausente';

    public const TARDE = 'tarde';

    public const JUSTIFICADO = 'ausencia_justificada';

    protected $fillable = ['asignacion_id', 'registrado_por', 'fecha', 'estado', 'observacion'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public static function estados(): array
    {
        return [
            self::PRESENTE => 'Presente',
            self::AUSENTE => 'Ausente',
            self::TARDE => 'Tarde',
            self::JUSTIFICADO => 'Ausencia justificada',
        ];
    }

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(AsignacionEscolar::class, 'asignacion_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function justificacion(): HasOne
    {
        return $this->hasOne(JustificacionInasistencia::class, 'asistencia_id');
    }
}
