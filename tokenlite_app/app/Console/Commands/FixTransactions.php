<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\IcoStage;
use App\Helpers\TokenCalculate as TC;
use App\Helpers\ReferralHelper;
use Illuminate\Console\Command;

class FixTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'price_fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix for the transaction issue we had on the 12th of March 2022';

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
        $this->info('Starting fix');
        
        $stage = active_stage();
        if (empty($stage)) {
            $this->info('Could not get current stage');
            return false;
        }

        $approved_transactions = Transaction::where(
            'liquidity', '>', '3000000'
        )->where(
            'status', 'approved'
        )->orderBy(
            'updated_at', 'desc'
        )->get();

        $this->info('Rolling back ' . count($approved_transactions) . ' transactions');

        foreach ($approved_transactions as $tnx) {
            IcoStage::token_add_to_account($tnx, null, 'sub');
            IcoStage::token_add_to_account($tnx, 'sub');
        }

        $this->info('Rollback completed');

        $problem_transaction = Transaction::where(
            'id', 8761
        )->get();

        if (count($problem_transaction) > 0) {
            $this->info('Fixing stage');

            $problem_transaction = $problem_transaction[0];
            $problem_transaction->base_amount = 1255755;
            IcoStage::token_add_to_account($problem_transaction, 'sub');

            $problem_transaction->base_amount = 47.12104375;
            IcoStage::token_add_to_account($problem_transaction, 'add');

            $problem_transaction->save();

            $this->info('Stage is fixed');
        } else {
            $this->info('Exiting, could not find problem transaction');
            return false;
        }

        $approved_transactions = Transaction::where(
            'liquidity', '>', '3000000'
        )->where(
            'status', 'approved'
        )->orderBy(
            'updated_at', 'asc'
        )->get();

        $this->info('Re-applying approved transactions');

        foreach ($approved_transactions as $transaction) {
            $tc = new TC();
            $stage = active_stage();
            $currency = $transaction->currency;
            $old_base_rate = $transaction->base_currency_rate;

            $transaction->currency_rate = ($transaction->currency_rate / $old_base_rate) * $stage->base_price;
            $transaction->base_currency_rate = $stage->base_price;
            $transaction->liquidity = $stage->liquidity;

            $transaction->tokens = $transaction->receive_amount / $transaction->currency_rate;
            $transaction->base_amount = $transaction->tokens * $transaction->base_currency_rate;
            $transaction->bonus_on_base = $tc->calc_token($transaction->tokens, 'bonus-base', $transaction);
            $transaction->bonus_on_token = $tc->calc_token($transaction->tokens, 'bonus-token', $transaction);
            $transaction->total_bonus = $tc->calc_token($transaction->tokens, 'bonus', $transaction);
            $transaction->total_tokens = $tc->calc_token($transaction->tokens, 'total', $transaction);
            $transaction->amount = round($tc->calc_token($transaction->tokens, 'price', $transaction)->$currency, max_decimal());

            $transaction->save();

            IcoStage::token_add_to_account($transaction, null, 'add');
            IcoStage::token_add_to_account($transaction, 'add');
        }

        $this->info('Completed re-applying approved transactions');
        
        $pending_transactions = Transaction::where(
            'status', 'pending'
        )->orderBy(
            'updated_at', 'asc'
        )->get();

        $this->info('Fixing ' . count($pending_transactions) . ' pending transactions');

        foreach ($pending_transactions as $transaction) {
            $tc = new TC();
            $stage = active_stage();
            $currency = $transaction->currency;
            $old_base_rate = $transaction->base_currency_rate;

            $transaction->currency_rate = ($transaction->currency_rate / $old_base_rate) * $stage->base_price;
            $transaction->base_currency_rate = $stage->base_price;
            $transaction->liquidity = $stage->liquidity;

            $transaction->tokens = $transaction->amount / $transaction->currency_rate;
            $transaction->base_amount = $transaction->tokens * $transaction->base_currency_rate;
            $transaction->bonus_on_base = $tc->calc_token($transaction->tokens, 'bonus-base', $transaction);
            $transaction->bonus_on_token = $tc->calc_token($transaction->tokens, 'bonus-token', $transaction);
            $transaction->total_bonus = $tc->calc_token($transaction->tokens, 'bonus', $transaction);
            $transaction->total_tokens = $tc->calc_token($transaction->tokens, 'total', $transaction);

            $transaction->save();
        }

        $this->info('Completed fixing pending transactions');

        $this->info('Fix is complete!!!');
    }
}
