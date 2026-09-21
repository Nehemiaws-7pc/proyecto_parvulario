<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'rol_id',
        'codigo_usuario',
        'nombre',
        'password',
        'telefono',
        'correo',
        'activo',
        'cambiar_password',
        'ultimo_acceso',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'cambiar_password' => 'boolean',
            'ultimo_acceso' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    public function registrosBitacora(): HasMany
    {
        return $this->hasMany(Bitacora::class, 'usuario_id');
    }

    public function gruposDocente(): HasMany
    {
        return $this->hasMany(Grupo::class, 'docente_id');
    }

    public function gruposAsignados(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'grupo_docente', 'docente_id', 'grupo_id')
            ->withPivot(['tipo', 'activo', 'fecha_inicio', 'fecha_fin'])
            ->withTimestamps();
    }

    public function asignacionesDocentes(): HasMany
    {
        return $this->hasMany(AsignacionDocente::class, 'docente_id');
    }

    public function encargado(): HasOne
    {
        return $this->hasOne(Encargado::class, 'usuario_id');
    }

    public function asistenciasRegistradas(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'registrado_por');
    }

    public function evaluacionesRegistradas(): HasMany
    {
        return $this->hasMany(Evaluacion::class, 'evaluado_por');
    }

    public function evaluacionesPublicadas(): HasMany
    {
        return $this->hasMany(Evaluacion::class, 'publicado_por');
    }

    public function actividadesCreadas(): HasMany
    {
        return $this->hasMany(Actividad::class, 'creado_por');
    }

    public function justificacionesSolicitadas(): HasMany
    {
        return $this->hasMany(JustificacionInasistencia::class, 'solicitado_por');
    }

    /**
     * @param  string|array<int, string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;

        return $this->role !== null
            && $this->role->activo
            && in_array($this->role->nombre, $roles, true);
    }
}
