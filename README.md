# Wbcod Core Laravel Package

This is the repo for the [Wbcodes Core](https://github.com/wbcodes/site-core) project. A Wbcodes Site package to use CRM in your laravel project simply.

## Documentation

All documentation is available on the [Wiki Pages](https://github.com/wbcodes/site-core/src/master). We encourage you to read it. If you are new start with the [Installation Guide](https://github.com/wbcodes/site-core/src/master). To update
the package consult the [Updating Guide](https://github.com/wbcodes/site-core/src/master).

## Requirements

The current package requirements are:

- Laravel >= 7.x
- PHP >= 7.3

## Installation

To Install our package you can follow Steps in below.

> **Note:** the next steps are valid for a fresh installation procedure, if you are updating the package, refers to the [Updating](https://github.com/wbcodes/site-core/src/master) section.

1. On the root folder of your Laravel project, require the package using composer:

   ```
   composer require wbcodes/site-core
   ```

2. **(For Laravel 7+ only)** If you want to install the authentication scaffolding, then require the `laravel/ui` package using composer:

   ```
   composer require laravel/ui
   php artisan ui vue --auth
   ```

   > **Note:** it is a recommendation to read the [Laravel Authentication Documentation](https://laravel.com/docs/7.x/authentication) for details about the authentication scaffolding.

4. install required package tables using the next commands:
   ```
   php artisan notifications:table          
   php artisan queue:failed-table           
   php artisan queue:table                  
   php artisan session:table    
   ```


5. publish vendor files from imtilak/site-core packege:
   ```
   php artisan vendor:publish --provider="Wbcodes\SiteCore\Providers\SiteCoreServiceProvider"
   ```

   **or you can extract you need fiels using --tag={name}**
   ```
   php artisan vendor:publish --provider="Wbcodes\SiteCore\Providers\SiteCoreServiceProvider" --tag=migrations
   php artisan vendor:publish --provider="Wbcodes\SiteCore\Providers\SiteCoreServiceProvider" --tag=seeders
   php artisan vendor:publish --provider="Wbcodes\SiteCore\Providers\SiteCoreServiceProvider" --tag=factories
   php artisan vendor:publish --provider="Wbcodes\SiteCore\Providers\SiteCoreServiceProvider" --tag=public
   php artisan vendor:publish --provider="Wbcodes\SiteCore\Providers\SiteCoreServiceProvider" --tag=lang
   php artisan vendor:publish --provider="Wbcodes\SiteCore\Providers\SiteCoreServiceProvider" --tag=helpers
   php artisan vendor:publish --provider="Wbcodes\SiteCore\Providers\SiteCoreServiceProvider" --tag=controllers
   php artisan vendor:publish --provider="Wbcodes\SiteCore\Providers\SiteCoreServiceProvider" --tag=views
   ```

6. Add this code to ./routes/web.php file
   ```
   Auth::routes(['register' => false, 'verify' => true]);
   ```

7. Create data base and add database configruation to .env file
   ```shell
      DB_DATABASE ='your_db_name'
      DB_USERNAME ='username'
      DB_PASSWORD ='password'
   ```

8. After than you should be run migrations using this command line:
   ```
   > php artisan migrate --seed
   ```

   Or you can use to command
   ``` 
    > php artisan migrate
    > php artisan db:seed
   ```

   And you can refresh data base using
   ``` 
    > php artisan migrate:refresh
   ```

   Or you can fresh data base using
   ``` 
    > php artisan migrate:fresh
   ```

### Finally, install the required package resources using the next command:

   ```
   php artisan sitecore:install
   ```

> You can use **--force** option to overwrite existing files.
>
> You can use **--interactive** option to be guided through the process and choose what you want to install.

#### Add This middleware to  Http/Kernel.php as $routeMiddleware

   ```shell
   'wbcodes_settings'        => \Wbcodes\SiteCore\Middleware\SiteSettingsMiddleware::class,
   'wbcodes_verified_device' => \Wbcodes\SiteCore\Middleware\VerifyDevice::class,
  ```

#### use SiteUserTrait middleware to  Http/Kernel.php as $routeMiddleware

   ```php
<?php

      namespace App\Models;
      
      use Wbcodes\SiteCore\Traits\SiteUserTrait;
      
      class User extends Authenticatable
      {
            use CoreUserTrait;
      }
  ```

[comment]: <> (   > You can check the installation status of the package resources with the command `php artisan sitecore:status`)

## Updating

1. First, update the package with the next composer command:

   ```
   composer update wbcodes/site-core
   ```

2. Then, update the required Wbcodes Site assets resources

   > **Note:** if you are using Wbcodes Site for Laravel 5.x and are upgrading to Laravel 6 version, first delete the folder Wbcodes Site inside your `public/vendor` directory.

   In order to publish the new Wbcodes Site assets, execute the next command:

   ```
   php artisan sitecore:update
   ```

3. If you have [published]() and modified the default `master.blade.php` file or any other view provided with this package, you may need to update them too. Please, note there could be huge updates on these views, so it is highly recommended to
   backup your files previosuly. To update the views, you may follow next steps:

    - Make a copy (or backup) of the views you have modified, those inside the folder `resources/views/vendor/wbcodes/site-core`.

    - Publish the new set of views, using the `--force` option to overwrite the existing files.

      ```
      php artisan sitecore:install --only=views --force
      ```

    - Compare the new installed views with your backup files and redo the modifications you previously did to those views.


4. From time to time, new configuration options may be added or default values may be changed, so it is also a recommendation to verify and update the package config file if needed. To update the configuration, you may follow next steps:

    - Make a copy (or backup) of your current package configuration file, the `config/site_core.php` file.

    - Now, publish the new package configuration file and accept the overwrite warning (or use `--force` option to avoid the warning).

      ```
      php artisan sitecore:install --only=config
      ```

    - Compare with your backup configuration file and redo the modifications you previously made.


5. New helper functions may be added or modified, So it is also a recommendation to verify and update the package helper files if needed. To update the helpers, you may follow next steps:

    - Make a copy (or backup) of your current package helper files, the `config/site_core.php` file.

    - Now, publish the new package helper files and accept the overwrite warning (or use `--force` option to avoid the warning).

      ```
      php artisan sitecore:install --only=helpers
      ```

    - Compare with your backup configuration file and redo the modifications you previously made.

## Artisan Console Commands

This package provides some artisan commands in order to manage its resources. These commands are explained in the next sections. First, we going to give a little summary of the available resources, they are distinguished by a key name:

**>** `php artisan sitecore:clear`

**>** `php artisan sitecore:create:list-option`

**>** `php artisan sitecore:module:create`

**>** `php artisan sitecore:remove:reports-drafts`

**>** `php artisan sitecore:update:permissions`

**>** `php artisan sitecore:sync`

> `php artisan sitecore:clear`
> command will clear all of these (views, cache, config, route).

> `php artisan sitecore:create:list-option`
> command will create new list option.

> `php artisan sitecore:module:create`
> command will add new row to module table.

> `php artisan sitecore:remove:reports-drafts`
> command will be remove all draft reports which not saved.

> `php artisan sitecore:update:permissions`
> command will be update all permissions by removing don't used and create required permissions which not exists before.


> `php artisan sitecore:sync`
> .


[comment]: <> (- __`assets`__: The set of Wbcodes Site required assets, including dependencies like `Bootstrap` and `jQuery`.)

[comment]: <> (- __`config`__: The package configuration file.)

[comment]: <> (- __`translations`__: The set of translations files used by the package.)

[comment]: <> (- __`views`__: The set of package blade views that, in conjunction, provides the main layout you usually will extend.)

[comment]: <> (- __`routes`__: Routes definitions for the authentication scaffolding.)

[comment]: <> (### The `sitecore:install` Command)

[comment]: <> (You can install all the required and some additional package resources using the `php artisan sitecore:install` command. Without any option it will install the Wbcodes Site package assets, the configuration file and the translations.)

[comment]: <> (#### Command Options)

[comment]: <> (- `--force`: Use this option to force the overwrite of any existing files by default.)

[comment]: <> (- `--only=*`: Use this option to install only specific resources, the available resources are: **assets**, **config**, **translations**, **views**, or **routes**. This option can not be used with the `--with` option. Also, you can use this option)

[comment]: <> (  multiple times, for example:)

[comment]: <> (  ```)

[comment]: <> (  php artisan sitecore:install --only=config --only=main_views)

[comment]: <> (  ```)

[comment]: <> (- `--with=*`: Use this option to install with additional resources, the available resources are: **views** or **routes**. This option can be used multiple times, examples:)

[comment]: <> (  ```)

[comment]: <> (  php artisan sitecore:install --with=auth_views --with=basic_routes)

[comment]: <> (  php artisan sitecore:install --type=full --with=main_views)

[comment]: <> (  ```)

[comment]: <> (- `--interactive` : Use this to enable be guided through the installation process and choose what you want to install.)

[comment]: <> (### The `sitecore:update` Command)

[comment]: <> (This command is only a shortcut for `php artisan sitecore:install --force --only=assets`.)

[comment]: <> (> **Note:** this command will only update the Wbcodes Site assets located on the `public/vendor` folder. It will not update any other package resources, refer to section [Updating]&#40;https://github.com/wbcodes/site-core/src/master&#41; to check how to make a complete update.)

[comment]: <> (### The `sitecore:status` Command)

[comment]: <> (This command is very useful to check the package resources installation status, to run it execute the command:)

[comment]: <> (```)

[comment]: <> (php artisan sitecore:status)

[comment]: <> (```)

[comment]: <> (Once complete, it will display a table with all the available package resources and they installation status. The status can be one of the nexts:)

[comment]: <> (- **Installed**: This means that the resource is installed and matches with the package resource.)

[comment]: <> (- **Mismatch**: This means that the installed resource mismatch the package resource. This can happen due to an update available or when you have made some modifications on the installed resource.)

[comment]: <> (- **Not Installed**: This means that package resource is not installed.)

[comment]: <> (The table also shows a column which tells what resources are required for the package to work correctly. So, for these packages you should read **Installed** or **Mismatch** on the status column, otherwise the package won't work.)

## Credits

- [Zahir Hayrullah][link-author]
- [All Contributors][link-contributors]

[link-author]: https://github.com/zaherkhirullah

[link-contributors]: ../../contributors
