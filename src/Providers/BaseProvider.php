<?php

namespace Wbcodes\Core\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class BaseProvider extends ServiceProvider
{
    protected $commands_array = [];

    /**
     * @param $path
     * @param  null  $folderName
     * @return array
     */
    protected function getDirectoryFilesArray($path, $folderName = null)
    {
        $publishable_files = [];
        $path = $this->getFixedPathName($path, $folderName);
        $files = File::files($path);
        foreach ($files as $file) {
            $fileName = $file->getFilename();
            $publishable_files["{$path}/{$fileName}"] = database_path("{$folderName}/{$fileName}");
        }

        return $publishable_files;
    }

    /**
     * @param  null  $folderName
     * @return array
     */
    protected function getMigrationFilesArray($folderName = null)
    {
        $folderName = $folderName ?? 'migrations';
        $publishable_files = [];
        $path = $this->getFixedPathName('../publishable/database', $folderName);

        $files = File::files($path);
        foreach ($files as $file) {
            $fileName = $file->getFilename();
            $publishable_files["{$path}/{$fileName}"] = $this->generateMigrationFileName($folderName, $fileName);
        }

        return $publishable_files;
    }

    /**
     * @param $dir
     * @param $folderName
     * @param $path
     * @return array
     */
    protected function getStubFilesArray($dir, $folderName, $path)
    {
        $dir = $dir ?? 'stubs';
        $publishable_files = [];
        $files = File::files($this->packagePath("{$dir}/{$folderName}"));
        foreach ($files as $file) {
            $fileName = $file->getFilename();
            $publishable_files[$this->packagePath("{$dir}/{$folderName}/{$fileName}")] = str_replace('.stub', '.php', "{$path}/{$fileName}");

        }

        return $publishable_files;
    }

    /**
     * Publish Files form package.
     * @param $commands
     * @return void
     */
    protected function registerCommands($commands)
    {
        $commands = is_array($commands) ? $commands : [$commands];
        $this->commands($commands);
    }

    /**
     * @param $path
     * @param  null  $folderName
     * @return string
     */
    protected function getFixedPathName($path, $folderName = null)
    {
        $path = trim($path, '/');
        if ($folderName) {
            $path = $path."/".trim($folderName, '/');
        }

        return $this->packagePath($path);
    }

    /**
     * @param $path
     * @return string
     */
    protected function packagePath($path)
    {
        return __DIR__."/../../{$path}";
    }

    /**
     * @param $path
     * @return string
     */
    protected function packageSrcPath($path)
    {
        return $this->packagePath("src/{$path}");
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     * @param $folderName
     * @param $fileName
     * @return string
     */
    protected function generateMigrationFileName($folderName, $fileName)
    {
        $dirPath = $this->app->databasePath()."/{$folderName}/";
        $timestamp = date('Y_m_d_His');

        return Collection::make($dirPath)->flatMap(function ($path) use ($fileName) {
            return glob($path."*_{$fileName}");
        })->push($this->app->databasePath()."/migrations/{$timestamp}_{$fileName}")->first();
    }
}