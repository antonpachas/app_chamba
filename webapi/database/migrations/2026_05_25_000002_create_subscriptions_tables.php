<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscription_plans')) {
            DB::statement('CREATE TABLE subscription_plans (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) NOT NULL,
                name VARCHAR(150) NOT NULL,
                audience ENUM("proveedor","cliente") NOT NULL,
                tier ENUM("free","pro","premium") NOT NULL,
                price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                currency CHAR(3) NOT NULL DEFAULT "PEN",
                features JSON NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT uk_plan_code UNIQUE (code),
                CONSTRAINT chk_plan_price CHECK (price >= 0),
                INDEX idx_plan_audience (audience, is_active)
            ) ENGINE=InnoDB');

            DB::table('subscription_plans')->insert([
                [
                    'code' => 'provider_free',
                    'name' => 'Proveedor Free',
                    'audience' => 'proveedor',
                    'tier' => 'free',
                    'price' => 0.00,
                    'currency' => 'PEN',
                    'features' => json_encode([
                        'contacts_per_month' => 3,
                        'max_services' => 1,
                        'top_search' => false,
                        'verified_badge' => false,
                        'whatsapp_visible' => false,
                        'analytics' => false,
                    ]),
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'provider_pro',
                    'name' => 'Proveedor Pro',
                    'audience' => 'proveedor',
                    'tier' => 'pro',
                    'price' => 29.00,
                    'currency' => 'PEN',
                    'features' => json_encode([
                        'contacts_per_month' => null,
                        'max_services' => 20,
                        'top_search' => true,
                        'verified_badge' => true,
                        'whatsapp_visible' => true,
                        'analytics' => true,
                    ]),
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'client_free',
                    'name' => 'Cliente',
                    'audience' => 'cliente',
                    'tier' => 'free',
                    'price' => 0.00,
                    'currency' => 'PEN',
                    'features' => json_encode([
                        'priority_support' => false,
                        'verified_badge' => false,
                        'guarantee' => false,
                    ]),
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'client_premium',
                    'name' => 'Cliente Premium',
                    'audience' => 'cliente',
                    'tier' => 'premium',
                    'price' => 9.00,
                    'currency' => 'PEN',
                    'features' => json_encode([
                        'priority_support' => true,
                        'verified_badge' => true,
                        'guarantee' => true,
                    ]),
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (! Schema::hasTable('user_subscriptions')) {
            DB::statement('CREATE TABLE user_subscriptions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                plan_id BIGINT UNSIGNED NOT NULL,
                status ENUM("trial","active","pending_payment","past_due","cancelled","expired") NOT NULL DEFAULT "active",
                current_period_start TIMESTAMP NULL,
                current_period_end TIMESTAMP NULL,
                trial_ends_at TIMESTAMP NULL,
                next_billing_at TIMESTAMP NULL,
                auto_renew TINYINT(1) NOT NULL DEFAULT 1,
                cancelled_at TIMESTAMP NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_subs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT fk_subs_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id),
                INDEX idx_subs_user_status (user_id, status),
                INDEX idx_subs_period_end (current_period_end),
                INDEX idx_subs_trial (trial_ends_at)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('subscription_payments')) {
            DB::statement('CREATE TABLE subscription_payments (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                subscription_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                currency CHAR(3) NOT NULL DEFAULT "PEN",
                payment_method ENUM("yape","plin","transferencia","culqi","mercadopago","otro") NOT NULL DEFAULT "yape",
                payment_reference VARCHAR(100) NULL,
                period_start TIMESTAMP NULL,
                period_end TIMESTAMP NULL,
                status ENUM("pendiente_revision","confirmado","rechazado") NOT NULL DEFAULT "pendiente_revision",
                paid_at TIMESTAMP NULL,
                confirmed_at TIMESTAMP NULL,
                confirmed_by BIGINT UNSIGNED NULL,
                rejection_reason TEXT NULL,
                notes TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_subpay_sub FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT fk_subpay_user FOREIGN KEY (user_id) REFERENCES users(id),
                CONSTRAINT fk_subpay_admin FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT chk_subpay_amount CHECK (amount > 0),
                INDEX idx_subpay_status (status),
                INDEX idx_subpay_user (user_id),
                INDEX idx_subpay_subscription (subscription_id)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('contact_events')) {
            DB::statement('CREATE TABLE contact_events (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                client_user_id BIGINT UNSIGNED NOT NULL,
                provider_profile_id BIGINT UNSIGNED NOT NULL,
                provider_user_id BIGINT UNSIGNED NOT NULL,
                provider_service_id BIGINT UNSIGNED NULL,
                service_request_id BIGINT UNSIGNED NULL,
                kind ENUM("solicitud","contacto") NOT NULL DEFAULT "contacto",
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_ce_client FOREIGN KEY (client_user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_ce_provider_profile FOREIGN KEY (provider_profile_id) REFERENCES provider_profiles(id) ON DELETE CASCADE,
                CONSTRAINT fk_ce_provider_user FOREIGN KEY (provider_user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_ce_service FOREIGN KEY (provider_service_id) REFERENCES provider_services(id) ON DELETE SET NULL,
                CONSTRAINT fk_ce_request FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE SET NULL,
                INDEX idx_ce_provider_month (provider_user_id, created_at),
                INDEX idx_ce_client_month (client_user_id, created_at)
            ) ENGINE=InnoDB');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_events');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
