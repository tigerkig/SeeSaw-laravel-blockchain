<?php 

namespace App\PayModule\Nowpayments;

/**
 * Nowpayments Payment Method for TokenLite Application
 * To run this application, required TokenLite v1.1.2+ version.
 *
 * Nowpayments Module
 */

use Auth;
use Route;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Setting;
use App\Models\IcoStage;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Models\EmailTemplate;
use App\PayModule\ModuleHelper;
use App\PayModule\PmInterface;
use App\Notifications\TnxStatus;
use App\PayModule\Nowpayments\NowpaymentsPay;
use App\Helpers\TokenCalculate as TC;

class NowpaymentsModule implements PmInterface
{
    const SLUG = 'nowpayments';
    const SUPPORT_CURRENCY = ['ETH', 'BTC', 'LTC', 'BCH', 'XRP', 'TRX', 'USDT', 'USDC', 'DASH', 'DOGE', 'ADA', 'BNBMAINNET', 'BNBBSC', 'USDTERC20', 'USDTTRC20', 'BUSDERC20', 'DOT', 'SOL', 'SHIB', 'SAND', 'MANA', 'FLOKI', 'AXS'];
    const VERSION = '1.0.0';
    const APP_VERSION = '^1.1.2';

    /* @function routes()  @version v1.0  @since 1.0.0 */
    public function routes()
    {
        Route::post('callback/nowpayments', 'Nowpayments\NowpaymentsController@callback')->name('nowpayments.callback');
        Route::get('nowpayments/success', 'Nowpayments\NowpaymentsController@success')->name('nowpayments.success');
    }

    /* @function admin_views()  @version v1.0  @since 1.0.0 */
    public function admin_views()
    {
        $pmData = PaymentMethod::get_data(self::SLUG, true);
        $name = self::SLUG;
    	return ModuleHelper::view('Nowpayments.views.card', compact('pmData', 'name'));
    }

    /* @function admin_views_details()  @version v1.0  @since 1.0.0 */
    public function admin_views_details()
    {
        $pmData = PaymentMethod::get_data(self::SLUG, true);
        return ModuleHelper::view('Nowpayments.views.admin', compact('pmData'));
    }

    /* @function show_action()  @version v1.0  @since 1.0.0 */
    public function show_action()
    {
        $pmData = PaymentMethod::get_data(self::SLUG, true);
        $html = '<li class="pay-item"><div class="input-wrap">
                    <input type="radio" class="pay-check" Value="'.self::SLUG.'" name="pay_option" required="required" id="pay-'.self::SLUG.'" data-msg-required="'.__('Select your payment method.').'">
                    <label class="pay-check-label" for="pay-'.self::SLUG.'"><span class="pay-check-text" title="'.$pmData->details.'">'.$pmData->title.'</span><img class="pay-check-img" src="'.asset('assets/images/pay-nowpayments.png').'" alt="'.ucfirst(self::SLUG).'"></label>
                </div></li>';
        return [
            'currency' => $this->check_currency(),
            'html' => ModuleHelper::str2html($html)
        ];
    }

    /* @function check_currency()  @version v1.0  @since 1.0.0 */
    public function check_currency()
    {
        return self::SUPPORT_CURRENCY;
    }

    /* @function transaction_details()  @version v1.0  @since 1.0.0 */
    public function transaction_details($transaction)
    {
        return ModuleHelper::view('Nowpayments.views.tnx_details', compact('transaction'));
    }

    public function nowpay_force_process($transaction_id)
    {
        $helper = new NowpaymentsPay();
        if(method_exists($helper, 'nowpay_force_process')){
            return $helper->nowpay_force_process($transaction_id);
        }
    }

    /* @function email_details()  @version v1.0  @since 1.0.0 */
    public function email_details($transaction){
        $data = json_decode($transaction->extra);
        $pm = get_pm(self::SLUG, true);
        $address = (isset($data->pay_address) ? $data->pay_address : '');
        
        $pay_address = '<tr><td>Payment to Address</td><td>:</td><td><strong>'.$address.' ('.strtoupper($transaction->currency).')</strong></td></tr>';
    }

    /* @function create_transaction()  @version v1.0  @since 1.0.0 */
    public function create_transaction(Request $request)
    {
    	$helper = new NowpaymentsPay();
    	if(method_exists($helper, 'nowpay_pay')){
        	return $helper->nowpay_pay($request);
    	}
    	$response['msg'] = 'info';
        $response['message'] = __('messages.nothing');
    	return $response;
    }

    /* @function payment_address()  @version v1.0  @since 1.0.0 */
    public function payment_address()
    {
        $text = 'To your connected Nowpayments wallet';
        return $text;
    }

    /* @function save_data()  @version v1.0  @since 1.0.0 */
    public function save_data(Request $request)
    {
    	$response['msg'] = 'info';
        $response['message'] = __('messages.nothing');
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'details' => 'required',
        ]);

        if ($validator->fails()) {
            if ($validator->errors()->has('title')) {
                $message = $validator->errors()->first();
            } elseif ($validator->errors()->has('details')) {
                $message = $validator->errors()->first();
            } else {
                $message = __('messages.something_wrong');
            }

            $response['msg'] = 'warning';
            $response['message'] = $message;
        } else {
	        $old = PaymentMethod::get_single_data(self::SLUG);
	        $nowpayments_data = [
                'publicApiKey' => $request->input('cnp_api'),
                'secretKey' => $request->input('cnp_api2'),
                'sandbox' => isset($request->cnp_sandbox),
            ];
	        $pmc = PaymentMethod::where('payment_method', 'nowpayments')->first();
            if (!$pmc) {
                $pmc = new PaymentMethod();
                $pmc->payment_method = 'nowpayments';
            }
            $pmc->title = $request->input('title');
            $pmc->description = $request->input('details');
            $pmc->status = isset($request->status) ? 'active' : 'inactive';
            $pmc->data = json_encode($nowpayments_data);
	            
	        if ($pmc->save()) {
	            $response['msg'] = 'success';
	            $response['message'] = __('messages.update.success', ['what' => 'NOWPayments payment information']);
	        }else{
	            $response['msg'] = 'error';
	            $response['message'] = __('messages.update.failed', ['what' => 'NOWPayments payment information']);
	        }
	    }
        return $response;
    }

    /* @function demo_data()  @version v1.0  @since 1.0.0 */
    public function demo_data()
    {
        $data = [
            'publicApiKey' => NULL,
            'secretKey' => NULL,
            'sandbox' => 0
        ];

        if (PaymentMethod::check(self::SLUG)) {
            $nowpayments = new PaymentMethod();
            $nowpayments->payment_method = self::SLUG;
            $nowpayments->title = 'Pay with NOWPayments';
            $nowpayments->description = 'You can pay with your NOWPayments wallet.';
            $nowpayments->data = json_encode($data);
            $nowpayments->status = 'inactive';
            $nowpayments->save();
        }
    }
}
