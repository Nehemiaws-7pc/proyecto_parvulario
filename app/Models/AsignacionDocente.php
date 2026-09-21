<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionDocente extends Model
{
    use HasFactory;

    protected $table = 'grupo_docente';

    public const TITULAR = 'titular';

    public const EDUCACION_FISICA = 'educacion_fisica';

    public const EDUCACION_ESPECIAL = 'educacion_especial';

    protected $fillable = [
        'grupo_id',
        'docente_id',
        'tipo',
        'activo',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'fecha_inicio' => 'date', 'fecha_fin' => 'date'];
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
    }
}
