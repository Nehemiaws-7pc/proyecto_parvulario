<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bitacora extends Model
{
    protected $table = 'bitacora';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'accion',
        'modulo',
        'descripcion',
        'fecha',
    ];

    protected function casts(): array
    {
        return ['fecha' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
