# ⚗️ About DbugitSearch Scout Driver
Driver for Laravel Scout database search package based

## ✨ How to...

**1**: First, you may use [Composer](https://getcomposer.org) to install DbugitSearch as a required dependency into your Laravel project:
```bash
composer require dbugit/laravel-scout-database-driver
```

**2**: Then, you have to publish the database migration into your Laravel project:
```bash
php artisan vendor:publish --tag=dbugitsearch-migrations
```

**3**: Now, you have to migrate the database migration into your Laravel project:
```bash
php artisan migrate --path=/database/migrations/2020_01_30_100107_create_searchables_table.php
```

**4**: Add DbugitSearchScoutServiceProvider the providers list on your projects **config/app.php**:
```php
<?php

return [
    //...
    'providers' => [
        //...
        Laravel\Scout\ScoutServiceProvider::class,
        Dbugit\Scout\DbugitSearchScoutServiceProvider::class,
    ],
    //...
];      
```
**5**: Include Scout class on the models you want to implement Searchable:
```php
<?php

namespace App\Model;

//..
use Laravel\Scout\Searchable;


class Model extends Model
{
    //..
    use Searchable;
```

**6**: Optionally, you can import existing models (using Scout Searchable) have to migrate the database migration into your Laravel project:
```bash
php artisan dbugitsearch:import Path\\To\\Model  
```



## 📖 License

DbugitSearch is an open-sourced software licensed under the [MIT license](LICENSE.md).
