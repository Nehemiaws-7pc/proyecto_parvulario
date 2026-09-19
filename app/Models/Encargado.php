<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Encargado extends Model
{
    use HasFactory;

    protected $fillable = ['usuario_id', 'nombre', 'telefono', 'correo', 'direccion'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(Estudiante::class, 'estudiante_encargado')
            ->withPivot(['parentesco', 'contacto_principal', 'contacto_emergencia', 'autorizado_recoger'])
            ->withTimestamps();
    }
}
