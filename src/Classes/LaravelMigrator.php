<?php

namespace DavidzHolland\Laravel;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Str;

class LaravelMigrator extends Migrator
{
    /**
     * The migration repository implementation.
     *
     * @var \Illuminate\Database\Migrations\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The name of the default connection.
     *
     * @var string
     */
    protected $connection;

    /**
     * The notes for the current operation.
     *
     * @var array
     */
    protected $notes = [];

    /**
     * The paths for the current operation.
     *
     * @var array
     */
    protected $paths = [];

    /**
     * Run the outstanding migrations at a given path.
     *
     * @param  array  $paths
     * @param  bool    $pretend
     * @return void
     */
    public function run($paths, $pretend = false)
    {
        $this->notes = [];
        $this->paths = $paths;

        $files = $this->getMigrationFiles($this->paths);

        // Once we grab all of the migration files for the path, we will compare them
        // against the migrations that have already been run for this package then
        // run each of the outstanding migrations against a database connection.
        $ran = $this->repository->getRan();

        $migrations = array_diff($files, $ran);

        $this->requireFiles($this->paths, $migrations);

        $this->runMigrationList($migrations, $pretend);
    }

    /**
     * Get all of the migration files in a given path.
     *
     * @param  string  $path
     * @return array
     */
    public function getMigrationFiles($paths)
    {
        $files = [];

        foreach($paths as $path)
        {
            $files = array_merge($files, $this->files->glob($path.'/*_*.php'));
        }

        // Once we have the array of files in the directory we will just remove the
        // extension and take the basename of the file which is all we need when
        // finding the migrations that haven't been run against the databases.
        if (count($files) == 0) {
            return [];
        }

        $files = array_map(function ($file) {
            return $this->pathToMigrationName($file);
        }, $files);

        // Once we have all of the formatted file names we will sort them and since
        // they all start with a timestamp this should give us the migrations in
        // the order they were actually created by the application developers.
        sort($files);

        return $files;
    }

    private function pathToMigrationName($path)
    {
        return str_replace('.php', '', basename($path));;
    }

    /**
     *
     */
    public function getMigrateFilesFullPaths()
    {
        $files = [];

        foreach($this->paths as $path)
        {
            $files = array_merge($files, $this->files->glob($path.'/*_*.php'));
        }

        return $files;
    }

    /**
     * Require in all the migration files in a given path.
     *
     * @param  string  $path
     * @param  array   $files
     * @return void
     */
    public function requireFiles($paths, array $files)
    {
        foreach ($files as $file)
        {
            $fullPath = $this->getMigrationFileFullPath($file);

            $this->files->requireOnce($fullPath);
        }
    }

    private function getMigrationFileFullPath($migrationName)
    {
        $fullPaths = $this->getMigrateFilesFullPaths($this->paths);

        foreach($fullPaths as $fullPath)
        {
            if ($this->pathToMigrationName($fullPath) == $migrationName)
            {
                return $fullPath;
            }
        }
        return false;
    }

    /**
     * Resolve a migration instance from a file.
     *
     * @param  string  $file
     * @return object
     */
    public function resolve($file)
    {
        $fileWithoutTimestamp = implode('_', array_slice(explode('_', $file), 4));
        $timestamp = implode('_', array_slice(explode('_', $file), 0, 4));

        $class = Str::studly($fileWithoutTimestamp);

        if ( ! class_exists($class))
        {
            // attempt class with timestamp appended
            $class = $class . '_' . $timestamp;
        }

        return new $class;
    }
}
