@if (! empty($arena))
    <a href="{{ route('display.arena', $arena) }}" target="_blank" rel="noopener"
        class="btn btn-sm btn-outline-primary{{ ! empty($class) ? ' ' . $class : '' }}"
        title="Buka display untuk {{ $arena->name }}" aria-label="Buka display untuk {{ $arena->name }}">
        <i class="bi bi-broadcast me-1"></i>{{ $label ?? 'Display' }}
    </a>
@endif
