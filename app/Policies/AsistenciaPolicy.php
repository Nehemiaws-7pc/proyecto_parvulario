<?php

namespace App\Policies;

use App\Models\Asistencia;
use App\Models\Role;
use App\Models\User;

class AsistenciaPolicy
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

    public function view(User $user, Asistencia $attendance): bool
    {
        if ($user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO])) {
            return true;
        }

        if ($user->hasRole(Role::DOCENTE)) {
            return $attendance->asignacion->grupo->tieneDocente($user);
        }

        if ($user->hasRole(Role::ENCARGADO)) {
            return $attendance->asignacion->estudiante->encargados()
                ->where('usuario_id', $user->id)
                ->exists();
        }

        return false;
    }

    public function update(User $user, Asistencia $attendance): bool
    {
        return $user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO]);
    }
}
