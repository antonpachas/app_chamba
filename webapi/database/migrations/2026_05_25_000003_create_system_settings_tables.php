<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_settings')) {
            DB::statement('CREATE TABLE system_settings (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `key` VARCHAR(100) NOT NULL,
                `value` TEXT NULL,
                `type` ENUM("string","integer","decimal","boolean","json") NOT NULL DEFAULT "string",
                `group` VARCHAR(50) NOT NULL DEFAULT "general",
                label VARCHAR(150) NOT NULL,
                description VARCHAR(500) NULL,
                is_editable TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT uk_settings_key UNIQUE (`key`),
                INDEX idx_settings_group (`group`)
            ) ENGINE=InnoDB');

            $now = now();
            $rows = [
                // Pagos de la plataforma
                ['payouts.platform_yape', config('chamba.payouts.platform_yape'), 'string', 'payouts', 'Yape de la plataforma', 'Número Yape/Plin donde los usuarios depositan las membresías.'],
                ['payouts.platform_bank_name', config('chamba.payouts.platform_bank_name'), 'string', 'payouts', 'Banco', 'Nombre del banco de la cuenta receptora.'],
                ['payouts.platform_bank_account', config('chamba.payouts.platform_bank_account'), 'string', 'payouts', 'Cuenta bancaria', 'Número de cuenta corriente o de ahorros.'],
                ['payouts.platform_bank_holder', config('chamba.payouts.platform_bank_holder'), 'string', 'payouts', 'Titular', 'Razón social o persona titular de la cuenta.'],

                // Suscripciones
                ['subscriptions.provider_trial_days', (string) config('chamba.subscriptions.provider.trial_days'), 'integer', 'subscriptions', 'Días de trial Pro al registrarse', 'Cantidad de días gratis que recibe un proveedor nuevo.'],
                ['subscriptions.grace_days', (string) config('chamba.subscriptions.grace_days'), 'integer', 'subscriptions', 'Días de gracia tras vencimiento', 'Días que mantenemos activa una suscripción vencida antes de bajar a Free.'],
                ['subscriptions.currency', (string) config('chamba.subscriptions.currency', 'PEN'), 'string', 'subscriptions', 'Moneda', 'Código ISO de la moneda en la que se cobra (PEN, USD).'],

                // Comisión (escrow, dormido)
                ['commission.default_rate', (string) config('chamba.commission.default_rate'), 'decimal', 'escrow', 'Comisión por defecto (%)', 'Porcentaje de comisión sobre cada servicio si el modo escrow está activo.'],

                // Features (bandera de modo)
                ['features.escrow', config('chamba.features.escrow') ? '1' : '0', 'boolean', 'features', 'Modo custodia (escrow)', 'Si está activo, los pagos pasan por la plataforma con comisión.'],
                ['features.subscriptions', config('chamba.features.subscriptions') ? '1' : '0', 'boolean', 'features', 'Membresías', 'Si está activo, se cobran membresías Pro/Premium.'],
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

        if (! Schema::hasTable('system_settings_log')) {
            DB::statement('CREATE TABLE system_settings_log (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL,
                old_value TEXT NULL,
                new_value TEXT NULL,
                changed_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_setlog_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_setlog_key (setting_key, created_at)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('subscription_plans_log')) {
            DB::statement('CREATE TABLE subscription_plans_log (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                plan_id BIGINT UNSIGNED NOT NULL,
                field VARCHAR(50) NOT NULL,
                old_value TEXT NULL,
                new_value TEXT NULL,
                changed_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_planlog_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE CASCADE,
                CONSTRAINT fk_planlog_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_planlog_plan (plan_id, created_at)
            ) ENGINE=InnoDB');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans_log');
        Schema::dropIfExists('system_settings_log');
        Schema::dropIfExists('system_settings');
    }
};
