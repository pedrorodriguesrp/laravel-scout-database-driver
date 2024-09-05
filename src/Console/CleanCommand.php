<?php

namespace AdrianoPedro\Scout\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Schema;
use AdrianoPedro\Scout\APSearchable;

class CleanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apsearch:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned records in the APSearchable table that do not have corresponding model records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of orphaned records...');

        try {
            $orphanedIds = [];

            APSearchable::get()->each(function ($apsearchable) use (&$orphanedIds) {
                $modelClass = $apsearchable->searchable_model;

                // Check if the model class exists
                if (class_exists($modelClass)) {
                    // Try to find the corresponding record in the model's table
                    $model = $modelClass::find($apsearchable->searchable_id);

                    // If the record doesn't exist, add to orphaned IDs array
                    if (!$model) {
                        $orphanedIds[] = $apsearchable->id;
                    }
                } else {
                    // If the model class doesn't exist, add to orphaned IDs array
                    $orphanedIds[] = $apsearchable->id;
                    $this->line("<comment>Non-existent model class:</comment> {$modelClass} (APSearchable ID: {$apsearchable->id})");
                }
            });

            // Delete all orphaned records in bulk
            if (!empty($orphanedIds)) {
                APSearchable::whereIn('id', $orphanedIds)->delete();
                $this->line('<comment>Deleted orphaned APSearchable records:</comment> ' . implode(', ', $orphanedIds));
            } else {
                $this->info('No orphaned records found.');
            }

        } catch (\Exception $e) {
            $this->error('An error occurred during cleanup: ' . $e->getMessage());
        }

        $this->info('Cleanup completed.');
    }
}
