<?php

namespace App\Console\Commands;

use App\Services\PaymentService;
use Illuminate\Console\Command;

/**
 * Libera automáticamente pagos en custodia cuyo trabajo fue entregado
 * hace más de `escrow.auto_release_days` días sin que el cliente confirme.
 *
 * Programar en App\Console\Kernel::schedule():
 *   $schedule->command('chamba:escrow:auto-release')->dailyAt('03:30');
 */
class AutoReleaseEscrowCommand extends Command
{
    protected $signature = 'chamba:escrow:auto-release';

    protected $description = 'Auto-libera pagos en custodia tras la ventana sin acción del cliente.';

    public function handle(PaymentService $payments): int
    {
        $count = $payments->autoReleaseExpired();
        $this->info("Liberados automáticamente: {$count} pago(s).");
        return self::SUCCESS;
    }
}
