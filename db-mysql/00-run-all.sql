-- Ejecutar en este orden para dejar la BD lista:
SOURCE 01-schema-mvp.sql;
SOURCE 02-routines-mvp.sql;
SOURCE 03-seed-mvp.sql;
SOURCE 06-sp-search-address-text-patch.sql;
SOURCE 07-payments-schema.sql;

-- Opcional: tokens API (Laravel Sanctum). Mejor en el servidor del API:
--   php artisan migrate --force
-- Solo si NO usarás migrate, descomenta la siguiente línea:
-- SOURCE 04-laravel-sanctum.sql;
