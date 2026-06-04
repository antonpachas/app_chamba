<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#003874">
    <meta name="description" content="BuscaPE — Encuentra negocios y profesionales cerca de ti.">
    <meta name="application-name" content="BuscaPE">

    <title>BuscaPE — Negocios cerca de ti</title>

    <link rel="sitemap" type="application/xml" href="{{ url('/sitemap.xml') }}">
    <link rel="icon"              type="image/png" sizes="16x16"  href="{{ asset('img/icon-16.png') }}">
    <link rel="icon"              type="image/png" sizes="32x32"  href="{{ asset('img/icon-32.png') }}">
    <link rel="icon"              type="image/png" sizes="192x192" href="{{ asset('img/icon-192.png') }}">
    <link rel="apple-touch-icon"  sizes="180x180"  href="{{ asset('img/icon-180.png') }}">
    <link rel="manifest" href="{{ route('chamba.manifest') }}">

    <meta property="og:type"        content="website">
    <meta property="og:site_name"   content="BuscaPE">
    <meta property="og:title"       content="BuscaPE — Negocios cerca de ti">
    <meta property="og:description" content="Directorio de negocios y profesionales en Perú. Busca por ubicación y contacta directo.">
    <meta property="og:image"       content="{{ asset('img/icon-512.png') }}">
    <meta name="twitter:card"       content="summary">
    <meta name="twitter:title"      content="BuscaPE — Negocios cerca de ti">
    <meta name="twitter:image"      content="{{ asset('img/icon-512.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:wght@600;700&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:wght@600;700&display=swap"
          rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap"
          rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/main.js'])

    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        body { font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        .safe-area-pb { padding-bottom: env(safe-area-inset-bottom); }
    </style>
</head>
<body class="min-h-screen bg-[#f8f9ff] text-[#0b1c30] antialiased">
    <div id="chamba-root" class="min-h-screen"></div>

    @php
        $platform = [
            'provider_public_profile' => (bool) chamba_setting('providers.public_profile_enabled', true),
            'provider_show_contact'   => (bool) chamba_setting('providers.show_contact_on_public_profile', true),
            'search_grid_columns_sm'  => max(1, min(2, (int) chamba_setting('ui.search_grid_columns_sm', 1))),
            'search_grid_columns_md'  => max(1, min(4, (int) chamba_setting('ui.search_grid_columns_md', 2))),
        ];
    @endphp

    <script>
        window.CHAMBA_API_BASE    = @json(rtrim(url('/api/v1'), '/'));
        window.CHAMBA_APP_URL     = @json(rtrim(url('/'), '/'));
        window.CHAMBA_PLATFORM    = @json($platform);
    </script>
</body>
</html>
