<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\PasswordRecoveryRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordRecoveryController extends Controller
{
    public function create(): View
    {
        return view('auth.password-recovery');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'codigo_usuario' => ['required', 'string', 'max:30'],
            'nombre' => ['required', 'string', 'max:150'],
            'telefono' => ['required', 'string', 'max:20'],
        ]);
        $key = 'password-recovery:'.sha1($request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->with('status', 'Si los datos coinciden con una cuenta autorizada, Dirección revisará la solicitud.');
        }
        RateLimiter::hit($key, 600);

        $code = Str::upper(trim($data['codigo_usuario']));
        $name = Str::lower(trim($data['nombre']));
        $user = User::query()->where('codigo_usuario', $code)->where('activo', true)
            ->whereRaw('LOWER(nombre) = ?', [$name])
            ->where('telefono', trim($data['telefono']))
            ->whereHas('role', fn ($role) => $role->where('activo', true)->whereIn('nombre', [Role::DOCENTE, Role::ENCARGADO]))
            ->first();

        if ($user) {
            PasswordRecoveryRequest::create([
                'usuario_id' => $user->id, 'codigo_usuario' => $code,
                'nombre' => trim($data['nombre']), 'telefono' => trim($data['telefono']),
            ]);
        }

        return back()->with('status', 'Si los datos coinciden con una cuenta autorizada, Dirección revisará la solicitud.');
    }

    public function index(): View
    {
        abort_unless(auth()->user()->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO]), 403);

        return view('auth.password-recovery-requests', [
            'requests' => PasswordRecoveryRequest::with('usuario')->where('estado', PasswordRecoveryRequest::PENDING)->latest()->get(),
        ]);
    }

    public function reset(Request $request, PasswordRecoveryRequest $recovery): View
    {
        abort_unless(auth()->user()->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO]), 403);
        abort_unless($recovery->estado === PasswordRecoveryRequest::PENDING && $recovery->usuario, 404);
        $temporary = Str::password(20);

        DB::transaction(function () use ($request, $recovery, $temporary) {
            $user = User::whereKey($recovery->usuario_id)->lockForUpdate()->firstOrFail();
            $user->update(['password' => Hash::make($temporary), 'cambiar_password' => true]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $recovery->update(['estado' => PasswordRecoveryRequest::RESOLVED, 'resuelto_por' => $request->user()->id, 'resuelto_at' => now()]);
            Bitacora::create(['usuario_id' => $request->user()->id, 'accion' => 'restablecer_password', 'modulo' => 'acceso',
                'descripcion' => "Solicitud {$recovery->id} resuelta para cuenta {$user->codigo_usuario}.", 'fecha' => now()]);
        });

        return view('auth.password-recovery-result', ['temporary' => $temporary, 'user' => $recovery->usuario->fresh(), 'request' => $recovery]);
    }
}
