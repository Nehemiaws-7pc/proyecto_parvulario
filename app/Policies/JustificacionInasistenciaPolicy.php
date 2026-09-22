<?php

namespace App\Policies;

use App\Models\JustificacionInasistencia;
use App\Models\Role;
use App\Models\User;

class JustificacionInasistenciaPolicy
{
    public function viewAny(User $user): bool
    {
        return (new AsistenciaPolicy)->viewAny($user);
    }

    public function view(User $user, JustificacionInasistencia $justificacion): bool
    {
        if ($user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO])) {
            return true;
        }

        if ($user->hasRole(Role::DOCENTE)) {
            return $this->viewAny($user) && $justificacion->asistencia->asignacion->grupo->tieneDocente($user);
        }

        return $user->hasRole(Role::ENCARGADO)
            && $justificacion->solicitado_por === $user->id
            && $justificacion->asistencia->asignacion->estudiante->encargados()
                ->where('usuario_id', $user->id)
                ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::ENCARGADO);
    }

    public function resolve(User $user): bool
    {
        return $user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO]);
    }
}
