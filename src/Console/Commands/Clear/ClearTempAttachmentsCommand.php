<?php

namespace Wbcodes\Core\Console\Commands\Clear;

use Illuminate\Console\Command;
use Wbcodes\Core\Console\Commands\CoreCommandTrait;
use Wbcodes\Core\Models\Attachment;
use Wbcodes\Core\Models\DropzoneTemp;

class ClearTempAttachmentsCommand extends Command
{
    use CoreCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wbcore:clear:attachments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all temp attachments before on a day, week, month, year by run schedule cron job (daily, weekly, monthly, yearly)';

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
        $command_title = "Temporary attachments";
        $this->commandStartInfo("Clear {$command_title}.");

        try {
            $clear_notifications_time = config('wbcore.clear_time.attachments', 'monthly');
            $clear_time = $this->getClearTimeFromConfig($clear_notifications_time);

            $attachments = Attachment::where('attachable_type', DropzoneTemp::class)
                ->whereDate('created_at', '<', $clear_time)
                ->get();

            foreach ($attachments as $attachment) {
                $this->info("Delete Attachment => Id:{$attachment->id} has been deleted successfully.");
                $attachment->forceDelete();
            }

            $this->commandEndInfo("{$command_title} has been cleared successfully.");

            return true;
        } catch (\Exception $exp) {

            $this->warn($exp);

            return $exp;
        }
    }


}
