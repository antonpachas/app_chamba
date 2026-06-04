<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'chamba:expire-subscriptions';

    protected $description = 'Caduca trials y suscripciones vencidas, hace downgrade a Free.';

    public function handle(SubscriptionService $subs): int
    {
        $count = $subs->expireDueSubscriptions();
        $this->info("Procesadas {$count} suscripciones.");

        return self::SUCCESS;
    }
}
