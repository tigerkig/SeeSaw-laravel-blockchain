<?php
namespace App\PayModule\Nowpayments;

/**
 * Nowpayments Payment Method for TokenLite Application
 * To run this application, required TokenLite v1.1.2+ version.
 *
 * Nowpayments Pay Class
 *
 * @version 1.0.3
 * @since 1.0.0
 * @package TokenLite
 * @author Softnio
 *
 */

use Auth;
use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Notifications\TnxStatus;
use App\Helpers\ReferralHelper;
use App\Helpers\TokenCalculate as TC;
use App\Helpers\HubSpot;
// Models
use App\Models\User;
use App\Models\Setting;
use App\Models\IcoStage;
use App\Models\Transaction;
use App\Models\PaymentMethod;
use App\Models\NowpaymentsTracking;
use App\PayModule\ModuleHelper;
// Coinpayments API
use App\PayModule\Nowpayments\Library\NowpaymentsAPI;
use Illuminate\Support\Facades\Log;

class NowpaymentsPay
{
    const RESPONSE_CODES = [
        'error_generic' => [
            'code' => 400,
            'type' => 'info',
            'message' => 'Sorry! we unable to process your request. Error Code: {code}'
        ],
        'error_get_payment_status' => [
            'code' => 400,
            'type' => 'danger',
            'message' => 'Sorry we unable check the transaction at the moment, please contact our support.'
        ],
        'approved_missing_tnx' => [
            'code' => 400,
            'type' => 'info',
            'message' => 'Thank you! We received your payment but we found something wrong in your transaction, please contact with us with the order id: {tnx_id}.'
        ],
        'error_no_payment' => [
            'code' => 400,
            'type' => 'warning',
            'message' => 'Sorry, we have not received your payment!'
        ],
        'status_pending' => [
            'code' => 400,
            'type' => 'warning',
            'message' => 'Your order is still pending.'
        ],
        'invalid_order' => [
            'code' => 404,
            'type' => 'danger',
            'message' => 'Sorry, order not found or invalid order id.',
        ],
        'error_payment_canceled' => [
            'code' => 400,
            'type' => 'warning',
            'message' => 'Sorry, your order has been canceled due to payment! <br> And your payment Status is <strong>{tnx_status}</strong>'
        ],
        'error_not_enough' => [
            'code' => 400,
            'type' => 'warning',
            'message' => 'You haven\'t sent enough {tnx_pay_currency} to NOWPayments. Please send {remaining} {tnx_pay_currency} to {pay_address}. If you need help, please contact with us with the order id: {tnx_id}.' 
        ],
        'success' => [
            'code' => 200,
            'type' => 'success',
            'message' => 'Thanks for payment and purchase tokens! Check your token balance in your account.'
        ]
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected function cpApi()
    {
        $pm = PaymentMethod::get_data('nowpayments', true);
        if ($pm->status == 'active') {
            $secret_key = $pm->secret->secretKey;
            $public_key = $pm->secret->publicApiKey;
            $test_mode = !empty($pm->secret->sandbox);
            return new NowpaymentsAPI($secret_key, $public_key, $test_mode);
        }
        return false;
    }

    /**
     * Make Payment via Nowpayments
     */
    public function nowpay_pay(Request $request)
    {
        if (version_compare(phpversion(), '7.1', '>=')) {
            ini_set('precision', get_setting('token_decimal_max', 8));
            ini_set('serialize_precision', -1);
        }
        $return['msg'] = 'info';
        $return['message'] = __('messages.nothing');
        $validator = Validator::make($request->all(), [
            'agree' => 'required',
        ], [
            'agree.required' => __('messages.agree')
        ]);
        if ($validator->fails()) {
            if ($validator->errors()->has('agree')) {
                $msg = $validator->errors()->first();
            } else {
                $msg = __('messages.something_wrong');
            }

            $return['msg'] = 'warning';
            $return['message'] = $msg;
        } else {
            $tc = new TC();
            $cur_price = $tc->get_current_price();
            $all_rate = Setting::exchange_rate($cur_price); 
            $tokens = $request->input('pp_token');
            $currency = strtolower($request->input('pp_currency'));
            $base_currency = strtolower(base_currency());
            $currency_rate = Setting::exchange_rate($cur_price, $currency);
            $base_currency_rate = Setting::exchange_rate($cur_price, $base_currency);
            $all_currency_rate = json_encode($all_rate);
            $trnx_data = [
                'token' => $tokens,
                'bonus_on_base' => $tc->calc_token($tokens, 'bonus-base'),
                'bonus_on_token' => $tc->calc_token($tokens, 'bonus-token'),
                'total_bonus' => $tc->calc_token($tokens, 'bonus'),
                'total_tokens' => $tc->calc_token($tokens),
                'base_price' => $tc->calc_token($tokens, 'price')->base,
                'amount' => round($tc->calc_token($tokens, 'price')->$currency, max_decimal()),
            ];

            // check min payment with nowpayments
            $min_payment = $this->cpApi()->GetMinPaymentAmount($currency, $currency);
            if (!empty($min_payment['min_amount'])) {
                $min_payment = (float) $min_payment['min_amount'];
                $min_payment *= 1.5; // 50% past the minimum
                if ($trnx_data['amount'] < $min_payment) {
                    // this check is to prevent PHP from displaying the minimum payment in scientific/exponential notation
                    if ($min_payment > 1000) {
                        $min_payment = intval(ceil($min_payment));
                    } else {
                        $min_payment = round($min_payment, max_decimal());
                    }
                    $msg = 'Requires a minimum of ' . $min_payment . ' ' . strtoupper($currency) . '.';
                    return back()->with(['warning' => $msg]);
                }
            } else {
                $min_payment = 0; // either it was actually 0, or there was an error getting the min_amount
            }
            $save_data = [
                'created_at' => Carbon::now()->toDateTimeString(),
                'tnx_id' => set_id(rand(100, 999), 'trnx'),
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
                'payment_method' => 'nowpayments',
                'receive_currency' => $currency,
                'details' => 'Tokens Purchase',
                'status' => 'new',
            ];
            $user = User::where('id', Auth::id())->first();
            $return['msg'] = 'info';
            $return['message'] = 'Submitted!';
            $tid = Transaction::insertGetId($save_data);

            $nowpayments = get_pm('nowpayments', true);
            
            $order = false;
            try {
                // Create actual transaction in CoinPayments
                $auth_user = Auth::user();
                $custom = 'Token purchase from '.site_info('name');
                $pcurrency = $currency;
                $item_number = $trnx_data['total_tokens'];
                $item_name = $item_number." ". token_symbol()." Token Purchase";
                $order = $this->cpApi()->CreatePayment($tid, $trnx_data['amount'], $pcurrency, $pcurrency);
            } catch(\Exception $e){
                $return['msg'] = 'error';
                $return['error'] = $e->getMessage();
                $return['message'] = __('messages.trnx.canceled');
                Transaction::where('id', $tid)->delete();
            } finally {
                if (!empty($order['payment_status']) && $order["payment_status"] == "waiting") {
                    $return['msg'] = 'success';
                    $return['message'] = __('messages.trnx.created');

                    $_tnx = Transaction::where('id', $tid)->first();
                    $_tnx->tnx_id = set_id($tid, 'trnx');
                    $_tnx->payment_id = $order['payment_id'];
                    $_tnx->payment_to = $order['pay_address'];
                    $_tnx->extra = json_encode(arr_convert($order));
                    $_tnx->status = 'pending';
                    $_tnx->save();

                    if ($_tnx) {
                        $hubSpot = new HubSpot();
                        $hubSpot->put($user, $_tnx);

                        $return['charge'] = $order;
                        $return['modal'] = ModuleHelper::view('Nowpayments.views.success', ['transaction'=> $_tnx, 'data' => $order], false);
                        try {
                            $this->send_order_placed_email($_tnx);
                        } catch (\Exception $e) {}
                    } else {
                        $return['msg'] = 'error';
                        $return['message'] = __('messages.trnx.canceled');
                        if (!empty($order['request_id'])) {
                            $return['message'] .= ' Error ID: ' . $order['request_id'];
                        }
                        Transaction::where('id', $tid)->delete();
                    }
                } else {
                    $return['msg'] = 'error';
                    $return['error'] = json_encode($order);
                    $return['message'] = __('messages.trnx.canceled');
                    if (!empty($order['request_id'])) {
                        $return['message'] .= ' Error ID: ' . $order['request_id'];
                    }
                    Transaction::where('id', $tid)->delete();
                }
            }
        }

        if ($request->ajax()) {
            return response()->json($return);
        }
		$modal = "";
		if (!empty($return["modal"])) $modal = $return["modal"];
		Log::info("MODAL: " . $modal);
        $return = [
            $return['msg'] => $return['message'],
            'modal' => $modal,
        ];
        if (empty($return['modal']) || $return["modal"] == "") {
			Log::info("MODAL EMPTY");
            unset($return['modal']);
        }
        return back()->with($return);
    }

    private function build_nowpay_response($error_type, $frontend, array $data = [])
    {
        if (empty(self::RESPONSE_CODES[$error_type])) {
            if ($frontend) {
                return redirect(route('user.token'))->with(['info'=>'Sorry! we unable to process your request.']);
            } else {
                return abort(400);
            }
        }

        $response = self::RESPONSE_CODES[$error_type];
        $message = $response['message'];
        $matches = [];
        preg_match_all("/{\w+_*\w+}/", $message, $matches, PREG_UNMATCHED_AS_NULL);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $entry) {
                $key = trim($entry, '{}');
                if (isset($data[$key])) {
                    $message = str_replace($entry, $data[$key], $message);
                }
            }
        }

