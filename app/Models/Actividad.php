<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Actividad extends Model
{
    use HasFactory;

    protected $table = 'actividades';

    public const DESCRIPTIVA = 'descriptiva';

    public const NUMERICA = 'numerica';

    protected $fillable = [
        'grupo_id',
        'periodo_id',
        'creado_por',
        'titulo',
        'descripcion',
        'area_aprendizaje',
        'fecha',
        'punteo_maximo',
        'tipo',
        'tipo_docente',
        'publicada',
        'publicada_at',
        'publicada_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'publicada' => 'boolean',
            'publicada_at' => 'datetime',
            'punteo_maximo' => 'decimal:2',
        ];
    }

    public static function tipos(): array
    {
        return [
            self::DESCRIPTIVA => 'Descriptiva',
            self::NUMERICA => 'Numérica',
        ];
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function publicadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publicada_por');
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(Calificacion::class, 'actividad_id');
    }
}
