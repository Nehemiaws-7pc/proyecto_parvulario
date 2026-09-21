<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvisoAvance extends Model
{
    protected $table = 'avisos_avance';

    protected $fillable = [
        'autor_id', 'estudiante_id', 'grupo_id', 'asunto', 'mensaje',
        'fecha_publicacion', 'activo', 'consultado_at',
    ];

    protected function casts(): array
    {
        return ['fecha_publicacion' => 'datetime', 'consultado_at' => 'datetime', 'activo' => 'boolean'];
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }
}
