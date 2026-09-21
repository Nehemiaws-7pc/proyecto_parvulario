<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            Role::DIRECCION => 'Administra usuarios, seguridad e información general.',
            Role::ADMINISTRATIVO => 'Gestiona los procesos administrativos autorizados.',
            Role::DOCENTE => 'Accede a las funciones académicas de sus grupos.',
            Role::ENCARGADO => 'Consulta información autorizada de sus estudiantes relacionados.',
        ];

        foreach ($roles as $nombre => $descripcion) {
            Role::firstOrCreate(
                ['nombre' => $nombre],
                ['descripcion' => $descripcion, 'activo' => true],
            );
        }
    }
}
