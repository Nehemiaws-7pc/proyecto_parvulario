<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EscalaEvaluacion extends Model
{
    use HasFactory;

    protected $table = 'escalas_evaluacion';

    protected $fillable = ['codigo', 'nombre', 'orden', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function setCodigoAttribute(string $value): void
    {
        $this->attributes['codigo'] = Str::upper(trim($value));
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(Evaluacion::class, 'escala_id');
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(Calificacion::class, 'escala_id');
    }
}
