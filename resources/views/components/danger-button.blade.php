<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-danger border border-transparent rounded-xl font-semibold text-sm text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-danger focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
