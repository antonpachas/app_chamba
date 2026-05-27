<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\District;
use App\Models\ProviderProfile;
use App\Models\ProviderService;
use App\Models\User;
use App\Services\ListingLifecycleService;
use App\Services\StoredProcedureService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Limpia anuncios (listings) y datos ligados; crea negocios y anuncios de demo.
 */
class BuscaPeDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->purgeListings();

        $district = District::query()->orderBy('id')->first();
        if ($district === null) {
            $this->command?->error('No hay distritos en BD. Corre migraciones primero.');

            return;
        }

        $categories = Category::query()->where('is_active', true)->orderBy('id')->limit(6)->get();
        if ($categories->isEmpty()) {
            $this->command?->error('No hay categorías activas.');

            return;
        }

        $sp = app(StoredProcedureService::class);
        $listings = app(ListingLifecycleService::class);

        $providers = $this->ensureDemoProviders($sp, (int) $district->id);

        $samples = [
            ['business' => 'Ferretería El Tornillo', 'title' => 'Ferretería y materiales de construcción', 'desc' => 'Cemento, fierros, pinturas y herramientas. Delivery en el distrito.', 'price' => 0, 'type' => 'cotizar', 'cat' => 0],
            ['business' => 'Disco Nova Club', 'title' => 'Discoteca Nova — reservas y eventos', 'desc' => 'Música en vivo los viernes. Reserva mesas por WhatsApp.', 'price' => 50, 'type' => 'desde', 'cat' => 1],
            ['business' => 'Taller Mecánico Rápido', 'title' => 'Mecánica general y mantenimiento', 'desc' => 'Cambio de aceite, frenos, diagnóstico computarizado.', 'price' => 80, 'type' => 'desde', 'cat' => 2],
            ['business' => 'Restaurante Sabor Limeño', 'title' => 'Comida criolla — menú del día', 'desc' => 'Ceviche, lomo saltado y menú ejecutivo de lunes a viernes.', 'price' => 25, 'type' => 'desde', 'cat' => 0],
            ['business' => 'Boutique Estilo PE', 'title' => 'Ropa y accesorios para toda la familia', 'desc' => 'Nueva colección temporada. Atención en tienda y delivery.', 'price' => 39, 'type' => 'desde', 'cat' => 1],
            ['business' => 'Clínica Dental Sonrisa', 'title' => 'Odontología general y estética', 'desc' => 'Limpieza, blanqueamiento y ortodoncia. Primera consulta informativa.', 'price' => 120, 'type' => 'desde', 'cat' => 2],
            ['business' => 'Gym PowerFit', 'title' => 'Gimnasio y clases grupales', 'desc' => 'Musculación, spinning y yoga. Plan mensual y anual.', 'price' => 99, 'type' => 'fijo', 'cat' => 0],
            ['business' => 'Pet Shop Huellitas', 'title' => 'Veterinaria y pet shop', 'desc' => 'Baño, vacunas y alimento premium para mascotas.', 'price' => 45, 'type' => 'desde', 'cat' => 1],
        ];

        $created = 0;
        foreach ($samples as $i => $sample) {
            $provider = $providers[$i % count($providers)];
            $profile = $provider->providerProfile;
            if ($profile === null) {
                continue;
            }

            $cat = $categories[$sample['cat'] % $categories->count()];

            $serviceId = $sp->createProviderService(
                (int) $profile->id,
                (int) $cat->id,
                $sample['title'],
                $sample['desc'],
                $sample['price'] > 0 ? (float) $sample['price'] : null,
                $sample['type'],
            );

            $listing = ProviderService::query()->findOrFail($serviceId);

            // Primer anuncio del 2.º proveedor: pausado; último del 1.º: vencido (para probar UI).
            if ($i === 1) {
                $listings->setActive($listing, $profile, $provider, false);
            } elseif ($i === count($samples) - 1) {
                $listings->publish($listing, $profile);
                $listing->expires_at = now()->subDay();
                $listing->is_active = false;
                $listing->deactivated_at = now()->subDay();
                $listing->save();
            } else {
                $listings->publish($listing, $profile);
            }

            $created++;
        }

        $this->command?->info("Listados purgados. Anuncios de demo creados: {$created}.");
        $this->command?->info('Proveedores demo: demo1@buscape.pe / demo2@buscape.pe — clave: demo1234');
        $this->command?->info('Proveedor existente: proveedor@gmail.com (si existe) también recibe anuncios.');
    }

    private function purgeListings(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'service_payments',
            'service_quotes',
            'service_request_evidence',
            'service_request_events',
            'service_requests',
            'search_events',
            'service_images',
            'provider_service_locations',
            'provider_services',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * @return array<int, User>
     */
    private function ensureDemoProviders(StoredProcedureService $sp, int $districtId): array
    {
        $defs = [
            ['email' => 'demo1@buscape.pe', 'name' => 'Demo Negocio 1', 'business' => 'Ferretería El Tornillo'],
            ['email' => 'demo2@buscape.pe', 'name' => 'Demo Negocio 2', 'business' => 'Disco Nova Club'],
        ];

        $users = [];

        foreach ($defs as $def) {
            $user = User::query()->where('email', $def['email'])->first();
            if ($user === null) {
                $userId = $sp->registerUser(
                    $def['name'],
                    $def['email'],
                    '999000111',
                    Hash::make('demo1234'),
                    'proveedor',
                );
                $user = User::query()->findOrFail($userId);
            }

            if ($user->providerProfile === null) {
                $profileId = $sp->createProviderProfile(
                    (int) $user->id,
                    $def['business'],
                    'Perfil de prueba Busca PE.',
                    '51999000111',
                    '51999000111',
                    'Av. Demo 123',
                    $districtId,
                );
                $user->load('providerProfile');
                if ($user->providerProfile === null) {
                    $user->setRelation('providerProfile', ProviderProfile::query()->find($profileId));
                }
            }

            app(\App\Services\SubscriptionService::class)->startProviderTrial($user);
            $users[] = $user->fresh(['providerProfile']);
        }

        $legacy = User::query()->where('email', 'proveedor@gmail.com')->first();
        if ($legacy !== null && $legacy->providerProfile !== null) {
            $users[] = $legacy->load('providerProfile');
        }

        return $users;
    }
}
