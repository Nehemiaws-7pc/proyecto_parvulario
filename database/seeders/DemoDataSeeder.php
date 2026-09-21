<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('app.demo_data_enabled')) {
            return;
        }

        $password = config('app.demo_user_password');

        if (! is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('DEMO_USER_PASSWORD must contain at least 12 characters.');
        }

        $users = [
            [Role::DIRECCION, 'DIR-001', 'Ana López', '5550-0101'],
            [Role::ADMINISTRATIVO, 'ADM-001', 'Carlos Méndez', '5550-0102'],
            [Role::DOCENTE, 'DOC-001', 'María García', '5550-0103'],
            [Role::ENCARGADO, 'ENC-0001', 'José Ramírez', '5550-0104'],
        ];

        foreach ($users as [$role, $code, $name, $phone]) {
            User::updateOrCreate(
                ['codigo_usuario' => $code],
                [
                    'rol_id' => Role::where('nombre', $role)->value('id'),
                    'nombre' => $name,
                    'password' => $password,
                    'telefono' => $phone,
                    'correo' => null,
                    'activo' => true,
                    'cambiar_password' => false,
                ],
            );
        }

        $this->call(SchoolDataSeeder::class);
    }
}
