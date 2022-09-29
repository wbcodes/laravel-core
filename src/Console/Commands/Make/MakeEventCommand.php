<?php

namespace Wbcodes\Core\Console\Commands\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Wbcodes\Core\Console\Commands\CoreCommandTrait;

class MakeEventCommand extends GeneratorCommand
{
    use CoreCommandTrait;

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'wbcore:make:event {name}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Generate Model Events Extend from package events';

    /**
     * The type of class being generated.
     * @var string
     */
    protected $type = 'Event';

    /**
     * Get the stub file for the generator.
     * @return string
     */
    protected function getStub()
    {
        $stub_name = $this->argument('name');
        $stub_name = Str::snake($stub_name);
        $filePath = $this->stubPath("/Events/{$stub_name}.stub");;
        if (File::exists($filePath)) {
            return $filePath;
        }

        $this->type ="{$this->argument('name')} Event";
        return $this->stubPath('event.stub');
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
        return $rootNamespace.'\Events';
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
        $name = str_replace('Created', '', class_basename($name));
        $name = str_replace('Updated', '', class_basename($name));
        $stub = str_replace('DummyModel', $name, $stub);
        $stub = str_replace('DummyEvent', $this->argument('name'), $stub);

        return $stub;
    }
}
