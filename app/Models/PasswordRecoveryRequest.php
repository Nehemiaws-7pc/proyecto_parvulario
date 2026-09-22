<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordRecoveryRequest extends Model
{
    public const PENDING = 'pendiente';

    public const RESOLVED = 'resuelta';

    protected $fillable = ['usuario_id', 'codigo_usuario', 'nombre', 'telefono', 'estado', 'resuelto_por', 'resuelto_at'];

    protected function casts(): array
    {
        return ['resuelto_at' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function resueltoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }
}
