<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CicloEscolar extends Model
{
    use HasFactory;

    protected $table = 'ciclos_escolares';

    protected $fillable = ['anio', 'fecha_inicio', 'fecha_fin', 'estado'];

    protected function casts(): array
    {
        return ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class, 'ciclo_id');
    }

    public function periodos(): HasMany
    {
        return $this->hasMany(Periodo::class, 'ciclo_id');
    }
}
