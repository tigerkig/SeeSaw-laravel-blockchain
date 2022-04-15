<?php
namespace App\PayModule\Coinpayments;

/**
 * CoinPayments Payment Method for TokenLite Application
 * To run this application, required TokenLite v1.1.2+ version.
 *
 * CoinPayments Controller
 *
 * @version 1.0.3
 * @since 1.0.0
 * @package TokenLite
 * @author Softnio
 *
 */

use Illuminate\Http\Request;
use App\PayModule\Coinpayments\CoinpaymentsPay;
use App\Http\Controllers\Controller;

class CoinpaymentsController extends Controller
{
    private $instance;

    public function __construct()
    {
        $this->instance = new CoinpaymentsPay();
    }
    public function success(Request $request)
    {
        if(method_exists($this->instance, 'coinpay_success')){
            return $this->instance->coinpay_success($request);
        }
    }

    public function callback(Request $request)
    {
        if(method_exists($this->instance, 'coinpay_callback')){
            return $this->instance->coinpay_callback($request);
        }
    }

    public function cancel(Request $request, $name='Order has been canceled due to payment!')
    {
        if(method_exists($this->instance, 'payment_cancel')){
            return $this->instance->payment_cancel($request, $name);
        }
    }
}
