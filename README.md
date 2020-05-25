# About APSearch Scout Driver

Laravel Scout Driver based on database search


## How to...

> **Requires:**
- **[PHP 7.2+](https://php.net/releases/)**
- **[Laravel 5.8+](https://github.com/laravel/laravel)**

**1**: First, you may use [Composer](https://getcomposer.org) to install APSearch as a required dependency into your Laravel project:
```bash
composer require AdrianoPedro/laravel-scout-database-driver
```

**2**: Then, you have to publish the database migration into your Laravel project:
```bash
php artisan vendor:publish --tag=apsearch-migrations
```

**3**: Now, you have to migrate the database migration into your Laravel project:
```bash
php artisan migrate --path=/database/migrations/create_searchables_table.php
```

**5**: Update your ***.env*** and ***config/scout.php*** files to set scout driver to apsearch:

***.env***
```php
	SCOUT_DRIVER = apsearch
	SCOUT_QUEUE  = true //for queueing the process, if false it will be processed emmidiatly uppon creation/update/delete
```
***config/scout.php***
```php
//...
'algolia' => [
	'id' => env('ALGOLIA_APP_ID', ''),
	'secret' => env('ALGOLIA_SECRET', ''),
],
//...
'apsearch' => [
    'asYouType'     => true,
    'searchMode'    => "LIKE",  // LIKE, BOOLEAN, NATURAL, DIRECT (direct
                                // search over model collection).
                                // searchModel can also be defined per Model.
],
//...
```

**5**: Add apSearchScoutServiceProvider the providers list on your projects **config/app.php**:
```php
<?php

return [
    //...
    'providers' => [
        //...
        Laravel\Scout\ScoutServiceProvider::class,
        AdrianoPedro\Scout\APSearchScoutServiceProvider::class,
    ],
    //...
];      
```
**6**: Include Scout class on the models you want to implement Searchable:
```php
<?php

namespace App\Model;

//..
use Laravel\Scout\Searchable;


class Model extends Model
{
    //..
    use Searchable;

    public searchMode = "LIKE"; // Optional. If not defined config/scout.php
                                // option will be used.
```

**7**: Optionally, you can import existing models (using Scout Searchable):
```bash
php artisan apsearch:import Path\\To\\Model
```



## License

APSearch is an open-sourced software licensed under the [MIT license](LICENSE).
