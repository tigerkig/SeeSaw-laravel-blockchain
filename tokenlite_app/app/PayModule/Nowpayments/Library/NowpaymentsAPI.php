<?php
namespace App\PayModule\Nowpayments\Library;

/**
 * Nowpayments PHP API Wrapper
 *
 * @link https://documenter.getpostman.com/view/7907941/S1a32n38 Official Nowpayments API Documentation.
 */

use Auth;
use App\Models\User;
use App\PayModule\Nowpayments\Library\NowpaymentsCurlRequest;
use App\Helpers\IcoHandler;

class NowpaymentsAPI
{
    private $callback_url;
    private $private_key = '';
    private $public_key = '';
    private $test_mode = false;
    private $request_handler;

    /**
     * NowpaymentsAPI constructor.
     * @param $private_key
     * @param $public_key
     */
    public function __construct($private_key, $public_key, $test_mode)
    {

        // Set keys and format passed to class
        $this->private_key = $private_key;
        $this->public_key = $public_key;
        $this->test_mode = $test_mode;
        $this->callback_url = route('payment.nowpayments.callback');

        // Throw an error if the keys are not both passed
        try {
            if (empty($this->private_key) || empty($this->public_key)) {
                throw new Exception("Your private and public keys are not both set!");
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }

        // Initiate a cURL request object
        $this->request_handler = new NowpaymentsCurlRequest($this->private_key, $this->public_key, $this->test_mode);
    }

    public function GetStatus()
    {
        return $this->request_handler->get('status', []);
    }

    public function GetCurrencies()
    {
        return $this->request_handler->get('currencies', []);
    }

    public function GetEstimate($amount, $currency_from, $currency_to)
    {
        $fields = [
            'amount' => $amount,
            'currency_from' => $currency_from,
            'currency_to' => $currency_to
        ];
        return $this->request_handler->get('estimate', $fields);
    }

    public function GetPaymentStatus($payment_id)
    {
        return $this->request_handler->get('payment/' . $payment_id, []);
    }

    public function GetMinPaymentAmount($currency_from, $currency_to)
    {
        $fields = [
            'currency_from' => $currency_from,
            'currency_to' => $currency_to
        ];
        return $this->request_handler->get('min-amount', $fields);
    }

    public function CreatePayment($transaction_id, $amount, $price_currency, $pay_currency)
    {
        $fields = [
            'price_amount' => $amount, // Value of the currency (USD for example) the user sending
            'price_currency' => $price_currency, // Which currency (USD for example)
            'pay_currency' => $pay_currency, // What's the currency they are sending (BTC for example)
            'order_id' => $transaction_id, // unique ID generated on our end
            'case' => 'success', // this is for test mode only. will be removed from the request if test mode is off
            'ipn_callback_url' => $this->callback_url
        ];

        $userId = Auth::id();
        if (!empty($userId)) {
            $user = User::where('id', Auth::id())->first();
            if (!empty($user)) {
                $fields['order_description'] = $user->name . ' - ' . $user->email;
            }
        }

        return $this->request_handler->post('payment', $fields);
    }
}
