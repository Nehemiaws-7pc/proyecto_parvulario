<?php

namespace Database\Seeders;

use App\Models\Actividad;
use App\Models\AreaAprendizaje;
use App\Models\AsignacionDocente;
use App\Models\Asistencia;
use App\Models\AvisoAvance;
use App\Models\Bitacora;
use App\Models\Calificacion;
use App\Models\CicloEscolar;
use App\Models\Encargado;
use App\Models\EscalaEvaluacion;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\IndicadorEvaluacion;
use App\Models\JustificacionInasistencia;
use App\Models\Periodo;
use App\Models\Seccion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class SchoolDataSeeder extends Seeder
{
    public function run(): void
    {
        $cycle = CicloEscolar::firstOrCreate(
            ['anio' => 2026],
            ['fecha_inicio' => '2026-01-15', 'fecha_fin' => '2026-10-31', 'estado' => 'activo'],
        );
        $periods = collect([
            ['nombre' => 'Primer período', 'fecha_inicio' => '2026-01-15', 'fecha_fin' => '2026-05-31'],
            ['nombre' => 'Segundo período', 'fecha_inicio' => '2026-06-01', 'fecha_fin' => '2026-10-31'],
        ])->mapWithKeys(function (array $definition) use ($cycle) {
            $period = Periodo::firstOrCreate(
                ['ciclo_id' => $cycle->id, 'nombre' => $definition['nombre']],
                $definition + ['activo' => true],
            );

            return [$definition['nombre'] => $period];
        });
        $grades = collect(['Preparatoria', 'Párvulos', 'Kinder'])->mapWithKeys(function (string $name) {
            $grade = Grado::firstOrCreate(
                ['nombre' => $name],
                ['descripcion' => "Nivel ficticio {$name} para demostración.", 'activo' => true],
            );

            return [$name => $grade];
        });
        $sections = collect(['A', 'B', 'C'])->mapWithKeys(function (string $name) {
            $section = Seccion::firstOrCreate(
                ['nombre' => $name],
                ['capacidad' => 25, 'activo' => true],
            );

            return [$name => $section];
        });
        $scales = collect([
            ['codigo' => 'LA', 'nombre' => 'Logro alcanzado', 'orden' => 1],
            ['codigo' => 'EP', 'nombre' => 'En proceso', 'orden' => 2],
            ['codigo' => 'NA', 'nombre' => 'Necesita apoyo', 'orden' => 3],
            ['codigo' => 'NE', 'nombre' => 'No evaluado', 'orden' => 4],
        ])->mapWithKeys(function (array $definition) {
            $scale = EscalaEvaluacion::firstOrCreate(
                ['codigo' => $definition['codigo']],
                $definition + ['activo' => true],
            );

            return [$definition['codigo'] => $scale];
        });

        $area = AreaAprendizaje::firstOrCreate(
            ['nombre' => 'Destrezas de aprendizaje'],
            ['descripcion' => 'Área ficticia para demostración.', 'activo' => true],
        );
        foreach ($grades as $grade) {
            IndicadorEvaluacion::firstOrCreate(
                ['area_id' => $area->id, 'grado_id' => $grade->id, 'nombre' => 'Participa en experiencias de aprendizaje'],
                ['descripcion' => 'Indicador ficticio configurable.', 'activo' => true],
            );
        }

        $guardianUser = User::where('codigo_usuario', 'ENC-0001')->firstOrFail();
        $guardian = Encargado::firstOrCreate(
            ['usuario_id' => $guardianUser->id],
            [
                'nombre' => 'Familia Demo Lucero',
                'telefono' => '5550-0301',
                'correo' => null,
                'direccion' => 'Dirección ficticia de demostración',
            ],
        );
        $direction = User::where('codigo_usuario', 'DIR-001')->firstOrFail();
        $secondPeriod = $periods->get('Segundo período');
        $groupDefinitions = [
            ['grade' => 'Preparatoria', 'section' => 'A', 'titular' => 'DOC-001'],
            ['grade' => 'Preparatoria', 'section' => 'B', 'titular' => 'DOC-002'],
            ['grade' => 'Preparatoria', 'section' => 'C', 'titular' => 'DOC-003'],
            ['grade' => 'Párvulos', 'section' => 'A', 'titular' => 'DOC-004'],
            ['grade' => 'Párvulos', 'section' => 'B', 'titular' => 'DOC-005'],
            ['grade' => 'Kinder', 'section' => 'A', 'titular' => 'DOC-004'],
        ];
        $this->migrateLegacyParvulosCToKinder($cycle, $grades, $sections);
        $groupNumber = 0;
        $studentNumber = 0;

        foreach ($groupDefinitions as $definition) {
            $groupNumber++;
            $gradeName = $definition['grade'];
            $sectionName = $definition['section'];
            $grade = $grades->get($gradeName);
            $section = $sections->get($sectionName);
            $teacher = User::where('codigo_usuario', $definition['titular'])->firstOrFail();
            $group = Grupo::firstOrCreate(
                ['ciclo_id' => $cycle->id, 'grado_id' => $grade->id, 'seccion_id' => $section->id],
                ['docente_id' => $teacher->id, 'activo' => true],
            );
            $this->ensureTeachingAssignments($group, $teacher);
            $assignments = $group->asignaciones()->where('estado', 'activa')->orderBy('id')->get();

            for ($position = 1; $position <= 4; $position++) {
                $studentNumber++;
                $assignment = $assignments->get($position - 1);
                if (! $assignment) {
                    $student = Estudiante::firstOrCreate(
                        ['codigo' => sprintf('EST-DEMO-%02d', $studentNumber)],
                        [
                            'nombres' => sprintf('Estudiante Demo %02d', $studentNumber),
                            'apellidos' => "{$gradeName} {$sectionName} Ficticio",
                            'fecha_nacimiento' => CarbonImmutable::create(2020, (($studentNumber - 1) % 12) + 1, (($studentNumber - 1) % 20) + 1),
                            'sexo' => $studentNumber % 2 === 0 ? 'femenino' : 'masculino',
                            'direccion' => 'Dirección ficticia para demostración',
                            'informacion_medica' => 'Sin datos médicos reales.',
                            'observaciones' => 'Registro completamente ficticio.',
                            'estado' => 'activo',
                        ],
                    );
                    $assignment = $student->asignaciones()->firstOrCreate(
                        ['grupo_id' => $group->id],
                        ['fecha_asignacion' => '2026-01-15', 'estado' => 'activa'],
                    );
                    $assignments->push($assignment);
                }
                $student = $assignment->estudiante()->firstOrFail();
                if (! $student->encargados()->whereKey($guardian->id)->exists()) {
                    $student->encargados()->attach($guardian->id, [
                        'parentesco' => 'Encargado de demostración',
                        'contacto_principal' => true,
                        'contacto_emergencia' => true,
                        'autorizado_recoger' => true,
                    ]);
                }

                foreach (['2026-09-16', '2026-09-17', '2026-09-18'] as $dayIndex => $date) {
                    $state = match ($dayIndex) {
                        0 => Asistencia::PRESENTE,
                        1 => $studentNumber === 2
                            ? Asistencia::JUSTIFICADO
                            : ($studentNumber % 3 === 0 ? Asistencia::AUSENTE : Asistencia::TARDE),
                        default => $studentNumber % 4 === 0 ? Asistencia::AUSENTE : Asistencia::PRESENTE,
                    };
                    Asistencia::firstOrCreate(
                        ['asignacion_id' => $assignment->id, 'fecha' => CarbonImmutable::parse($date)],
                        [
                            'registrado_por' => $teacher->id,
                            'estado' => $state,
                            'observacion' => $state === Asistencia::PRESENTE ? null : 'Registro ficticio de demostración.',
                        ],
                    );
                }
            }

            $descriptiveActivity = Actividad::firstOrCreate(
                ['grupo_id' => $group->id, 'periodo_id' => $secondPeriod->id, 'titulo' => "Proyecto descriptivo {$gradeName} {$sectionName}"],
                [
                    'creado_por' => $teacher->id,
                    'descripcion' => 'Actividad descriptiva completamente ficticia.',
                    'fecha' => '2026-09-10',
                    'tipo' => Actividad::DESCRIPTIVA,
                    'publicada' => true,
                    'publicada_at' => '2026-09-19 10:00:00',
                    'publicada_por' => $teacher->id,
                ],
            );
            $numericPublished = $groupNumber % 2 === 0;
            $numericActivity = Actividad::firstOrCreate(
                ['grupo_id' => $group->id, 'periodo_id' => $secondPeriod->id, 'titulo' => "Conteo numérico {$gradeName} {$sectionName}"],
                [
                    'creado_por' => $teacher->id,
                    'descripcion' => 'Actividad numérica completamente ficticia.',
                    'fecha' => '2026-09-12',
                    'tipo' => Actividad::NUMERICA,
                    'publicada' => $numericPublished,
                    'publicada_at' => $numericPublished ? '2026-09-19 11:00:00' : null,
                    'publicada_por' => $numericPublished ? $teacher->id : null,
                ],
            );

            foreach ($assignments->values() as $index => $assignment) {
                $scale = $scales->get(['LA', 'EP', 'NA', 'NE'][$index]);
                Calificacion::firstOrCreate(
                    ['actividad_id' => $descriptiveActivity->id, 'estudiante_id' => $assignment->estudiante_id],
                    [
                        'asignacion_id' => $assignment->id,
                        'escala_id' => $scale->id,
                        'nota' => null,
                        'observacion' => 'Observación descriptiva ficticia.',
                        'calificado_por' => $teacher->id,
                    ],
                );
                Calificacion::firstOrCreate(
                    ['actividad_id' => $numericActivity->id, 'estudiante_id' => $assignment->estudiante_id],
                    [
                        'asignacion_id' => $assignment->id,
                        'escala_id' => null,
                        'nota' => 70 + ($groupNumber * 2) + ($index * 3),
                        'observacion' => 'Observación numérica ficticia.',
                        'calificado_por' => $teacher->id,
                    ],
                );
            }
        }

        foreach ([
            ['student' => 1, 'status' => JustificacionInasistencia::PENDIENTE, 'response' => null],
            ['student' => 2, 'status' => JustificacionInasistencia::ACEPTADA, 'response' => 'Aceptada para la demostración.'],
            ['student' => 3, 'status' => JustificacionInasistencia::RECHAZADA, 'response' => 'Rechazada para la demostración.'],
        ] as $definition) {
            $student = Estudiante::where('codigo', sprintf('EST-DEMO-%02d', $definition['student']))->firstOrFail();
            $attendance = Asistencia::query()
                ->whereHas('asignacion', fn ($query) => $query->where('estudiante_id', $student->id))
                ->whereDate('fecha', '2026-09-17')
                ->firstOrFail();
            $resolved = $definition['status'] !== JustificacionInasistencia::PENDIENTE;
            $justification = JustificacionInasistencia::firstOrCreate(
                ['asistencia_id' => $attendance->id],
                [
                    'solicitado_por' => $guardianUser->id,
                    'motivo' => 'Motivo completamente ficticio para la demostración.',
                    'estado' => $definition['status'],
                    'respuesta' => $definition['response'],
                    'resuelto_por' => $resolved ? $direction->id : null,
                    'resuelto_at' => $resolved ? '2026-09-19 12:00:00' : null,
                ],
            );

            if ($resolved) {
                Bitacora::firstOrCreate(
                    [
                        'usuario_id' => $direction->id,
                        'accion' => 'resolver_justificacion',
                        'modulo' => 'asistencia',
                        'descripcion' => "Justificación demo {$justification->id} resuelta como {$definition['status']}.",
                    ],
                    ['fecha' => '2026-09-19 12:00:00'],
                );
            }
        }
        $this->seedDemoNotices();
    }

    private function seedDemoNotices(): void
    {
        $author = User::where('codigo_usuario', 'DOC-007')->first();
        if (! $author) {
            return;
        }
        foreach (Estudiante::query()->where('codigo', 'like', 'EST-DEMO-%')->take(2)->get() as $student) {
            AvisoAvance::firstOrCreate(
                ['autor_id' => $author->id, 'estudiante_id' => $student->id, 'asunto' => 'Avance ficticio de demostración'],
                ['mensaje' => 'Este aviso es completamente ficticio.', 'fecha_publicacion' => '2026-09-19 13:00:00', 'activo' => true],
            );
        }
    }

    private function migrateLegacyParvulosCToKinder(CicloEscolar $cycle, $grades, $sections): void
    {
        $legacy = Grupo::query()
            ->where('ciclo_id', $cycle->id)
            ->where('grado_id', $grades->get('Párvulos')->id)
            ->where('seccion_id', $sections->get('C')->id)
            ->first();
        $kinder = Grupo::query()
            ->where('ciclo_id', $cycle->id)
            ->where('grado_id', $grades->get('Kinder')->id)
            ->where('seccion_id', $sections->get('A')->id)
            ->first();

        if ($legacy && ! $kinder) {
            $legacy->update(['grado_id' => $grades->get('Kinder')->id]);
        } elseif ($legacy && $kinder && $legacy->docente_id === User::where('codigo_usuario', 'DOC-003')->value('id')) {
            $legacy->update(['activo' => false]);
        }
    }

    private function ensureTeachingAssignments(Grupo $group, User $titular): void
    {
        $legacyTeacherId = $group->docente_id;
        $assignments = [
            [$titular, AsignacionDocente::TITULAR],
            [User::where('codigo_usuario', 'DOC-006')->firstOrFail(), AsignacionDocente::EDUCACION_FISICA],
            [User::where('codigo_usuario', 'DOC-007')->firstOrFail(), AsignacionDocente::EDUCACION_ESPECIAL],
        ];

        foreach ($assignments as [$teacher, $type]) {
            AsignacionDocente::firstOrCreate(
                ['grupo_id' => $group->id, 'docente_id' => $teacher->id, 'tipo' => $type, 'fecha_inicio' => null],
                ['activo' => true],
            );
        }

        if ($legacyTeacherId !== $titular->id && in_array($legacyTeacherId, User::whereIn('codigo_usuario', [
            'DOC-001', 'DOC-002', 'DOC-003', 'DOC-004', 'DOC-005', 'DOC-006',
        ])->pluck('id')->all(), true)) {
            $group->asignacionesDocentes()
                ->where('tipo', AsignacionDocente::TITULAR)
                ->where('docente_id', '!=', $titular->id)
                ->where('activo', true)
                ->update(['activo' => false, 'fecha_fin' => today()]);
            $group->update(['docente_id' => $titular->id]);
        }
    }
}
