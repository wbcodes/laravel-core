<?php

namespace Wbcodes\Core\Console\Commands\Update;

use App\Models\Module;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Wbcodes\Core\Console\Commands\CoreCommandTrait;

class UpdateModulesCommand extends Command
{
    use CoreCommandTrait;

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'wbcore:modules:update';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'This command will create a new module if not exists from config/wbcore.php';

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
        $this->commandStartInfo("Updated Modules.");

        $modules = config('wbcore.modules', []);
        $no_new_module = true;
        foreach ($modules as $name => $module_array) {
            if ($this->firstOrCreateModule($name)) {
                $no_new_module = false;
            }
        }

        if ($no_new_module){
            $this->warn("There are no modules to create.");
        }

        $this->commandEndInfo("Modules Updated Successfully.");

//        $this->removeDontUsedModules($modules);

        return 0;
    }

    protected $singularNames = [
    ];

    /**
     * @param $name
     * @param  null  $title
     * @param  null  $table_name
     * @return int
     */
    function firstOrCreateModule($name, $title = null, $table_name = null)
    {
        $name = Str::ucfirst(Str::singular(Str::camel($name)));
        if (in_array($name, $this->singularNames)) {
            $table_name = $table_name ?? Str::snake($name);
        } else {
            $table_name = $table_name ?? Str::plural(Str::snake($name));
        }
        $title = $title ?? $name;
        $title = ucwords(implode(" ", explode('_', Str::snake($title))));

        $module = Module::where('name', $name)->first();
        if ($module) {
//            $this->warn("Module Exists => {$module->name}.");
            return 0;
        }
        $module = new Module();
        $module->name = $name;
        $module->title = $title;
        $module->table_name = $table_name;
        $module->icon_class = get_site_core_core_module($table_name, 'icon');
        $module->controller = get_site_core_core_module($table_name, 'controller');
        $module->model = get_site_core_core_module($table_name, 'model');
        $module->save();
        $this->info($module->name.' module has been created successfully.');
        return true;
    }
}
