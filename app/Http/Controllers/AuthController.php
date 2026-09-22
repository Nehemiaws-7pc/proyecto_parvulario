<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(
            [
                'codigo_usuario' => ['required', 'string', 'max:30'],
                'password' => ['required', 'string'],
            ],
            [
                'codigo_usuario.required' => 'Ingresa tu código identificador.',
                'codigo_usuario.max' => 'El código no puede superar los 30 caracteres.',
                'password.required' => 'Ingresa tu contraseña.',
            ],
        );

        $credentials['codigo_usuario'] = Str::upper(trim($credentials['codigo_usuario']));
        $throttleKey = Str::lower($credentials['codigo_usuario']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'codigo_usuario' => 'Demasiados intentos. Intenta nuevamente en '.RateLimiter::availableIn($throttleKey).' segundos.',
            ]);
        }

        if (! Auth::attempt([
            'codigo_usuario' => $credentials['codigo_usuario'],
            'password' => $credentials['password'],
            'activo' => true,
        ])) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'codigo_usuario' => 'El código o la contraseña no son correctos, o la cuenta está inactiva.',
            ]);
        }

        $user = $request->user();

        if (! $user->role?->activo) {
            Auth::logout();
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'codigo_usuario' => 'El código o la contraseña no son correctos, o la cuenta está inactiva.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $user->forceFill(['ultimo_acceso' => now()])->save();
        Bitacora::create([
            'usuario_id' => $user->id,
            'accion' => 'inicio_sesion',
            'modulo' => 'acceso',
            'descripcion' => 'Inicio de sesión correcto.',
            'fecha' => now(),
        ]);

        if ($user->cambiar_password) {
            return redirect()->route('password.edit');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()) {
            Bitacora::create([
                'usuario_id' => $request->user()->id,
                'accion' => 'cierre_sesion',
                'modulo' => 'acceso',
                'descripcion' => 'Cierre de sesión correcto.',
                'fecha' => now(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sesión cerrada correctamente.');
    }
}
