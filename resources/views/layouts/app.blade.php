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
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-warm-bg lg:flex">
            <livewire:layout.navigation />

            <div class="flex-1 min-w-0">
                <!-- Page Heading -->
                @if (isset($header))
                    <header class="bg-warm-surface border-b border-warm-border-soft">
                        <div class="max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <!-- Page Content -->
                <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
