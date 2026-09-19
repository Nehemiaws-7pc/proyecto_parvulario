<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $usuarios = [
            [Role::DIRECCION, 'DIR-001', 'Ana López', '5550-0101'],
            [Role::ADMINISTRATIVO, 'ADM-001', 'Carlos Méndez', '5550-0102'],
            [Role::DOCENTE, 'DOC-001', 'María García', '5550-0103'],
            [Role::ENCARGADO, 'ENC-0001', 'José Ramírez', '5550-0104'],
        ];

        foreach ($usuarios as [$rol, $codigo, $nombre, $telefono]) {
            User::updateOrCreate(
                ['codigo_usuario' => $codigo],
                [
                    'rol_id' => Role::where('nombre', $rol)->value('id'),
                    'nombre' => $nombre,
                    'password' => 'Demo1234!',
                    'telefono' => $telefono,
                    'correo' => null,
                    'activo' => true,
                    'cambiar_password' => false,
                ],
            );
        }

        $this->call(SchoolDataSeeder::class);
    }
}
