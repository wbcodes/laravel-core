<?php

namespace Wbcodes\SiteCore\Console\Commands\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Wbcodes\SiteCore\Console\Commands\CoreCommandTrait;

class MakeNotificationCommand extends GeneratorCommand
{
    use CoreCommandTrait;

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'sitecore:make:notification
                            {name : notification class name}
                            {--t|--type=Create : Type of notification Create or Update}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Generate Model notifications Extend from package notifications';

    /**
     * The type of class being generated.
     * @var string
     */
    protected $type = 'Notification';

    /**
     * Get the stub file for the generator.
     * @return string
     */
    protected function getStub()
    {
        $stub_name = $this->argument('name');
        $stub_name = Str::snake($stub_name);
        $filePath = $this->stubPath("/notifications/{$stub_name}.stub");;
        if (File::exists($filePath)) {
            return $filePath;
        }

        $this->type = "{$this->argument('name')} Notification";

        $name = "notification.create.stub";

        if (in_array($this->option('type'), ['update', 'Update'])) {
            $name = "notification.update.stub";
        }

        return $this->stubPath($name);
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
        return $rootNamespace.'\Notifications';
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
        $name = $this->argument('name');
        $name = str_replace(
            ["create", "Create", "update", "Update", "Notification", "Notifications", "notification", "notifications",],
            ['', '', '', '', '', '', '', '', '',],
            class_basename($name)
        );

        $tableName = Str::snake(Str::plural($name));
        $stub = str_replace('DummyTable', $tableName, $stub);

        $title = str_replace('-', ' ', Str::ucfirst(Str::singular(Str::kebab($name))));
        $stub = str_replace('DummyTitle', $title, $stub);

        $stub = str_replace('DummyNotification', $this->argument('name'), $stub);

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
//            ['all', 'a', InputOption::VALUE_NONE, 'Generate a migration, seeder, factory, and resource controller for the model'],
            ['type', 't', InputOption::VALUE_NONE, 'Create a new notification with type create or update'],
        ];
    }
}
