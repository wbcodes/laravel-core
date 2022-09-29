<?php


namespace Wbcodes\Core\Console\Commands;


use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OwenIt\Auditing\Models\Audit;
use Wbcodes\Core\Providers\CoreServiceProvider;

trait CoreCommandTrait
{
    protected $acceptAnswers = ['yes', 'y'];

    /**
     * @param $path
     * @return string
     */
    protected function packagePath($path)
    {
        if (app()->isProduction()) {
            return base_path("vendor/wbcodes/laravel-core/{$path}");
        };
        return __DIR__."/../../{$path}";
    }

    /**
     * @param $path
     * @return string
     */
    protected function stubPath($path)
    {
        return $this->packagePath("stubs/{$path}");
    }

    /**
     * @param $message
     */
    function commandStartInfo($message)
    {
        $this->info("{$message} \n--------------------------------------------------");
    }

    /**
     * @param $message
     */
    function commandEndInfo($message)
    {
        $this->info("{$message} \n==*==*==*==*==*==*==*==*==*==*==*==*==*==*==*==*==\n");
    }

    /**
     * @param $clear_notifications_time
     * @return \Carbon\Carbon
     */
    function getClearTimeFromConfig($clear_notifications_time)
    {
        $now = \Carbon\Carbon::now();

        $clear_notifications_time = trim(Str::lower($clear_notifications_time));

        switch ($clear_notifications_time) {
            case 'daily':
                $clear_time = $now->subDay();
                break;
            case 'weekly':
                $clear_time = $now->subWeek();
                break;
            case 'yearly':
                $clear_time = $now->subYear();
                break;
            case 'monthly':
            default:
                $clear_time = $now->subMonth();
                break;
        }
        return $clear_time;
    }

    /**
     * @param  string  $logMessage
     * @param  string  $channel
     */
    private function save_and_print_info_log(string $channel, string $logMessage)
    {
        $this->save_and_print_log($channel, $logMessage, 'info');
    }

    /**
     * @param  string  $channel
     * @param  string  $logMessage
     */
    private function save_and_print_warn_log(string $channel, string $logMessage)
    {
        $this->save_and_print_log($channel, $logMessage, 'warning');
    }

    /**
     * @param  string  $logMessage
     * @param  string  $log_type
     * @param  string  $channel
     */
    private function save_and_print_log(string $channel, string $logMessage, $log_type = 'warning')
    {
        switch ($log_type) {
            case 'debug':
            case 'notice':
            case 'alert':
            case 'warning':
                $type = 'warn';
                break;
            case 'emergency':
            case 'error':
                $type = 'error';
                break;
            case 'info':
            default:
                $type = $log_type;
                break;
        }

        Log::channel($channel)->{$log_type}($logMessage);

        $this->{$type}($logMessage);
    }


    /**
     * @param $row
     * @param $column_names
     * @param  null  $event_class
     * @param  null  $event_function
     * @param  null  $original
     * @param  null  $old_values
     * @param  null  $new_values
     * @param  string  $event_type
     * @param  bool  $bySystem
     */
    function save_audit(
        $row,
        $column_names,
        $event_class = null,
        $event_function = null,
        $original = null,
        $old_values = null,
        $new_values = null,
        $event_type = 'updated',
        $bySystem = true
    ) {
        $event_function = $event_function ? Str::snake($event_function) : $event_function;
        $audit_changes = [
            'event'          => $event_type,
            'auditable_type' => get_class($row),
            'auditable_id'   => $row->id,
            'url'            => $row->url,
            'event_class'    => $event_class,
            'event_function' => $event_function,
            // 'ip_address'     => '',
            // 'user_agent'     => '',
            // 'tags'           => '',
        ];

        if (auth()->check()) {
            $user_id = auth()->id();
        }

        $created_by = $user_id ?? ($row->modified_by ?? $row->created_by);

        if ($bySystem) {
            $user_type = null;
            $user_id = null;
        } else {
            $user_type = User::class;
            $user_id = $created_by;
        }

        $audit_changes['user_type'] = $user_type;
        $audit_changes['user_id'] = $user_id;
        $audit_changes['created_by'] = $created_by;

        $original = $original ?? $row->getOriginal();

        if (is_null($old_values) and is_null($new_values)) {
            $old_values = [];
            $new_values = [];
            $column_names = is_array($column_names) ? $column_names : [$column_names];
            foreach ($column_names as $column_name) {
                $old_values[] = $original[$column_name] ?? null;
                $new_values[] = $row->{$column_name};
            }
        }

        $this->create_new_audit($column_names, $old_values, $new_values, $audit_changes);
    }

