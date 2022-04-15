<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\User;
use App\Helpers\HubSpot;
use Illuminate\Console\Command;

class UpdateHubSpotContacts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-hubspot-contacts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates HubSpot with all the contacts';

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
		$this->info('Starting hubspot update');

        // the parameter inits the Hubspot module without vaidating settings,
        // this allows us to import just contacts if we know the api key and business unit is setup at least
        $hubSpot = new HubSpot(true); 

        $users = User::get();
        $total = count($users);
        foreach ($users as $i => $user) {
        	$this->info(round($i/$total, 4) * 100 . '%');
    		$hubSpot->put($user);
        }
    }
}
