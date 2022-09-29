<?php

namespace Wbcodes\Core\Console\Commands\Remove;

use Illuminate\Console\Command;
use Wbcodes\Core\Models\Report;

class RemoveDraftReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wbcore:remove:reports-drafts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command well be remove all draft reports which not saved.';

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
        $draft_reports = Report::Draft()->get();
        $draft_reports->each->forceDelete();
        $this->info('Draft reports has been successfully');

        return 0;
    }
}
