<?php

namespace Wbcodes\Core\Console\Commands\Clear;

use Illuminate\Console\Command;
use Wbcodes\Core\Console\Commands\CoreCommandTrait;

class ClearSiteCoreCommand extends Command
{
    use CoreCommandTrait;

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'wbcore:clear';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'This command to clear all of view, cache, config and route.';

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
     * @return void
     */
    public function handle()
    {
        $this->commandStartInfo("Clear Site files.");

        $this->call('view:clear');
        $this->call('cache:clear');
        $this->call('route:clear');
        $this->call('config:cache');

        $this->commandEndInfo("View, Cache, Route and Config has been cleared successfully.");
    }
}
