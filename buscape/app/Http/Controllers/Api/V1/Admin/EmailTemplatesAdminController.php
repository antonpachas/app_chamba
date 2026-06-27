<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

final class EmailTemplatesAdminController extends Controller
{
    private const SMTP_KEYS = [
        'mail.host', 'mail.port', 'mail.encryption',
        'mail.username', 'mail.password', 'mail.from_address', 'mail.from_name',
    ];

    private const TEMPLATE_EVENTS = [
        'registro_cliente', 'registro_proveedor',
        'recuperar_password', 'nuevo_contacto',
    ];

    public function __construct(private readonly SystemSettingsService $settings) {}

    public function index(): JsonResponse
    {
        $all = $this->settings->listForAdmin()->keyBy('key');

        $smtp = [];
        foreach (self::SMTP_KEYS as $key) {
            $row = $all->get($key);
            $smtp[$key] = [
                'value' => $row?->value ?? '',
                'label' => $row?->label ?? $key,
                'description' => $row?->description ?? '',
                'type' => $row?->type ?? 'string',
            ];
        }

        $templates = [];
        foreach (self::TEMPLATE_EVENTS as $event) {
            $subjectKey = "mail.template.{$event}.subject";
            $bodyKey = "mail.template.{$event}.body";
            $subjectRow = $all->get($subjectKey);
            $bodyRow = $all->get($bodyKey);
            $templates[$event] = [
                'subject' => [
                    'key' => $subjectKey,
                    'value' => $subjectRow?->value ?? '',
                    'label' => $subjectRow?->label ?? $subjectKey,
                    'description' => $subjectRow?->description ?? '',
                ],
                'body' => [
                    'key' => $bodyKey,
                    'value' => $bodyRow?->value ?? '',
                    'label' => $bodyRow?->label ?? $bodyKey,
                    'description' => $bodyRow?->description ?? '',
                ],
            ];
        }

        return response()->json([
            'data' => [
                'smtp' => $smtp,
                'templates' => $templates,
                'global_enabled' => (bool) chamba_setting('notifications.email_enabled', false),
            ],
        ]);
    }

    public function sendTest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'to' => 'required|email',
            'event' => 'required|string|in:' . implode(',', self::TEMPLATE_EVENTS),
        ]);

        $event = $data['event'];
        $to = $data['to'];

        $subject = chamba_setting("mail.template.{$event}.subject", "Correo de prueba: {$event}");
        $body = chamba_setting("mail.template.{$event}.body", "<p>Correo de prueba para el evento <strong>{$event}</strong>.</p>");

        // Sustituir variables de prueba
        $vars = [
            '{nombre}' => 'Usuario Demo',
            '{nombre_proveedor}' => 'Negocio Demo',
            '{email}' => $to,
            '{enlace}' => url('/app/restablecer?token=TEST&email=' . urlencode($to)),
            '{titulo_anuncio}' => 'Servicio de ejemplo',
            '{mensaje}' => 'Hola, estoy interesado en tu servicio.',
        ];
        $subject = str_replace(array_keys($vars), array_values($vars), $subject);
        $body = str_replace(array_keys($vars), array_values($vars), $body);

        $fromAddress = chamba_setting('mail.from_address', config('mail.from.address', 'noreply@buscape.pe'));
        $fromName = chamba_setting('mail.from_name', config('mail.from.name', 'Busca PE'));

        try {
            Mail::html($body, function ($msg) use ($to, $subject, $fromAddress, $fromName) {
                $msg->to($to)
                    ->subject($subject)
                    ->from($fromAddress, $fromName);
            });

            return response()->json(['message' => "Correo de prueba enviado a {$to}."]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al enviar: ' . $e->getMessage()], 422);
        }
    }
}
