<?php

namespace Wbcodes\Core\Console\Commands\Update;

use App\Models\Module;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Wbcodes\Core\Console\Commands\CoreCommandTrait;
use Wbcodes\Core\Models\CustomView;

class UpdateDefaultCustomViewsCommand extends Command
{
    use CoreCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wbcore:custom-views:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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

        $this->commandStartInfo("Updated Custom Views.");

        $modules = Module::AvailableFieldPermission()->get();
        foreach ($modules as $module) {
            $this->createNewDefaultCustomView("All", $module->id, $module->title);
            $this->createNewDefaultCustomView("My", $module->id, $module->title);
//            $this->createNewDefaultCustomView("Shared By Me", $module->id, $module->name,);
//            $this->createNewDefaultCustomView("Shared With Me", $module->id, $module->name,);
            $this->createNewDefaultCustomView("New Last Week", $module->id, $module->title);
            $this->createNewDefaultCustomView("New This Week", $module->id, $module->title);
            $this->createNewDefaultCustomView("Recently Created", $module->id, $module->title);
            $this->createNewDefaultCustomView("Recently Modified", $module->id, $module->title);
            $this->createNewDefaultCustomView("Unread", $module->id, $module->title);
        }

        $this->commandEndInfo("Custom Views Updated Successfully.");

        return 0;
    }

    /**
     * @param $name
     * @param $module_id
     * @param $module_name
     * @param  string  $columns
     * @param  int  $is_default
     * @param  int  $is_locked
     */
    private function createNewDefaultCustomView($name, $module_id, $module_name, $columns = 'All', $is_default = 1, $is_locked = 1)
    {
        $module_name = Str::plural($module_name);
        $title = "{$name} {$module_name}";

        $slug = Str::slug($title);
        $title = Str::ucfirst($title);
        $item = CustomView::where('slug', $slug)->where('title', $title)->where('module_id', $module_id)->first();
        if ($item) {
            $this->warn("CustomView Exists => {$slug}.");
//            $this->warn("CustomView Exists ({$module_name}) => {$slug}.");
            return 0;
        }

        $item = new CustomView();
        $item->slug = $slug;
        $item->title = $title;
        $item->module_id = $module_id;
        $item->module_name = Str::slug($module_name);
        $item->columns = $columns;
//        $item->criteria = $criteria;
        $item->criteria_pattern = Str::slug($name, '_');
//        $item->description = $description;
//        $item->ordering = $ordering;
        $item->is_locked = $is_locked;
        $item->is_default = $is_default;
        $item->save();
    }
}
