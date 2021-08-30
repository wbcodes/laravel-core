<?php

namespace Wbcodes\SiteCore\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Wbcodes\SiteCore\Console\Commands\ClearCommand;
use Wbcodes\SiteCore\Console\Commands\Make\MakeBaseController;

class SiteCoreServiceProvider extends BaseProvider
{

    public function __construct($app)
    {
        parent::__construct($app);
        $this->commands_array = [
            ClearCommand::class,
            // UpdateListOptionsCommand::class,
            // UpdatePermissionsCommand::class,
            // UpdateModulesCommand::class,

            // MAKE COMMAND GENERATE FORM (stubs)
            MakeBaseController::class,
            // MakeColumnCommand::class,
            // MakeModelCommand::class,

            // Install or update and run Site package (controllers and models)
            // SiteInstallCommand::class,
            // SiteUpdateCommand::class,
            // RunSiteProject::class,
        ];
    }

    /**
     * Bootstrap services.
     * @return void
     */
    public function boot()
    {
//        $this->loadMigrationsFrom(__DIR__.'/../../publishable/database/migrations');
        Schema::defaultStringLength(191);

        $this->registerHelpers();

        $this->registerRoutes();

        $this->registerViewComposer();

        $this->loadTranslations();

        $this->loadViewsFrom($this->packageSrcPath('resources/views/web'), 'site');

        if ($this->app->runningInConsole()) {
            $this->registerCommands($this->commands_array);
        }

        $this->publishFiles();
    }

    /**
     * Register services.
     * @return void
     */
    public function register()
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(AuthServiceProvider::class);

        $this->registerConfig();
    }


    /**
     *
     */
    protected function loadTranslations()
    {
        $translationsPath = $this->packageSrcPath('resources/lang');

        $this->loadTranslationsFrom($translationsPath, 'site');
    }

    /**
     *
     */
    protected function registerViewComposer()
    {
        foreach (get_cpanel_modules() as $module => $moduleInfo) {
            $viewName = $moduleInfo['folder_name'];
            View::composer(["sitecore::{$viewName}.*"], function ($view) use ($module, $moduleInfo) {
                $controllerName = $moduleInfo['controller'];
                $view->with('module', $module);
                $view->with('controllerName', $controllerName);
            });
        }
    }

    /**
     *
     */
    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom($this->packageSrcPath('routes/api.php'));
            $this->loadRoutesFrom($this->packageSrcPath('routes/ajax.php'));
            $this->loadRoutesFrom($this->packageSrcPath('routes/web.php'));
        });
    }

    /**
     * @return array
     */
    protected function routeConfiguration()
    {
        return [
            'prefix'     => config('site.routes.prefix'),
            'middleware' => config('site.routes.middleware', ['web', 'auth']),
        ];
    }

    /**
     * Publish Files form package.
     * @return void
     */
    protected function publishFiles()
    {
        $publishes = [
            'assets'  => [
                $this->packagePath('../publishable/assets') => public_path('themes/frest/assets'),
            ],
            'lang'    => [
                $this->packageSrcPath('resources/lang') => resource_path('lang/vendor/site'),
            ],
            'helpers' => [
                $this->packageSrcPath('Helpers/site_helper.php') => app_path('Helpers/site_helper.php'),
            ],

            'config' => [
                $this->packagePath('../publishable/config/site.php') => config_path('site.php'),
            ],

            'views' => [
                $this->packageSrcPath("resources/views/web/auth")   => resource_path('views/auth'),
                $this->packageSrcPath("resources/views/web/errors") => resource_path('views/errors'),
                $this->packageSrcPath("resources/views/web/vendor") => resource_path('views/vendor'),
                $this->packageSrcPath("resources/views/web/users")  => resource_path('views/vendor/site/users'),

                $this->packageSrcPath("resources/views/web/base")    => resource_path('views/vendor/site/base'),
                $this->packageSrcPath("resources/views/web/layouts") => resource_path('views/vendor/site/layouts'),
            ],
        ];

        // Publish helper, controller, views and lang files files
        foreach ($publishes as $group => $files) {
            $this->publishes($files, $group);
        }

        // Publish database folder files
        $migrationFiles = $this->getMigrationFilesArray();
        $seederFiles = $this->getDirectoryFilesArray('seeders');
        $factoryFiles = $this->getDirectoryFilesArray('factories');

        // Publish support columns classes
        $SupportColumns = $this->getStubFilesArray('stubs', 'columns', app_path('Support'));

        $publishes_directory_files = [
            'columns'    => $SupportColumns,
            'migrations' => $migrationFiles,
            'seeders'    => $seederFiles,
            'factories'  => $factoryFiles,
        ];

        if ($this->app->runningInConsole()) {
            foreach ($publishes_directory_files as $group => $files) {
                $this->publishes($files, $group);
            }
        }
    }


    /**
     * Register package config.
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom($this->packagePath('src/config/site.php'), 'site');
    }

    /**
     * Register helpers file
     */
    public function registerHelpers()
    {
        // Load the helpers in app/Http/site_helper.php
        if (file_exists($file = app_path('Helpers/site_helper.php'))) {
            require $file;
        }
    }

}