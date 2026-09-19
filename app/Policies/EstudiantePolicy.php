<?php

namespace App\Policies;

use App\Models\Estudiante;
use App\Models\Role;
use App\Models\User;

class EstudiantePolicy
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

    public function view(User $user, Estudiante $estudiante): bool
    {
        if ($this->manage($user)) {
            return true;
        }

        if ($user->hasRole(Role::DOCENTE)) {
            return $estudiante->asignaciones()
                ->where('estado', 'activa')
                ->whereHas('grupo', fn ($query) => $query
                    ->where('docente_id', $user->id)
                    ->where('activo', true))
                ->exists();
        }

        if ($user->hasRole(Role::ENCARGADO)) {
            return $estudiante->encargados()
                ->where('usuario_id', $user->id)
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Estudiante $estudiante): bool
    {
        return $this->manage($user);
    }

    private function manage(User $user): bool
    {
        return $user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO]);
    }
}
