<?php

namespace Wbcodes\Core\Providers;

use Illuminate\Support\Facades\Schema;
use Wbcodes\Core\Console\Commands\Clear\ClearDraftReportsCommand;
use Wbcodes\Core\Console\Commands\Clear\ClearNotificationsTableCommand;
use Wbcodes\Core\Console\Commands\Clear\ClearRedisCacheCommand;
use Wbcodes\Core\Console\Commands\Clear\ClearSiteCoreCommand;
use Wbcodes\Core\Console\Commands\Clear\ClearTempAttachmentsCommand;
use Wbcodes\Core\Console\Commands\Create\CreateListOptionsCommand;
use Wbcodes\Core\Console\Commands\Create\CreateModuleCommand;
use Wbcodes\Core\Console\Commands\Import\ImportElasticSearchModulesCommand;
use Wbcodes\Core\Console\Commands\Make\MakeBaseController;
use Wbcodes\Core\Console\Commands\Make\MakeColumnCommand;
use Wbcodes\Core\Console\Commands\Make\MakeEventCommand;
use Wbcodes\Core\Console\Commands\Make\MakeListenerCommand;
use Wbcodes\Core\Console\Commands\Make\MakeModelCommand;
use Wbcodes\Core\Console\Commands\Make\MakeNotificationCommand;
use Wbcodes\Core\Console\Commands\Remove\RemoveDraftReportsCommand;
use Wbcodes\Core\Console\Commands\Remove\RemoveModelCommand;
use Wbcodes\Core\Console\Commands\RunSiteCoreProject;
use Wbcodes\Core\Console\Commands\InstallSiteCoreCommand;
use Wbcodes\Core\Console\Commands\Update\UpdateDefaultCustomViewsCommand;
use Wbcodes\Core\Console\Commands\Update\UpdateListOptionsCommand;
use Wbcodes\Core\Console\Commands\Update\UpdateModulesCommand;
use Wbcodes\Core\Console\Commands\Update\UpdatePermissionsCommand;
use Wbcodes\Core\Console\Commands\Update\UpdateSiteCoreCommand;
use Wbcodes\Core\Console\Commands\Update\UpdateSyncCommand;

class CoreServiceProvider extends BaseProvider
{

    public function __construct($app)
    {
        parent::__construct($app);

        $this->commands_array = [
            // CLEAR COMMANDS
            ClearDraftReportsCommand::class,
            ClearNotificationsTableCommand::class,
            ClearRedisCacheCommand::class,
            ClearSiteCoreCommand::class,
            ClearTempAttachmentsCommand::class,

            // IMPORT COMMANDS
            ImportElasticSearchModulesCommand::class,

            // MAKE COMMAND GENERATE FORM (stubs)
            MakeBaseController::class,
            MakeColumnCommand::class,
            MakeEventCommand::class,
            MakeListenerCommand::class,
            MakeModelCommand::class,
            MakeNotificationCommand::class,

            // REMOVE COMMANDS
            RemoveDraftReportsCommand::class,
            RemoveModelCommand::class,

            // CREATE COMMANDS
            CreateListOptionsCommand::class,
            CreateModuleCommand::class,

            // UPDATE COMMANDS
            UpdateDefaultCustomViewsCommand::class,
            UpdateListOptionsCommand::class,
            UpdateModulesCommand::class,
            UpdatePermissionsCommand::class,
            UpdateSiteCoreCommand::class,
            UpdateSyncCommand::class,

            // Install or update and run Site package (controllers and models)
            InstallSiteCoreCommand::class,
            RunSiteCoreProject::class,
        ];
    }

    /**
     * Bootstrap services.
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        $this->registerHelpers();

        $this->loadViewsFrom($this->packageSrcPath('resources/views'), 'wbcore');

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
        $this->registerConfig();
    }

    /**
     * Publish Files form package.
     * @return void
     */
    protected function publishFiles()
    {
        $publishes = [
            'helpers' => [
//                $this->packageSrcPath('Helpers/site_helper.php') => app_path('Helpers/site_helper.php'),
            ],

            'config' => [
                $this->packageSrcPath('Config/wbcore.php') => config_path('wbcore.php'),
            ],

            'views' => [
            ],
        ];

        // Publish helper, controller, views and lang files files
        foreach ($publishes as $group => $files) {
            $this->publishes($files, $group);
        }

        // Publish support columns classes
//        $SupportColumns = $this->getStubFilesArray('stubs', 'columns', app_path('Support'));
//
        $publishes_directory_files = [
//            'columns' => $SupportColumns,
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
        $this->mergeConfigFrom($this->packagePath('src/config/wbcore.php'), 'wbcore');
    }

    /**
     * Register helpers file
     */
    public function registerHelpers()
    {
        // Load the helpers in app/Http/site_helper.php
        //        if (file_exists($file = app_path('Helpers/site_helper.php'))) {
        //            require $file;
        //        }
    }

}