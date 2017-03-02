<?php

namespace DavidzHolland\Laravel;

use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Database\Console\Migrations\StatusCommand;

class LaravelStatusCommand extends StatusCommand
{
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if (! $this->migrator->repositoryExists()) {
            return $this->error('No migrations found.');
        }

        $this->migrator->setConnection($this->input->getOption('database'));

        if (! is_null($paths = $this->input->getOption('paths')))
        {
            if ( ! is_array($paths)) $paths = [ $paths ];

            foreach($paths as $key => $path) {
                $paths[$key] = $this->laravel->basePath() . '/' . $path;
            }
        } else {
            $paths = [
                $this->getMigrationPath()
            ];
        }

        $ran = $this->migrator->getRepository()->getRan();

        $migrations = [];

        foreach ($this->getAllMigrationFiles($paths) as $migration) {
            $migrations[] = in_array($migration, $ran) ? ['<info>Y</info>', $migration] : ['<fg=red>N</fg=red>', $migration];
        }

        if (count($migrations) > 0) {
            $this->table(['Ran?', 'Migration'], $migrations);
        } else {
            $this->error('No migrations found');
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],

            ['paths', null, InputOption::VALUE_OPTIONAL, 'The paths of migrations files to use.'],
        ];
    }
}
