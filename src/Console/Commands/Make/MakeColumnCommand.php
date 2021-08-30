<?php

namespace Wbcodes\SiteCore\Console\Commands\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Wbcodes\SiteCore\Console\Commands\CoreCommandTrait;

class MakeColumnCommand extends GeneratorCommand
{
    use CoreCommandTrait;

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'sitecore:make:column {name}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Generate Model Extend from package model';

    /**
     * The type of class being generated.
     * @var string
     */
    protected $type = 'Column';

    /**
     * Get the stub file for the generator.
     * @return string
     */
    protected function getStub()
    {
        $stub_name = Str::snake($this->argument('name') ?? '');
        $filePath = $this->stubPath("columns/{$stub_name}.stub");
        if (File::exists($filePath)) {
            return $filePath;
        }

        return $this->stubPath('column.stub');
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
        return $rootNamespace.'\Support';
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the column.'],
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
        $name = str_replace(
            ['cols', 'Cols'],
            ['', ''],
            $this->argument('name')
        );
        $stub = str_replace('DummyColumn', $name, $stub);

        $table = Str::plural(Str::snake($name));

        $stub = str_replace('DummyTable', $table, $stub);

        $model = Str::singular(Str::studly($name));
        $stub = str_replace('DummyModel', $model, $stub);

        $permission = $model;
        $stub = str_replace('DummyPermission', $permission, $stub);

        return $stub;
    }
}
