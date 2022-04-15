<?php
namespace App\PayModule\Nowpayments;

/**
 * Nowpayments Payment Method for TokenLite Application
 * To run this application, required TokenLite v1.1.2+ version.
 *
 * Nowpayments Controller
 */

use Illuminate\Http\Request;
use App\PayModule\Nowpayments\NowpaymentsPay;
use App\Http\Controllers\Controller;

class NowpaymentsController extends Controller
{
    private $instance;

    public function __construct()
    {
        $this->instance = new NowpaymentsPay();
    }

    public function success(Request $request)
    {
        if(method_exists($this->instance, 'nowpay_success')){
            return $this->instance->nowpay_success($request);
        }
    }

    public function callback(Request $request)
    {
        if(method_exists($this->instance, 'nowpay_callback')){
            return $this->instance->nowpay_callback($request);
        }
    }

    public function cancel(Request $request, $name='Order has been canceled due to payment!')
    {
        if(method_exists($this->instance, 'payment_cancel')){
            return $this->instance->payment_cancel($request, $name);
        }
    }
}
