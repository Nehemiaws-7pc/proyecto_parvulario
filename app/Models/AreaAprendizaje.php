<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AreaAprendizaje extends Model
{
    use HasFactory;

    protected $table = 'areas_aprendizaje';

    protected $fillable = ['nombre', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function indicadores(): HasMany
    {
        return $this->hasMany(IndicadorEvaluacion::class, 'area_id');
    }
}
