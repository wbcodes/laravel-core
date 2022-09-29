<?php

namespace Wbcodes\Core\Console\Commands\Update;

use Illuminate\Console\Command;
use Wbcodes\Core\Console\Commands\CoreCommandTrait;

class UpdateListOptionsCommand extends Command
{
    use CoreCommandTrait;
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'wbcore:listOptions:update';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'This command will create new list option';

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
        $this->commandStartInfo("Update List Options.");

        $this->call('db:seed', ['--class' => 'ListOptionSeeder']);

        $this->commandEndInfo("List Options Updated Successfully.");

        return 0;
    }
}
