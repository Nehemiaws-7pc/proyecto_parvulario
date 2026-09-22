<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:150', Rule::unique('users', 'correo')->ignore($user->id)],
        ], [
            'correo.email' => 'Escribe un correo electrónico válido.',
            'correo.unique' => 'Ese correo ya está registrado por otra cuenta.',
        ]);

        DB::transaction(function () use ($user, $data) {
            $user->update($data);
            Bitacora::create([
                'usuario_id' => $user->id,
                'accion' => 'actualizar_perfil',
                'modulo' => 'acceso',
                'descripcion' => "Perfil actualizado por el usuario {$user->codigo_usuario}.",
                'fecha' => now(),
            ]);
        });

        return back()->with('status', 'Mi perfil fue actualizado correctamente.');
    }
}
