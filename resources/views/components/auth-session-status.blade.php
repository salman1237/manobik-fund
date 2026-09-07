@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-primary-dark']) }}>
        {{ $status }}
    </div>
@endif
