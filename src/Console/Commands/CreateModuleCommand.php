<?php

namespace Wbcodes\SiteCore\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Module;

class CreateModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = ':module:create {moduleName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'this command will add new row to module table';

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
     *
     */
    public function handle()
    {
        $moduleName = $this->argument('moduleName');
        $module = new Module();
        $module->name = $moduleName;
        $module->title = $moduleName;
        $module->save();

        $this->info("Module $moduleName has been added successfully");
    }
}
