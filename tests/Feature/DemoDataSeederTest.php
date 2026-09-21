<?php

namespace Tests\Feature;

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

        $counts = [
            'users' => $this->countTable('users'),
            'estudiantes' => $this->countTable('estudiantes'),
            'asignaciones_escolares' => $this->countTable('asignaciones_escolares'),
            'asistencias' => $this->countTable('asistencias'),
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
