<?php

namespace App\Console\Commands;

use App\Services\ListingLifecycleService;
use Illuminate\Console\Command;

class ExpireListingsCommand extends Command
{
    protected $signature = 'busca:listings:expire';

    protected $description = 'Oculta anuncios cuya fecha de vencimiento ya pasó (Busca PE)';

    public function handle(ListingLifecycleService $listings): int
    {
        $count = $listings->deactivateExpired();
        $this->info("Anuncios vencidos ocultados: {$count}");

        return self::SUCCESS;
    }
}
