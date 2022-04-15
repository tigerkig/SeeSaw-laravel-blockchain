<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Helpers\ReferralHelper;
use Illuminate\Console\Command;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'General command for testing via CLI';

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
        // insert code you want to test here
        // then enter `php artisan test` into the command line, 
        // prints and echo great work here
        $this->info('Hello World');
    }
}
