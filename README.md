# laravel-scout-dbugitsearch-driver
Driver for Laravel Scout database search package based

composer require dbugit/laravel-scout-database-driver

php artisan vendor:publish --tag=dbugitsearch-migrations

php artisan migrate --path=/database/migrations/2020_01_30_100107_create_searchables_table.php

php artisan dbugitsearch:import Path\\To\\Model  
