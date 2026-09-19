@extends('layouts.app')

@section('title', 'Resumen mensual de asistencia')

@section('content')
<main class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
        <div>
            <a href="{{ route('asistencia.index', ['grupo_id' => $group?->id]) }}" class="btn btn-link px-0">← Volver a asistencia diaria</a>
            <h1 class="h2 fw-bold mb-1">Resumen mensual</h1>
            <p class="text-secondary mb-0">Totales por estudiante entre {{ $start->format('d/m/Y') }} y {{ $end->format('d/m/Y') }}.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('asistencia.monthly') }}" class="card panel-card rounded-4 mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="mes">Mes</label>
                <input id="mes" type="month" name="mes" value="{{ $month }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="grupo_id">Grupo</label>
                <select id="grupo_id" name="grupo_id" class="form-select" required>
                    @foreach ($groups as $option)
                        <option value="{{ $option->id }}" @selected($group?->id === $option->id)>
                            {{ $option->nombre_completo }} · {{ $option->docente->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-school w-100">Consultar</button></div>
        </div>
    </form>

    @if (! $group)
        <div class="alert alert-info">No hay grupos disponibles para tu cuenta.</div>
    @else
        <div class="card panel-card rounded-4 overflow-hidden">
            <div class="card-header bg-white p-4"><h2 class="h5 fw-bold mb-0">{{ $group->nombre_completo }}</h2></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Estudiante</th><th>Presente</th><th>Ausente</th><th>Tarde</th><th>Justificada</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($summary as $row)
                            <tr>
                                <td>{{ $row['estudiante']->nombre_completo }}</td>
                                <td>{{ $row['presente'] }}</td>
                                <td>{{ $row['ausente'] }}</td>
                                <td>{{ $row['tarde'] }}</td>
                                <td>{{ $row['justificada'] }}</td>
                                <td class="fw-semibold">{{ $row['total'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-secondary py-5">No hay registros para este mes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</main>
@endsection
