<?php

namespace AdrianoPedro\Scout\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SearchablesImportModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apsearch:import-from-model-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all files from the App\Models\ directory';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $directory = app_path('Models');
        $files = File::allFiles($directory);

        foreach ($files as $file) {
            // Convert file path to class name
            $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relativePath = str_replace('.php', '', $relativePath);
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
            $className = 'App\\Models\\' . $relativePath;

            if (in_array($relativePath, ['Permissions\Permission', 'Sample'])) {
                continue;
            }
            $this->info($relativePath);
            $this->info($className);
            // Check if the class exists before attempting to import
            if (class_exists($className)) {
                if (method_exists($className, 'toSearchableArray')) {
                    $this->info('Importing ' . $className);
                    Artisan::call('apsearch:import', ['model' => $className]);
                } else {
                    $this->warn('Class ' . $className . ' does not have a toSearchableArray method. Skipping.');
                }
            } else {
                $this->error('Class ' . $className . ' does not exist.');
            }
        }

        $this->info('All model files have been processed.');

        return 0;
    }
}
