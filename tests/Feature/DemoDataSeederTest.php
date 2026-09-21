<?php

namespace Tests\Feature;

use App\Models\AsignacionDocente;
use App\Models\Asistencia;
use App\Models\Calificacion;
use App\Models\Encargado;
use App\Models\JustificacionInasistencia;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_is_not_loaded_when_it_is_disabled(): void
    {
        Config::set('demo.enabled', false);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('estudiantes', 0);
    }

    public function test_demo_data_is_idempotent_when_it_is_enabled(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.user_password', Str::password(16));

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 10);
        $this->assertSame(7, User::whereHas('role', fn ($query) => $query->where('nombre', Role::DOCENTE))->count());
        $this->assertSame(10, User::where('cambiar_password', true)->count());
        $this->assertSame([
            'ADM-001',
            'DIR-001',
            'DOC-001',
            'DOC-002',
            'DOC-003',
            'DOC-004',
            'DOC-005',
            'DOC-006',
            'DOC-007',
            'ENC-0001',
        ], User::orderBy('codigo_usuario')->pluck('codigo_usuario')->sort()->values()->all());
        $this->assertDatabaseCount('grados', 3);
        $this->assertDatabaseCount('secciones', 3);
        $this->assertDatabaseCount('grupos', 6);
        $this->assertDatabaseCount('grupo_docente', 18);
        $this->assertDatabaseCount('estudiantes', 24);
        $this->assertSame(24, app('db')->table('estudiantes')->where('estado', 'activo')->count());
        $this->assertDatabaseCount('asignaciones_escolares', 24);
        $this->assertDatabaseCount('estudiante_encargado', 24);
        $this->assertDatabaseCount('actividades', 12);
        $this->assertDatabaseCount('calificaciones', 48);
        $this->assertDatabaseCount('asistencias', 72);
        $this->assertDatabaseCount('justificaciones_inasistencia', 3);
        $this->assertDatabaseCount('bitacora', 2);
        $this->assertDatabaseCount('avisos_avance', 2);

        foreach (range(1, 5) as $number) {
            $teacher = User::where('codigo_usuario', sprintf('DOC-%03d', $number))->firstOrFail();
            $this->assertGreaterThanOrEqual(1, AsignacionDocente::where('docente_id', $teacher->id)
                ->where('tipo', AsignacionDocente::TITULAR)->where('activo', true)->count());
        }
        $blanca = User::where('codigo_usuario', 'DOC-004')->firstOrFail();
        $this->assertSame(2, AsignacionDocente::where('docente_id', $blanca->id)
            ->where('tipo', AsignacionDocente::TITULAR)->where('activo', true)->count());
        foreach (['DOC-006' => AsignacionDocente::EDUCACION_FISICA, 'DOC-007' => AsignacionDocente::EDUCACION_ESPECIAL] as $code => $type) {
            $teacher = User::where('codigo_usuario', $code)->firstOrFail();
            $this->assertSame(6, AsignacionDocente::where('docente_id', $teacher->id)
                ->where('tipo', $type)->where('activo', true)->count());
        }

        $guardian = Encargado::whereHas('usuario', fn ($query) => $query->where('codigo_usuario', 'ENC-0001'))->firstOrFail();
        $this->assertSame(24, $guardian->estudiantes()->count());

        $counts = [
            'users' => $this->countTable('users'),
            'grados' => $this->countTable('grados'),
            'secciones' => $this->countTable('secciones'),
            'grupos' => $this->countTable('grupos'),
            'grupo_docente' => $this->countTable('grupo_docente'),
            'estudiantes' => $this->countTable('estudiantes'),
            'asignaciones_escolares' => $this->countTable('asignaciones_escolares'),
            'asistencias' => $this->countTable('asistencias'),
            'actividades' => $this->countTable('actividades'),
            'calificaciones' => $this->countTable('calificaciones'),
            'justificaciones_inasistencia' => $this->countTable('justificaciones_inasistencia'),
            'bitacora' => $this->countTable('bitacora'),
            'avisos_avance' => $this->countTable('avisos_avance'),
        ];

        $this->seed(DatabaseSeeder::class);

        foreach ($counts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
    }

    public function test_second_forced_seed_preserves_user_changes_and_only_restores_missing_demo_records(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.user_password', Str::password(16));
        $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);

        $teacher = User::where('codigo_usuario', 'DOC-001')->firstOrFail();
        $newPassword = Str::password(20);
        $teacher->update([
            'password' => $newPassword,
            'cambiar_password' => false,
        ]);
        $changedPasswordHash = $teacher->getRawOriginal('password');

        $changedGrade = Calificacion::whereNotNull('nota')->firstOrFail();
        $changedGrade->update([
            'nota' => 99.50,
            'observacion' => 'Calificación modificada por un usuario.',
        ]);

        $changedAttendance = Asistencia::query()
            ->where('estado', Asistencia::TARDE)
            ->whereDoesntHave('justificacion')
            ->firstOrFail();
        $changedAttendance->update([
            'estado' => Asistencia::PRESENTE,
            'observacion' => 'Asistencia corregida por un usuario.',
        ]);

        $changedJustification = JustificacionInasistencia::where('estado', JustificacionInasistencia::PENDIENTE)
            ->firstOrFail();
        $changedJustification->update([
            'motivo' => 'Motivo modificado por un usuario.',
            'estado' => JustificacionInasistencia::RECHAZADA,
            'respuesta' => 'Resolución modificada por un usuario.',
        ]);

        $assignment = $changedAttendance->asignacion()->with('grupo')->firstOrFail();
        $guardian = User::where('codigo_usuario', 'ENC-0001')->firstOrFail();
        $userAttendance = Asistencia::create([
            'asignacion_id' => $assignment->id,
            'registrado_por' => $assignment->grupo->docente_id,
            'fecha' => '2026-09-21',
            'estado' => Asistencia::AUSENTE,
            'observacion' => 'Asistencia creada por un usuario.',
        ]);
        $userJustification = JustificacionInasistencia::create([
            'asistencia_id' => $userAttendance->id,
            'solicitado_por' => $guardian->id,
            'motivo' => 'Justificación creada por un usuario.',
            'estado' => JustificacionInasistencia::PENDIENTE,
        ]);

        $missingGrade = Calificacion::whereKeyNot($changedGrade->id)->firstOrFail();
        $missingGradeIdentity = [
            'actividad_id' => $missingGrade->actividad_id,
            'estudiante_id' => $missingGrade->estudiante_id,
        ];
        $missingGrade->delete();

        Config::set('demo.user_password', Str::password(24));
        $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);

        $teacher->refresh();
        $this->assertSame($changedPasswordHash, $teacher->getRawOriginal('password'));
        $this->assertTrue(Hash::check($newPassword, $teacher->password));
        $this->assertFalse($teacher->cambiar_password);

        $changedGrade->refresh();
        $this->assertSame('99.50', $changedGrade->nota);
        $this->assertSame('Calificación modificada por un usuario.', $changedGrade->observacion);

        $changedAttendance->refresh();
        $this->assertSame(Asistencia::PRESENTE, $changedAttendance->estado);
        $this->assertSame('Asistencia corregida por un usuario.', $changedAttendance->observacion);

        $changedJustification->refresh();
        $this->assertSame(JustificacionInasistencia::RECHAZADA, $changedJustification->estado);
        $this->assertSame('Motivo modificado por un usuario.', $changedJustification->motivo);
        $this->assertSame('Resolución modificada por un usuario.', $changedJustification->respuesta);

        $this->assertDatabaseHas('asistencias', [
            'id' => $userAttendance->id,
            'estado' => Asistencia::AUSENTE,
            'observacion' => 'Asistencia creada por un usuario.',
        ]);
        $this->assertDatabaseHas('justificaciones_inasistencia', [
            'id' => $userJustification->id,
            'motivo' => 'Justificación creada por un usuario.',
            'estado' => JustificacionInasistencia::PENDIENTE,
        ]);
        $this->assertDatabaseHas('calificaciones', $missingGradeIdentity);
        $this->assertDatabaseCount('calificaciones', 48);
        $this->assertDatabaseCount('asistencias', 73);
        $this->assertDatabaseCount('justificaciones_inasistencia', 4);
    }

    public function test_password_reset_is_explicit_and_limited_to_the_eight_demo_accounts(): void
    {
        $demoCodes = [
            'DIR-001',
            'DOC-001',
            'DOC-002',
            'DOC-003',
            'DOC-004',
            'DOC-005',
            'DOC-006',
            'ENC-0001',
        ];
        Config::set('demo.enabled', true);
        Config::set('demo.user_password', Str::password(16));
        Config::set('demo.reset_passwords', false);
        $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);

        foreach (User::whereIn('codigo_usuario', $demoCodes)->get() as $user) {
            $user->update([
                'password' => Str::password(20),
                'activo' => false,
                'cambiar_password' => true,
            ]);
        }

        $unrelatedPassword = Str::password(20);
        $unrelated = User::factory()->create([
            'rol_id' => Role::where('nombre', Role::DIRECCION)->value('id'),
            'codigo_usuario' => 'USR-NO-DEMO',
            'password' => $unrelatedPassword,
            'activo' => false,
            'cambiar_password' => true,
        ]);
        $unrelatedHash = $unrelated->getRawOriginal('password');
        $resetPassword = 'Demo2026!';

        Config::set('demo.user_password', $resetPassword);
        Config::set('demo.reset_passwords', true);
        $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);

        $demoUsers = User::whereIn('codigo_usuario', $demoCodes)->get();
        $this->assertCount(8, $demoUsers);
        foreach ($demoUsers as $user) {
            $this->assertTrue(Hash::check('Demo2026!', $user->password));
            $this->assertTrue($user->activo);
            $this->assertFalse($user->cambiar_password);
        }

        $unrelated->refresh();
        $this->assertSame($unrelatedHash, $unrelated->getRawOriginal('password'));
        $this->assertTrue(Hash::check($unrelatedPassword, $unrelated->password));
        $this->assertFalse($unrelated->activo);
        $this->assertTrue($unrelated->cambiar_password);

        Config::set('demo.user_password', Str::password(24));
        Config::set('demo.reset_passwords', false);
        $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);

        foreach (User::whereIn('codigo_usuario', $demoCodes)->get() as $user) {
            $this->assertTrue(Hash::check('Demo2026!', $user->password));
            $this->assertFalse($user->cambiar_password);
        }
    }

    private function countTable(string $table): int
    {
        return (int) app('db')->table($table)->count();
    }
}
