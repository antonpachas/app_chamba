<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminPasswordResetMail;
use App\Models\User;
use App\Services\UserModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        $data = collect($paginator->items())->map(fn (User $u) => $this->toAdminRow($u));

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
}
