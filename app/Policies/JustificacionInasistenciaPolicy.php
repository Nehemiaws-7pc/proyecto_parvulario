<?php

namespace App\Policies;

use App\Models\JustificacionInasistencia;
use App\Models\Role;
use App\Models\User;

class JustificacionInasistenciaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole([Role::DIRECCION, Role::DOCENTE, Role::ENCARGADO]);
    }

    public function view(User $user, JustificacionInasistencia $justificacion): bool
    {
        if ($user->hasRole(Role::DIRECCION)) {
            return true;
        }

        if ($user->hasRole(Role::DOCENTE)) {
            return $justificacion->asistencia->asignacion->grupo->docente_id === $user->id;
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
        return $user->hasRole(Role::DIRECCION);
    }
}
