<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#003874">
    <title>Chamba — Web</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/chamba-app.js'])
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="min-h-screen bg-[#f8f9ff] text-[#0b1c30] antialiased" style="font-family: Inter, ui-sans-serif, system-ui, sans-serif">
    <div id="chamba-root" class="min-h-screen"></div>
    <script>
        window.CHAMBA_API_BASE = @json(rtrim(url('/api/v1'), '/'));
        window.CHAMBA_HOME_URL = @json(rtrim(url('/'), '/'));
        window.CHAMBA_LANDING_URL = @json(rtrim(url('/'), '/'));
        window.CHAMBA_APP_URL = @json(url('/app'));
    </script>
</body>
</html>
