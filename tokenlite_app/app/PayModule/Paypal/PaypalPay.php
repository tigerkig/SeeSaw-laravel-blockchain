<?php

namespace App\PayModule\Paypal;

/**
 * PaypalPay Helper Class
 */

use Auth;
use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Notifications\TnxStatus;
use App\Helpers\TokenCalculate as TC;
use Illuminate\Support\Facades\Log;

// Models
use App\Models\User;
use App\Models\Setting;
use App\Models\IcoStage;
use App\Models\Transaction;
use App\Models\PaymentMethod;
use App\Helpers\ReferralHelper;

// Paypal
use Illuminate\Validation\ValidationException;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalHttp\HttpException;
use Illuminate\Http\Response;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

class PaypalPay
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string
     */

    public function apiContext()
    {
        if (PaymentMethod::get_data('paypal')->sandbox == 0) {
            $environment = new ProductionEnvironment(PaymentMethod::get_data('paypal')->clientId, PaymentMethod::get_data('paypal')->clientSecret);
        } else {
            $environment = new SandboxEnvironment(PaymentMethod::get_data('paypal')->clientId, PaymentMethod::get_data('paypal')->clientSecret);
        }

        return new PayPalHttpClient($environment);
    }

    /**
     * @return mixed
     * @throws ValidationException
     * @version 1.2.2
     * @since 1.2.2
     */
    private function createPayment($amount, $currency, $tnxId)
    {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            "intent" => "CAPTURE",
            "purchase_units" => [[
                "reference_id" => $tnxId,
                "amount" => [
                    "value" => $amount,
                    "currency_code" => $currency
                ]
            ]],
            "application_context" => [
                "cancel_url" => route('payment.paypal.cancel'),
                "return_url" => route('payment.paypal.success'),
            ]
        ];

        return $this->apiContext()->execute($request);
    }

    /**
     * @param $transaction
     * @param $paymentMethodDetails
     * @return bool
     * @version 1.2.2
     * @since 1.2.2
     */
    public function verifyPayment($paymentId)
    {
        $request = new OrdersCaptureRequest($paymentId);
        $request->prefer('return=representation');
        try {
            $response = $this->apiContext()->execute($request);
            return $response->result;
        } catch (HttpException $ex) {
            Log::error('PAYPAL_VERIFICATION', [$ex->getMessage()]);
            return false;
        }
    }


    /**
     * Make Payment via PayPal
     *
     * @return void
     * @since 1.0.2
     * @version 1.0.1
     */
    public function paypal_pay(Request $request)
    {
        if (version_compare(phpversion(), '7.1', '>=')) {
            ini_set('precision', get_setting('token_decimal_max', 8));
            ini_set('serialize_precision', -1);
        }
        $tc = new TC();
        $token = $request->input('pp_token');
        $calc_token = $tc->calc_token($token, 'array');
        $current_price = $tc->get_current_price();
        $exrate = Setting::exchange_rate($current_price, 'array');

        $currency = strtolower($request->input('pp_currency'));
        $currency_rate = isset($exrate['fx'][$currency]) ? $exrate['fx'][$currency] : 0;
        $base_currency = strtolower(base_currency());
        $all_currency_rate = isset($exrate['except']) ? json_encode($exrate['except']) : json_encode([]);
        $base_currency_rate = isset($exrate['base']) ? $exrate['base'] : 0;
        $trnx_data = [
            'token' => round($token, min_decimal()),
            'bonus_on_base' => round($calc_token['bonus-base'], min_decimal()),
            'bonus_on_token' => round($calc_token['bonus-token'], min_decimal()),
            'total_bonus' => round($calc_token['bonus'], min_decimal()),
            'total_tokens' => round($calc_token['total'], min_decimal()),
            'base_price' => round($calc_token['price']->base, max_decimal()),
            'amount' => round($calc_token['price']->$currency, 2),
        ];

        try {
            $tnxId = set_id(rand(100, 999), 'trnx');
            $payment = $this->createPayment($trnx_data['amount'], strtoupper($currency), $tnxId);
            $approvalLink = false;
            if (($payment->statusCode == Response::HTTP_CREATED)) {
                $filtered = (array) array_values(array_filter(data_get($payment->result, 'links'), function ($item) {
                    return data_get($item, 'rel') == 'approve';
                }));
                $approvalLink = array_shift($filtered)->href;
            }

            $save_data = [
                'created_at' => Carbon::now()->toDateTimeString(),
                'tnx_id' => $tnxId,
                'tnx_type' => 'purchase',
                'tnx_time' => Carbon::now()->toDateTimeString(),
                'tokens' => $trnx_data['token'],
                'bonus_on_base' => $trnx_data['bonus_on_base'],
                'bonus_on_token' => $trnx_data['bonus_on_token'],
                'total_bonus' => $trnx_data['total_bonus'],
                'total_tokens' => $trnx_data['total_tokens'],
                'stage' => active_stage()->id,
                'liquidity' => active_stage()->liquidity,
                'user' => Auth::id(),
                'amount' => $trnx_data['amount'],
                'base_amount' => $trnx_data['base_price'],
                'base_currency' => $base_currency,
                'base_currency_rate' => $base_currency_rate,
                'currency' => $currency,
                'currency_rate' => $currency_rate,
                'all_currency_rate' => $all_currency_rate,
                'payment_method' => 'paypal',
                'receive_currency' => data_get($payment, 'result.gross_amount.currency_code'),
                'payment_to' => get_pm('paypal')->email,
                'payment_id' => data_get($payment->result, 'id'),
                'extra' => json_encode(['url' => $approvalLink, 'token' => data_get($payment, 'result.id'), 'info' => data_get($payment, 'result')]),
                'details' => 'Tokens Purchase',
                'status' => 'pending',
            ];
            $ret['msg'] = 'success';
            $ret['message'] = __('messages.trnx.created');;
            $iid = Transaction::insertGetId($save_data);

            if ($approvalLink) {
                $tnx = Transaction::where('id', $iid)->first();
                $tnx->tnx_id = set_id($iid, 'trnx');
                $tnx->save();

                IcoStage::token_add_to_account($tnx, 'add');
                $ret['param'] = ['cta' => route('payment.paypal.notify'), 'tnx' => $tnx->id, 'notify' => 'order-placed', 'user' => 'submit-online-user', 'system' => 'placed-admin'];
                $ret['link'] = $approvalLink;
            } else {
                $ret['msg'] = 'error';
                $ret['message'] = __('messages.trnx.canceled');
                Transaction::where('id', $iid)->delete();
            }

            if ($request->ajax()) {
                return response()->json($ret);
            }
            return redirect($payment->getApprovalLink());
        } catch (HttpException $ex) {
            if ($request->ajax()) {
                $ret['msg'] = 'info';
                $ret['message'] = !empty($ex->getMessage()) ? $ex->getMessage() : 'Unable to connect with PayPal.';
                if (!empty($ex->getMessage())) {
                    Log::warning("PayPal: Invalid API credentials / " . $ex->getMessage());
                }
                return response()->json($ret);
            }
            return $this->payment_cancel($ex->getMessage());
        }
    }

    /**
     * Success Callback Payment via PayPal
     *
     * @return void
     * @since 1.0.2
     * @version 1.0.0
     */
    public function paypal_success(Request $request)
    {
        try {
            $paymentId = $request->get('token');
            try {
                $payment = $this->verifyPayment($paymentId);
                $tranx = Transaction::where('payment_id', $paymentId)->first();
                $_old_status = $tranx->status;
                $tranx->status = ($payment->status == 'COMPLETED') ? 'approved' : (($payment->status == 'VOIDED') ? 'rejected' : 'canceled');
                if ($payment->status == 'COMPLETED') {
                    $tranx->wallet_address = data_get($payment, 'payer.email_address');
                    $tranx->receive_amount = data_get($payment, 'purchase_units.0.payments.captures.0.amount.value');
                    $tranx->tnx_time = date('Y-m-d H:i:s', strtotime(data_get($payment, 'purchase_units.0.payments.captures.0.create_time', 'now')));
                    $tranx->checked_by = json_encode(['name' => 'paypal', 'id' => $paymentId]);
                    $tranx->checked_time = Carbon::now()->toDateTimeString();
                    $tranx->extra = json_encode((array) $payment);
                    $tranx->save();

                    if ($_old_status == 'deleted') {
                        $tranx->status = 'missing';
                        $tranx->save();
                        return redirect()->route('user.token')->with(['info', 'Thank you! We received your payment but we found something wrong in your transaction, please contact with us with the order id: ' . $tranx->tnx_id . '.']);
                    } else {
                        if ($tranx->status == 'approved' && is_active_referral_system()) {
                            $referral = new ReferralHelper($tranx);
                            $referral->addToken('refer_to');
                            $referral->addToken('refer_by');
                        }
                        IcoStage::token_add_to_account($tranx, null, 'add');
                        try {
                            $tranx->tnxUser->notify((new TnxStatus($tranx, 'successful-user')));
                            if (get_emailt('order-successful-admin', 'notify') == 1) {
                                notify_admin($tranx, 'successful-admin');
                            }
                        } catch (\Exception $e) {
                            $response['error'] = $e->getMessage();
                        }
                        return redirect(route('user.token'))->with(['success' => 'Thank You, We have received your payment!', 'modal' => 'success']);
                    }
                } else {
                    $tranx->save();
                    IcoStage::token_add_to_account($tranx, 'sub');

                    return redirect(route('user.token'))->with(['warning' => 'Sorry, We have not received your payment!', 'modal' => 'failed']);
                }
            } catch (\Exception $ex) {
                return $this->payment_cancel($request);
            }

        } catch (\Exception $ex) {
            return $this->payment_cancel($request);
        }
    }

    /**
     * Payment Cancel
     *
     * @return void
     * @since 1.0.2
     * @version 1.0.0
     */
    public function payment_cancel(Request $request, $name = 'Order has been canceled due to payment!')
    {
        if ($request->get('tnx_id') || $request->get('token')) {
            $id = $request->get('tnx_id');
            $pay_token = $request->get('token');
            if ($pay_token != null) {
                $pay_token = (starts_with($pay_token, 'EC-') ? str_replace('EC-', '', $pay_token) : $pay_token);
            }
            $apv_name = ucfirst('paypal');
            if (!empty($id)) {
                $tnx = Transaction::where('id', $id)->first();
            } elseif (!empty($pay_token)) {
                $tnx = Transaction::where('payment_id', $pay_token)->first();
                if (empty($tnx)) {
                    $tnx = Transaction::where('extra', 'like', '%' . $pay_token . '%')->first();
                }
            } else {
                return redirect(route('user.token'))->with(['danger' => "Sorry, we're unable to proceed the transaction. This transaction may deleted. Please contact with administrator.", 'modal' => 'danger']);
            }
            if ($tnx) {
                $_old_status = $tnx->status;
                if ($_old_status == 'deleted' || $_old_status == 'canceled') {
                    $name = "Your transaction is already " . $_old_status . ". Sorry, we're unable to proceed the transaction.";
                } elseif ($_old_status == 'approved') {
                    $name = "Your transaction is already " . $_old_status . ". Please check your account balance.";
                } elseif (!empty($tnx) && ($tnx->status == 'pending' || $tnx->status == 'onhold')) {
                    $tnx->status = 'canceled';
                    $tnx->checked_by = json_encode(['name' => $apv_name, 'id' => $pay_token]);
                    $tnx->checked_time = Carbon::now()->toDateTimeString();
                    $tnx->save();

                    IcoStage::token_add_to_account($tnx, 'sub');
                    try {
                        $tnx->tnxUser->notify((new TnxStatus($tnx, 'canceled-user')));
                        if (get_emailt('order-rejected-admin', 'notify') == 1) {
                            notify_admin($tnx, 'rejected-admin');
                        }
                    } catch (\Exception $e) {
                        $response['error'] = $e->getMessage();
                    }
                }
            } else {
                $name = "Transaction is not found!!";
            }
        } else {
            $name = "Transaction id or key is not valid!";
        }
        return redirect(route('user.token'))->with(['danger' => $name, 'modal' => 'danger']);
    }
}
