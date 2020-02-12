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

            $this->setFuzziness();
            $this->setAsYouType();

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


    protected function setFuzziness()
    {
        // $tnt->fuzziness            = config('scout.apsearch.fuzziness', $tnt->fuzziness);
        // $tnt->fuzzy_distance       = config('scout.apsearch.fuzzy.distance', $tnt->fuzzy_distance);
        // $tnt->fuzzy_prefix_length  = config('scout.apsearch.fuzzy.prefix_length', $tnt->fuzzy_prefix_length);
        // $tnt->fuzzy_max_expansions = config('scout.apsearch.fuzzy.max_expansions', $tnt->fuzzy_max_expansions);
    }

    protected function setAsYouType()
    {
        // $tnt->asYouType = config('scout.apsearch.asYouType', $tnt->asYouType);
    }
}
