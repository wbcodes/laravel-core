<?php

namespace Wbcodes\SiteCore\Console\Commands\Update;

use Illuminate\Console\Command;

class UpdateSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitecore:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will be update every thing in system like (modules, permissions and list options)';

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
        $this->call('sitecore:listOptions:update');
        $this->call('sitecore:modules:update');
        $this->call('sitecore:permissions:update');
        return 0;
    }
}
