<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const DIRECCION = 'direccion';

    public const ADMINISTRATIVO = 'personal_administrativo';

    public const DOCENTE = 'docente';

    public const ENCARGADO = 'padre_encargado';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }

    public function getEtiquetaAttribute(): string
    {
        return match ($this->nombre) {
            self::DIRECCION => 'Dirección',
            self::ADMINISTRATIVO => 'Personal administrativo',
            self::DOCENTE => 'Docente',
            self::ENCARGADO => 'Padre o encargado',
            default => $this->nombre,
        };
    }
}
