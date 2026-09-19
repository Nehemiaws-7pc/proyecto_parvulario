<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Encargado;
use App\Models\Estudiante;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EncargadoController extends Controller
{
    public function store(Request $request, Estudiante $estudiante): RedirectResponse
    {
        Gate::authorize('update', $estudiante);
        $validated = $this->validated($request);

        DB::transaction(function () use ($request, $estudiante, $validated) {
            $guardianData = collect($validated)->only(['usuario_id', 'nombre', 'telefono', 'correo', 'direccion'])->all();
            $guardian = ! empty($guardianData['usuario_id'])
                ? Encargado::updateOrCreate(['usuario_id' => $guardianData['usuario_id']], $guardianData)
                : Encargado::create($guardianData);

            $this->saveRelationship($request, $estudiante, $guardian, $validated['parentesco']);
            $this->audit($request, $estudiante, 'agregar_encargado');
        });

        return back()->with('status', 'Encargado agregado correctamente.');
    }

    public function update(Request $request, Estudiante $estudiante, Encargado $encargado): RedirectResponse
    {
        Gate::authorize('update', $estudiante);
        abort_unless($estudiante->encargados()->whereKey($encargado->id)->exists(), 404);
        $validated = $this->validated($request, $encargado);

        DB::transaction(function () use ($request, $estudiante, $encargado, $validated) {
            $encargado->update(collect($validated)->only(['usuario_id', 'nombre', 'telefono', 'correo', 'direccion'])->all());
            $this->saveRelationship($request, $estudiante, $encargado, $validated['parentesco']);
            $this->audit($request, $estudiante, 'actualizar_encargado');
        });

        return back()->with('status', 'Encargado actualizado correctamente.');
    }

    private function validated(Request $request, ?Encargado $encargado = null): array
    {
        $userRule = ['nullable', 'integer', 'exists:users,id'];
        if ($encargado) {
            $userRule[] = Rule::unique('encargados', 'usuario_id')->ignore($encargado->id);
        }

        $validator = Validator::make($request->all(), [
            'usuario_id' => $userRule,
            'nombre' => ['required', 'string', 'max:150'],
            'telefono' => ['required', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:2000'],
            'parentesco' => ['required', 'string', 'max:50'],
            'contacto_principal' => ['nullable', 'boolean'],
            'contacto_emergencia' => ['nullable', 'boolean'],
            'autorizado_recoger' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->filled('usuario_id') && ! User::query()
                ->whereKey($request->integer('usuario_id'))
                ->where('activo', true)
                ->whereHas('role', fn ($role) => $role->where('nombre', Role::ENCARGADO))
                ->exists()) {
                $validator->errors()->add('usuario_id', 'La cuenta seleccionada debe ser una cuenta activa de padre o encargado.');
            }
        });

        return $validator->validate();
    }

    private function saveRelationship(Request $request, Estudiante $student, Encargado $guardian, string $relationship): void
    {
        if ($request->boolean('contacto_principal')) {
            DB::table('estudiante_encargado')
                ->where('estudiante_id', $student->id)
                ->update(['contacto_principal' => false]);
        }

        $student->encargados()->syncWithoutDetaching([$guardian->id => [
            'parentesco' => $relationship,
            'contacto_principal' => $request->boolean('contacto_principal'),
            'contacto_emergencia' => $request->boolean('contacto_emergencia'),
            'autorizado_recoger' => $request->boolean('autorizado_recoger'),
        ]]);
    }

    private function audit(Request $request, Estudiante $student, string $action): void
    {
        Bitacora::create([
            'usuario_id' => $request->user()->id,
            'accion' => $action,
            'modulo' => 'estudiantes',
            'descripcion' => "Contactos del expediente {$student->codigo}.",
            'fecha' => now(),
        ]);
    }
}
