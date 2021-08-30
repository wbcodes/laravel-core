<?php

namespace Wbcodes\SiteCore\Console\Commands\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Wbcodes\SiteCore\Console\Commands\CoreCommandTrait;

class MakeModelCommand extends GeneratorCommand
{
    use CoreCommandTrait;

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'sitecore:make:model 
                        {name : model class name}
                        {--a|--all : Generate a migration, seeder, factory, and resource controller for the model. }
                        {--c|--controller : Create a new controller for the model. }
                        {--f|--factory : Create a new factory for the model. }
                        {--force : Create the class even if the model already exists. }
                        {--m|--migration : Create a new migration file for the model. }
                        {--cl|--column : Create a new Column class for the model. }
                        {--e|--event : Create a new events for the model. }
                        {--l|--listener : Create a new listeners for the model. }
                        {--nt|--notification : Create a new notifications for the model. }
                        {--s|--seed : Create a new seeder file for the model. }
                        {--b|--base : Indicates if the generated model should be a custom intermediate table model. }
                        {--p|--pivot : Indicates if the generated model should be a custom intermediate table model. }
                        {--r|--resource : Indicates if the generated controller should be a resource controller. }
                        {--api : Indicates if the generated controller should be an API controller. }
                        {--model}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Generate a new Eloquent Model Extend from package model class';

    /**
     * The type of class being generated.
     * @var string
     */
    protected $type = 'Model';

    protected $nameArgument;

    /**
     * @return false
     * @throws FileNotFoundException
     */
    public function handle()
    {
        if (parent::handle() === false && !$this->option('force')) {
            return false;
        }
        $this->nameArgument = $this->nameArgument;

        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
            $this->input->setOption('migration', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('resource', true);
            $this->input->setOption('base', true);
            $this->input->setOption('column', true);
            $this->input->setOption('event', true);
            $this->input->setOption('listener', true);
            $this->input->setOption('notification', true);
        }

        if ($this->option('factory')) {
            $this->createFactory();
        }

        if ($this->option('migration')) {
            $this->createMigration();
        }

        if ($this->option('seed')) {
            $this->createSeeder();
        }

        if ($this->option('controller') || $this->option('resource') || $this->option('api')) {
            $this->createController();
        }

        if ($this->option('column')) {
            $this->createColumn();
        }

        if ($this->option('event')) {
            $this->createEvents();
        }

        if ($this->option('listener')) {
            $this->createListeners();
        }

        if ($this->option('notification')) {
            $this->createNotifications();
        }
    }

    /**
     * Create a model factory for the model.
     * @return void
     */
    protected function createFactory()
    {
        $factory = Str::studly($this->argument('name'));

        $this->call('make:factory', [
            'name'    => "{$factory}Factory",
            '--model' => $this->qualifyClass($this->getNameInput()),
        ]);
    }

