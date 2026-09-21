<?php

namespace App\Http\Controllers;

use App\Models\AreaAprendizaje;
use App\Models\CicloEscolar;
use App\Models\EscalaEvaluacion;
use App\Models\Evaluacion;
use App\Models\Grado;
use App\Models\IndicadorEvaluacion;
use App\Models\Periodo;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfiguracionEvaluacionController extends Controller
{
    public function index(): View
    {
        $this->authorizeConfiguration();

        return view('evaluations.configuration', [
            'areas' => AreaAprendizaje::query()->orderBy('nombre')->get(),
            'ciclos' => CicloEscolar::query()->orderByDesc('anio')->get(),
            'escalas' => EscalaEvaluacion::query()->orderBy('orden')->orderBy('nombre')->get(),
            'grados' => Grado::query()->orderBy('nombre')->get(),
            'indicadores' => IndicadorEvaluacion::query()->with(['area', 'grado'])->orderBy('nombre')->get(),
            'periodos' => Periodo::query()->with('ciclo')->orderByDesc('fecha_inicio')->get(),
        ]);
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        $this->authorizeConfiguration();
        Periodo::create($this->periodData($request));

        return back()->with('status', 'Período creado correctamente.');
    }

    public function updatePeriod(Request $request, Periodo $periodo): RedirectResponse
    {
        $this->authorizeConfiguration();
        $data = $this->periodData($request, $periodo);
        if ((int) $data['ciclo_id'] !== $periodo->ciclo_id && $periodo->evaluaciones()->exists()) {
            throw ValidationException::withMessages([
                'ciclo_id' => 'No se puede cambiar el ciclo de un período con evaluaciones registradas.',
            ]);
        }
        $periodo->update($data);

        return back()->with('status', 'Período actualizado correctamente.');
    }

    public function storeArea(Request $request): RedirectResponse
    {
        $this->authorizeConfiguration();
        AreaAprendizaje::create($this->areaData($request));

        return back()->with('status', 'Área de aprendizaje creada correctamente.');
    }

    public function updateArea(Request $request, AreaAprendizaje $area): RedirectResponse
    {
        $this->authorizeConfiguration();
        $area->update($this->areaData($request, $area));

        return back()->with('status', 'Área de aprendizaje actualizada correctamente.');
    }

    public function storeIndicator(Request $request): RedirectResponse
    {
        $this->authorizeConfiguration();
        IndicadorEvaluacion::create($this->indicatorData($request));

        return back()->with('status', 'Indicador creado correctamente.');
    }

    public function updateIndicator(Request $request, IndicadorEvaluacion $indicador): RedirectResponse
    {
        $this->authorizeConfiguration();
        $data = $this->indicatorData($request, $indicador);
        $changesStructure = (int) $data['area_id'] !== $indicador->area_id
            || (int) $data['grado_id'] !== $indicador->grado_id;
        if ($changesStructure && $indicador->evaluaciones()->exists()) {
            throw ValidationException::withMessages([
                'area_id' => 'No se puede cambiar el área o grado de un indicador con evaluaciones registradas.',
            ]);
        }
        $indicador->update($data);

        return back()->with('status', 'Indicador actualizado correctamente.');
    }

    public function storeScale(Request $request): RedirectResponse
    {
        $this->authorizeConfiguration();
        EscalaEvaluacion::create($this->scaleData($request));

        return back()->with('status', 'Resultado descriptivo creado correctamente.');
    }

    public function updateScale(Request $request, EscalaEvaluacion $escala): RedirectResponse
    {
        $this->authorizeConfiguration();
        $data = $this->scaleData($request, $escala);
        if ($escala->activo && ! $data['activo'] && EscalaEvaluacion::query()->where('activo', true)->count() === 1) {
            throw ValidationException::withMessages([
                'activo' => 'Debe permanecer al menos un resultado descriptivo activo.',
            ]);
        }
        $escala->update($data);

        return back()->with('status', 'Resultado descriptivo actualizado correctamente.');
    }

    private function authorizeConfiguration(): void
    {
        Gate::authorize('configure', Evaluacion::class);
    }

    private function periodData(Request $request, ?Periodo $period = null): array
    {
        $data = $request->validate([
            'ciclo_id' => ['required', 'integer', 'exists:ciclos_escolares,id'],
            'nombre' => [
                'required',
                'string',
                'max:80',
                Rule::unique('periodos', 'nombre')
                    ->where(fn ($query) => $query->where('ciclo_id', $request->integer('ciclo_id')))
                    ->ignore($period?->id),
            ],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'activo' => ['nullable', 'boolean'],
        ]);
        $cycle = CicloEscolar::findOrFail($data['ciclo_id']);
        $start = CarbonImmutable::parse($data['fecha_inicio']);
        $end = CarbonImmutable::parse($data['fecha_fin']);
        if ($start->lt($cycle->fecha_inicio) || $end->gt($cycle->fecha_fin)) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'Las fechas del período deben estar dentro del ciclo escolar.',
            ]);
        }
        $data['activo'] = $request->boolean('activo');

        return $data;
    }

    private function areaData(Request $request, ?AreaAprendizaje $area = null): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('areas_aprendizaje', 'nombre')->ignore($area?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'activo' => ['nullable', 'boolean'],
        ]);
        $data['activo'] = $request->boolean('activo');

        return $data;
    }

    private function indicatorData(Request $request, ?IndicadorEvaluacion $indicator = null): array
    {
        $data = $request->validate([
            'area_id' => ['required', 'integer', 'exists:areas_aprendizaje,id'],
            'grado_id' => ['required', 'integer', 'exists:grados,id'],
            'nombre' => [
                'required',
                'string',
                'max:180',
                Rule::unique('indicadores_evaluacion', 'nombre')
                    ->where(fn ($query) => $query
                        ->where('area_id', $request->integer('area_id'))
                        ->where('grado_id', $request->integer('grado_id')))
                    ->ignore($indicator?->id),
            ],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'activo' => ['nullable', 'boolean'],
        ]);
        $data['activo'] = $request->boolean('activo');

        return $data;
    }

    private function scaleData(Request $request, ?EscalaEvaluacion $scale = null): array
    {
        $request->merge(['codigo' => Str::upper(trim((string) $request->input('codigo')))]);
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:30', Rule::unique('escalas_evaluacion', 'codigo')->ignore($scale?->id)],
            'nombre' => ['required', 'string', 'max:80', Rule::unique('escalas_evaluacion', 'nombre')->ignore($scale?->id)],
            'orden' => ['required', 'integer', 'between:1,999'],
            'activo' => ['nullable', 'boolean'],
        ]);
        $data['activo'] = $request->boolean('activo');

        return $data;
    }
}
