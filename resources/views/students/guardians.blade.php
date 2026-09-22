@extends('layouts.app')
@section('title', 'Gestionar encargados')
@section('content')
<main class="container py-5">
    <a href="{{ route('estudiantes.show', $estudiante) }}">Volver al expediente</a>
    <h1 class="h3 mt-3">Encargados de {{ $estudiante->nombre_completo }}</h1>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">Revisa los campos indicados.</div>@endif
    <h2 class="h5 mt-4">Vínculos actuales</h2>
    @foreach($estudiante->encargados as $guardian)
        <div class="border rounded p-3 mb-2 d-flex flex-wrap gap-3 justify-content-between">
            <span>{{ $guardian->nombre }} · {{ $guardian->usuario?->codigo_usuario ?? 'Sin cuenta' }} · {{ $guardian->pivot->parentesco }}</span>
            <form method="POST" action="{{ route('estudiantes.encargados.destroy', [$estudiante, $guardian]) }}" data-confirm="¿Retirar únicamente el vínculo con este estudiante?">
                @csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Desvincular</button>
            </form>
        </div>
    @endforeach
    <h2 class="h5 mt-4">Buscar cuenta existente</h2>
    <form method="GET" class="mb-3">
        <label for="q" class="form-label">Código, nombre, correo o teléfono</label>
        <div class="input-group"><input id="q" name="q" value="{{ $search }}" class="form-control" maxlength="150"><button class="btn btn-outline-primary">Buscar</button></div>
        @error('q')<div class="text-danger">{{ $message }}</div>@enderror
    </form>
    @error('usuario_id')<div class="text-danger">{{ $message }}</div>@enderror
    @foreach($users as $account)
        <div class="border rounded p-3 mb-2">
            <strong>{{ $account->codigo_usuario }} · {{ $account->nombre }}</strong>
            @if($estudiante->encargados->contains('usuario_id', $account->id))
                <span>Ya vinculado</span>
            @elseif(!$account->activo)
                <span>Cuenta inactiva: no crear otra cuenta.</span>
            @else
                <form method="POST" action="{{ route('estudiantes.encargados.store', $estudiante) }}" class="mt-2">
                    @csrf
                    <input type="hidden" name="usuario_id" value="{{ $account->id }}">
                    <input type="hidden" name="nombre" value="{{ $account->nombre }}">
                    <input type="hidden" name="correo" value="{{ $account->correo }}">
                    <label for="phone-{{ $account->id }}">Teléfono de contacto</label>
                    <input id="phone-{{ $account->id }}" name="telefono" value="{{ $account->telefono }}" required maxlength="20" class="form-control mb-2">
                    @error('telefono')<div class="text-danger">{{ $message }}</div>@enderror
                    <label for="relation-{{ $account->id }}">Parentesco</label>
                    <input id="relation-{{ $account->id }}" name="parentesco" required maxlength="50" class="form-control mb-2">
                    @error('parentesco')<div class="text-danger">{{ $message }}</div>@enderror
                    <button class="btn btn-school btn-sm">Vincular cuenta existente</button>
                </form>
            @endif
        </div>
    @endforeach
    {{ $users->links() }}
    <h2 class="h5 mt-4">Crear cuenta si no existe</h2>
    <p>Busca primero para evitar duplicados. Una persona puede tener varios estudiantes; no necesita una cuenta por estudiante.</p>
    <form method="POST" action="{{ route('encargados.accounts.store', $estudiante) }}" class="row g-3">
        @csrf
        @foreach(['codigo_usuario' => 'Código único', 'nombre' => 'Nombre completo', 'telefono' => 'Teléfono', 'correo' => 'Correo (opcional)', 'parentesco' => 'Parentesco', 'password' => 'Contraseña inicial (mínimo 12 caracteres)', 'password_confirmation' => 'Confirmar contraseña inicial'] as $field => $label)
            <div class="col-md-6">
                <label for="new-{{ $field }}" class="form-label">{{ $label }}</label>
                <input id="new-{{ $field }}" name="{{ $field }}" type="{{ str_starts_with($field, 'password') ? 'password' : ($field === 'correo' ? 'email' : 'text') }}" value="{{ str_starts_with($field, 'password') ? '' : old($field) }}" autocomplete="{{ str_starts_with($field, 'password') ? 'new-password' : 'off' }}" class="form-control" @required($field !== 'correo')>
                @error($field)<div class="text-danger">{{ $message }}</div>@enderror
            </div>
        @endforeach
        <div><button class="btn btn-school">Crear cuenta y vincular</button></div>
    </form>
</main>
@endsection
