<?php

namespace App\PayModule\Paypal;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\PayModule\ModuleHelper;
use App\PayModule\Paypal\PaypalPay;
use App\Http\Controllers\Controller;

class PaypalController extends Controller
{
    private $instance;

    public function __construct()
    {
        $this->instance = new PaypalPay();
    }
    public function success(Request $request)
    {
        if(method_exists($this->instance, 'paypal_success')){
            return $this->instance->paypal_success($request);
        }
    }

    public function cancel(Request $request, $name='Order has been canceled due to payment!')
    {
        if(method_exists($this->instance, 'payment_cancel')){
            return $this->instance->payment_cancel($request, $name);
        }
    }

    public function email_notify(Request $request) {
        $tnx_id = isset($request->tnx) ? $request->tnx : false;
        $mail_type = isset($request->notify) ? $request->notify : false;

        if($tnx_id && $mail_type) {
            $tnx = Transaction::where('id', $tnx_id)->first();
            if(empty($tnx)) return false;
            return ModuleHelper::enotify($tnx, $mail_type, $request);
        }
        return false;
    }
}
