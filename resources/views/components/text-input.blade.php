@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-warm-border focus:border-primary focus:ring-primary rounded-lg shadow-sm text-ink placeholder:text-ink-faint']) }}>
