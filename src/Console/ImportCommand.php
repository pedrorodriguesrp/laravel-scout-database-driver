<?php

namespace AdrianoPedro\Scout\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Schema;
use AdrianoPedro\Scout\APSearchable;

class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apsearch:import {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the given model into the search index';

    /**
     * Execute the console command.
     *
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     *
     * @return void
     */
    public function handle(Dispatcher $events)
    {
        $modelclass = $this->argument('model');

        $modelclass::get()->each(function ($model) use ($modelclass) {
            $array              = $model->toSearchableArray();
            $modelclass         = str_replace("\App", "App", $modelclass);
            $apsearchable       = APSearchable::where('searchable_id', $model->getKey())->where("searchable_model", $modelclass)->first() ?? new APSearchable();
            $searchable_data    = mb_strtolower(implode(" ", $model->toSearchableArray()));

            if (empty($array)) {
                return;
            }

            $apsearchable->fill([
                "searchable_id"     => $model->getKey(),
                "searchable_model"  => $modelclass,
                "searchable_data"   => $searchable_data,
            ]);
            $apsearchable->save();
            $this->line('<comment>Imported [' . $modelclass . '] model ID:</comment> ' . $apsearchable->id);
        });
    }
}
