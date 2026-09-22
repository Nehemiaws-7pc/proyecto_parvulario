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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EncargadoController extends Controller
{
    public function index(Request $request, Estudiante $estudiante)
    {
        Gate::authorize('update', $estudiante);
        $data = $request->validate(['q' => ['nullable', 'string', 'max:150']]);
        $search = trim($data['q'] ?? '');
        $users = User::query()->whereHas('role', fn ($role) => $role->where('nombre', Role::ENCARGADO)->where('activo', true))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('codigo_usuario', 'like', "%{$search}%")->orWhere('nombre', 'like', "%{$search}%")
                ->orWhere('correo', 'like', "%{$search}%")->orWhere('telefono', 'like', "%{$search}%")))
            ->orderBy('nombre')->paginate(15)->withQueryString();

        return view('students.guardians', ['estudiante' => $estudiante->load('encargados.usuario'), 'users' => $users, 'search' => $search]);
    }

    public function createAccount(Request $request, Estudiante $estudiante): RedirectResponse
    {
        Gate::authorize('update', $estudiante);
        $request->merge([
            'codigo_usuario' => Str::upper(trim((string) $request->input('codigo_usuario'))),
            'correo' => $request->filled('correo') ? Str::lower(trim($request->input('correo'))) : null,
        ]);
        $data = $request->validate([
            'codigo_usuario' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9-]+$/', 'unique:users,codigo_usuario'],
            'nombre' => ['required', 'string', 'max:150'],
            'telefono' => ['required', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:150', 'unique:users,correo'],
            'password' => ['required', 'string', 'min:12', 'max:72', 'confirmed'],
            'parentesco' => ['required', 'string', 'max:50'],
        ]);
        DB::transaction(function () use ($request, $estudiante, $data) {
            // Serializa las altas de encargados para evitar duplicados concurrentes.
            $role = Role::where('nombre', Role::ENCARGADO)->where('activo', true)->lockForUpdate()->firstOrFail();
            $duplicate = User::whereRaw('LOWER(codigo_usuario) = ?', [Str::lower($data['codigo_usuario'])])
                ->when($data['correo'] ?? null, fn ($query, $email) => $query->orWhereRaw('LOWER(correo) = ?', [$email]))
                ->orWhere(fn ($query) => $query->whereRaw('LOWER(nombre) = ?', [Str::lower(trim($data['nombre']))])
                    ->where('telefono', $data['telefono']))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['codigo_usuario' => 'Ya existe una cuenta coincidente. Búscala y vincúlala, sin crear otra.']);
            }
            $user = User::create([
                'rol_id' => $role->id, 'codigo_usuario' => $data['codigo_usuario'], 'nombre' => trim($data['nombre']),
                'telefono' => $data['telefono'], 'correo' => $data['correo'] ?? null,
                'password' => Hash::make($data['password']), 'activo' => true, 'cambiar_password' => true,
            ]);
            $guardian = Encargado::create(['usuario_id' => $user->id, 'nombre' => $user->nombre,
                'telefono' => $user->telefono, 'correo' => $user->correo]);
            $this->saveRelationship($request, $estudiante, $guardian, $data['parentesco']);
            $this->audit($request, $estudiante, 'crear_cuenta_encargado', $guardian);
        });

        return back()->with('status', 'Cuenta creada y vinculada. Entrega la contraseña inicial por un medio privado; deberá cambiarla al acceder.');
    }

    public function destroy(Request $request, Estudiante $estudiante, Encargado $encargado): RedirectResponse
    {
        Gate::authorize('update', $estudiante);
        DB::transaction(function () use ($request, $estudiante, $encargado) {
            $guardian = Encargado::whereKey($encargado->id)->lockForUpdate()->firstOrFail();
            abort_unless($estudiante->encargados()->whereKey($guardian->id)->exists(), 404);
            $estudiante->encargados()->detach($guardian->id);
            $this->audit($request, $estudiante, 'desvincular_encargado', $guardian);
        });

        return back()->with('status', 'Vínculo retirado. La cuenta y sus demás vínculos se conservaron.');
    }

    public function store(Request $request, Estudiante $estudiante): RedirectResponse
    {
        Gate::authorize('update', $estudiante);
        $validated = $this->validated($request);

        DB::transaction(function () use ($request, $estudiante, $validated) {
            if (! empty($validated['usuario_id'])) {
                User::whereKey($validated['usuario_id'])->lockForUpdate()->firstOrFail();
            }
            $guardianData = collect($validated)->only(['usuario_id', 'nombre', 'telefono', 'correo', 'direccion'])->all();
            $guardian = ! empty($guardianData['usuario_id'])
                ? Encargado::firstOrCreate(['usuario_id' => $guardianData['usuario_id']], $guardianData)
                : Encargado::create($guardianData);

            if (! $estudiante->encargados()->whereKey($guardian->id)->exists()) {
                $this->saveRelationship($request, $estudiante, $guardian, $validated['parentesco']);
                $this->audit($request, $estudiante, 'agregar_encargado', $guardian);
            }
        });

        return back()->with('status', 'Encargado agregado correctamente.');
    }

    public function update(Request $request, Estudiante $estudiante, Encargado $encargado): RedirectResponse
    {
        Gate::authorize('update', $estudiante);
        abort_unless($estudiante->encargados()->whereKey($encargado->id)->exists(), 404);
        $validated = $this->validated($request, $encargado);
        if ($encargado->usuario_id && (int) ($validated['usuario_id'] ?? 0) !== $encargado->usuario_id) {
            throw ValidationException::withMessages(['usuario_id' => 'No se puede reemplazar la identidad de un contacto compartido. Desvincula y vincula la cuenta correcta.']);
        }

        DB::transaction(function () use ($request, $estudiante, $encargado, $validated) {
            $encargado->update(collect($validated)->only(['usuario_id', 'nombre', 'telefono', 'correo', 'direccion'])->all());
            $this->saveRelationship($request, $estudiante, $encargado, $validated['parentesco']);
            $this->audit($request, $estudiante, 'actualizar_encargado', $encargado);
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
                ->whereHas('role', fn ($role) => $role->where('nombre', Role::ENCARGADO)->where('activo', true))
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

    private function audit(Request $request, Estudiante $student, string $action, Encargado $guardian): void
    {
        Bitacora::create([
            'usuario_id' => $request->user()->id,
            'accion' => $action,
            'modulo' => 'estudiantes',
            'descripcion' => "Estudiante {$student->id} ({$student->codigo}); encargado {$guardian->id}; cuenta ".($guardian->usuario_id ?? 'sin cuenta').'.',
            'fecha' => now(),
        ]);
    }
}
