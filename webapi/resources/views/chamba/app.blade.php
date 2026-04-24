<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f766e">
    <title>Chamba — Web</title>
    @vite(['resources/css/app.css', 'resources/js/chamba-app.js'])
</head>
<body class="min-h-screen bg-stone-100 text-stone-900 antialiased">
    <div id="chamba-root" class="min-h-screen"></div>
    <script>
        window.CHAMBA_API_BASE = @json(rtrim(url('/api/v1'), '/'));
        window.CHAMBA_HOME_URL = @json(rtrim(url('/'), '/'));
    </script>
</body>
</html>
