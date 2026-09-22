@extends('layouts.app')
@section('content')
<main class="container py-4">
    <h1 class="h3">{{ $nombre }}</h1>
    <p>{{ $codigo }} · {{ $grupo }}</p>
    <a href="{{ route('avisos.index') }}" class="btn btn-school">Avisos y avances</a>
</main>
@endsection
