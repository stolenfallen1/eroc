<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeModelControllerMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:modelcontrollermigration {name : The name of the model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a model, controller, and migration in one command';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $name = $this->argument('name');

        // Generate the model
        $this->call('make:model', [
            'name' => $name
        ]);

        // Generate the controller
        $this->call('make:controller', [
            'name' => $name.'Controller',
            '--resource' => true
        ]);

        // Generate the migration
        // $this->call('make:migration', [
        //     'name' => 'create_' . Str::snake(Str::pluralStudly($name)) . '_table',
        //     '--create' => Str::snake(Str::pluralStudly($name))
        // ]);

    }
}
