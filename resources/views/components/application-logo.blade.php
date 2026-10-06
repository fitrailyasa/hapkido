@if (config('app.logo'))
    <img src="{{ config('app.logo') }}" alt="{{ config('app.short_name', config('app.name')) }}"
        style="object-fit: contain;" {{ $attributes }} />
@else
    @include('components.application-logo-default')
@endif
