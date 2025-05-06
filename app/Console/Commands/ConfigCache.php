<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ConfigCache extends Command
{
    
    protected $signature = 'config:cache';
    protected $description = 'Cache the configuration files';
    
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
     * @return mixed
     */
    public function handle()
    {
        $this->call('config:cache');
    }
}
