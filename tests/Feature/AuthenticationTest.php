<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_is_available(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_login_screen_loads_the_configured_vite_entries_over_https(): void
    {
        $buildDirectory = 'build-test';
        $manifestPath = public_path("{$buildDirectory}/manifest.json");
        File::ensureDirectoryExists(dirname($manifestPath));
        File::put($manifestPath, json_encode([
            'resources/css/app.css' => [
                'file' => 'assets/app-test.css',
                'src' => 'resources/css/app.css',
                'isEntry' => true,
            ],
            'resources/js/app.js' => [
                'file' => 'assets/app-test.js',
                'src' => 'resources/js/app.js',
                'isEntry' => true,
            ],
        ], JSON_THROW_ON_ERROR));
        $vite = new Vite;
        $this->swap(Vite::class, $vite);
        $vite
            ->useBuildDirectory($buildDirectory)
            ->useHotFile(storage_path('framework/testing/nonexistent-vite-hot-file'));

        try {
            $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.10'])
                ->withHeaders([
                    'X-Forwarded-Host' => 'demo.example',
                    'X-Forwarded-Port' => '443',
                    'X-Forwarded-Proto' => 'https',
                ])->get('http://demo.example/iniciar-sesion');

            $response->assertOk()->assertViewIs('auth.login');
            $content = $response->getContent();
            $this->assertStringContainsString('https://demo.example/build-test/assets/app-test.css', $content);
            $this->assertStringContainsString('https://demo.example/build-test/assets/app-test.js', $content);
            $this->assertSame(1, substr_count(
                $content,
                'rel="stylesheet" href="https://demo.example/build-test/assets/app-test.css"',
            ));
            $this->assertSame(1, substr_count(
                $content,
                'type="module" src="https://demo.example/build-test/assets/app-test.js"',
            ));
            $this->assertStringNotContainsString('http://demo.example/build-test/assets', $content);

            $layout = File::get(resource_path('views/layouts/app.blade.php'));
            $this->assertSame(1, substr_count(
                $layout,
                "@vite(['resources/css/app.css', 'resources/js/app.js'])",
            ));
            $this->assertStringContainsString('<!doctype html>', $layout);
            $this->assertStringContainsString('<head>', $layout);
            $this->assertStringContainsString('<body>', $layout);
            $this->assertStringContainsString(
                "input: ['resources/css/app.css', 'resources/js/app.js']",
                File::get(base_path('vite.config.js')),
            );
            $this->assertStringStartsWith(
                '@import "bootstrap/dist/css/bootstrap.min.css";',
                trim(File::get(resource_path('css/app.css'))),
            );
        } finally {
            File::deleteDirectory(public_path($buildDirectory));
        }
    }

    public function test_user_can_login_with_identifier_code_and_logout(): void
    {
        $role = Role::factory()->create(['nombre' => Role::DIRECCION]);
        $user = User::factory()->create([
            'rol_id' => $role->id,
            'codigo_usuario' => 'DIR-001',
            'password' => 'clave-segura',
        ]);

        $this->post(route('login.store'), [
            'codigo_usuario' => 'dir-001',
            'password' => 'clave-segura',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->ultimo_acceso);
        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $user->id,
            'accion' => 'inicio_sesion',
        ]);

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame(2, Bitacora::where('usuario_id', $user->id)->count());
    }

    public function test_invalid_password_is_rejected(): void
    {
        $user = User::factory()->create([
            'codigo_usuario' => 'DOC-001',
            'password' => 'clave-correcta',
        ]);

        $this->post(route('login.store'), [
            'codigo_usuario' => $user->codigo_usuario,
            'password' => 'incorrecta',
        ])->assertSessionHasErrors('codigo_usuario');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create([
            'codigo_usuario' => 'ADM-009',
            'password' => 'clave-segura',
        ]);

        $this->post(route('login.store'), [
            'codigo_usuario' => $user->codigo_usuario,
            'password' => 'clave-segura',
        ])->assertSessionHasErrors('codigo_usuario');

        $this->assertGuest();
    }

    public function test_user_with_inactive_role_cannot_login(): void
    {
        $role = Role::factory()->create([
            'nombre' => Role::DOCENTE,
            'activo' => false,
        ]);
        $user = User::factory()->create([
            'rol_id' => $role->id,
            'codigo_usuario' => 'DOC-099',
            'password' => 'clave-segura',
        ]);

        $this->post(route('login.store'), [
            'codigo_usuario' => $user->codigo_usuario,
            'password' => 'clave-segura',
        ])->assertSessionHasErrors('codigo_usuario');

        $this->assertGuest();
        $this->assertDatabaseMissing('bitacora', ['usuario_id' => $user->id]);
    }
}
