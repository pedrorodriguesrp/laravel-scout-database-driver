<?php 

namespace AdrianoPedro\Scout;

use Laravel\Scout\EngineManager;
use Laravel\Scout\Builder;
use Illuminate\Support\ServiceProvider;
use AdrianoPedro\Scout\Console\ImportCommand;
use AdrianoPedro\Scout\Engines\APSearchEngine;
use AdrianoPedro\Scout\APSearchable;

class APSearchScoutServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->app[EngineManager::class]->extend('apsearch', function ($app) {
            $apsearchable = new APSearchable();

            $this->setSearchMode($apsearchable);

            return new APSearchEngine($apsearchable);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportCommand::class,
            ]);
            
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'apsearch-migrations');
        }

        Builder::macro('constrain', function($constraints) {
            $this->constraints = $constraints;
            return $this;
        });
    }


    protected function setSearchMode($apsearchable)
    {
        $apsearchable->searchMode = config('scout.apsearch.searchMode', $apsearchable->searchMode);
    }

}
