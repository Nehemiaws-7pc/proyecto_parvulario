<?php

namespace App\Policies;

use App\Models\Actividad;
use App\Models\Role;
use App\Models\User;

class ActividadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO, Role::DOCENTE, Role::ENCARGADO]);
    }

    public function view(User $user, Actividad $actividad): bool
    {
        if ($user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO])) {
            return true;
        }

        if ($user->hasRole(Role::DOCENTE)) {
            return $actividad->grupo->activo && $actividad->grupo->tieneDocente($user);
        }

        return $user->hasRole(Role::ENCARGADO)
            && $actividad->publicada
            && $actividad->calificaciones()
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
            && ($user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO]) || $actividad->grupo->tieneDocente($user));
    }
}
