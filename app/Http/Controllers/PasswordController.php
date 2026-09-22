<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function edit()
    {
        return view('auth.change-password');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:12', 'max:72', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.min' => 'La nueva contraseña debe tener al menos 12 caracteres.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
        ]);
        DB::transaction(function () use ($request, $data) {
            $request->user()->update(['password' => Hash::make($data['password']), 'cambiar_password' => false]);
            $request->user()->forceFill(['remember_token' => Str::random(60)])->save();
            Bitacora::create(['usuario_id' => $request->user()->id, 'accion' => 'cambiar_password',
                'modulo' => 'acceso', 'descripcion' => 'Contraseña actualizada por su titular.', 'fecha' => now()]);
        });
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Contraseña actualizada.');
    }
}
