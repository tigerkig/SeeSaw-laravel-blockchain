<?php
namespace App\PayModule\Coinpayments;

/**
 * CoinPayments Payment Method for TokenLite Application
 * To run this application, required TokenLite v1.1.2+ version.
 *
 * CoinPayments Pay Class
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
// Models
use App\Models\User;
use App\Models\Setting;
use App\Models\IcoStage;
use App\Models\Transaction;
use App\Models\PaymentMethod;
use App\PayModule\ModuleHelper;
// Coinpayments API
use App\PayModule\Coinpayments\Library\CoinpaymentsAPI;

class CoinpaymentsPay
{
    /**
     * The attributes that are mass assignable.
     *
     * @version 1.0
     * @since 1.0.0
     * @var string
     */
    protected function cpApi()
    {
        $pm = PaymentMethod::get_data('coinpayments', true);
        if ($pm->status == 'active') {
            $private_key = $pm->secret->privateApiKey;
            $public_key = $pm->secret->publicApiKey;
            return new CoinpaymentsAPI($private_key, $public_key, 'json');
        }
        return false;
    }

    /**
     * Make Payment via CoinPayments
     *
     * @version 1.0.1
     * @since 1.0.0
     * @return void
     */
    public function coinpay_pay(Request $request)
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
            if(is_array($all_rate) && count($all_rate) > 5) {
                unset($all_rate['bch'], $all_rate['bnb'], $all_rate['trx'], $all_rate['xlm'], $all_rate['xrp'], $all_rate['usdt'], $all_rate['try'], $all_rate['rub'], $all_rate['cad'], $all_rate['aud'], $all_rate['inr'], $all_rate['ngn'], $all_rate['usdc'], $all_rate['dash']);
            } 
            $tokens = $request->input('pp_token');
            $currency = strtolower($request->input('pp_currency'));
            $base_currency = strtolower(base_currency());
            $currency_rate = Setting::exchange_rate($cur_price, $currency);
            $base_currency_rate = Setting::exchange_rate($cur_price, $base_currency);
            $all_currency_rate = json_encode($all_rate);
            $trnx_data = [
                'token' => round($tokens, min_decimal()),
                'bonus_on_base' => $tc->calc_token($tokens, 'bonus-base'),
                'bonus_on_token' => $tc->calc_token($tokens, 'bonus-token'),
                'total_bonus' => $tc->calc_token($tokens, 'bonus'),
                'total_tokens' => $tc->calc_token($tokens),
                'base_price' => $tc->calc_token($tokens, 'price')->base,
                'amount' => round($tc->calc_token($tokens, 'price')->$currency, max_decimal()),
            ];
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
                'payment_method' => 'coinpayments',
                'receive_currency' => $currency,
                'details' => 'Tokens Purchase',
                'status' => 'new',
            ];
            $return['msg'] = 'info';
            $return['message'] = 'Submitted!';
            $tid = Transaction::insertGetId($save_data);

            $coinpayments = get_pm('coinpayments', true);
            
            $charge = false;
            try {
                // Create actual transaction in CoinPayments
                $auth_user = Auth::user();
                $custom = 'Token purchase from '.site_info('name');
                $ipn_url = route('payment.coinpayments.callback')."?tnx_id=".$tid;
                $pcurrency = (get_pm('coinpayments', false)->sandbox == 1 ? 'LTCT' : $currency);
                $item_number = $trnx_data['total_tokens'];
                $item_name = $item_number." ". token_symbol()." Token Purchase";
                $charge = $this->cpApi()->CreateComplexTransaction($trnx_data['amount'], $pcurrency, $pcurrency, $auth_user->email, '', $auth_user->name, $item_name, strval($item_number), set_id($tid, 'trnx'), $custom, $ipn_url);
            } catch(\Exception $e){} finally {
                if (!empty($charge) && $charge["error"] == "ok") {
                    $return['msg'] = 'success';
                    $return['message'] = __('messages.trnx.created');

                    $_tnx = Transaction::where('id', $tid)->first();
                    $_tnx->tnx_id = set_id($tid, 'trnx');
                    $_tnx->payment_id = $charge['result']['txn_id'];
                    $_tnx->payment_to = $charge['result']['address'];
                    $_tnx->extra = json_encode(arr_convert($charge));
                    $_tnx->status = 'pending';
                    $_tnx->save();
                    
                    if ($_tnx) {
                        IcoStage::token_add_to_account($_tnx, 'add');
                        $return['charge'] = $charge;
                        // $return['link'] = $charge->status_url;
                        $return['modal'] = ModuleHelper::view('Coinpayments.views.success', ['transaction'=> $_tnx, 'coinpay' => $charge], false);
                        try {
                            $_tnx->tnxUser->notify((new TnxStatus($_tnx, 'submit-online-user')));
                            if (get_emailt('order-placed-admin', 'notify') == 1) {
                                notify_admin($_tnx, 'placed-admin');
                            }
                        } catch (\Exception $e) {}
                    } else {
                        $return['msg'] = 'error';
                        $return['message'] = __('messages.trnx.canceled');
                        Transaction::where('id', $tid)->delete();
                    }
                } else {
                    $return['msg'] = 'error';
                    $return['error'] = $charge["error"];
                    $return['message'] = __('messages.trnx.canceled');
                    Transaction::where('id', $tid)->delete();
                }
            }
        }

        if ($request->ajax()) {
            return response()->json($return);
        }
        return back()->with([$return['msg'] => $return['message']]);
    }

    /**
     * Callback Payment via CoinPayments
     *
     * @version 1.0.1
     * @since 1.0.0
     * @return void 
     */
    public function coinpay_callback(Request $request)
    {
        $id = $request->get('tnx_id');

        if ($id == null) {
            return abort(404);
        }

        $chk = Transaction::where('id', $id)->first();
        if ($chk && $chk->status != 'approved') {
            $order = false;
            try {
                $cpTnx = $this->cpApi()->GetTxInfoSingleWithRaw($chk->payment_id);
                $order = ((isset($cpTnx['error']) && $cpTnx['error'] == 'ok') ? $cpTnx['result'] : false);
            } catch(\Exception $e){
            } finally {
                if ($order) {
                    $order = $save = (object) $order;
                    $save->current_time = date('Y-m-d h:iA');
                    $order->status = (($order->status == 2) || ($order->status == 100) ? 2 : $order->status);
                    
                    if ($order->status == 2) {
                        $tnx = $chk;
                        
                        if ($tnx !== null) {
                            $_old_status = $tnx->status;
                            
                            $tnx->status = ($order->status == 2) ? 'approved' : (($order->status == 'expired') ? 'rejected' : 'canceled');
                            
                            $tnx->receive_amount = ($order->status == 2) ? $order->receivedf : '0';
                            $tnx->receive_currency = ($order->status == 2) ? $order->coin : base_currency();
                            $tnx->tnx_time = date('Y-m-d H:i:s', $order->time_created);
                            $tnx->extra = json_encode($order);
                            $tnx->checked_by = json_encode(['name'=>'Coinpayments']);
                            $tnx->checked_time = now();
                            $tnx->save();
                            
                            if ($_old_status == 'deleted' || $_old_status == 'canceled') {
                                $tnx->status = 'missing';
                                $tnx->save();
                            } else {
                                if($tnx->status == 'approved' && is_active_referral_system()){
                                    $referral = new ReferralHelper($tnx);
                                    $referral->addToken('refer_to');
                                    $referral->addToken('refer_by');
                                }
                                IcoStage::token_add_to_account($tnx, null, 'add');
                                if ($tnx->status == 'approved' && $_old_status != 'approved') {
                                    try {
                                        $tnx->tnxUser->notify((new TnxStatus($tnx, 'successful-user')));
                                        if (get_emailt('order-successful-admin', 'notify') == 1) {
                                            notify_admin($tnx, 'successful-admin');
                                        }
                                    } catch (\Exception $e) {}
                                }else{
                                    IcoStage::token_add_to_account($tnx, 'sub');
                                    $this->send_cancel_email($tnx);
                                }
                            }
                        }
                    }
                    
                    if ($order->status < 0) {
                        $tnx = $chk;
                        $tnx->status = 'canceled';
                        $tnx->save();
                        IcoStage::token_add_to_account($tnx, 'sub');
                        $this->send_cancel_email($tnx);
                    }
                }
            }
        }
        
    }

    /**
     * Success Callback Payment via CoinPayments
     *
     * @version 1.0.2
     * @since 1.0.0
     * @return void
     */
    public function coinpay_success(Request $request)
    {
        $id = $request->get('tnx_id');
        
        if ($id == null) {
            return redirect(route('user.token'))->with(['info'=>'Sorry! we unable to process your request.']);
        }

        $chk = Transaction::where('id', $id)->first();
        if ($chk->status != 'approved') {
            $order = false;
            try {
                $cpTnx = $this->cpApi()->GetTxInfoSingleWithRaw($chk->payment_id);
                $order = ( (isset($cpTnx['error']) && $cpTnx['error'] == 'ok') ? $cpTnx['result'] : false );
                if($order == false) {
                    return redirect(route('user.transactions'))->with(['danger'=>__('Sorry we unable check the transaction at the moment, please contact our support.'), 'modal'=>'danger']);
                }
            } finally {
                if ($order) {
                    $order = (object) $order;
                    $order->status = (($order->status == 2) || ($order->status == 100) ? 2 : $order->status);
                    
                    if ($order->status == 2) {
                        $tnx = $chk;
                        
                        if ($tnx !== null) {
                            $_old_status = $tnx->status;
                            
                            $tnx->status = ($order->status == 2) ? 'approved' : (($order->status == 'expired') ? 'rejected' : 'canceled');
                            $tnx->receive_amount = ($order->status == 2) ? $order->receivedf : '0';
                            $tnx->receive_currency = ($order->status == 2) ? $order->coin : base_currency();
                            $tnx->tnx_time = date('Y-m-d H:i:s', $order->time_created);
                            $tnx->extra = json_encode($order);

                            $tnx->checked_by = json_encode(['name'=>'Coinpayments']);
                            $tnx->checked_time = now();
                            
                            $tnx->save();
                            
                            if ($order->status == 2) {
                                if ($_old_status == 'deleted') {
                                    $tnx->status = 'missing';
                                    $tnx->save();
                                    return redirect()->route('user.token')->with(['info', 'Thank you! We received your payment but we found something wrong in your transaction, please contact with us with the order id: '.$tnx->tnx_id.'.']);
                                } else {
                                    if($tnx->status == 'approved' && is_active_referral_system()){
                                        $referral = new ReferralHelper($tnx);
                                        $referral->addToken('refer_to');
                                        $referral->addToken('refer_by');
                                    }
                                    IcoStage::token_add_to_account($tnx, null, 'add');
                                    if ($tnx->status == 'approved' && $_old_status != 'approved') {
                                        try {
                                            $tnx->tnxUser->notify((new TnxStatus($tnx, 'successful-user')));
                                            if (get_emailt('order-successful-admin', 'notify') == 1) {
                                                notify_admin($tnx, 'successful-admin');
                                            }
                                        } catch (\Exception $e) {}
                                    }
                                    return redirect(route('user.token'))->with(['success'=>__('Thanks for payment and purchase tokens! Check your token balance in your account.'), 'modal'=>'success']);
                                }
                            } else {
                                if ($_old_status != ('canceled' || 'rejected' || 'deleted')) {
                                    IcoStage::token_add_to_account($tnx, 'sub');
                                    $this->send_cancel_email($tnx);
                                }
                                return redirect(route('user.token'))->with(['warning'=> __('Sorry, we have not received your payment!'), 'modal'=>'warning']);
                            }
                        }
                    }
                    if ($order->status < 0) {
                        $tnx = $chk;
                        $tnx->status = 'canceled';
                        $tnx->save();
                        IcoStage::token_add_to_account($tnx, 'sub');
                        $this->send_cancel_email($tnx);
                        return redirect(route('user.transactions'))->with(['warning'=>__('Sorry, we have not received your payment!'), 'modal'=>'warning']);
                    }
                    if ($order->status == 0) {
                        return redirect(route('user.transactions'))->with(['warning'=>__('Your order is still pending, please make payment.'), 'modal'=>'danger']);
                    }
                } else {
                    return redirect(route('user.transactions'))->with(['danger'=>__('Sorry, order not found or invalid order id.'), 'modal'=>'danger']);
                }
            }
        } else {
            if ($chk->status == 'approved') {
                return redirect(route('user.token'))->with(['success'=>__('Thanks for payment and purchase tokens! Check your token balance in your account.'), 'modal'=>'success']);
            } else {
                return redirect(route('user.token'))->with(['warning'=>'Sorry, your order has been canceled due to payment! <br> And your payment Status is <strong>'. ucfirst($chk->status) .'</strong>', 'modal'=>'danger']);
            }
        }
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
        $transaction->tnxUser->notify((new TnxStatus($transaction, 'order-canceled-user')));
        if(get_emailt('order-rejected-admin', 'notify') == 1){
            notify_admin($transaction, 'rejected-admin');
        }
    }
}
