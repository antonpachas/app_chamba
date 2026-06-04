<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // En producción los datos base (categorías, distritos, planes y settings)
        // se siembran desde las migraciones.
        // Datos de anuncios demo: php artisan busca:refresh-demo-listings --force
    }
}
