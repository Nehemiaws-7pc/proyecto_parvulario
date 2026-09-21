<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\CicloEscolar;
use App\Models\Encargado;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Role;
use App\Models\Seccion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class SchoolDataSeeder extends Seeder
{
    public function run(): void
    {
        $cycle = CicloEscolar::updateOrCreate(
            ['anio' => 2026],
            ['fecha_inicio' => '2026-01-15', 'fecha_fin' => '2026-10-31', 'estado' => 'activo'],
        );
        $grade = Grado::updateOrCreate(
            ['nombre' => 'Preprimaria 5 años'],
            ['descripcion' => 'Nivel ficticio para demostración.', 'activo' => true],
        );
        $section = Seccion::updateOrCreate(['nombre' => 'A'], ['capacidad' => 25, 'activo' => true]);
        $teacher = User::where('codigo_usuario', 'DOC-001')->firstOrFail();
        $group = Grupo::updateOrCreate(
            ['ciclo_id' => $cycle->id, 'grado_id' => $grade->id, 'seccion_id' => $section->id],
            ['docente_id' => $teacher->id, 'activo' => true],
        );

        $student = Estudiante::updateOrCreate(
            ['codigo' => 'EST-0001'],
            [
                'nombres' => 'Sofía',
                'apellidos' => 'Morales Díaz',
                'fecha_nacimiento' => '2021-03-12',
                'sexo' => 'femenino',
                'direccion' => 'Zona ficticia 1, Totonicapán',
                'informacion_medica' => 'Sin información médica registrada.',
                'observaciones' => 'Expediente ficticio para desarrollo.',
                'estado' => 'activo',
            ],
        );
        $assignment = $student->asignaciones()->firstOrCreate(
            ['grupo_id' => $group->id],
            ['fecha_asignacion' => '2026-01-15', 'estado' => 'activa'],
        );
        Asistencia::updateOrCreate(
            ['asignacion_id' => $assignment->id, 'fecha' => CarbonImmutable::parse('2026-09-18')],
            ['registrado_por' => $teacher->id, 'estado' => Asistencia::PRESENTE, 'observacion' => null],
        );

        $parentUser = User::whereHas('role', fn ($query) => $query->where('nombre', Role::ENCARGADO))
            ->where('codigo_usuario', 'ENC-0001')->firstOrFail();
        $guardian = Encargado::updateOrCreate(
            ['usuario_id' => $parentUser->id],
            ['nombre' => 'José Ramírez', 'telefono' => '5550-0104', 'correo' => null, 'direccion' => 'Dirección ficticia'],
        );
        $student->encargados()->syncWithoutDetaching([
            $guardian->id => [
                'parentesco' => 'Padre',
                'contacto_principal' => true,
                'contacto_emergencia' => true,
                'autorizado_recoger' => true,
            ],
        ]);
        $student->personasAutorizadas()->updateOrCreate(
            ['nombre' => 'Elena Pérez'],
            ['parentesco' => 'Tía', 'telefono' => '5550-0199', 'activo' => true],
        );
    }
}