    /**
     * @param $column_names
     * @param $old_values
     * @param $new_values
     * @param $audit_changes
     */
    function create_new_audit($column_names, $old_values, $new_values, $audit_changes)
    {
        if (is_null($old_values) and is_null($new_values)) {
            return 0;
        }

        $old_values = is_array($old_values) ? $old_values : [$old_values];
        $new_values = is_array($new_values) ? $new_values : [$new_values];
        $column_names = is_array($column_names) ? $column_names : [$column_names];

        $old_values_data = [];
        $new_values_data = [];

        foreach ($column_names as $key => $column_name) {
            $old_value = $old_values[$key] ?? null;
            $new_value = $new_values[$key] ?? null;
            if ($old_value != $new_value) {
                $old_values_data[$column_name] = $old_value;
                $new_values_data[$column_name] = $new_value;
            }
        }

        if (count($old_values_data) or count($new_values_data)) {
            $audit_changes['old_values'] = $old_values_data;
            $audit_changes['new_values'] = $new_values_data;
            Audit::create($audit_changes);
        }
    }

    /**
     * @param $modules
     */
    private function generateControllers($modules)
    {
        foreach (collect($modules)->where('is_base_controller', 1) as $moduleName => $module) {
            $controllerName = $module['controller'];
            if (!class_exists("App\\Http\\Controllers\\{$controllerName}")) {
                $this->call('wbcore:make:controller', ['name' => $controllerName]);
            }
        }
        $this->warn('No More Controllers Needs To Publish');
    }

    /**
     * @param $modules
     */
    private function generateModels($modules)
    {
        $moduleModel = site_core_module('module', Module::class, null);
        $modules = collect($modules)->add($moduleModel)->toArray();

        foreach ($modules as $moduleName => $module) {
            $modelName = class_basename($module['model']);
            $className = "App\\Models\\{$modelName}";
            try {
                if (!class_exists($className)) {
                    $this->call('wbcore:make:model', ['name' => $modelName, '--base' => true]);
                }
            } catch (Exception $exp) {
                $this->call('wbcore:make:model', ['name' => $modelName, '--base' => true]);
            }
        }
        $this->warn('No More Models Needs To Publish');
    }


    /**
     *
     */
    private function replaceSupportFilesNamespace()
    {
        $namespace = $this->laravel->getNamespace();
        $files = File::files(app_path('Support'));
        foreach ($files as $file) {
            $fileName = $file->getFilename();
            $filePath = $file->getPath()."/".$fileName;
            file_put_contents($filePath, str_replace(
                "{{namespace}}",
                "{$namespace}Support",
                file_get_contents($filePath)
            ));
        }
    }

    /**
     * @param $folder
     */
    private function replaceFilesNamespace($folder)
    {
        $namespace = $this->laravel->getNamespace();
        $files = File::files(app_path("{$folder}"));
        $inDirFiles = File::files(app_path("{$folder}/*"));
        $files = collect($files)->merge($inDirFiles)->unique();

        foreach ($files as $file) {
            $fileName = $file->getFilename();
            $filePath = $file->getPath()."/".$fileName;
            file_put_contents($filePath, str_replace(
                "namespace Wbcodes\\SysCore\\",
                "namespace {$namespace}",
                file_get_contents($filePath)
            ));
        }
    }

    /**
     *
     */
    private function publishVendor()
    {
        if (in_array(Str::lower($this->ask("Are you sure you want republish site core files ? (yes/no).", 'no')), $this->acceptAnswers)) {
            $this->call('vendor:publish', ['--provider' => CoreServiceProvider::class]);
        }
    }

    /**
     *
     */
    private function runMigration()
    {
        if (in_array(Str::lower($this->ask("Do you want run migrations with seeders? (yes/no).", 'no')), $this->acceptAnswers)) {
            $this->call('migrate');
            $this->call('db:seed');
        } else {
            if (in_array(Str::lower($this->ask("Do you want run migrations? (yes/no).", 'no')), $this->acceptAnswers)) {
                $this->call('migrate');
            }

            if (in_array(Str::lower($this->ask("Do you want run seeders? (yes/no).", 'no')), $this->acceptAnswers)) {
                $this->call('db:seed');
            }
        }
    }

    /**
     *
     */
    private function runServer()
    {
        $year = now()->year;
        if (in_array(Str::lower($this->ask("Do you want run project on {$year} port on localhost? (yes/no).", 'yes')), $this->acceptAnswers)) {
            $this->call('wbcore:run');
        }
    }
}