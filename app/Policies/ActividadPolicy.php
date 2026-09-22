<?php

namespace App\Policies;

use App\Models\Actividad;
use App\Models\Role;
use App\Models\User;

class ActividadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO, Role::ENCARGADO])
            || ($user->hasRole(Role::DOCENTE) && ! $user->gruposAsignados()->wherePivot('tipo', 'educacion_especial')->wherePivot('activo', true)->exists());
    }

    public function view(User $user, Actividad $actividad): bool
    {
        if ($user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO])) {
            return true;
        }

        if ($user->hasRole(Role::DOCENTE)) {
            return $actividad->grupo->activo && $actividad->grupo->tieneDocente($user)
                && ($actividad->tipo_docente === null || $actividad->tipo_docente === $user->asignacionesDocentes()
                    ->where('grupo_id', $actividad->grupo_id)->where('activo', true)->value('tipo'));
        }

        return $user->hasRole(Role::ENCARGADO)
            && $actividad->publicada
            && $actividad->calificaciones()
                ->where(function ($state) {
                    $state->where('estado', 'calificada')->orWhere(function ($legacy) {
                        $legacy->where(fn ($value) => $value->whereNotNull('nota')->orWhereNotNull('escala_id'));
                    });
                })
                ->whereHas('estudiante.encargados', fn ($query) => $query->where('usuario_id', $user->id))
                ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO, Role::DOCENTE]);
    }

    public function update(User $user, Actividad $actividad): bool
    {
        return $user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO, Role::DOCENTE])
            && $actividad->grupo->activo
            && ($user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO]) || ($actividad->grupo->tieneDocente($user)
                && ($actividad->tipo_docente === null || $actividad->tipo_docente === $user->asignacionesDocentes()
                    ->where('grupo_id', $actividad->grupo_id)->where('activo', true)->value('tipo'))));
    }
}
