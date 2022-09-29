<?php

namespace Wbcodes\Core\Console\Commands\Update;

use App\Models\Module;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Wbcodes\Core\Console\Commands\CoreCommandTrait;
use Wbcodes\Core\Models\Permission;
use Wbcodes\Core\Models\Role;

class UpdatePermissionsCommand extends Command
{
    use CoreCommandTrait;

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'wbcore:permissions:update';

    /**
     * The console command description.
     * @var string
     */
    protected $description = "This command will be update all permissions by removing don't used and create required permissions which not exists before";

    protected $sleep_counter = 0;

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

        $this->commandStartInfo("Update Permissions.");

        // Create Module Permission Actions (View,Create,Edit,Export,.....)
        $modules = Module::all();

        $permission_fields = collect();
        foreach ($modules as $module) {
            $moduleName = $module->name;
            $modelClassName = $module->model;
            if (!class_exists($modelClassName)) {
                $this->warn($module->name.' Model not exists');
                $module->delete();
                $this->warn("Delete Module => {$module->name}");
                continue;
            }
            if (defined("$modelClassName::PERMISSION_ACTION")) {
                foreach ($modelClassName::PERMISSION_ACTION as $permissionName => $value) {
                    $permission_fields = $permission_fields->add("{$moduleName}.{$permissionName}");
                    $this->newItem("{$moduleName}.{$permissionName}", $module, 'profile');
                }
            }
        }

        // Create Module Field Permissions (Lead.id.readonly, Lead.title.readwrite,.....)
        $modules = Module::AvailableFieldPermission()->get();

        foreach ($modules as $module) {
            $moduleName = $module->name;
            $moduleClass = $module->model;
            if (!class_exists($moduleClass)) {
                $this->warn($moduleClass.' not exists');
                continue;
            }
            if (isset($moduleClass::$columns)) {
                $moduleColsClass = $moduleClass::$columns;
            } else {
                $this->warn($moduleClass.' columns class not exists');
                continue;
            }
            if (class_exists($moduleColsClass)) {
                $READ_WRITE = Permission::FIELD_PERMISSION_READ_WRITE;
                $READ_ONLY = Permission::FIELD_PERMISSION_READ_ONLY;
                $DONT_SHOW = Permission::FIELD_PERMISSION_DONT_SHOW;

                $ob = new $moduleColsClass;
                // Create Permission Columns in DB
                $fields = $ob->permissionColumns();
                foreach ($fields->pluck('name') as $field) {
                    $per_names = [
                        "{$moduleName}.{$field}.{$READ_WRITE}",
                        "{$moduleName}.{$field}.{$READ_ONLY}",
                        "{$moduleName}.{$field}.{$DONT_SHOW}",
                    ];
                    $permission_fields = $permission_fields->merge(collect($per_names));
                    foreach ($per_names as $per_name) {
                        $this->newItem($per_name, $module);
                    }
                }

                // Remove Not Permission Columns: (filter and hidden) columns from DB
                $fields = $ob->notPermissionColumns();
                foreach ($fields->pluck('name') as $field) {
                    $this->dropItemIfExists("{$moduleName}.{$field}.{$READ_WRITE}");
                    $this->dropItemIfExists("{$moduleName}.{$field}.{$READ_ONLY}");
                    $this->dropItemIfExists("{$moduleName}.{$field}.{$DONT_SHOW}");
                }

                // Remove Not Used Permission Columns: When this columns removed from cols classes remove it from db
                $notUsedModulePermissions = Permission::where('module_id', $module->id)->whereNotIn('name', $permission_fields->toArray())->get()->pluck('name');
                if (is_countable($notUsedModulePermissions) and count($notUsedModulePermissions)) {
                    foreach ($notUsedModulePermissions as $permission) {
                        $this->dropItemIfExists($permission);
                    }
                }
            }
        }

        $this->giveTheRolePermissions('developer');
        $this->giveTheRolePermissions('owner');
        $this->giveTheRolePermissions('super-admin');

        $this->commandEndInfo("Permissions Updated Successfully.");


        return 0;
    }

    /**
     * @param      $name
     * @param      $module
     * @param  null  $permission_type
     */
    function newItem($name, $module, $permission_type = null)
    {

        if (!Permission::where('name', $name)->first()) {
            $this->sleep_counter++;
            if ($this->sleep_counter % 100 == 0) {
                $this->warn('sleeping to 5 second...');
                sleep(5);
            }
            $item = new Permission();
            $item->name = $name;
            $name = str_replace('_', ' ', $name);
            $name = str_replace('.', ' ', $name);
            $item->title = ucwords($name);
            $item->permission_type = $permission_type ?? 'field';
            $item->module_id = optional($module)->id;
            $item->save();
            $this->info("{$item->name} permission has been created successfully");
        }
    }

    /**
     * @param      $name
     */
    function dropItemIfExists($name)
    {
        $field_exists = Permission::where('name', $name)->first();
        if ($field_exists) {
            $field_exists->delete();
            $this->info("DELETED => {$field_exists->name} permission has been deleted successfully");
        }
    }

    private function giveTheRolePermissions($role_name = 'ceo')
    {
        $dontShow = Permission::FIELD_PERMISSION_DONT_SHOW;
        $readOnly = Permission::FIELD_PERMISSION_READ_ONLY;

        $role = Role::findByName(Str::slug($role_name));
        $permissions = Permission::where('name', 'not like', "%$dontShow%")
            ->where('name', 'not like', "%$readOnly%")
            ->get();

        if ($role) {
            $roleTitle = Str::upper($role_name);
            $permissions_array = $permissions->pluck('name')->toArray();
            $role_permissions_array = $role->permissions->pluck('name')->toArray();
            if (!count(array_diff($permissions_array, $role_permissions_array))) {
                $this->warn("Permissions Already up to date => {$roleTitle}.");

                return;
            }

            $role->givePermissionTo($permissions->pluck('name'));
            $this->info(count($permissions)." Permissions has been assigned to {$roleTitle} successfully.");

            $this->warn('sleeping to 1 second...');
            sleep(1);
        } else {
            $this->warn("Role Not Found.");
        }
    }

}
