<?php

namespace Tests\Feature;

use App\Models\Encargado;
use App\Models\Grupo;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class DemoAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_users_only_see_the_students_and_groups_assigned_to_them(): void
    {
        $this->seedDemoData();

        foreach (range(1, 5) as $number) {
            $teacher = User::where('codigo_usuario', sprintf('DOC-%03d', $number))->firstOrFail();
            $response = $this->actingAs($teacher)->get(route('estudiantes.index'));

            $response->assertOk();
            $this->assertSame($number === 4 ? 8 : 4, $response->viewData('estudiantes')->total());
        }

        foreach ([6, 7] as $number) {
            $teacher = User::where('codigo_usuario', sprintf('DOC-%03d', $number))->firstOrFail();
            $response = $this->actingAs($teacher)->get(route('estudiantes.index'));

            $response->assertOk();
            $this->assertSame(24, $response->viewData('estudiantes')->total());
        }

        $direction = User::where('codigo_usuario', 'DIR-001')->firstOrFail();
        $this->actingAs($direction)->get(route('estructura.index'))
            ->assertOk()
            ->assertViewHas('grupos', fn ($groups) => $groups->count() === 6);

        $guardian = User::where('codigo_usuario', 'ENC-0001')->firstOrFail();
        $this->assertSame(24, Encargado::where('usuario_id', $guardian->id)->firstOrFail()->estudiantes()->count());
        $guardianResponse = $this->actingAs($guardian)->get(route('estudiantes.index'));

        $guardianResponse->assertOk();
        $this->assertSame(24, $guardianResponse->viewData('estudiantes')->total());
        $this->assertSame(6, Grupo::where('activo', true)->count());
    }

    private function seedDemoData(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.user_password', Str::password(16));
        $this->seed(DatabaseSeeder::class);
    }
}
