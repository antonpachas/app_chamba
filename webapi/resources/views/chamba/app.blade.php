<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#003874">
    <meta name="description" content="Chamba — Encuentra plomeros, electricistas, carpinteros y más. Servicios locales con reputación y precios claros.">
    <meta name="application-name" content="Chamba">

    <title>Chamba — Encuentra al experto ideal</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/chamba-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('img/chamba-icon.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('img/chamba-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Chamba">
    <meta property="og:title" content="Chamba — Encuentra al experto ideal">
    <meta property="og:description" content="Plomeros, electricistas, carpinteros y más. Servicios locales con reputación y precios claros.">
    <meta property="og:image" content="{{ asset('img/chamba-icon.png') }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Chamba — Encuentra al experto ideal">
    <meta name="twitter:image" content="{{ asset('img/chamba-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">

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
        $chambaFeatures = [
            'escrow' => (bool) chamba_setting('features.escrow', config('chamba.features.escrow')),
            'subscriptions' => (bool) chamba_setting('features.subscriptions', config('chamba.features.subscriptions')),
        ];

        $chambaPricing = \Illuminate\Support\Facades\Cache::remember('chamba.pricing.public.v1', 600, function () {
            try {
                $providerPro = \App\Models\SubscriptionPlan::where('code', 'provider_pro')->first();
                $clientPremium = \App\Models\SubscriptionPlan::where('code', 'client_premium')->first();
                $providerFree = \App\Models\SubscriptionPlan::where('code', 'provider_free')->first();
                return [
                    'provider_pro_price' => (float) ($providerPro?->price ?? config('chamba.subscriptions.provider.pro_price')),
                    'client_premium_price' => (float) ($clientPremium?->price ?? config('chamba.subscriptions.client.premium_price')),
                    'free_contacts_per_month' => (int) ($providerFree?->features['contacts_per_month'] ?? config('chamba.subscriptions.provider.free_contacts_per_month')),
                    'currency' => $providerPro?->currency ?? config('chamba.subscriptions.currency'),
                ];
            } catch (\Throwable $e) {
                return [
                    'provider_pro_price' => (float) config('chamba.subscriptions.provider.pro_price'),
                    'client_premium_price' => (float) config('chamba.subscriptions.client.premium_price'),
                    'free_contacts_per_month' => (int) config('chamba.subscriptions.provider.free_contacts_per_month'),
                    'currency' => config('chamba.subscriptions.currency'),
                ];
            }
        });
    @endphp
    <script>
        window.CHAMBA_API_BASE = @json(rtrim(url('/api/v1'), '/'));
        window.CHAMBA_LANDING_URL = @json(rtrim(url('/'), '/'));
        window.CHAMBA_APP_URL = @json(url('/app'));
        window.CHAMBA_FEATURES = @json($chambaFeatures);
        window.CHAMBA_PRICING = @json($chambaPricing);
    </script>
</body>
</html>
