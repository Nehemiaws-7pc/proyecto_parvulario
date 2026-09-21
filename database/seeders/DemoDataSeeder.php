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

        if (! is_string($password) || strlen($password) < 8) {
            throw new RuntimeException('DEMO_USER_PASSWORD must contain at least 8 characters.');
        }

        $users = [
            [Role::DIRECCION, 'DIR-001', 'Dirección Demo Arcoíris', '5550-0101'],
            [Role::DOCENTE, 'DOC-001', 'Docente Demo Aurora', '5550-0201'],
            [Role::DOCENTE, 'DOC-002', 'Docente Demo Brisa', '5550-0202'],
            [Role::DOCENTE, 'DOC-003', 'Docente Demo Cielo', '5550-0203'],
            [Role::DOCENTE, 'DOC-004', 'Docente Demo Dalia', '5550-0204'],
            [Role::DOCENTE, 'DOC-005', 'Docente Demo Estrella', '5550-0205'],
            [Role::DOCENTE, 'DOC-006', 'Docente Demo Fantasía', '5550-0206'],
            [Role::ENCARGADO, 'ENC-0001', 'Familia Demo Lucero', '5550-0301'],
        ];

        foreach ($users as [$role, $code, $name, $phone]) {
            User::firstOrCreate(
                ['codigo_usuario' => $code],
                [
                    'rol_id' => Role::where('nombre', $role)->value('id'),
                    'nombre' => $name,
                    'password' => $password,
                    'telefono' => $phone,
                    'correo' => null,
                    'activo' => true,
                    'cambiar_password' => true,
                ],
            );
        }

        $this->call(SchoolDataSeeder::class);
    }
}
