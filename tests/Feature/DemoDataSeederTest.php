<?php

namespace Tests\Feature;

use App\Models\Encargado;
use App\Models\Grupo;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_is_not_loaded_when_it_is_disabled(): void
    {
        Config::set('app.demo_data_enabled', false);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('estudiantes', 0);
    }

    public function test_demo_data_is_idempotent_when_it_is_enabled(): void
    {
        Config::set('app.demo_data_enabled', true);
        Config::set('app.demo_user_password', Str::password(16));

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 8);
        $this->assertSame(6, User::whereHas('role', fn ($query) => $query->where('nombre', Role::DOCENTE))->count());
        $this->assertSame(8, User::where('cambiar_password', true)->count());
        $this->assertSame([
            'DIR-001',
            'DOC-001',
            'DOC-002',
            'DOC-003',
            'DOC-004',
            'DOC-005',
            'DOC-006',
            'ENC-0001',
        ], User::orderBy('codigo_usuario')->pluck('codigo_usuario')->sort()->values()->all());
        $this->assertDatabaseCount('grados', 2);
        $this->assertDatabaseCount('secciones', 3);
        $this->assertDatabaseCount('grupos', 6);
        $this->assertDatabaseCount('estudiantes', 24);
        $this->assertSame(24, app('db')->table('estudiantes')->where('estado', 'activo')->count());
        $this->assertDatabaseCount('asignaciones_escolares', 24);
        $this->assertDatabaseCount('estudiante_encargado', 24);
        $this->assertDatabaseCount('actividades', 12);
        $this->assertDatabaseCount('calificaciones', 48);
        $this->assertDatabaseCount('asistencias', 72);
        $this->assertDatabaseCount('justificaciones_inasistencia', 3);
        $this->assertDatabaseCount('bitacora', 2);

        foreach (range(1, 6) as $number) {
            $teacher = User::where('codigo_usuario', sprintf('DOC-%03d', $number))->firstOrFail();
            $this->assertSame(1, Grupo::where('docente_id', $teacher->id)->count());
            $this->assertSame(4, Grupo::where('docente_id', $teacher->id)
                ->firstOrFail()->asignaciones()->where('estado', 'activa')->count());
        }

        $guardian = Encargado::whereHas('usuario', fn ($query) => $query->where('codigo_usuario', 'ENC-0001'))->firstOrFail();
        $this->assertSame(24, $guardian->estudiantes()->count());

        $counts = [
            'users' => $this->countTable('users'),
            'grados' => $this->countTable('grados'),
            'secciones' => $this->countTable('secciones'),
            'grupos' => $this->countTable('grupos'),
            'estudiantes' => $this->countTable('estudiantes'),
            'asignaciones_escolares' => $this->countTable('asignaciones_escolares'),
            'asistencias' => $this->countTable('asistencias'),
            'actividades' => $this->countTable('actividades'),
            'calificaciones' => $this->countTable('calificaciones'),
            'justificaciones_inasistencia' => $this->countTable('justificaciones_inasistencia'),
            'bitacora' => $this->countTable('bitacora'),
        ];

        $this->seed(DatabaseSeeder::class);

        foreach ($counts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
    }

    private function countTable(string $table): int
    {
        return (int) app('db')->table($table)->count();
    }
}
