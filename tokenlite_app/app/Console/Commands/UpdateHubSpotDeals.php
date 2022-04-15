<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\User;
use App\Helpers\HubSpot;
use Illuminate\Console\Command;

class UpdateHubSpotDeals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-hubspot-deals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates HubSpot with all the contacts and deals associated with them';

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
        $hubSpot = new HubSpot();
        $transactions = Transaction::where(['tnx_type' => 'purchase'])->get();
        $total = count($transactions);
        foreach ($transactions as $i => $tnx) {
        	$this->info(round($i/$total, 4) * 100 . '%');
        	if (empty($tnx->user)) {
        		continue;
        	}
        	$user = User::where(['id' => $tnx->user])->get();
        	if (!empty($user[0]) && !empty($tnx)) {
        		$user = $user[0];
        		$this->info('user: ' . $user->id . ' tnx: ' . $tnx->id);
        		$hubSpot->put($user, $tnx);
        	}
        }
    }
}
