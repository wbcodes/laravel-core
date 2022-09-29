<?php

namespace Wbcodes\Core\Console\Commands\Update;

use App\Models\Module;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Wbcodes\Core\Console\Commands\CoreCommandTrait;

class UpdateSiteCoreCommand extends Command
{
    use CoreCommandTrait;

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'wbcore:update';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Site Core update Command. This Command will be generate all of these (Models, Controllers) and publish new features or updates.';

    /**
     * Create a new command instance.
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @return int
     */
    public function handle()
    {
        $modules = config('wbcore.modules', []);

        $this->generateControllers($modules);

        $this->generateModels($modules);

        $this->publishVendor();

        $this->runMigration();

        $replaceFileNameSpaces = [
            'Events',
            'Listeners',
            'Notifications',
            'Http/Resources',
        ];

        foreach ($replaceFileNameSpaces as $fileNamespace) {
            $this->replaceFilesNamespace($fileNamespace);
        }

        $this->replaceSupportFilesNamespace();

        $this->info("Site Core updated successfully.");

        $this->runServer();
    }

}
