<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Estudiante;
use App\Models\PersonaAutorizada;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PersonaAutorizadaController extends Controller
{
    public function store(Request $request, Estudiante $estudiante): RedirectResponse
    {
        Gate::authorize('update', $estudiante);
        $data = $this->validated($request);
        $data['activo'] = $request->boolean('activo', true);
        $estudiante->personasAutorizadas()->create($data);
        $this->audit($request, $estudiante, 'agregar_persona_autorizada');

        return back()->with('status', 'Persona autorizada agregada correctamente.');
    }

    public function update(Request $request, Estudiante $estudiante, PersonaAutorizada $persona): RedirectResponse
    {
        Gate::authorize('update', $estudiante);
        abort_unless($persona->estudiante_id === $estudiante->id, 404);
        $data = $this->validated($request);
        $data['activo'] = $request->boolean('activo');
        $persona->update($data);
        $this->audit($request, $estudiante, 'actualizar_persona_autorizada');

        return back()->with('status', 'Persona autorizada actualizada correctamente.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'parentesco' => ['required', 'string', 'max:50'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'activo' => ['nullable', 'boolean'],
        ]);
    }

    private function audit(Request $request, Estudiante $student, string $action): void
    {
        Bitacora::create([
            'usuario_id' => $request->user()->id,
            'accion' => $action,
            'modulo' => 'estudiantes',
            'descripcion' => "Personas autorizadas del expediente {$student->codigo}.",
            'fecha' => now(),
        ]);
    }
}
