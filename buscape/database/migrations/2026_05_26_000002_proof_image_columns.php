<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Comprobante de pago del cliente (captura Yape/Plin/transferencia) en pagos de servicio.
        if (Schema::hasTable('service_payments') && ! Schema::hasColumn('service_payments', 'proof_image_path')) {
            DB::statement('ALTER TABLE service_payments
                ADD COLUMN proof_image_path VARCHAR(255) NULL AFTER notes');
        }

        // Comprobante del pago al proveedor que registra el admin al pagar un retiro.
        if (Schema::hasTable('wallet_withdrawals') && ! Schema::hasColumn('wallet_withdrawals', 'proof_image_path')) {
            DB::statement('ALTER TABLE wallet_withdrawals
                ADD COLUMN proof_image_path VARCHAR(255) NULL AFTER notes');
        }

        // Hitos de ciclo de vida de la solicitud de servicio (custodia + entrega).
        if (Schema::hasTable('service_requests')) {
            $columns = [
                'delivered_at' => 'TIMESTAMP NULL',
                'client_confirmed_at' => 'TIMESTAMP NULL',
                'auto_release_at' => 'TIMESTAMP NULL',
                'disputed_at' => 'TIMESTAMP NULL',
                'cancelled_at' => 'TIMESTAMP NULL',
            ];
            foreach ($columns as $col => $type) {
                if (! Schema::hasColumn('service_requests', $col)) {
                    DB::statement("ALTER TABLE service_requests ADD COLUMN {$col} {$type}");
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('service_payments', 'proof_image_path')) {
            DB::statement('ALTER TABLE service_payments DROP COLUMN proof_image_path');
        }
        if (Schema::hasColumn('wallet_withdrawals', 'proof_image_path')) {
            DB::statement('ALTER TABLE wallet_withdrawals DROP COLUMN proof_image_path');
        }
        foreach (['delivered_at', 'client_confirmed_at', 'auto_release_at', 'disputed_at', 'cancelled_at'] as $col) {
            if (Schema::hasColumn('service_requests', $col)) {
                DB::statement("ALTER TABLE service_requests DROP COLUMN {$col}");
            }
        }
    }
};
