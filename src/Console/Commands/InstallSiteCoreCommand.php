<?php

namespace Wbcodes\Core\Console\Commands;

use App\Models\Module;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InstallSiteCoreCommand extends Command
{
    use CoreCommandTrait;

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'wbcore:install';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Site Core Install Command. This Command will be generate all of these (Models,Controllers).';

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

        $this->setRoutesInWebRouteFile();

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

        $this->info("Site Core installed successfully.");

        $this->runServer();
    }

    /**
     */
    private function setRoutesInWebRouteFile()
    {
        $routeFileContent = file_get_contents(__DIR__.'/../../../stubs/routes/web.stub');
        if (!Str::contains($routeFileContent, '$models = config("wbcore.modules");')) {
            file_put_contents(base_path('routes/web.php'), $routeFileContent, FILE_APPEND);
        }
    }
}

