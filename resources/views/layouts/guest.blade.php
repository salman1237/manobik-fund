<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=newsreader:500,600,700|public-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="min-h-screen lg:grid lg:grid-cols-2 bg-warm-bg">
            <!-- Brand panel -->
            <div class="hidden lg:flex relative flex-col justify-between overflow-hidden p-14"
                 style="background:linear-gradient(160deg, oklch(40% 0.09 175) 0%, oklch(30% 0.07 180) 100%);">
                <svg width="600" height="600" viewBox="0 0 600 600" class="absolute -right-44 -bottom-44 opacity-35" aria-hidden="true"><circle cx="300" cy="300" r="300" fill="oklch(55% 0.13 40)"/></svg>
                <svg width="360" height="360" viewBox="0 0 360 360" class="absolute -left-28 -top-24 opacity-15" aria-hidden="true"><circle cx="180" cy="180" r="180" fill="white"/></svg>

                <a href="/" wire:navigate class="relative flex items-center gap-2.5">
                    <x-application-logo class="h-7 w-7 text-white" />
                    <span class="font-serif text-xl font-semibold text-white">Manobik Fund</span>
                </a>

                <div class="relative">
                    <p class="font-serif text-3xl font-semibold leading-snug text-white mb-5 max-w-md">
                        &ldquo;I could see exactly where every taka went &mdash; that&rsquo;s why I kept donating.&rdquo;
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full" style="background:oklch(70% 0.10 60);"></div>
                        <div>
                            <div class="text-sm font-bold text-white">Tanvir Ahmed</div>
                            <div class="text-xs text-white/75">Monthly donor since 2025</div>
                        </div>
                    </div>
                </div>

                <div class="relative flex gap-9">
                    <div>
                        <div class="font-serif text-xl font-bold text-white">318</div>
                        <div class="text-xs text-white/75">Campaigns funded</div>
                    </div>
                    <div>
                        <div class="font-serif text-xl font-bold text-white">96%</div>
                        <div class="text-xs text-white/75">Verified in 72h</div>
                    </div>
                </div>
            </div>

            <!-- Form panel -->
            <div class="flex flex-col items-center justify-center px-6 py-10">
                <a href="/" wire:navigate class="lg:hidden flex items-center gap-2 mb-8">
                    <x-application-logo class="h-7 w-7 text-accent" />
                    <span class="font-serif text-xl font-semibold text-ink">Manobik Fund</span>
                </a>

                <div class="w-full max-w-sm">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
