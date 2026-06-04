<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Activamos custodia (escrow) por defecto en producción ahora que estamos en fase 2.
        DB::table('system_settings')
            ->where('key', 'features.escrow')
            ->update(['value' => '1', 'updated_at' => now()]);

        // Bajamos la comisión global por defecto de 15% a 10% (consistente con escrow.commission_percent).
        DB::table('commission_settings')
            ->whereNull('category_id')
            ->update(['rate' => 10.00, 'updated_at' => now()]);
        DB::table('system_settings')
            ->where('key', 'commission.default_rate')
            ->update(['value' => '10.00', 'updated_at' => now()]);

        $now = now();
        $rows = [
            // Custodia (escrow)
            ['escrow.commission_percent', '10.00', 'decimal', 'escrow',
                'Comisión (%) sobre custodia',
                'Porcentaje que retiene Chamba sobre cada pago en custodia. Se aplica al monto cotizado: el proveedor recibe (monto − comisión).'],
            ['escrow.auto_release_days', '7', 'integer', 'escrow',
                'Auto-liberación en días',
                'Días desde la entrega del proveedor para auto-liberar el pago si el cliente no confirma.'],
            ['escrow.evidence_min_photos', '1', 'integer', 'escrow',
                'Fotos mínimas de evidencia',
                'Cantidad mínima de fotos que el proveedor debe subir al marcar el trabajo como entregado.'],
            ['escrow.dispute_window_days', '7', 'integer', 'escrow',
                'Ventana de disputa (días)',
                'Días que tiene el cliente para abrir disputa después de la entrega.'],

            // Sedes / direcciones por proveedor
            ['provider.locations.max_free', '1', 'integer', 'subscriptions',
                'Sedes máximas plan Free',
                'Cantidad de sedes que puede registrar un proveedor Free.'],
            ['provider.locations.max_pro', '5', 'integer', 'subscriptions',
                'Sedes máximas plan Pro',
                'Cantidad de sedes que puede registrar un proveedor Pro/Premium.'],

            // Captura obligatoria en pagos
            ['payments.proof_required', '1', 'boolean', 'payouts',
                'Comprobante obligatorio al pagar',
                'Si está activo, el cliente debe subir captura de Yape/Plin/transferencia al registrar un pago.'],
        ];

        foreach ($rows as [$k, $v, $t, $g, $label, $desc]) {
            DB::table('system_settings')->insertOrIgnore([
                'key' => $k,
                'value' => $v,
                'type' => $t,
                'group' => $g,
                'label' => $label,
                'description' => $desc,
                'is_editable' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'escrow.commission_percent',
            'escrow.auto_release_days',
            'escrow.evidence_min_photos',
            'escrow.dispute_window_days',
            'provider.locations.max_free',
            'provider.locations.max_pro',
            'payments.proof_required',
        ])->delete();
    }
};
