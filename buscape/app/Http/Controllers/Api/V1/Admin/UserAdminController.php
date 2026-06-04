<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminPasswordResetMail;
use App\Models\Favorite;
use App\Models\ProviderService;
use App\Models\ProviderVisibilityEvent;
use App\Models\Review;
use App\Models\ServicePayment;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestMessage;
use App\Models\User;
use App\Services\UserModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class UserAdminController extends Controller
{
    public function __construct(
        private readonly UserModerationService $moderation,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $role = (string) $request->query('role', 'all');
        $status = (string) $request->query('status', 'all');

        $query = User::query()
            ->with('providerProfile:id,user_id,business_name')
            ->where('role', '!=', 'admin')
            ->orderByDesc('id');

        if ($role !== 'all' && in_array($role, ['cliente', 'proveedor'], true)) {
            $query->where('role', $role);
        }

        if ($status !== 'all' && in_array($status, ['activo', 'suspendido'], true)) {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('full_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $paginator = $query->paginate(min(50, max(10, (int) $request->query('per_page', 20))));

        $rows = collect($paginator->items())->map(fn (User $u) => $this->toAdminRow($u))->values();
        $riskByUser = $this->buildRiskMap($rows);
        $data = $rows->map(function (array $row) use ($riskByUser): array {
            $row['risk'] = $riskByUser[(int) $row['id']] ?? $this->emptyRisk();
            return $row;
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function suspend(Request $request, int $user): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'hide_listings' => ['sometimes', 'boolean'],
        ]);

        $target = User::query()->with('providerProfile')->findOrFail($user);
        try {
            $result = $this->moderation->suspend(
                $target,
                $request->user(),
                $data['reason'],
                (bool) ($data['hide_listings'] ?? true),
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $emailNote = $result['email_sent']
            ? ' Se notificó por correo al usuario.'
            : ' No se pudo enviar el correo (revisa MAIL_* en el servidor).';

        return response()->json([
            'message' => 'Cuenta deshabilitada. El usuario no podrá iniciar sesión.'.$emailNote,
            'email_sent' => $result['email_sent'],
            'data' => $this->toAdminRow($result['user']),
        ]);
    }

    public function resetPassword(Request $request, int $user): JsonResponse
    {
        $target = User::query()->findOrFail($user);

        if ($target->role === 'admin') {
            return response()->json(['message' => 'No puedes reiniciar la contraseña de otro administrador.'], 422);
        }

        if (! filter_var($target->email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'El usuario no tiene un correo válido.'], 422);
        }

        $plain = Str::password(12, symbols: false);

        $target->password_hash = Hash::make($plain);
        $target->save();
        $target->tokens()->delete();

        $emailSent = false;
        try {
            Mail::to($target->email)->send(new AdminPasswordResetMail($target, $plain));
            $emailSent = true;
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar correo de contraseña reiniciada', [
                'user_id' => $target->id,
                'error' => $e->getMessage(),
            ]);
        }

        if (! $emailSent) {
            return response()->json([
                'message' => 'Contraseña actualizada, pero no se pudo enviar el correo. Configura el servidor de correo (MAIL_*).',
                'email_sent' => false,
            ], 422);
        }

        return response()->json([
            'message' => 'Contraseña reiniciada. Se envió la nueva contraseña temporal al correo del usuario.',
            'email_sent' => true,
        ]);
    }

    public function activate(int $user): JsonResponse
    {
        $target = User::query()->with('providerProfile')->findOrFail($user);
        $updated = $this->moderation->activate($target);

        return response()->json([
            'message' => 'Cuenta reactivada.',
            'data' => $this->toAdminRow($updated),
        ]);
    }

    public function activity(int $user): JsonResponse
    {
        $target = User::query()->with('providerProfile')->findOrFail($user);
        $profileId = (int) ($target->providerProfile?->id ?? 0);
        $userRow = $this->toAdminRow($target);
        $risk = $this->buildRiskMap(collect([$userRow]))[(int) $target->id] ?? $this->emptyRisk();
        $messagesTotal = (int) ServiceRequestMessage::query()
            ->where('user_id', (int) $target->id)
            ->count();
        $messagesCountSub = ServiceRequestMessage::query()
            ->select('service_request_id', DB::raw('COUNT(*) as total'))
            ->groupBy('service_request_id');

        $clientRequests = ServiceRequest::query()
            ->with('providerService:id,title,provider_profile_id')
            ->leftJoinSub($messagesCountSub, 'msg_count', function ($join): void {
                $join->on('msg_count.service_request_id', '=', 'service_requests.id');
            })
            ->where('client_user_id', (int) $target->id)
            ->latest('id')
            ->limit(150)
            ->get([
                'service_requests.id',
                'service_requests.provider_service_id',
                'service_requests.status',
                'service_requests.contact_channel',
                'service_requests.created_at',
                DB::raw('COALESCE(msg_count.total, 0) as messages_count'),
            ]);

        $providerRequests = $profileId > 0
            ? ServiceRequest::query()
                ->join('provider_services as ps', 'ps.id', '=', 'service_requests.provider_service_id')
                ->leftJoinSub($messagesCountSub, 'msg_count', function ($join): void {
                    $join->on('msg_count.service_request_id', '=', 'service_requests.id');
                })
                ->where('ps.provider_profile_id', $profileId)
                ->orderByDesc('service_requests.id')
                ->limit(150)
                ->get([
                    'service_requests.id',
                    'service_requests.client_user_id',
                    'service_requests.provider_service_id',
                    'service_requests.status',
                    'service_requests.contact_channel',
                    'service_requests.created_at',
                    'ps.title as provider_service_title',
                    DB::raw('COALESCE(msg_count.total, 0) as messages_count'),
                ])
            : collect();

        $reviews = Review::query()
            ->where('client_user_id', (int) $target->id)
            ->latest('id')
            ->limit(100)
            ->get(['id', 'service_request_id', 'provider_profile_id', 'rating', 'comment', 'created_at']);

        $favoritesQ = Favorite::query()
            ->where('user_id', (int) $target->id)
            ->latest('id')
            ->limit(100);
        $favoriteColumns = ['id', 'provider_profile_id', 'created_at'];
        if (Schema::hasColumn('favorites', 'provider_service_id')) {
            $favoriteColumns[] = 'provider_service_id';
        }
        $favorites = $favoritesQ->get($favoriteColumns);

        $listings = $profileId > 0
            ? ProviderService::query()
                ->where('provider_profile_id', $profileId)
                ->latest('id')
                ->limit(200)
                ->get(['id', 'title', 'is_active', 'admin_hidden', 'published_at', 'expires_at', 'created_at'])
            : collect();

        $visibility = $profileId > 0
            ? ProviderVisibilityEvent::query()
                ->where('provider_profile_id', $profileId)
                ->select('source', DB::raw('COUNT(*) as total'))
                ->groupBy('source')
                ->get()
            : collect();

        $payments = collect();
        if (Schema::hasTable('service_payments')) {
            $payments = ServicePayment::query()
                ->where('client_user_id', (int) $target->id)
                ->orWhere('provider_profile_id', $profileId > 0 ? $profileId : -1)
                ->latest('id')
                ->limit(120)
                ->get(['id', 'service_request_id', 'amount', 'status', 'payment_method', 'created_at']);
        }

        return response()->json([
            'data' => [
                'user' => $this->toAdminRow($target),
                'risk' => $risk,
                'summary' => [
                    'client_requests_total' => $clientRequests->count(),
                    'provider_requests_total' => $providerRequests->count(),
                    'messages_total' => $messagesTotal,
                    'reviews_total' => $reviews->count(),
                    'favorites_total' => $favorites->count(),
                    'listings_total' => $listings->count(),
                    'payments_total' => $payments->count(),
                ],
                'client_requests' => $clientRequests,
                'provider_requests' => $providerRequests,
                'reviews' => $reviews,
                'favorites' => $favorites,
                'listings' => $listings,
                'visibility' => $visibility,
                'payments' => $payments,
            ],
        ]);
    }

    public function requestMessages(int $user, int $serviceRequest): JsonResponse
    {
        $target = User::query()->with('providerProfile')->findOrFail($user);
        $profileId = (int) ($target->providerProfile?->id ?? 0);

        $request = ServiceRequest::query()
            ->join('provider_services as ps', 'ps.id', '=', 'service_requests.provider_service_id')
            ->where('service_requests.id', $serviceRequest)
            ->where(function ($q) use ($target, $profileId): void {
                $q->where('service_requests.client_user_id', (int) $target->id);
                if ($profileId > 0) {
                    $q->orWhere('ps.provider_profile_id', $profileId);
                }
            })
            ->select([
                'service_requests.id',
                'service_requests.client_user_id',
                'service_requests.provider_service_id',
                'service_requests.status',
                'service_requests.created_at',
                'ps.title as service_title',
            ])
            ->firstOrFail();

        $messages = ServiceRequestMessage::query()
            ->where('service_request_id', $serviceRequest)
            ->orderBy('created_at')
            ->limit(500)
            ->get(['id', 'service_request_id', 'user_id', 'author_role', 'body', 'created_at']);

        return response()->json([
            'data' => [
                'service_request' => $request,
                'messages' => $messages,
            ],
        ]);
    }

    private function toAdminRow(User $u): array
    {
        return [
            'id' => $u->id,
            'full_name' => $u->full_name,
            'email' => $u->email,
            'phone' => $u->phone,
            'role' => $u->role,
            'status' => $u->status,
            'suspended_at' => $u->suspended_at,
            'suspended_reason' => $u->suspended_reason,
            'created_at' => $u->created_at,
            'provider_profile' => $u->providerProfile ? [
                'id' => $u->providerProfile->id,
                'business_name' => $u->providerProfile->business_name,
            ] : null,
        ];
    }

    private function emptyRisk(): array
    {
        return [
            'score' => 0,
            'level' => 'bajo',
            'flags' => [],
            'metrics' => [
                'requests_7d' => 0,
                'messages_7d' => 0,
                'disputes_total' => 0,
                'payments_total' => 0,
            ],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string,mixed>>  $rows
     * @return array<int, array<string,mixed>>
     */
    private function buildRiskMap($rows): array
    {
        $userIds = $rows->pluck('id')->map(fn ($v) => (int) $v)->filter()->values();
        if ($userIds->isEmpty()) {
            return [];
        }

        $profileByUser = $rows
            ->mapWithKeys(fn (array $r): array => [(int) $r['id'] => (int) ($r['provider_profile']['id'] ?? 0)]);
        $profileIds = $profileByUser->values()->filter(fn ($v) => $v > 0)->values();

        $clientRequests7d = ServiceRequest::query()
            ->select('client_user_id', DB::raw('COUNT(*) as total'))
            ->whereIn('client_user_id', $userIds)
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('client_user_id')
            ->pluck('total', 'client_user_id');

        $providerRequests7d = collect();
        if ($profileIds->isNotEmpty()) {
            $providerRequests7d = ServiceRequest::query()
                ->join('provider_services as ps', 'ps.id', '=', 'service_requests.provider_service_id')
                ->join('provider_profiles as pp', 'pp.id', '=', 'ps.provider_profile_id')
                ->select('pp.user_id', DB::raw('COUNT(*) as total'))
                ->whereIn('pp.id', $profileIds)
                ->where('service_requests.created_at', '>=', now()->subDays(7))
                ->groupBy('pp.user_id')
                ->pluck('total', 'pp.user_id');
        }

        $messages7d = ServiceRequestMessage::query()
            ->select('user_id', DB::raw('COUNT(*) as total'))
            ->whereIn('user_id', $userIds)
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $disputesByClient = ServiceRequest::query()
            ->select('client_user_id', DB::raw('COUNT(*) as total'))
            ->whereIn('client_user_id', $userIds)
            ->where('status', 'disputado')
            ->groupBy('client_user_id')
            ->pluck('total', 'client_user_id');

        $disputesByProvider = collect();
        if ($profileIds->isNotEmpty()) {
            $disputesByProvider = ServiceRequest::query()
                ->join('provider_services as ps', 'ps.id', '=', 'service_requests.provider_service_id')
                ->join('provider_profiles as pp', 'pp.id', '=', 'ps.provider_profile_id')
                ->select('pp.user_id', DB::raw('COUNT(*) as total'))
                ->whereIn('pp.id', $profileIds)
                ->where('service_requests.status', 'disputado')
                ->groupBy('pp.user_id')
                ->pluck('total', 'pp.user_id');
        }

        $paymentsByUser = collect();
        if (Schema::hasTable('service_payments')) {
            $paymentsByUser = ServicePayment::query()
                ->select('client_user_id as uid', DB::raw('COUNT(*) as total'))
                ->whereIn('client_user_id', $userIds)
                ->groupBy('client_user_id')
                ->pluck('total', 'uid');
        }

        $out = [];
        foreach ($rows as $row) {
            $uid = (int) $row['id'];
            $score = 0;
            $flags = [];
            $req7 = (int) ($clientRequests7d[$uid] ?? 0) + (int) ($providerRequests7d[$uid] ?? 0);
            $msg7 = (int) ($messages7d[$uid] ?? 0);
            $disp = (int) ($disputesByClient[$uid] ?? 0) + (int) ($disputesByProvider[$uid] ?? 0);
            $pay = (int) ($paymentsByUser[$uid] ?? 0);

            if (($row['status'] ?? '') === 'suspendido') {
                $score += 40;
                $flags[] = 'cuenta_suspendida';
            }
            if ($req7 >= 20) {
                $score += 20;
                $flags[] = 'alto_volumen_solicitudes_7d';
            } elseif ($req7 >= 10) {
                $score += 10;
            }
            if ($msg7 >= 40) {
                $score += 20;
                $flags[] = 'alto_volumen_mensajes_7d';
            } elseif ($msg7 >= 20) {
                $score += 10;
            }
            if ($disp > 0) {
                $score += min(24, $disp * 8);
                $flags[] = 'historial_disputas';
            }

            $level = $score >= 60 ? 'alto' : ($score >= 30 ? 'medio' : 'bajo');
            $out[$uid] = [
                'score' => min(100, $score),
                'level' => $level,
                'flags' => $flags,
                'metrics' => [
                    'requests_7d' => $req7,
                    'messages_7d' => $msg7,
                    'disputes_total' => $disp,
                    'payments_total' => $pay,
                ],
            ];
        }

        return $out;
    }
}
