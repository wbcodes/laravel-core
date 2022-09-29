<?php

namespace Wbcodes\Core\Console\Commands\Import;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportElasticSearchModulesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wbcore:import:elastic {--flush} {--delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate, Flush or remove elastic indexes.';

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
        $searchable_modules = config('wbcore.searchable.modules', []);

        // Remove all module index files
        if ($this->option('delete')) {
            $this->deleteElasticIndexes($searchable_modules);
            return 0;
        }

        // Clear all module index files
        if ($this->option('flush')) {
            $this->flushElasticIndexes($searchable_modules);
        }

        // Generate all modules index files
        $this->generateElasticIndexes($searchable_modules);

    }

    /**
     * @param $searchable_modules
     */
    private function deleteElasticIndexes($searchable_modules)
    {
        $this->info(Str::upper("\nDelete process started: \n----------------------------"));

        foreach ($searchable_modules as $searchable_module) {
            if (class_exists($searchable_module)) {

                $index_name = app($searchable_module)->searchableAs();

                try {

                    $this->call("scout:delete-index", ["name" => $index_name]);

                } catch (Exception $e) {

                    $this->error("no such index [$index_name] to delete.");
                }

            }

        }
    }

    /**
     * @param $searchable_modules
     */
    private function flushElasticIndexes($searchable_modules)
    {
        $this->info(Str::upper("\nFlush process started: \n----------------------------"));

        // Clear all module index files
        foreach ($searchable_modules as $searchable_module) {
            $index_name = app($searchable_module)->searchableAs();
            if (class_exists($searchable_module)) {
                try {

                    $this->call("scout:flush", ["model" => $searchable_module]);

                } catch (Exception $e) {

                    $this->error("no such index [$index_name] to flush.");

                }
            }
        }
        $this->warn("sleeping to 5 seconds:");
        sleep(5);
    }

    /**
     * @param $searchable_modules
     */
    private function generateElasticIndexes($searchable_modules)
    {
        $this->info(Str::upper("\nImport process started: \n----------------------------"));
        foreach ($searchable_modules as $searchable_module) {
            if (class_exists($searchable_module)) {
                try {
                    $index_name = app($searchable_module)->searchableAs();
                    $this->info("\n-------------------------------------------------------------");
                    $this->info($index_name);
                    $this->info("-------------------------------------------------------------");

                    $this->call("scout:import", ["model" => $searchable_module]);
                    sleep(1);

                } catch (Exception $e) {
                    $this->error("Failed to index $searchable_module records, make sure it's not empty and import it again");
                }

            }
        }
    }
}
