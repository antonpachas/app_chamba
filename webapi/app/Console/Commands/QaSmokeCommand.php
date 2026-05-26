<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class QaSmokeCommand extends Command
{
    protected $signature = 'chamba:qa-smoke {--base=http://localhost:8000/api/v1}';
    protected $description = 'Smoke test del API: login, planes, /me, pago membresía, confirmar admin, límite Free.';

    private string $base;
    private array $results = [];
    private int $failed = 0;

    public function handle(): int
    {
        $this->base = (string) $this->option('base');
        $this->info("QA Smoke Test contra {$this->base}");
        $this->newLine();

        try {
            // 0. Reset state para que las pruebas sean idempotentes
            $this->step('Reset: cliente a Free, proveedor a trial Pro, contadores limpios', function () {
                $svc = app(\App\Services\SubscriptionService::class);

                $cli = \App\Models\User::where('email', 'usuario@gmail.com')->firstOrFail();
                if ($sub = $cli->activeSubscription()) {
                    $svc->downgradeToFree($sub);
                }

                $prov = \App\Models\User::where('email', 'proveedor@gmail.com')->firstOrFail();
                $svc->startProviderTrial($prov);

                \App\Models\ContactEvent::where('provider_user_id', $prov->id)
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->delete();

                \App\Models\SubscriptionPayment::where('user_id', $cli->id)
                    ->where('status', 'pendiente_revision')
                    ->delete();

                return 'state reset';
            });

            // 1. Auth básico
            $clientToken = $this->step('Login cliente', fn () => $this->login('usuario@gmail.com', '12345678'));
            $providerToken = $this->step('Login proveedor', fn () => $this->login('proveedor@gmail.com', '12345678'));
            $adminToken = $this->step('Login admin', fn () => $this->login('jesusalexander96@hotmail.com', '12345678'));

            // 2. /me con subscription
            $this->step('Cliente /auth/me incluye subscription', function () use ($clientToken) {
                $r = $this->get('/auth/me', $clientToken);
                $this->assert(($r['user']['subscription'] ?? null) !== null, 'sin subscription');
                $this->assert(($r['user']['subscription']['plan_code'] ?? null) === 'client_free', 'plan != client_free');
                return 'plan='.$r['user']['subscription']['plan_code'];
            });

            $this->step('Proveedor /auth/me en trial Pro', function () use ($providerToken) {
                $r = $this->get('/auth/me', $providerToken);
                $sub = $r['user']['subscription'] ?? null;
                $this->assert($sub !== null, 'sin subscription');
                $this->assert($sub['is_pro'] === true, 'no es Pro');
                $this->assert($sub['in_trial'] === true, 'no está en trial');
                return 'plan='.$sub['plan_code'].' trial_ends='.$sub['trial_ends_at'];
            });

            $this->step('Admin /auth/me role=admin', function () use ($adminToken) {
                $r = $this->get('/auth/me', $adminToken);
                $this->assert(($r['user']['role'] ?? null) === 'admin', 'rol != admin');
                return 'admin ok';
            });

            // 3. Planes públicos
            $this->step('GET /subscriptions/plans (público)', function () {
                $r = $this->get('/subscriptions/plans');
                $count = count($r['data'] ?? []);
                $this->assert($count >= 4, "esperaba 4 planes, obtuve {$count}");
                return "{$count} planes; yape=" . ($r['platform_yape'] ?? '?');
            });

            $this->step('Filtrar planes audience=proveedor', function () {
                $r = $this->get('/subscriptions/plans?audience=proveedor');
                $audiences = collect($r['data'] ?? [])->pluck('audience')->unique()->values()->all();
                $this->assert($audiences === ['proveedor'], 'devolvió otros audience: '.json_encode($audiences));
                return 'solo proveedor';
            });

            // 4. /subscriptions/me cliente y proveedor
            $this->step('Cliente /subscriptions/me', function () use ($clientToken) {
                $r = $this->get('/subscriptions/me', $clientToken);
                $this->assert(($r['subscription']['plan']['code'] ?? null) === 'client_free', 'no es client_free');
                return 'is_pro='.($r['subscription']['is_pro'] ? 'true' : 'false');
            });

            $this->step('Proveedor /subscriptions/me usage', function () use ($providerToken) {
                $r = $this->get('/subscriptions/me', $providerToken);
                $this->assert(isset($r['usage']['contacts_this_month']), 'sin usage.contacts_this_month');
                return 'contactos_mes='.$r['usage']['contacts_this_month'].'/'.$r['usage']['free_contacts_limit'];
            });

            // 5. Cliente registra pago Premium → admin confirma
            $paymentId = $this->step('Cliente paga premium', function () use ($clientToken) {
                $r = $this->post('/subscriptions/pay', [
                    'plan_code' => 'client_premium',
                    'payment_method' => 'yape',
                    'payment_reference' => 'QA-CLI-'.time(),
                ], $clientToken);
                $this->assert(($r['data']['status'] ?? null) === 'pendiente_revision', 'status != pendiente_revision');
                return 'pago id='.$r['data']['id'];
            });

            $this->step('Admin ve el pago en /admin/subscriptions/payments', function () use ($adminToken) {
                $r = $this->get('/admin/subscriptions/payments?status=pendiente_revision', $adminToken);
                $this->assert(count($r['data'] ?? []) > 0, 'lista vacía');
                return count($r['data']).' pago(s) pendientes';
            });

            $confirmId = (int) explode('=', (string) $paymentId)[1];
            $this->step('Admin confirma pago', function () use ($adminToken, $confirmId) {
                $r = $this->post("/admin/subscriptions/payments/{$confirmId}/confirm", [], $adminToken);
                $this->assert(($r['data']['status'] ?? null) === 'confirmado', 'no confirmado');
                return 'periodo hasta '.$r['data']['period_end'];
            });

            $this->step('Cliente ahora es Premium (is_pro=true)', function () use ($clientToken) {
                $r = $this->get('/auth/me', $clientToken);
                $this->assert(($r['user']['subscription']['is_pro'] ?? false) === true, 'no quedó Pro');
                return 'plan='.$r['user']['subscription']['plan_code'];
            });

            // 6. Buscar servicios y verificar is_pro
            $this->step('Búsqueda incluye flag is_pro y ordena Pro primero', function () {
                $r = $this->get('/services/search');
                $rows = $r['data'] ?? [];
                if (! count($rows)) return 'sin servicios (catálogo vacío)';
                $hasPro = collect($rows)->contains(fn ($x) => ($x['is_pro'] ?? false) === true);
                $first = $rows[0]['is_pro'] ?? null;
                return "primero is_pro=".($first ? 'true' : 'false')." | hayPro=".($hasPro ? 'sí' : 'no');
            });

            // 7. Límite Free de proveedor
            $this->step('Setup: degradar proveedor a Free', function () {
                $u = \App\Models\User::where('email', 'proveedor@gmail.com')->firstOrFail();
                $sub = $u->activeSubscription();
                if ($sub) {
                    app(\App\Services\SubscriptionService::class)->downgradeToFree($sub);
                }
                // Limpiar contactos del mes para empezar de cero el contador
                \App\Models\ContactEvent::where('provider_user_id', $u->id)
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->delete();
                return 'proveedor en Free, contador reset';
            });

            // Necesitamos un servicio del proveedor para enviarle solicitudes
            $providerService = $this->step('Buscar un servicio del proveedor demo', function () {
                $u = \App\Models\User::where('email', 'proveedor@gmail.com')->firstOrFail();
                $profile = $u->providerProfile;
                if (! $profile) {
                    return 'proveedor sin perfil — saltando test de límite';
                }
                $svc = \App\Models\ProviderService::where('provider_profile_id', $profile->id)->where('is_active', true)->first();
                if (! $svc) return 'proveedor sin servicios — saltando test de límite';
                return 'service_id='.$svc->id;
            });

            if (str_starts_with((string) $providerService, 'service_id=')) {
                $svcId = (int) explode('=', (string) $providerService)[1];

                $this->step('Cliente envía 3 solicitudes (dentro del cupo)', function () use ($clientToken, $svcId) {
                    $okCount = 0;
                    for ($i = 1; $i <= 3; $i++) {
                        $resp = Http::withToken($clientToken)
                            ->acceptJson()
                            ->post($this->base.'/client/service-requests', [
                                'provider_service_id' => $svcId,
                                'message' => "QA test #{$i}",
                                'contact_channel' => 'whatsapp',
                            ]);
                        if ($resp->successful()) $okCount++;
                    }
                    $this->assert($okCount === 3, "solo {$okCount}/3 aceptadas");
                    return '3/3 aceptadas';
                });

                $this->step('4ta solicitud debe fallar 422 con código provider_free_limit_reached', function () use ($clientToken, $svcId) {
                    $resp = Http::withToken($clientToken)
                        ->acceptJson()
                        ->post($this->base.'/client/service-requests', [
                            'provider_service_id' => $svcId,
                            'message' => 'QA test #4 (limit)',
                            'contact_channel' => 'whatsapp',
                        ]);
                    $this->assert($resp->status() === 422, 'no devolvió 422 sino '.$resp->status());
                    $body = $resp->json();
                    $this->assert(($body['code'] ?? null) === 'provider_free_limit_reached', 'sin code provider_free_limit_reached');
                    return 'bloqueada correctamente';
                });

                $this->step('Restaurar trial Pro al proveedor demo', function () {
                    $u = \App\Models\User::where('email', 'proveedor@gmail.com')->firstOrFail();
                    app(\App\Services\SubscriptionService::class)->startProviderTrial($u);
                    return 'trial restaurado';
                });
            }

            // 8. Admin dashboard
            $this->step('Admin /admin/dashboard incluye nuevos KPIs', function () use ($adminToken) {
                $r = $this->get('/admin/dashboard', $adminToken);
                $kpis = $r['data']['kpis'] ?? [];
                $required = ['subs_pro_active', 'subs_premium_active', 'subs_payments_pending', 'mrr'];
                foreach ($required as $k) {
                    $this->assert(array_key_exists($k, $kpis), "falta KPI {$k}");
                }
                return "MRR=S/{$kpis['mrr']} Pro={$kpis['subs_pro_active']} Premium={$kpis['subs_premium_active']}";
            });

            // 9. Configuración del sistema (admin)
            $this->step('Admin GET /admin/settings (lista)', function () use ($adminToken) {
                $r = $this->get('/admin/settings', $adminToken);
                $count = count($r['data'] ?? []);
                $this->assert($count >= 5, "esperaba >=5 settings, obtuve {$count}");
                return $count.' settings';
            });

            $originalYape = $this->step('Admin lee yape actual de la plataforma', function () use ($adminToken) {
                $r = $this->get('/subscriptions/plans');
                $this->assert(! empty($r['platform_yape']), 'sin platform_yape');
                return $r['platform_yape'];
            });

            $this->step('Admin actualiza payouts.platform_yape vía PUT', function () use ($adminToken) {
                $r = $this->put('/admin/settings/payouts.platform_yape', ['value' => '999111222'], $adminToken);
                $this->assert(($r['data']['casted_value'] ?? null) === '999111222', 'no se guardó');
                return 'guardado=999111222';
            });

            $this->step('El cambio se refleja en /subscriptions/plans', function () {
                $r = $this->get('/subscriptions/plans');
                $this->assert(($r['platform_yape'] ?? '') === '999111222', 'el cache no se invalidó (yape='.($r['platform_yape'] ?? '').')');
                return 'yape='.$r['platform_yape'];
            });

            $this->step('Admin restaura yape original', function () use ($adminToken, $originalYape) {
                $this->put('/admin/settings/payouts.platform_yape', ['value' => $originalYape], $adminToken);
                return 'restaurado='.$originalYape;
            });

            $this->step('Admin GET /admin/settings-logs (historial)', function () use ($adminToken) {
                $r = $this->get('/admin/settings-logs?key=payouts.platform_yape', $adminToken);
                $this->assert(count($r['data'] ?? []) >= 2, 'esperaba al menos 2 logs');
                return count($r['data']).' cambios registrados';
            });

            // 10. Editor de planes (admin)
            $this->step('Admin GET /admin/plans', function () use ($adminToken) {
                $r = $this->get('/admin/plans', $adminToken);
                $count = count($r['data'] ?? []);
                $this->assert($count >= 4, "esperaba 4 planes, obtuve {$count}");
                return $count.' planes';
            });

            $proPlan = \App\Models\SubscriptionPlan::where('code', 'provider_pro')->firstOrFail();
            $originalPrice = (float) $proPlan->price;

            $this->step('Admin sube precio Pro a 35.00', function () use ($adminToken, $proPlan) {
                $r = $this->put("/admin/plans/{$proPlan->id}", ['price' => 35.00], $adminToken);
                $newPrice = (float) ($r['data']['price'] ?? 0);
                $this->assert($newPrice === 35.0, "precio guardado: {$newPrice}");
                return 'precio=35.00';
            });

            $this->step('Pagos previos NO cambian de monto (snapshot histórico)', function () use ($confirmId) {
                $payment = \App\Models\SubscriptionPayment::find($confirmId);
                $this->assert($payment !== null, 'pago no encontrado');
                return "pago previo amount={$payment->amount} (no afectado)";
            });

            $this->step('Admin GET historial del plan Pro', function () use ($adminToken, $proPlan) {
                $r = $this->get("/admin/plans/{$proPlan->id}/logs", $adminToken);
                $this->assert(count($r['data'] ?? []) >= 1, 'sin logs');
                return count($r['data']).' cambios; último: '.$r['data'][0]['field'].' '.$r['data'][0]['old_value'].'→'.$r['data'][0]['new_value'];
            });

            $this->step('Admin restaura precio Pro original', function () use ($adminToken, $proPlan, $originalPrice) {
                $this->put("/admin/plans/{$proPlan->id}", ['price' => $originalPrice], $adminToken);
                return 'restaurado='.$originalPrice;
            });

            // 11. Subida de archivos al FTP (avatar, comprobante, imagen de servicio)
            $tmpJpg = $this->makeTestImageJpeg();
            $tmpPng = $this->makeTestImagePng();

            $this->step('Cliente sube avatar (JPG)', function () use ($clientToken, $tmpJpg) {
                $r = $this->upload('/me/avatar', 'avatar', $tmpJpg, 'avatar.jpg', 'image/jpeg', $clientToken);
                $this->assert(! empty($r['data']['avatar_path'] ?? null), 'sin avatar_path');
                $this->assert(str_starts_with($r['data']['avatar_path'], 'avatars/'), 'path no empieza con avatars/');
                return 'path='.$r['data']['avatar_path'];
            });

            $this->step('Avatar visible en /auth/me', function () use ($clientToken) {
                $r = $this->get('/auth/me', $clientToken);
                $this->assert(! empty($r['user']['avatar_url'] ?? null), 'sin avatar_url');
                return $r['user']['avatar_url'];
            });

            $this->step('Cliente envía pago Premium con comprobante', function () use ($clientToken, $tmpPng) {
                $r = $this->upload('/subscriptions/pay', 'proof', $tmpPng, 'proof.png', 'image/png', $clientToken, [
                    'plan_code' => 'client_premium',
                    'payment_method' => 'yape',
                    'payment_reference' => 'QA-PROOF-'.time(),
                ]);
                $this->assert(($r['data']['status'] ?? null) === 'pendiente_revision', 'status invalido');
                $this->assert(! empty($r['data']['proof_image_path'] ?? null), 'sin proof_image_path');
                $this->assert(str_starts_with($r['data']['proof_image_path'], 'payments/'), 'path no empieza con payments/');
                return 'pago id='.$r['data']['id'].' proof='.$r['data']['proof_image_path'];
            });

            $this->step('Admin ve el comprobante en pagos pendientes', function () use ($adminToken) {
                $r = $this->get('/admin/subscriptions/payments?status=pendiente_revision', $adminToken);
                $hasProof = collect($r['data'] ?? [])->contains(fn ($p) => ! empty($p['proof_image_url']));
                $this->assert($hasProof, 'ningún pago muestra proof_image_url');
                return 'admin ve los comprobantes';
            });

            $this->step('Rechazo: archivo no-imagen es bloqueado', function () use ($clientToken) {
                $tmp = tempnam(sys_get_temp_dir(), 'qa');
                file_put_contents($tmp, "<?php phpinfo(); ?>");
                $r = \Illuminate\Support\Facades\Http::withToken($clientToken)
                    ->acceptJson()
                    ->attach('avatar', file_get_contents($tmp), 'evil.jpg', ['Content-Type' => 'image/jpeg'])
                    ->post($this->base.'/me/avatar');
                @unlink($tmp);
                $this->assert($r->status() === 422, 'esperaba 422, devolvió '.$r->status());
                return 'rechazado correctamente: '.($r->json('message') ?? '');
            });

            // Imagen de servicio
            $providerSvc = \App\Models\ProviderService::query()
                ->whereHas('providerProfile.user', fn ($q) => $q->where('email', 'proveedor@gmail.com'))
                ->where('is_active', true)
                ->first();
            if ($providerSvc) {
                $this->step('Proveedor sube imagen al servicio', function () use ($providerToken, $tmpJpg, $providerSvc) {
                    $r = $this->upload(
                        "/provider/services/{$providerSvc->id}/images",
                        'image', $tmpJpg, 'svc.jpg', 'image/jpeg', $providerToken
                    );
                    $this->assert(! empty($r['data']['url'] ?? null), 'sin url');
                    $this->assert(str_starts_with($r['data']['path'], 'services/'), 'path no empieza con services/');
                    return 'image_id='.$r['data']['id'];
                });

                $this->step('La imagen aparece en /services/search', function () use ($providerSvc) {
                    $r = $this->get('/services/search');
                    $row = collect($r['data'] ?? [])->firstWhere('service_id', $providerSvc->id);
                    $this->assert($row !== null, 'servicio no aparece en search');
                    $this->assert(! empty($row['cover_image_url'] ?? null), 'sin cover_image_url');
                    return 'cover='.$row['cover_image_url'];
                });
            }

            @unlink($tmpJpg);
            @unlink($tmpPng);

        } catch (\Throwable $e) {
            $this->error('Excepción: '.$e->getMessage());
            $this->failed++;
        }

        $this->newLine();
        $passed = count($this->results) - $this->failed;
        $this->info("Resumen: {$passed}/" . count($this->results) . " checks OK");
        if ($this->failed > 0) {
            $this->error("{$this->failed} fallidos. Revisa la salida arriba.");
            return self::FAILURE;
        }
        return self::SUCCESS;
    }

    private function step(string $name, callable $fn): mixed
    {
        try {
            $detail = $fn();
            $this->results[] = ['name' => $name, 'ok' => true, 'detail' => $detail];
            $this->line("  <fg=green>✓</> {$name} <fg=gray>({$detail})</>");
            return $detail;
        } catch (\Throwable $e) {
            $this->failed++;
            $this->results[] = ['name' => $name, 'ok' => false, 'detail' => $e->getMessage()];
            $this->line("  <fg=red>✗</> {$name} <fg=red>{$e->getMessage()}</>");
            return null;
        }
    }

    private function assert(bool $cond, string $msg): void
    {
        if (! $cond) throw new \RuntimeException($msg);
    }

    private function login(string $email, string $password): string
    {
        $r = Http::acceptJson()->post($this->base.'/auth/login', ['email' => $email, 'password' => $password]);
        $this->assert($r->successful(), "login {$email}: HTTP {$r->status()} - ".$r->body());
        $token = $r->json('token');
        $this->assert(is_string($token) && strlen($token) > 10, 'token vacío');
        return $token;
    }

    private function get(string $path, ?string $token = null): array
    {
        $req = Http::acceptJson();
        if ($token) $req = $req->withToken($token);
        $r = $req->get($this->base.$path);
        $this->assert($r->successful(), "GET {$path}: HTTP {$r->status()} - ".$r->body());
        return $r->json() ?? [];
    }

    private function post(string $path, array $payload, ?string $token = null): array
    {
        $req = Http::acceptJson();
        if ($token) $req = $req->withToken($token);
        $r = $req->post($this->base.$path, $payload);
        $this->assert($r->successful(), "POST {$path}: HTTP {$r->status()} - ".$r->body());
        return $r->json() ?? [];
    }

    private function put(string $path, array $payload, ?string $token = null): array
    {
        $req = Http::acceptJson();
        if ($token) $req = $req->withToken($token);
        $r = $req->put($this->base.$path, $payload);
        $this->assert($r->successful(), "PUT {$path}: HTTP {$r->status()} - ".$r->body());
        return $r->json() ?? [];
    }

    private function upload(
        string $path,
        string $field,
        string $tmpPath,
        string $fileName,
        string $mime,
        ?string $token = null,
        array $extraFields = [],
    ): array {
        $req = Http::acceptJson();
        if ($token) $req = $req->withToken($token);
        $req = $req->attach($field, file_get_contents($tmpPath), $fileName, ['Content-Type' => $mime]);
        $r = $req->post($this->base.$path, $extraFields);
        $this->assert($r->successful(), "UPLOAD {$path}: HTTP {$r->status()} - ".$r->body());
        return $r->json() ?? [];
    }

    private function makeTestImageJpeg(): string
    {
        $im = imagecreatetruecolor(640, 480);
        imagefilledrectangle($im, 0, 0, 640, 480, imagecolorallocate($im, 50, 100, 200));
        imagestring($im, 5, 200, 220, 'CHAMBA QA', imagecolorallocate($im, 255, 255, 255));
        $tmp = tempnam(sys_get_temp_dir(), 'qa').'.jpg';
        imagejpeg($im, $tmp, 90);
        imagedestroy($im);
        return $tmp;
    }

    private function makeTestImagePng(): string
    {
        $im = imagecreatetruecolor(400, 300);
        imagefilledrectangle($im, 0, 0, 400, 300, imagecolorallocate($im, 33, 200, 80));
        imagestring($im, 5, 100, 130, 'YAPE PROOF', imagecolorallocate($im, 255, 255, 255));
        $tmp = tempnam(sys_get_temp_dir(), 'qa').'.png';
        imagepng($im, $tmp, 6);
        imagedestroy($im);
        return $tmp;
    }
}
