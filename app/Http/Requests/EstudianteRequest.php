<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstudianteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole([Role::DIRECCION, Role::ADMINISTRATIVO]) ?? false;
    }

    public function rules(): array
    {
        $estudianteId = $this->route('estudiante')?->id;

        return [
            'codigo' => ['required', 'string', 'max:30', Rule::unique('estudiantes', 'codigo')->ignore($estudianteId)],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'],
            'sexo' => ['nullable', Rule::in(['femenino', 'masculino', 'otro'])],
            'direccion' => ['nullable', 'string', 'max:2000'],
            'informacion_medica' => ['nullable', 'string', 'max:5000'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'estado' => ['required', Rule::in(['activo', 'retirado', 'inactivo'])],
            'grupo_id' => ['required', Rule::exists('grupos', 'id')->where('activo', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código del estudiante es obligatorio.',
            'codigo.unique' => 'Este código ya está en uso.',
            'nombres.required' => 'Los nombres son obligatorios.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede estar en el futuro.',
            'grupo_id.required' => 'Selecciona el grado y la sección del ciclo correspondiente.',
            'grupo_id.exists' => 'El grupo seleccionado no está disponible.',
        ];
    }
}
