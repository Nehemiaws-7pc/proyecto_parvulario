<?php

namespace App\Policies;

use App\Models\AvisoAvance;
use App\Models\Role;
use App\Models\User;

class AvisoAvancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO, Role::DOCENTE, Role::ENCARGADO]);
    }

    public function view(User $user, AvisoAvance $aviso): bool
    {
        if ($user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO])) {
            return true;
        }
        if ($user->hasRole(Role::ENCARGADO)) {
            return $aviso->activo && $aviso->estudiante_id !== null
                && $aviso->estudiante?->encargados()->where('usuario_id', $user->id)->exists();
        }
        if ($user->hasRole(Role::DOCENTE)) {
            return $aviso->activo && (($aviso->grupo && $aviso->grupo->tieneDocente($user))
                || ($aviso->estudiante && $aviso->estudiante->asignaciones()->where('estado', 'activa')
                    ->whereHas('grupo', fn ($q) => $q->where('activo', true)->where(function ($g) use ($user) {
                        $g->where('docente_id', $user->id)->orWhereHas('docentes', fn ($t) => $t->whereKey($user->id)->where('grupo_docente.activo', true));
                    }))->exists()));
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO, Role::DOCENTE]);
    }
}
