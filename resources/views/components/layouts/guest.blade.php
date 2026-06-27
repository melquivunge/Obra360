<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Obra360') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background-color:var(--background);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;">
    {{ $slot }}
</body>
</html>
