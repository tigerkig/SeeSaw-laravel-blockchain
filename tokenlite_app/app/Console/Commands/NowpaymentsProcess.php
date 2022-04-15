<?php

namespace App\Console\Commands;

use App\PayModule\Module;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use Illuminate\Console\Command;

class NowpaymentsProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process {limit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Forces transactions to be checked with NOWPayments, just in case we missed a callback.';

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
        $limit = strtoupper($this->argument('limit'));
        $payment_method_model = new PaymentMethod();

        $module = new Module();
        $nowpayments_module = $module->getItemInstance('Nowpayments');

        $query = Transaction::where(
            'status', 'pending'
        )->orderBy(
            'updated_at', 'asc'
        );
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $pending_transactions = $query->get();

        $total = count($pending_transactions);
        $this->info('Reprocessing ' . $total . ' pending transactions, limit: ' . (!empty($limit) ? $limit : 'no limit'));

        $progress = 0;
        foreach ($pending_transactions as $transaction) {
            if ($progress % 10 == 0) {
                $this->info('Progress: ' . (($progress / $total) * 100) . ' %');
            }
            $nowpayments_module->nowpay_force_process($transaction->id);
            $progress += 1;
        }
    }
}
