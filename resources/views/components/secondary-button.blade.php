<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-warm-surface border border-warm-border rounded-xl font-semibold text-sm text-ink hover:bg-warm-alt focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-40 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