    /**
     * Create a migration file for the model.
     * @return void
     */
    protected function createMigration()
    {
        $table = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));

        if ($this->option('pivot')) {
            $table = Str::singular($table);
        }

        try {
            $this->call('make:migration', [
                'name'     => "create_{$table}_table",
                '--create' => $table,
            ]);
        } catch (\Exception $exp) {
            $this->error("Migration already exists!");
        }
    }

    /**
     * Create a seeder file for the model.
     * @return void
     */
    protected function createSeeder()
    {
        $seeder = $this->nameArgument;

        $this->call('make:seeder', [
            'name' => "{$seeder}Seeder",
        ]);
    }

    /**
     * Create a controller for the model.
     * @return void
     */
    protected function createController()
    {
        $controller = $this->nameArgument;
//        $modelName = $this->qualifyClass($this->getNameInput());

        if (!class_exists("App\\Http\\Controllers\\{$controller}Controller")) {
            $this->call('sitecore:make:controller', array_filter([
                'name' => "{$controller}Controller",
//                '--model' => $this->option('resource') || $this->option('api') ? $modelName : null,
//                '--api'   => $this->option('api'),
            ]));
        }
    }

    /**
     *
     */
    protected function createColumn()
    {
        $column = $this->nameArgument;

        if (!class_exists("App\\Support\\{$column}Cols")) {
            $this->call('sitecore:make:column', ['name' => "{$column}Cols"]);
        }
    }

    /**
     *
     */
    private function createEvents()
    {
        $name = $this->nameArgument;
        $events = [
            "{$name}\\{$name}Created",
            "{$name}\\{$name}Updated"
        ];
        foreach ($events as $event_name) {
            if (!class_exists("App\\Events\\{$event_name}")) {
                $this->call('sitecore:make:event', ['name' => $event_name]);
            }
        }
    }

    /**
     *
     */
    private function createNotifications()
    {
        $name = $this->nameArgument;
        $notifications = [
            "Create",
            "Update",
        ];
        foreach ($notifications as $notification_type) {
            $notification_name = "{$name}\\{$notification_type}{$name}Notification"; // Contact\\CreateContact
            // App\Notification\Contact\CreateContact
            if (!class_exists("App\\Notifications\\{$notification_name}")) {
                $this->call('sitecore:make:notification', [
                    'name'   => $notification_name,
                    '--type' => $notification_type,
                ]);
            }
        }
    }

    /**
     *
     */
    private function createListeners()
    {
        $name = $this->nameArgument;
        $events = [
            "$name\\{$name}CreatedListener",
            "$name\\{$name}UpdatedListener"
        ];
        foreach ($events as $listener_name) {
            if (!class_exists("App\\Listeners\\{$listener_name}")) {
                $this->call('sitecore:make:listener', ['name' => $listener_name]);
            }
        }
    }

    /**
     * Get the stub file for the generator.
     * @return string
     */
    protected function getStub()
    {
        if (!$this->option('base')) {
            $stub_name = $this->argument('name');
            $stub_name = Str::snake($stub_name);
            $filePath = $this->stubPath("/models/{$stub_name}.stub");;
            if (File::exists($filePath)) {
                return $filePath;
            }
        }

        $modelStub = $this->option('base') ? "model.stub" : "base.model.stub";

        return $this->stubPath($modelStub);
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return is_dir(app_path('Models')) ? $rootNamespace.'\\Models' : $rootNamespace;
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     *
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);
        $stub = str_replace('DummyModel', $this->argument('name'), $stub);
        $tableName = Str::plural(Str::lower(Str::snake($this->argument('name'))));
        $stub = str_replace('DummyTable', $tableName, $stub);
        $stub = str_replace('DummyURL', str_replace('_', '-', $tableName), $stub);

        return $stub;
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the model.'],
        ];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['all', 'a', InputOption::VALUE_NONE, 'Generate a migration, seeder, factory, and resource controller for the model'],
            ['controller', 'c', InputOption::VALUE_NONE, 'Create a new controller for the model'],
            ['factory', 'f', InputOption::VALUE_NONE, 'Create a new factory for the model'],
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
            ['migration', 'm', InputOption::VALUE_NONE, 'Create a new migration file for the model'],
            ['column', 'cl', InputOption::VALUE_NONE, 'Create a new Column class for the model'],
            ['event', 'e', InputOption::VALUE_NONE, 'Create a new events for the model'],
            ['listener', 'l', InputOption::VALUE_NONE, 'Create a new listeners for the model'],
            ['notification', 'nt', InputOption::VALUE_NONE, 'Create a new notifications for the model'],
            ['seed', 's', InputOption::VALUE_NONE, 'Create a new seeder file for the model'],
            ['pivot', 'p', InputOption::VALUE_NONE, 'Indicates if the generated model should be a custom intermediate table model'],
            ['base', 'b', InputOption::VALUE_NONE, 'Indicates if the generated model should be a custom intermediate table model'],
            ['resource', 'r', InputOption::VALUE_NONE, 'Indicates if the generated controller should be a resource controller'],
            ['api', null, InputOption::VALUE_NONE, 'Indicates if the generated controller should be an API controller'],
        ];
    }

}
