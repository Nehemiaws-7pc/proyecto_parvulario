<?php

namespace App\Policies;

use App\Models\Evaluacion;
use App\Models\Role;
use App\Models\User;

class EvaluacionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole([
            Role::DIRECCION,
            Role::ADMINISTRATIVO,
            Role::DOCENTE,
            Role::ENCARGADO,
        ]);
    }

    public function view(User $user, Evaluacion $evaluacion): bool
    {
        if ($user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO])) {
            return true;
        }

        if ($user->hasRole(Role::DOCENTE)) {
            return $evaluacion->asignacion->grupo->activo
                && $evaluacion->asignacion->grupo->docente_id === $user->id;
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
        return $user->hasRole(Role::DOCENTE);
    }

    public function update(User $user, Evaluacion $evaluacion): bool
    {
        return $user->hasRole(Role::DOCENTE)
            && $evaluacion->asignacion->grupo->activo
            && $evaluacion->asignacion->grupo->docente_id === $user->id;
    }

    public function publish(User $user): bool
    {
        return $user->hasRole(Role::DOCENTE);
    }

    public function configure(User $user): bool
    {
        return $user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO]);
    }
}
