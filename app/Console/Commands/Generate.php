<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Generate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate an .llm.md file for a project';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        dd($this->argument('path'));
    }
}
