<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsignacionEscolar extends Model
{
    use HasFactory;

    protected $table = 'asignaciones_escolares';

    protected $fillable = ['estudiante_id', 'grupo_id', 'fecha_asignacion', 'estado'];

    protected function casts(): array
    {
        return ['fecha_asignacion' => 'date'];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'asignacion_id');
    }
}
