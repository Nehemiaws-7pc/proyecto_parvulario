<?php

namespace App\Policies;

use App\Models\Evaluacion;
use App\Models\Role;
use App\Models\User;

class EvaluacionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO, Role::ENCARGADO])
            || ($user->hasRole(Role::DOCENTE) && ! $user->gruposAsignados()->wherePivot('tipo', 'educacion_especial')->wherePivot('activo', true)->exists());
    }

    public function view(User $user, Evaluacion $evaluacion): bool
    {
        if ($user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO])) {
            return true;
        }

        if ($user->hasRole(Role::DOCENTE)) {
            return $evaluacion->asignacion->grupo->activo
                && $evaluacion->asignacion->grupo->tieneDocente($user);
        }

        if ($user->hasRole(Role::ENCARGADO)) {
            return $evaluacion->publicado
                && $evaluacion->asignacion->estudiante->encargados()
                    ->where('usuario_id', $user->id)
                    ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO, Role::DOCENTE]);
    }

    public function update(User $user, Evaluacion $evaluacion): bool
    {
        return $user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO, Role::DOCENTE])
            && $evaluacion->asignacion->grupo->activo
            && ($user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO]) || $evaluacion->asignacion->grupo->tieneDocente($user));
    }

    public function publish(User $user): bool
    {
        return $user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO, Role::DOCENTE]);
    }

    public function configure(User $user): bool
    {
        return $user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO]);
    }
}
