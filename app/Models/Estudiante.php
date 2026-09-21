<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Estudiante extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'sexo',
        'direccion',
        'informacion_medica',
        'observaciones',
        'estado',
    ];

    protected function casts(): array
    {
        return ['fecha_nacimiento' => 'date'];
    }

    protected function codigo(): Attribute
    {
        return Attribute::make(set: fn (string $value) => Str::upper(trim($value)));
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionEscolar::class, 'estudiante_id');
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(Evaluacion::class, 'estudiante_id');
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(Calificacion::class, 'estudiante_id');
    }

    public function asignacionActual(): HasOne
    {
        return $this->hasOne(AsignacionEscolar::class, 'estudiante_id')
            ->where('estado', 'activa')
            ->latestOfMany('fecha_asignacion');
    }

    public function encargados(): BelongsToMany
    {
        return $this->belongsToMany(Encargado::class, 'estudiante_encargado')
            ->withPivot(['parentesco', 'contacto_principal', 'contacto_emergencia', 'autorizado_recoger'])
            ->withTimestamps();
    }

    public function personasAutorizadas(): HasMany
    {
        return $this->hasMany(PersonaAutorizada::class, 'estudiante_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }
}
