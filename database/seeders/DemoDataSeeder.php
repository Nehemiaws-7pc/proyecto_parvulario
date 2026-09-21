<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('demo.enabled')) {
            return;
        }

        $password = config('demo.user_password');
        $resetPasswords = (bool) config('demo.reset_passwords');

        if (! is_string($password) || strlen($password) < 8) {
            throw new RuntimeException('DEMO_USER_PASSWORD must contain at least 8 characters.');
        }

        $hashedPassword = Hash::make($password);

        $users = [
            [Role::DIRECCION, 'DIR-001', 'Carlota', '5550-0101', ['Dirección Demo Arcoíris']],
            [Role::ADMINISTRATIVO, 'ADM-001', 'Rosa Pérez', '5550-0102', []],
            [Role::DOCENTE, 'DOC-001', 'Sandra', '5550-0201', ['Docente Demo Aurora']],
            [Role::DOCENTE, 'DOC-002', 'Miriam', '5550-0202', ['Docente Demo Brisa']],
            [Role::DOCENTE, 'DOC-003', 'Yolanda', '5550-0203', ['Docente Demo Cielo']],
            [Role::DOCENTE, 'DOC-004', 'Blanca', '5550-0204', ['Docente Demo Dalia']],
            [Role::DOCENTE, 'DOC-005', 'Reyna', '5550-0205', ['Docente Demo Estrella']],
            [Role::DOCENTE, 'DOC-006', 'Carmen', '5550-0206', ['Docente Demo Fantasía']],
            [Role::DOCENTE, 'DOC-007', 'Josefa Patricia', '5550-0207', []],
            [Role::ENCARGADO, 'ENC-0001', 'Marta López', '5550-0301', ['Familia Demo Lucero']],
        ];
        $demoCodes = array_column($users, 1);

        foreach ($users as [$role, $code, $name, $phone, $legacyNames]) {
            $user = User::firstOrCreate(
                ['codigo_usuario' => $code],
                [
                    'rol_id' => Role::where('nombre', $role)->value('id'),
                    'nombre' => $name,
                    'password' => $hashedPassword,
                    'telefono' => $phone,
                    'correo' => null,
                    'activo' => true,
                    'cambiar_password' => true,
                ],
            );

            if ($legacyNames !== [] && in_array($user->nombre, $legacyNames, true)) {
                $user->update(['nombre' => $name]);
            }

            if ($resetPasswords && in_array($code, $demoCodes, true)) {
                $user->forceFill([
                    'password' => $hashedPassword,
                    'activo' => true,
                    'cambiar_password' => false,
                ])->save();
            }
        }

        $this->call(SchoolDataSeeder::class);
    }
}
