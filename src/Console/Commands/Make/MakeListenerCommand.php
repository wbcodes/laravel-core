<?php

namespace Wbcodes\SiteCore\Console\Commands\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Wbcodes\SiteCore\Console\Commands\CoreCommandTrait;

class MakeListenerCommand extends GeneratorCommand
{
    use CoreCommandTrait;

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'sitecore:make:listener {name}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Generate Model Listeners Extend from package listeners';

    /**
     * The type of class being generated.
     * @var string
     */
    protected $type = '{name} Listener';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $stub_name = $this->argument('name');
        $stub_name = Str::snake($stub_name);
        $filePath = $this->stubPath("/Listeners/{$stub_name}.stub");;
        if (File::exists($filePath)) {
            return $filePath;
        }

        $this->type ="{$this->argument('name')} Listener";
        return $this->stubPath('listener.stub');
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
        return $rootNamespace.'\Listeners';
    }

    /**
     * Get the console command arguments.
     *
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

        return str_replace('DummyListener', $this->argument('name'), $stub);
    }
}
