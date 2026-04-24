<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chamba</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gradient-to-br from-teal-700 via-teal-600 to-emerald-800 text-white antialiased flex flex-col items-center justify-center p-6">
    <div class="max-w-md text-center space-y-6">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/15 ring-1 ring-white/25">
            <svg class="w-9 h-9" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z"/></svg>
        </div>
        <h1 class="text-3xl font-extrabold tracking-tight">Chamba</h1>
        <p class="text-lg text-white/90 leading-relaxed">
            Servicios locales cerca de ti. Usa la aplicación web o la app móvil con la misma cuenta.
        </p>
        <a href="{{ url('/app') }}"
           class="inline-flex items-center justify-center w-full rounded-xl bg-white text-teal-800 font-bold py-3.5 px-6 shadow-lg hover:bg-teal-50 transition">
            Abrir aplicación web
        </a>
        <p class="text-sm text-white/70">
            API para integraciones: <code class="bg-black/20 px-1.5 py-0.5 rounded text-white/90">{{ url('/api/v1') }}</code>
        </p>
    </div>
</body>
</html>
