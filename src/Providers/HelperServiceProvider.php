<?php

namespace Wbcodes\Core\Helpers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class HelperServiceProvider extends ServiceProvider
{
    protected $HELPERS_PATH = 'Helpers/';

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerHelpers();
    }

    /**
     * Register helpers.
     *
     * @return void
     */
    public function registerHelpers()
    {
        $helpers = glob($this->packagePath('/Helpers/*.php'));
        foreach ($helpers as $helper) {
            if (file_exists($helper)) {
                require_once $helper;
            }
        }
    }

    /**
     * @param $path
     *
     * @return string
     */
    private function packagePath($path)
    {
        return __DIR__."/$path";
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // load command files
        $this->registerCommands();

        // load publisher files
        $this->registerResources();
    }

    /**
     * Register commands.
     *
     * @return void
     */
    private function registerCommands()
    {
        $this->commands(Commands\ClearCommand::class);
        $this->commands(Commands\CacheCommand::class);
    }

    /**
     * Register resources.
     *
     * @return void
     */
    public function registerResources()
    {
        if ($this->isLumen() === false and function_exists('config_path')) {
            // function not available and 'publish' not relevant in Lumen

            $this->publishes(
                [
                    $this->packagePath($this->HELPERS_PATH.'helper.php') => app_path('Helpers/vendor/hyr-helpers.php'),
                ],
                'hyr-helpers'
            );
        }
    }

    /**
     * Check if package is running under Lumen app.
     *
     * @return bool
     */
    protected function isLumen()
    {
        return Str::contains($this->app->version(), 'Lumen') === true;
    }
}
