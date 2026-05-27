<?php

namespace App\Console\Commands;

use Database\Seeders\BuscaPeDemoSeeder;
use Illuminate\Console\Command;

class RefreshDemoListingsCommand extends Command
{
    protected $signature = 'busca:refresh-demo-listings {--force : Ejecutar sin confirmación}';

    protected $description = 'Borra anuncios/solicitudes de prueba y crea anuncios demo (Busca PE)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('¿Borrar TODOS los anuncios y solicitudes ligadas y crear datos demo?', false)) {
            $this->info('Cancelado.');

            return self::SUCCESS;
        }

        $this->call('db:seed', [
            '--class' => BuscaPeDemoSeeder::class,
            '--force' => true,
        ]);

        return self::SUCCESS;
    }
}
