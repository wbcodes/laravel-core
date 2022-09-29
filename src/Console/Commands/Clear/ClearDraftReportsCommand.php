<?php

namespace Wbcodes\Core\Console\Commands\Clear;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Wbcodes\Core\Console\Commands\CoreCommandTrait;

class ClearDraftReportsCommand extends Command
{
    use CoreCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wbcore:clear:reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all draft reports before on a day, week, month, year by run schedule cron job (daily, weekly, monthly, yearly)';

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
     * @return \Exception|int
     */
    public function handle()
    {
        $command_title  ='Draft Reports';
        $this->commandStartInfo("Clear {$command_title}.");

        try {

            $clear_notifications_time = config('wbcore.clear_time.reports', 'monthly');

            $clear_time = $this->getClearTimeFromConfig($clear_notifications_time);

            DB::table('reports')
                ->where('is_draft', 1)
                ->whereDate('created_at', '<', $clear_time)
                ->delete();

            $this->commandEndInfo("{$command_title} has been cleared successfully.");

            return true;
        } catch (\Exception $exp) {
            $this->warn($exp);
        }
    }
}
