<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit log de transiciones de estado de la solicitud (quién cambió qué y cuándo).
        if (! Schema::hasTable('service_request_events')) {
            DB::statement('CREATE TABLE service_request_events (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                service_request_id BIGINT UNSIGNED NOT NULL,
                from_status VARCHAR(40) NULL,
                to_status VARCHAR(40) NOT NULL,
                actor_user_id BIGINT UNSIGNED NULL,
                actor_role VARCHAR(20) NULL,
                note VARCHAR(500) NULL,
                metadata JSON NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_srevents_request
                    FOREIGN KEY (service_request_id) REFERENCES service_requests(id)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT fk_srevents_actor
                    FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_srevents_request (service_request_id, created_at),
                INDEX idx_srevents_to_status (to_status)
            ) ENGINE=InnoDB');
        }

        // Evidencias subidas por el proveedor al marcar el trabajo como entregado.
        if (! Schema::hasTable('service_request_evidence')) {
            DB::statement('CREATE TABLE service_request_evidence (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                service_request_id BIGINT UNSIGNED NOT NULL,
                path VARCHAR(255) NOT NULL,
                caption VARCHAR(255) NULL,
                sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                uploaded_by_user_id BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_srevd_request
                    FOREIGN KEY (service_request_id) REFERENCES service_requests(id)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT fk_srevd_user
                    FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_srevd_request (service_request_id, sort_order)
            ) ENGINE=InnoDB');
        }

        // Hacer que el ENUM acepte además 'entregado' (sinónimo de "terminado" pero requiere evidencia)
        // y 'reembolsado' (cuando se cancela un pago en custodia).
        DB::statement(
            "ALTER TABLE service_requests MODIFY status ENUM(
                'nuevo','contactado','cotizado','aceptado',
                'pagado_pendiente','en_custodia','en_progreso',
                'entregado','terminado','confirmado','cancelado','disputado','reembolsado','cerrado'
            ) NOT NULL DEFAULT 'nuevo'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_evidence');
        Schema::dropIfExists('service_request_events');

        DB::statement(
            "ALTER TABLE service_requests MODIFY status ENUM(
                'nuevo','contactado','cotizado','aceptado',
                'pagado_pendiente','en_custodia','en_progreso',
                'terminado','confirmado','cancelado','disputado','cerrado'
            ) NOT NULL DEFAULT 'nuevo'"
        );
    }
};
