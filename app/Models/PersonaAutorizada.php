<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonaAutorizada extends Model
{
    use HasFactory;

    protected $table = 'personas_autorizadas';

    protected $fillable = ['estudiante_id', 'nombre', 'parentesco', 'telefono', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }
}
