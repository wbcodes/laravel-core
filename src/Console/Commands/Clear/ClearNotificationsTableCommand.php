<?php

namespace Wbcodes\Core\Console\Commands\Clear;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Wbcodes\Core\Console\Commands\CoreCommandTrait;

class ClearNotificationsTableCommand extends Command
{
    use CoreCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wbcore:clear:notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make scheduler to delete notifications that are 30 days old from now';

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
        $command_title = 'Notifications';
        $this->commandStartInfo("Clear {$command_title}.");

        try {
            $clear_notifications_time = config('wbcore.clear_time.notifications', 'monthly');
            $clear_time = $this->getClearTimeFromConfig($clear_notifications_time);

            DB::table('notifications')->whereDate('created_at', '<', $clear_time)->delete();

            return true;
        } catch (\Exception $exp) {
            return $exp;
        }

        $days = Carbon::now()->diffInDays($clear_time);

        $this->commandEndInfo("{$days} Days => Notifications has been cleared successfully.");
    }
}
