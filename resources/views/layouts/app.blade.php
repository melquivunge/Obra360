<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        {{-- A fonte da aplicação (Instrument Sans) é descarregada da Bunny na
             build pelo laravel-vite-plugin e servida de public/build — nada é
             pedido a um CDN em tempo de execução. O <link> para
             fonts.bunny.net que estava aqui era um resto do esqueleto do
             Breeze, apontava a uma fonte (Figtree) que o desenho não usa, e
             seria a única origem externa que a Content Security Policy teria
             de autorizar. --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
