@props(['icon', 'title', 'description', 'route'])
@php($icons = ['E' => '👥', 'A' => '🗓️', 'V' => '📊', 'T' => '📝', 'J' => '📄', 'N' => '📣', 'G' => '🏫'])

<div class="card panel-card h-100 rounded-4">
    <div class="card-body p-4">
        <span class="feature-icon mb-3" aria-hidden="true">{{ $icons[$icon] ?? $icon }}</span>
        <h2 class="h5 fw-bold">{{ $title }}</h2>
        <p class="text-secondary">{{ $description }}</p>
        <a href="{{ route($route) }}" class="btn btn-school stretched-link">Abrir sección</a>
    </div>
</div>
