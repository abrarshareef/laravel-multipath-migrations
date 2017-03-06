<?php

namespace DavidzHolland\Laravel\MultipathMigrations;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Database\Console\Migrations\MigrateCommand;


class LaravelMigrateCommand extends MigrateCommand
{
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        //place this before any script you want to calculate time
        $time_start = microtime(true);

        if (! $this->confirmToProceed()) {
            return;
        }

        if ($this->input->getOption('before'))
        {
            $this->call($this->input->getOption('before'));
        }

        $this->prepareDatabase();

        // The pretend option can be used for "simulating" the migration and grabbing
        // the SQL queries that would fire if the migration were to be run against
        // a database for real, which is helpful for double checking migrations.
        $pretend = $this->input->getOption('pretend');

        // Next, we will check to see if a path option has been defined. If it has
        // we will use the path relative to the root of this installation folder
        // so that migrations may be run for any path within the applications.

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

        $this->migrator->run($paths, $pretend);

        // Once the migrator has run we will grab the note output and send it out to
        // the console screen, since the migrator itself functions without having
        // any instances of the OutputInterface contract passed into the class.
        foreach ($this->migrator->getNotes() as $note) {
            $this->output->writeln($note);
        }

        // Finally, if the "seed" option has been given, we will re-run the database
        // seed task to re-populate the database, which is convenient when adding
        // a migration and a seed at the same time, as it is only this command.
        if ($this->input->getOption('seed')) {
            $this->call('db:seed', ['--force' => true]);
        }

        if ($this->input->getOption('after'))
        {
            $this->call($this->input->getOption('after'));
        }

        $this->info('...Elapsed Time: '. $this->getElapsedTime($time_start) . 's');
    }

    private function getElapsedTime($time_start)
    {
        $time_end = microtime(true);

        //dividing with 60 will give the execution time in minutes other wise seconds
        $execution_time = round(($time_end - $time_start) * 100) / 100;

        //execution time of the script
        return $execution_time;
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

            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],

            ['paths', null, InputOption::VALUE_OPTIONAL, 'The paths of migrations files to be executed.'],

            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],

            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'],

            ['before', null, InputOption::VALUE_NONE, 'Command to be run before migrations.'],

            ['after', null, InputOption::VALUE_NONE, 'Command to be run after migrations.'],
        ];
    }
}
