<?php

namespace DavidzHolland\Laravel\MultipathMigrations;

use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Support\ServiceProvider;


class MultipathMigrationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {}

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->overrideMigrateCommands();
	}

    /**
     * Override the Laravel migrate command
     * This allows us to source migrations from multiple directories
     */
	private function overrideMigrateCommands()
    {
        $this->app->singleton('laravel.migrator', function ($app) {
            $repository = $app['migration.repository'];

            return new LaravelMigrator($repository, $app['db'], $app['files']);
        });

        $this->app->extend('command.migrate', function ($command, $app)
        {
            return new LaravelMigrateCommand($app['laravel.migrator']);
        });

        $this->app->extend('command.migrate.rollback', function ($command, $app)
        {
            return new RollbackCommand($app['laravel.migrator']);
        });

        $this->app->extend('command.migrate.reset', function ($command, $app)
        {
            return new ResetCommand($app['laravel.migrator']);
        });

        $this->app->extend('command.migrate.status', function ($command, $app)
        {
            return new LaravelStatusCommand($app['laravel.migrator']);
        });
    }

}
