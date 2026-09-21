<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    use HasFactory;

    protected $fillable = ['ciclo_id', 'grado_id', 'seccion_id', 'docente_id', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_id');
    }

    public function grado(): BelongsTo
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class, 'seccion_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'grupo_docente', 'grupo_id', 'docente_id')
            ->withPivot(['tipo', 'activo', 'fecha_inicio', 'fecha_fin'])
            ->withTimestamps();
    }

    public function asignacionesDocentes(): HasMany
    {
        return $this->hasMany(AsignacionDocente::class, 'grupo_id');
    }

    public function tieneDocente(User|int $docente, ?string $tipo = null): bool
    {
        $docenteId = $docente instanceof User ? $docente->getKey() : $docente;

        return $this->docentes()
            ->whereKey($docenteId)
            ->wherePivot('activo', true)
            ->when($tipo, fn ($query) => $query->wherePivot('tipo', $tipo))
            ->exists()
            || ($tipo === null && (int) $this->docente_id === (int) $docenteId);
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionEscolar::class, 'grupo_id');
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(Actividad::class, 'grupo_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->ciclo->anio} · {$this->grado->nombre} · Sección {$this->seccion->nombre}";
    }
}
