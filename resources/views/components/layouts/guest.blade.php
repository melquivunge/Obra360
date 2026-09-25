<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta name="copy-success" content="{{ __('Copiado!') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Obra360') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background-color:var(--background);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;">
    <div style="width:100%;max-width:26rem;">
        <div class="flex justify-end mb-4"><x-language-switcher /></div>
        {{ $slot }}
    </div>
</body>
</html>
