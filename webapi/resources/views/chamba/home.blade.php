<!DOCTYPE html>
<html class="light scroll-smooth" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Chamba - Encuentra al experto ideal</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
    <script id="tailwind-config">
        tailwind.config = {
          darkMode: "class",
          theme: {
            extend: {
              "colors": {
                      "surface-tint": "#2e5ea5",
                      "on-secondary-container": "#602500",
                      "secondary": "#9f4200",
                      "on-primary-fixed-variant": "#08458b",
                      "outline": "#737782",
                      "on-secondary": "#ffffff",
                      "on-tertiary-fixed-variant": "#005228",
                      "primary-fixed": "#d6e3ff",
                      "secondary-fixed-dim": "#ffb692",
                      "on-background": "#0b1c30",
                      "on-primary-container": "#a3c3ff",
                      "on-tertiary-fixed": "#00210c",
                      "secondary-fixed": "#ffdbcb",
                      "surface-variant": "#d3e4fe",
                      "error": "#ba1a1a",
                      "inverse-on-surface": "#eaf1ff",
                      "primary-container": "#1a4f95",
                      "surface-container-low": "#eff4ff",
                      "on-secondary-fixed-variant": "#793100",
                      "on-error-container": "#93000a",
                      "on-tertiary": "#ffffff",
                      "outline-variant": "#c3c6d2",
                      "on-primary-fixed": "#001b3e",
                      "primary-fixed-dim": "#aac7ff",
                      "on-error": "#ffffff",
                      "tertiary": "#00431f",
                      "on-tertiary-container": "#5cda87",
                      "tertiary-fixed": "#7efba4",
                      "on-primary": "#ffffff",
                      "surface-container-highest": "#d3e4fe",
                      "surface-dim": "#cbdbf5",
                      "surface": "#f8f9ff",
                      "error-container": "#ffdad6",
                      "inverse-primary": "#aac7ff",
                      "surface-container-high": "#dce9ff",
                      "on-surface-variant": "#424751",
                      "background": "#f8f9ff",
                      "on-surface": "#0b1c30",
                      "tertiary-container": "#005d2e",
                      "inverse-surface": "#213145",
                      "tertiary-fixed-dim": "#61de8a",
                      "surface-bright": "#f8f9ff",
                      "surface-container-lowest": "#ffffff",
                      "on-secondary-fixed": "#341100",
                      "surface-container": "#e5eeff",
                      "primary": "#003874",
                      "secondary-container": "#ff7a2b"
              },
              "borderRadius": {
                      "DEFAULT": "0.25rem",
                      "lg": "0.5rem",
                      "xl": "0.75rem",
                      "full": "9999px"
              },
              "spacing": {
                      "xl": "32px",
                      "lg": "24px",
                      "base": "4px",
                      "md": "16px",
                      "sm": "8px",
                      "xs": "4px",
                      "gutter": "16px",
                      "margin": "20px"
              },
              "fontFamily": {
                      "h3": ["Inter"],
                      "h2": ["Inter"],
                      "body-sm": ["Inter"],
                      "body-md": ["Inter"],
                      "body-lg": ["Inter"],
                      "label-sm": ["Inter"],
                      "h1": ["Inter"],
                      "label-md": ["Inter"]
              },
              "fontSize": {
                      "h3": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                      "h2": ["24px", {"lineHeight": "32px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                      "body-sm": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                      "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                      "body-lg": ["18px", {"lineHeight": "28px", "fontWeight": "400"}],
                      "label-sm": ["12px", {"lineHeight": "16px", "fontWeight": "500"}],
                      "h1": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                      "label-md": ["14px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}]
              }
            },
          },
        }
      </script>
</head>
<body class="bg-background font-body-md text-on-surface">
<!-- TopNavBar -->
<header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 shadow-sm sticky top-0 z-50 transition-colors duration-200">
<nav class="flex justify-between items-center w-full px-4 h-16 max-w-7xl mx-auto">
<div class="flex items-center gap-8">
<a class="text-2xl font-black text-blue-900 dark:text-blue-400 tracking-tighter" href="{{ url('/') }}">Chamba</a>
<div class="hidden md:flex gap-6">
<a class="font-['Inter'] text-sm font-medium tracking-tight text-blue-900 dark:text-blue-400 border-b-2 border-blue-900 dark:border-blue-400 pb-1" href="#buscar">Buscar servicios</a>
<a class="font-['Inter'] text-sm font-medium tracking-tight text-slate-500 dark:text-slate-400 hover:text-blue-700 dark:hover:text-blue-300" href="#como-funciona">Cómo funciona</a>
</div>
</div>
<div class="flex items-center gap-4">
<a href="{{ url('/app') }}?cuenta=proveedor" class="hidden lg:block text-sm font-semibold text-primary hover:underline transition-all">Cambiar a proveedor</a>
<div class="flex items-center gap-2">
<button type="button" class="p-2 rounded-full hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-95 transition-all" onclick="document.getElementById('buscar')?.scrollIntoView({behavior:'smooth'})" aria-label="Ir a búsqueda">
<span class="material-symbols-outlined text-slate-600">location_on</span>
</button>
<a href="{{ url('/app') }}" class="p-2 rounded-full hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-95 transition-all inline-flex items-center justify-center" aria-label="Ir a la aplicación (notificaciones)">
<span class="material-symbols-outlined text-slate-600">notifications</span>
</a>
</div>
<a href="{{ url('/app') }}" class="inline-block" title="Entrar"><img alt="" class="w-8 h-8 rounded-full border border-slate-200" src="https://lh3.googleusercontent.com/aida-public/AB6AXuC0T2MD5rC2cLmGilO-DAqsj2VQkFDjRZCy1ZCodhwiwd9hFigQfPAKb8Rgx-kOYa3UJH3WYoqgTlplLjjcw5aYuChToENtcMAw8gNV-CRA0asqGOYIqRZU4-3671e5HAgX5nsSeNfcNtDewWNAQR4B5yYoMFoCAzJJKXuogkM8gBgU0RwxLlTrmT3vDKxcl-M_4dYt8cq1bq8h_CjKRban3Z9yThSySDDTALzCcu-EEnWfo3oFEKV6HlCsw7HyXFu8KHkzXcPDKw"/></a>
</div>
</nav>
</header>
<main class="max-w-7xl mx-auto px-4 md:px-8 pb-24">
<!-- Hero & Search Section -->
<section id="buscar" class="py-12 md:py-20 text-center flex flex-col items-center scroll-mt-20">
<h1 class="font-h1 text-h1 text-on-surface mb-8 max-w-2xl">Encuentra al experto ideal para tu hogar</h1>
<!-- Search Bar Component -->
<form class="w-full max-w-4xl bg-white p-4 md:p-2 rounded-xl md:rounded-full shadow-lg border border-slate-100 flex flex-col md:flex-row items-center gap-4" action="{{ url('/app') }}" method="get">
<div class="flex-1 w-full flex items-center px-4 gap-3">
<span class="material-symbols-outlined text-slate-400">search</span>
<input name="q" class="w-full border-none focus:ring-0 text-body-md placeholder:text-slate-400 bg-transparent" placeholder="¿Qué servicio necesitas?" type="search"/>
</div>
<div class="hidden md:block w-px h-8 bg-slate-200"></div>
<div class="w-full md:w-auto flex flex-col md:flex-row gap-2 px-2">
<select name="department" aria-label="Departamento" class="border-none focus:ring-0 text-label-md bg-slate-50 md:bg-transparent rounded-lg">
<option value="">Departamento</option>
<option>Lima</option>
</select>
<select name="province" aria-label="Provincia" class="border-none focus:ring-0 text-label-md bg-slate-50 md:bg-transparent rounded-lg">
<option value="">Provincia</option>
<option>Lima</option>
</select>
<select name="district" aria-label="Distrito" class="border-none focus:ring-0 text-label-md bg-slate-50 md:bg-transparent rounded-lg">
<option value="">Distrito</option>
<option>Miraflores</option>
</select>
</div>
<button type="submit" class="w-full md:w-auto bg-secondary-container text-on-secondary-container px-8 py-3 rounded-full font-bold active:scale-95 transition-all">
                    Buscar
                </button>
</form>
<p class="text-sm text-slate-500 mt-4 max-w-lg">La búsqueda te lleva a la aplicación web. Allí puedes explorar más filtros.</p>
</section>
<!-- Categories Grid -->
<section class="mb-16">
<div class="flex justify-between items-end mb-8">
<h2 class="font-h2 text-h2">Categorías populares</h2>
<a class="text-primary font-semibold text-body-sm hover:underline" href="{{ url('/app') }}">Ver todas</a>
</div>
<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
<!-- Plomería -->
<div class="group cursor-pointer flex flex-col items-center gap-3 p-6 bg-white rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-all">
<a href="{{ url('/app') }}" class="flex flex-col items-center gap-3 no-underline text-inherit w-full">
<div class="w-16 h-16 rounded-2xl bg-blue-50 flex items-center justify-center text-primary transition-colors group-hover:bg-blue-100">
<span class="material-symbols-outlined text-3xl">plumbing</span>
</div>
<span class="font-semibold text-body-md">Plomería</span>
</a>
</div>
<!-- Carpintería -->
<div class="group cursor-pointer flex flex-col items-center gap-3 p-6 bg-white rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-all">
<a href="{{ url('/app') }}" class="flex flex-col items-center gap-3 no-underline text-inherit w-full">
<div class="w-16 h-16 rounded-2xl bg-orange-50 flex items-center justify-center text-secondary transition-colors group-hover:bg-orange-100">
<span class="material-symbols-outlined text-3xl">carpenter</span>
</div>
<span class="font-semibold text-body-md">Carpintería</span>
</a>
</div>
<!-- Electricidad -->
<div class="group cursor-pointer flex flex-col items-center gap-3 p-6 bg-white rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-all">
<a href="{{ url('/app') }}" class="flex flex-col items-center gap-3 no-underline text-inherit w-full">
<div class="w-16 h-16 rounded-2xl bg-yellow-50 flex items-center justify-center text-yellow-700 transition-colors group-hover:bg-yellow-100">
<span class="material-symbols-outlined text-3xl">electrical_services</span>
</div>
<span class="font-semibold text-body-md">Electricidad</span>
</a>
</div>
<!-- Limpieza -->
<div class="group cursor-pointer flex flex-col items-center gap-3 p-6 bg-white rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-all">
<a href="{{ url('/app') }}" class="flex flex-col items-center gap-3 no-underline text-inherit w-full">
<div class="w-16 h-16 rounded-2xl bg-green-50 flex items-center justify-center text-tertiary transition-colors group-hover:bg-green-100">
<span class="material-symbols-outlined text-3xl">cleaning_services</span>
</div>
<span class="font-semibold text-body-md">Limpieza</span>
</a>
</div>
<!-- Pintura -->
<div class="group cursor-pointer flex flex-col items-center gap-3 p-6 bg-white rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-all">
<a href="{{ url('/app') }}" class="flex flex-col items-center gap-3 no-underline text-inherit w-full">
<div class="w-16 h-16 rounded-2xl bg-purple-50 flex items-center justify-center text-purple-700 transition-colors group-hover:bg-purple-100">
<span class="material-symbols-outlined text-3xl">format_paint</span>
</div>
<span class="font-semibold text-body-md">Pintura</span>
</a>
</div>
<!-- Jardinería -->
<div class="group cursor-pointer flex flex-col items-center gap-3 p-6 bg-white rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-all">
<a href="{{ url('/app') }}" class="flex flex-col items-center gap-3 no-underline text-inherit w-full">
<div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-700 transition-colors group-hover:bg-emerald-100">
<span class="material-symbols-outlined text-3xl">grass</span>
</div>
<span class="font-semibold text-body-md">Jardinería</span>
</a>
</div>
</div>
</section>
<!-- Featured Services -->
<section class="mb-20">
<div class="flex justify-between items-end mb-8">
<h2 class="font-h2 text-h2">Servicios destacados</h2>
<div class="flex gap-2">
<button type="button" class="p-2 rounded-full border border-slate-200 hover:bg-slate-50" aria-label="Anterior"><span class="material-symbols-outlined">chevron_left</span></button>
<button type="button" class="p-2 rounded-full border border-slate-200 hover:bg-slate-50" aria-label="Siguiente"><span class="material-symbols-outlined">chevron_right</span></button>
</div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
<!-- Service Card 1 -->
<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-lg transition-all group">
<a href="{{ url('/app') }}" class="block no-underline text-inherit">
<div class="relative h-48 bg-slate-200">
<img alt="Servicio de plomería" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="https://lh3.googleusercontent.com/aida-public/AB6AXuD58vS8vATXXGQKCavJtvoI2tEDSnHPl1JDc5Uj4_K3o_0dQmd1OZhVOwgRQFgGqtXzJPNahpsfcy7rp6-uElmrbMORN4JJRXzeocevMqiMGEVNKe4QXKy_Flr2eJYcFHq_sgKNbnXvQwrkQgZUQ97GeUUJSyEaZ2ZfHoByRl3f5KtYp1l7CdcxYXiQpIpMQ8eFCmpOSCs2HeukMECOqCpb_4z4RTsYSka1BR4qfslWGTgtfOtkoeEA6nuiewo_k4nLe6bm8OFMzw"/>
<div class="absolute top-3 right-3 bg-white/90 backdrop-blur px-2 py-1 rounded-lg flex items-center gap-1 shadow-sm">
<span class="material-symbols-outlined text-yellow-500 text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
<span class="text-xs font-bold">4.9</span>
</div>
</div>
<div class="p-4">
<div class="flex justify-between items-start mb-2">
<h3 class="font-bold text-on-surface">Gasfitería Eléctrica Pro</h3>
<span class="bg-blue-100 text-primary text-[10px] font-bold uppercase px-2 py-0.5 rounded-full">Destacado</span>
</div>
<div class="flex items-center gap-1 text-slate-500 text-sm mb-4">
<span class="material-symbols-outlined text-sm">location_on</span>
<span>Miraflores, Lima</span>
</div>
<div class="flex justify-between items-center border-t pt-4">
<div class="flex flex-col">
<span class="text-[10px] text-slate-400 font-bold uppercase">Desde</span>
<span class="text-lg font-black text-primary">S/ 45.00</span>
</div>
<span class="text-secondary font-bold text-sm border border-secondary px-4 py-2 rounded-lg inline-block">Cotizar</span>
</div>
</div>
</a>
</div>
<!-- Service Card 2 -->
<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-lg transition-all group">
<a href="{{ url('/app') }}" class="block no-underline text-inherit">
<div class="relative h-48 bg-slate-200">
<img alt="Carpintería" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDDHZ1qOrF9UP-VtZL_3rk8ARH9bngAO3-KRbVcB88X_2g1lq_yQtMvuRV51nIDt80F9oJYuVbh7lq1ydHYQsG3RXPPPi2cirMoJ3SKGl_g3Ky0GHXKw2kf2YbrPh73lWPSrSkBlEjyQmAefotpz2YLxqt1GfrfEPowjY5fANufQeWrz-Knz7AArvuj30TWcypBfuuUxyk68t0gJY2FXhg6pvQLUwlekhDwIs4Fc82RrGHcyACB0bg1g-JyA-XnmlF3l6wzQJNQvg"/>
<div class="absolute top-3 right-3 bg-white/90 backdrop-blur px-2 py-1 rounded-lg flex items-center gap-1 shadow-sm">
<span class="material-symbols-outlined text-yellow-500 text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
<span class="text-xs font-bold">4.8</span>
</div>
</div>
<div class="p-4">
<div class="flex justify-between items-start mb-2">
<h3 class="font-bold text-on-surface">Muebles &amp; Diseños Silva</h3>
</div>
<div class="flex items-center gap-1 text-slate-500 text-sm mb-4">
<span class="material-symbols-outlined text-sm">location_on</span>
<span>Surco, Lima</span>
</div>
<div class="flex justify-between items-center border-t pt-4">
<div class="flex flex-col">
<span class="text-[10px] text-slate-400 font-bold uppercase">Precio fijo</span>
<span class="text-lg font-black text-primary">S/ 120.00</span>
</div>
<span class="text-secondary font-bold text-sm border border-secondary px-4 py-2 rounded-lg inline-block">Ver más</span>
</div>
</div>
</a>
</div>
<!-- Service Card 3 -->
<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-lg transition-all group">
<a href="{{ url('/app') }}" class="block no-underline text-inherit">
<div class="relative h-48 bg-slate-200">
<img alt="Limpieza" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="https://lh3.googleusercontent.com/aida-public/AB6AXuATbbtBpBPf8uFWU40WQzdrYwGRsGr82rzNqGaZVwIIpsrW2N4NgO8An1wTwcaeXJs3EDlV_lYZkNIVzsSNookiuJyFTVg_i-aQ9k33GUTeukIEELcenmf3Gk4jTdTj8KjH1bjXb-pq2T0KZNjWLvvNSQ0xYUbVsDnbQ13HIW0SdqI0DJwZZkUujiD9IEZwcO5VtU7EHW5IDIekdXSpszdq2_y9tT4Nr9-_PbWG6_96QNgVh1HqP9IsS9cbBZGy7QY0qMCeZHokrA"/>
<div class="absolute top-3 right-3 bg-white/90 backdrop-blur px-2 py-1 rounded-lg flex items-center gap-1 shadow-sm">
<span class="material-symbols-outlined text-yellow-500 text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
<span class="text-xs font-bold">5.0</span>
</div>
</div>
<div class="p-4">
<div class="flex justify-between items-start mb-2">
<h3 class="font-bold text-on-surface">Limpieza Total Express</h3>
</div>
<div class="flex items-center gap-1 text-slate-500 text-sm mb-4">
<span class="material-symbols-outlined text-sm">location_on</span>
<span>San Isidro, Lima</span>
</div>
<div class="flex justify-between items-center border-t pt-4">
<div class="flex flex-col">
<span class="text-[10px] text-slate-400 font-bold uppercase">Desde</span>
<span class="text-lg font-black text-primary">S/ 60.00</span>
</div>
<span class="text-secondary font-bold text-sm border border-secondary px-4 py-2 rounded-lg inline-block">Consultar</span>
</div>
</div>
</a>
</div>
<!-- Service Card 4 -->
<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-lg transition-all group">
<a href="{{ url('/app') }}" class="block no-underline text-inherit">
<div class="relative h-48 bg-slate-200">
<img alt="Electricidad" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="https://lh3.googleusercontent.com/aida-public/AB6AXuC6rjxrOJEPdr5eGq_ft0Gaq9wCEB2OUBPR4g8Y-LcOUWihTmwPZr_O0KrUiXnitX_XfvHrX3Lj8GpFZ3r1POx4wzx6htZHNBZfCftPQGDUD0nBLkroPXG5H_KOxyEGT_fcOirAqsU8teeWPEI33faIaEp7cohwJ-mwTj_BNUfyWelF2A13LBbqJOQ3ssbH7Ga12UVPeevIYbZrscQeNpM0zpoB_aIceLFljmCrlljAMy370RsxbLtLNhhC_qMH1RbWJzG3-zNBHA"/>
<div class="absolute top-3 right-3 bg-white/90 backdrop-blur px-2 py-1 rounded-lg flex items-center gap-1 shadow-sm">
<span class="material-symbols-outlined text-yellow-500 text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
<span class="text-xs font-bold">4.7</span>
</div>
</div>
<div class="p-4">
<div class="flex justify-between items-start mb-2">
<h3 class="font-bold text-on-surface">Voltio Soluciones</h3>
</div>
<div class="flex items-center gap-1 text-slate-500 text-sm mb-4">
<span class="material-symbols-outlined text-sm">location_on</span>
<span>San Borja, Lima</span>
</div>
<div class="flex justify-between items-center border-t pt-4">
<div class="flex flex-col">
<span class="text-[10px] text-slate-400 font-bold uppercase">Cotizar</span>
<span class="text-lg font-black text-primary">Consultar</span>
</div>
<span class="text-secondary font-bold text-sm border border-secondary px-4 py-2 rounded-lg inline-block">Mensaje</span>
</div>
</div>
</a>
</div>
</div>
</section>
<!-- How it works -->
<section id="como-funciona" class="bg-surface-container-lowest rounded-3xl p-8 md:p-16 border border-slate-100 relative overflow-hidden scroll-mt-20">
<div class="relative z-10">
<h2 class="font-h2 text-h2 text-center mb-12">¿Cómo funciona Chamba?</h2>
<div class="grid grid-cols-1 md:grid-cols-3 gap-12">
<div class="flex flex-col items-center text-center">
<div class="w-16 h-16 rounded-full bg-primary text-white flex items-center justify-center font-black text-xl mb-6 shadow-lg shadow-primary/20">1</div>
<h3 class="font-h3 text-h3 mb-3">Busca el servicio</h3>
<p class="text-slate-600">Explora categorías y encuentra profesionales cerca de tu zona.</p>
</div>
<div class="flex flex-col items-center text-center">
<div class="w-16 h-16 rounded-full bg-primary text-white flex items-center justify-center font-black text-xl mb-6 shadow-lg shadow-primary/20">2</div>
<h3 class="font-h3 text-h3 mb-3">Compara y cotiza</h3>
<p class="text-slate-600">Revisa perfiles, trabajo previo y opiniones antes de contactar.</p>
</div>
<div class="flex flex-col items-center text-center">
<div class="w-16 h-16 rounded-full bg-primary text-white flex items-center justify-center font-black text-xl mb-6 shadow-lg shadow-primary/20">3</div>
<h3 class="font-h3 text-h3 mb-3">Contrata con confianza</h3>
<p class="text-slate-600">Coordina el servicio y acuerda el pago por el canal que elijas con el profesional.</p>
</div>
</div>
<div class="mt-16 text-center">
<a href="{{ url('/app') }}" class="inline-flex bg-primary text-white px-10 py-4 rounded-full font-bold text-lg hover:shadow-xl active:scale-95 transition-all no-underline items-center justify-center">
                        ¡Empieza ahora!
                    </a>
</div>
</div>
<!-- Decorative circle -->
<div class="absolute -bottom-24 -right-24 w-64 h-64 bg-primary-container/10 rounded-full blur-3xl"></div>
<div class="absolute -top-24 -left-24 w-64 h-64 bg-secondary-container/10 rounded-full blur-3xl"></div>
</section>
</main>
<!-- BottomNavBar (Mobile Only) -->
<nav class="fixed bottom-0 left-0 w-full z-50 flex justify-around items-center px-4 py-3 pb-safe bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 shadow-[0_-4px_12px_rgba(0,0,0,0.05)] md:hidden rounded-t-xl safe-area-pb">
<a class="flex flex-col items-center justify-center text-blue-900 dark:text-blue-400 bg-blue-50/50 dark:bg-blue-900/20 rounded-xl px-3 py-1 transition-all scale-100" href="#buscar">
<span class="material-symbols-outlined">search</span>
<span class="font-['Inter'] text-[10px] font-bold uppercase tracking-widest mt-1">Explorar</span>
</a>
<a class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 transition-all scale-90 active:scale-100" href="{{ url('/app') }}">
<span class="material-symbols-outlined">receipt_long</span>
<span class="font-['Inter'] text-[10px] font-bold uppercase tracking-widest mt-1">Pedidos</span>
</a>
<a class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 transition-all scale-90 active:scale-100" href="{{ url('/app') }}">
<span class="material-symbols-outlined">chat_bubble</span>
<span class="font-['Inter'] text-[10px] font-bold uppercase tracking-widest mt-1">Bandeja</span>
</a>
<a class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 transition-all scale-90 active:scale-100" href="{{ url('/app') }}">
<span class="material-symbols-outlined">person</span>
<span class="font-['Inter'] text-[10px] font-bold uppercase tracking-widest mt-1">Perfil</span>
</a>
</nav>
<!-- Footer -->
<footer class="bg-surface-container-high border-t border-slate-200 pt-16 pb-32 md:pb-16 mt-20">
<div class="max-w-7xl mx-auto px-4 md:px-8 grid grid-cols-1 md:grid-cols-4 gap-12">
<div>
<span class="text-2xl font-black text-blue-900 tracking-tighter mb-6 block">Chamba</span>
<p class="text-slate-600 text-sm leading-relaxed">Conectando el talento local con las necesidades de tu hogar. Calidad y confianza en cada servicio.</p>
</div>
<div>
<h4 class="font-bold text-on-surface mb-6 uppercase text-xs tracking-widest">Plataforma</h4>
<ul class="space-y-4 text-sm text-slate-600">
<li><a class="hover:text-primary transition-colors" href="#buscar">Buscar servicios</a></li>
<li><a class="hover:text-primary transition-colors" href="#como-funciona">Cómo funciona</a></li>
<li><a class="hover:text-primary transition-colors" href="{{ url('/app') }}?cuenta=proveedor">Soy proveedor</a></li>
</ul>
</div>
<div>
<h4 class="font-bold text-on-surface mb-6 uppercase text-xs tracking-widest">Soporte</h4>
<ul class="space-y-4 text-sm text-slate-600">
<li><a class="hover:text-primary transition-colors" href="{{ url('/app') }}">Centro de ayuda</a></li>
<li><a class="hover:text-primary transition-colors" href="{{ url('/app') }}">Términos de servicio</a></li>
<li><a class="hover:text-primary transition-colors" href="{{ url('/app') }}">Privacidad</a></li>
</ul>
</div>
<div>
<h4 class="font-bold text-on-surface mb-6 uppercase text-xs tracking-widest">Social</h4>
<div class="flex gap-4">
<a class="w-10 h-10 rounded-full bg-white flex items-center justify-center border border-slate-200 hover:border-primary hover:text-primary transition-all" href="#" aria-label="Facebook">FB</a>
<a class="w-10 h-10 rounded-full bg-white flex items-center justify-center border border-slate-200 hover:border-primary hover:text-primary transition-all" href="#" aria-label="Instagram">IG</a>
<a class="w-10 h-10 rounded-full bg-white flex items-center justify-center border border-slate-200 hover:border-primary hover:text-primary transition-all" href="#" aria-label="LinkedIn">LK</a>
</div>
</div>
</div>
<div class="max-w-7xl mx-auto px-4 md:px-8 mt-16 pt-8 border-t border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4">
<p class="text-xs text-slate-400">© {{ date('Y') }} Chamba. Todos los derechos reservados.</p>
<div class="flex gap-6 text-xs text-slate-400">
<span>Perú</span>
<span>Web v1</span>
</div>
</div>
</footer>
</body>
</html>