        if ($frontend) {
            return redirect(route('user.token'))->with([$response['type'] => $message]);
        } else if ($response['code'] >= 400) {
            return abort($response['code']);
        } else {
            return response('', $response['code']);
        }
    }

    private function nowpay_process($result, $transaction_id, $frontend) {
        $hubSpot = new HubSpot();
        $transaction = false;
        if ($frontend) {
            if (empty($transaction_id)) {
                return $this->build_nowpay_response('error_generic', $frontend, ['code' => 1]);
            }
            $transaction = Transaction::where('id', $transaction_id)->first();
            if (empty($transaction)) {
                return $this->build_nowpay_response('error_generic', $frontend, ['code' => 2]);
            }
            $result = $this->cpApi()->GetPaymentStatus($transaction->payment_id);
            if (empty($result)) {
                return $this->build_nowpay_response('error_get_payment_status', $frontend);
            }
        } else {
            if (empty($result)) {
                return $this->build_nowpay_response('error_generic', $frontend, ['code' => 3]);
            }
            $transaction_id = $result['order_id'];
            if (empty($transaction_id)) {
                return $this->build_nowpay_response('error_generic', $frontend, ['code' => 4]);
            }
            $transaction = Transaction::where('id', $transaction_id)->first();
            if (empty($transaction)) {
                return $this->build_nowpay_response('error_generic', $frontend, ['code' => 5]);
            }
        }
        // result, transaction_id, and transaction are all set at this point

        $required_fields = [
            'actually_paid',
            'order_id',
            'pay_address',
            'pay_currency',
            'payment_status',
            'price_amount'
        ];
        foreach ($required_fields as $field) {
            if (!isset($result[$field])) {
                return $this->build_nowpay_response('error_generic', $frontend, ['code' => json_encode($result)]);
            }
        }

        // everything is valid and set, begin processing result

        // update transaction
        $old_status = $transaction->status;
        switch ($result['payment_status']) {
            case 'confirming':
            case 'sending':
            case 'waiting':
            case 'confirming':
            case 'confirmed':
                $transaction->status = 'pending';
                break;
            case 'partially_paid':
                // make sure to update receive_amount
                $transaction->status = 'onhold';
                break;
            case 'finished':
                $transaction->status = 'approved';
                break;
            case 'failed':
            case 'refunded':
            case 'expired':
                $transaction->status = 'canceled';
                break;
            default:
                return $this->build_nowpay_response('error_generic', $frontend, ['code' => 7]);
        }
        $transaction->tnx_time = Carbon::now()->toDateTimeString();
        $transaction->extra = json_encode($result);
        $transaction->checked_by = json_encode(['name'=>'nowpayments']);
        $transaction->checked_time = now();
        $transaction->receive_amount = $result['actually_paid'];
        $transaction->receive_currency = $result['pay_currency'];

        $user = User::where('id', $transaction->user)->first();

        // now we have the new status and old status, successful result, AND a valid transaction
        // from this point on, we need to make sure we save the transaction before returning
        if ($old_status == 'deleted' || $old_status == 'canceled' || $old_status == 'missing') {
            $transaction->status = 'missing';
            $transaction->save();

            return $this->build_nowpay_response('error_payment_canceled', $frontend, ['tnx_status' => $result['payment_status']]);
        }
        if ($old_status == $transaction->status) {
            // status hasn't changed
            if ($transaction->status == 'pending') {
                // also we're still pending, nothing to do here
                $transaction->save();
                $hubSpot->put($user, $transaction);
                return $this->build_nowpay_response('status_pending', $frontend);
            }
            if ($transaction->status == 'approved') {
                // already approved, nothing to do here
                $transaction->save();
                $hubSpot->put($user, $transaction);
                return $this->build_nowpay_response('success', $frontend);
            }
            if ($transaction->status == 'canceled') {
                $transaction->save();
                $hubSpot->put($user, $transaction);
                return $this->build_nowpay_response('error_payment_canceled', $frontend, ['tnx_status' => $result['payment_status']]);
            }
            if ($transaction->status == 'onhold') {
                $transaction->save();
                $hubSpot->put($user, $transaction);
                return $this->build_nowpay_response('status_pending', $frontend);
            }
        } else if ($transaction->status == 'approved' || $transaction->status == 'onhold') {
            // payment status changed, we should probably do something
            if ($old_status == 'deleted') {
                // in this case, payment went through, but the user cancelled it before it could complete
                $transaction->status = 'missing';
                $transaction->save();
                $hubSpot->put($user, $transaction);
                return $this->build_nowpay_response('approved_missing_tnx', $frontend, ['tnx_id' => $transaction_id]);
            }
            if ($transaction->status == 'onhold' && $transaction->receive_currency == $transaction->currency) {
                // recalculate the tokens based on how much was actually sent to NOWPayments
                $tc = new TC();
                $currency = $transaction->currency;
                $transaction->tokens = $transaction->receive_amount / $transaction->currency_rate;
                $transaction->base_amount = $transaction->tokens * $transaction->base_currency_rate;
                $transaction->bonus_on_base = $tc->calc_token($transaction->tokens, 'bonus-base', $transaction);
                $transaction->bonus_on_token = $tc->calc_token($transaction->tokens, 'bonus-token', $transaction);
                $transaction->total_bonus = $tc->calc_token($transaction->tokens, 'bonus', $transaction);
                $transaction->total_tokens = $tc->calc_token($transaction->tokens, 'total', $transaction);
                $transaction->amount = round($tc->calc_token($transaction->tokens, 'price', $transaction)->$currency, max_decimal());
            } else if ($transaction->receive_currency != $transaction->currency) { // This should never happen, just a safety net
                $transaction->save();
                try {
                    $this->send_cancel_email($transaction);
                } catch (\Exception $e) {}

                $hubSpot->put($user, $transaction);

                return $this->build_nowpay_response('error_payment_canceled', $frontend, ['tnx_status' => $result['payment_status']]);
            }
            if (is_active_referral_system()){
                $referral = new ReferralHelper($transaction);
                $referral->addToken('refer_to');
                $referral->addToken('refer_by');
            }
            // everything went through smoothly, add the tokens they bought to their account
            IcoStage::token_add_to_account($transaction, null, 'add');
            // put the tokens in the stage
            IcoStage::token_add_to_account($transaction, 'add');

            try {
                if ($transaction->status == 'onhold') {
                    $transaction->status = 'approved';
                    $transaction->save();
                    $this->send_partial_success_email($transaction);
                } else {
                    $transaction->save();
                    $this->send_success_email($transaction);
                }
            } catch (\Exception $e) {}
            $hubSpot->put($user, $transaction);
            return $this->build_nowpay_response('success', $frontend);
        } else if ($transaction->status == 'canceled') {
            $transaction->save();
            try {
                $this->send_cancel_email($transaction);
            } catch (\Exception $e) {}
            
            $hubSpot->put($user, $transaction);
            return $this->build_nowpay_response('error_payment_canceled', $frontend, ['tnx_status' => $result['payment_status']]);
        } else if ($transaction->status == 'pending') {
            // also we're still pending, nothing to do here
            $transaction->save();
            $hubSpot->put($user, $transaction);
            return $this->build_nowpay_response('status_pending', $frontend);
        }
    }

    /**
     * Callback Payment via NOWPayments
     */
    public function nowpay_callback(Request $request)
    {
        $pm = PaymentMethod::get_data('nowpayments', true);
        $secret_key = $pm->secret->secretKey;
        $result = $this->processCallback($secret_key);
        NowpaymentsTracking::log('callback', '', json_encode($result));
        return $this->nowpay_process($result, false, false);
    }

    /**
     * Success Callback Payment via Nowpayments
     */
    public function nowpay_success(Request $request)
    {
        return $this->nowpay_process(false, $request->get('tnx_id'), true);
    }

    public function nowpay_force_process($transaction_id)
    {
        if (empty($transaction_id)) {
            return;
        }
        return $this->nowpay_process(false, $transaction_id, true);
    }

    private function processCallback($secret_key)
    {
        if (isset($_SERVER['HTTP_X_NOWPAYMENTS_SIG']) && !empty($_SERVER['HTTP_X_NOWPAYMENTS_SIG'])) {
            $recived_hmac = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'];

            $request_json = file_get_contents('php://input');
            $request_data = json_decode($request_json, true);
            ksort($request_data);
            $sorted_request_json = json_encode($request_data);

            if ($request_json !== false && !empty($request_json)) {
                $hmac = hash_hmac("sha512", $sorted_request_json, trim($secret_key));

                if ($hmac == $recived_hmac) {
                    return $request_data;
                } 
            } 
        } 
        return false;
    }

    /**
     * Send Email to Admin for Canceled Transaction
     *
     * @version 1.0
     * @since 1.0.0
     * @return void
     */
    protected function send_cancel_email($transaction)
    {
        $transaction->tnxUser->notify((new TnxStatus($transaction, 'canceled-user')));
        if(get_emailt('order-rejected-admin', 'notify') == 1){
            notify_admin($transaction, 'rejected-admin');
        }
    }

    protected function send_success_email($transaction)
    {
        $transaction->tnxUser->notify((new TnxStatus($transaction, 'successful-user')));
        if (get_emailt('order-successful-admin', 'notify') == 1) {
            notify_admin($transaction, 'successful-admin');
        }
    }

    protected function send_partial_success_email($transaction)
    {
        $transaction->tnxUser->notify((new TnxStatus($transaction, 'partial-success-user')));
        if (get_emailt('order-successful-admin', 'notify') == 1) {
            notify_admin($transaction, 'successful-admin');
        }
    }

    protected function send_order_placed_email($transaction)
    {
        $transaction->tnxUser->notify((new TnxStatus($transaction, 'submit-online-user')));
        if (get_emailt('order-placed-admin', 'notify') == 1) {
            notify_admin($transaction, 'placed-admin');
        }
    }
}
