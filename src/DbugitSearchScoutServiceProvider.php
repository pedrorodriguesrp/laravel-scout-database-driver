<?php 

namespace Dbugit\Scout;

use Laravel\Scout\EngineManager;
use Laravel\Scout\Builder;
use Illuminate\Support\ServiceProvider;
use Dbugit\Scout\Console\ImportCommand;
use Dbugit\Scout\Engines\DbugitSearchEngine;
use Dbugit\Scout\DbugitSearchable;

class DbugitSearchScoutServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app[EngineManager::class]->extend('dbugitsearch', function ($app) {
            $dbugitsearchable = new DbugitSearchable();

            $this->setFuzziness();
            $this->setAsYouType();

            return new DbugitSearchEngine($dbugitsearchable);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportCommand::class,
            ]);
        }

        Builder::macro('constrain', function($constraints) {
            $this->constraints = $constraints;
            return $this;
        });
    }

    protected function setFuzziness()
    {
        // $tnt->fuzziness            = config('scout.dbugitsearch.fuzziness', $tnt->fuzziness);
        // $tnt->fuzzy_distance       = config('scout.dbugitsearch.fuzzy.distance', $tnt->fuzzy_distance);
        // $tnt->fuzzy_prefix_length  = config('scout.dbugitsearch.fuzzy.prefix_length', $tnt->fuzzy_prefix_length);
        // $tnt->fuzzy_max_expansions = config('scout.dbugitsearch.fuzzy.max_expansions', $tnt->fuzzy_max_expansions);
    }

    protected function setAsYouType()
    {
        // $tnt->asYouType = config('scout.dbugitsearch.asYouType', $tnt->asYouType);
    }
}
