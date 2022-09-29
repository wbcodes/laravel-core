<?php

namespace Wbcodes\Core\Console\Commands;

use Illuminate\Console\Command;

class RunSiteCoreProject extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'wbcore:run';

    /**
     * The console command description.
     * @var string
     */
    protected $description = "This command used to open project on the PHP development server";

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
        $this->call('wbcore:clear');
        $this->call('serve', ['--port' => now()->year]);

        return 0;
    }
}
