<?php

namespace App\Http\Controllers;

use App\Models\CicloEscolar;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Role;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EstructuraEscolarController extends Controller
{
    public function index(): View
    {
        Gate::authorize('create', Estudiante::class);

        return view('structure.index', [
            'ciclos' => CicloEscolar::query()->orderByDesc('anio')->get(),
            'grados' => Grado::query()->orderBy('nombre')->get(),
            'secciones' => Seccion::query()->orderBy('nombre')->get(),
            'grupos' => Grupo::query()->with(['ciclo', 'grado', 'seccion', 'docente'])->latest('id')->get(),
            'docentes' => User::query()->where('activo', true)
                ->whereHas('role', fn ($query) => $query->where('nombre', Role::DOCENTE))
                ->orderBy('nombre')->get(),
        ]);
    }

    public function storeCycle(Request $request): RedirectResponse
    {
        CicloEscolar::create($this->cycleData($request));

        return back()->with('status', 'Ciclo escolar creado correctamente.');
    }

    public function updateCycle(Request $request, CicloEscolar $ciclo): RedirectResponse
    {
        $ciclo->update($this->cycleData($request, $ciclo));

        return back()->with('status', 'Ciclo escolar actualizado correctamente.');
    }

    public function storeGrade(Request $request): RedirectResponse
    {
        Grado::create($this->gradeData($request));

        return back()->with('status', 'Grado creado correctamente.');
    }

    public function updateGrade(Request $request, Grado $grado): RedirectResponse
    {
        $grado->update($this->gradeData($request, $grado));

        return back()->with('status', 'Grado actualizado correctamente.');
    }

    public function storeSection(Request $request): RedirectResponse
    {
        Seccion::create($this->sectionData($request));

        return back()->with('status', 'Sección creada correctamente.');
    }

    public function updateSection(Request $request, Seccion $seccion): RedirectResponse
    {
        $seccion->update($this->sectionData($request, $seccion));

        return back()->with('status', 'Sección actualizada correctamente.');
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        Grupo::create($this->groupData($request));

        return back()->with('status', 'Grupo creado correctamente.');
    }

    public function updateGroup(Request $request, Grupo $grupo): RedirectResponse
    {
        $data = $this->groupData($request, $grupo);
        $changesHistoricalIdentity = (int) $data['ciclo_id'] !== $grupo->ciclo_id
            || (int) $data['grado_id'] !== $grupo->grado_id
            || (int) $data['seccion_id'] !== $grupo->seccion_id;

        if ($changesHistoricalIdentity && $grupo->asignaciones()->exists()) {
            throw ValidationException::withMessages([
                'seccion_id' => 'No se puede cambiar el ciclo, grado o sección de un grupo con historial. Crea un grupo nuevo.',
            ]);
        }

        $grupo->update($data);

        return back()->with('status', 'Grupo actualizado correctamente.');
    }

    private function cycleData(Request $request, ?CicloEscolar $cycle = null): array
    {
        return $request->validate([
            'anio' => ['required', 'integer', 'between:2020,2100', Rule::unique('ciclos_escolares', 'anio')->ignore($cycle?->id)],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'estado' => ['required', Rule::in(['planificado', 'activo', 'cerrado'])],
        ]);
    }

    private function gradeData(Request $request, ?Grado $grade = null): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:80', Rule::unique('grados', 'nombre')->ignore($grade?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'activo' => ['nullable', 'boolean'],
        ]);
        $data['activo'] = $request->boolean('activo');

        return $data;
    }

    private function sectionData(Request $request, ?Seccion $section = null): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:30', Rule::unique('secciones', 'nombre')->ignore($section?->id)],
            'capacidad' => ['nullable', 'integer', 'between:1,100'],
            'activo' => ['nullable', 'boolean'],
        ]);
        $data['activo'] = $request->boolean('activo');

        return $data;
    }

    private function groupData(Request $request, ?Grupo $group = null): array
    {
        $data = $request->validate([
            'ciclo_id' => ['required', 'exists:ciclos_escolares,id'],
            'grado_id' => ['required', 'exists:grados,id'],
            'seccion_id' => ['required', 'exists:secciones,id'],
            'docente_id' => ['required', 'exists:users,id'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $duplicate = Grupo::query()
            ->where('ciclo_id', $data['ciclo_id'])
            ->where('grado_id', $data['grado_id'])
            ->where('seccion_id', $data['seccion_id'])
            ->when($group, fn ($query) => $query->whereKeyNot($group->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['seccion_id' => 'Ya existe este grado y sección para el ciclo seleccionado.']);
        }

        $validTeacher = User::query()->whereKey($data['docente_id'])->where('activo', true)
            ->whereHas('role', fn ($query) => $query->where('nombre', Role::DOCENTE))->exists();

        if (! $validTeacher) {
            throw ValidationException::withMessages(['docente_id' => 'Selecciona una cuenta docente activa.']);
        }

        $data['activo'] = $request->boolean('activo');

        return $data;
    }
}
