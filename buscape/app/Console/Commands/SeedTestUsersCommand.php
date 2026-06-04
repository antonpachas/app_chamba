<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\District;
use App\Models\ProviderProfile;
use App\Models\ProviderService;
use App\Models\User;
use App\Services\StoredProcedureService;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedTestUsersCommand extends Command
{
    protected $signature = 'chamba:seed-test-users
        {--password=12345678 : Contraseña a aplicar a los usuarios}';

    protected $description = 'Crea/actualiza usuarios de prueba (proveedor@gmail.com y usuario@gmail.com).';

    public function handle(StoredProcedureService $sp, SubscriptionService $subs): int
    {
        $password = (string) $this->option('password');
        if (strlen($password) < 8) {
            $this->error('La contraseña debe tener al menos 8 caracteres.');
            return self::FAILURE;
        }

        $accounts = [
            [
                'email' => 'proveedor@gmail.com',
                'name' => 'Proveedor Demo',
                'phone' => '999111222',
                'role' => 'proveedor',
            ],
            [
                'email' => 'usuario@gmail.com',
                'name' => 'Usuario Demo',
                'phone' => '999333444',
                'role' => 'cliente',
            ],
        ];

        $rows = [];
        foreach ($accounts as $acc) {
            $existing = User::query()->where('email', $acc['email'])->first();

            if ($existing) {
                $existing->password_hash = Hash::make($password);
                if (! $existing->status) {
                    $existing->status = 'activo';
                }
                $existing->save();
                $user = $existing;
                $action = 'actualizado';
            } else {
                $userId = $sp->registerUser(
                    $acc['name'],
                    $acc['email'],
                    $acc['phone'],
                    Hash::make($password),
                    $acc['role'],
                );
                $user = User::query()->findOrFail($userId);
                $action = 'creado';
            }

            if ($user->role === 'proveedor') {
                $subs->ensureSubscription($user);
                if (! $user->isPro()) {
                    $subs->startProviderTrial($user);
                }
                $this->ensureProviderProfileAndService($user);
            } else {
                $subs->ensureSubscription($user);
            }

            $sub = $user->fresh()->activeSubscription();
            $rows[] = [
                $user->id,
                $user->email,
                $user->role,
                $action,
                $sub?->plan?->name ?? '—',
                $sub?->status ?? '—',
                $sub?->trial_ends_at?->toDateString() ?? '—',
            ];
        }

        $this->info('Usuarios de prueba listos. Contraseña: '.$password);
        $this->table(
            ['ID', 'Email', 'Rol', 'Acción', 'Plan', 'Estado sub.', 'Trial hasta'],
            $rows,
        );

        return self::SUCCESS;
    }

    private function ensureProviderProfileAndService(User $user): void
    {
        $profile = ProviderProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'business_name' => 'Servicios Demo del Hogar',
                'description' => 'Proveedor demo de Chamba — atendemos plomería, electricidad y mantenimiento general.',
                'whatsapp' => '999111222',
                'contact_phone' => '999111222',
                'address_text' => 'Av. Demo 123',
                'district_id' => District::query()->value('id'),
                'is_verified' => true,
            ],
        );

        if (! $profile->providerServices()->where('is_active', true)->exists()) {
            $category = Category::query()->where('is_active', true)->orderBy('id')->first();
            if ($category) {
                ProviderService::create([
                    'provider_profile_id' => $profile->id,
                    'category_id' => $category->id,
                    'title' => 'Servicio demo · ' . $category->name,
                    'description' => 'Servicio publicado automáticamente por el seeder de pruebas.',
                    'base_price' => 80.00,
                    'price_type' => 'desde',
                    'is_active' => true,
                ]);
            }
        }
    }
}
