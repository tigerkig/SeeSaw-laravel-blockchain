<?php 

namespace App\PayModule\Coinpayments;

/**
 * CoinPayments Payment Method for TokenLite Application
 * To run this application, required TokenLite v1.1.2+ version.
 *
 * CoinPayments Module
 *
 * @version 1.0.3
 * @since 1.0.0
 * @package TokenLite
 * @author Softnio
 *
 */

use Auth;
use Route;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Setting;
use App\Models\IcoStage;
use App\Helpers\IcoHandler;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Models\EmailTemplate;
use App\PayModule\ModuleHelper;
use App\PayModule\PmInterface;
use App\Notifications\TnxStatus;
use App\PayModule\Coinpayments\CoinpaymnetsPay;
use App\Helpers\TokenCalculate as TC;

class CoinpaymentsModule implements PmInterface
{
    const SLUG = 'coinpayments';
    const SUPPORT_CURRENCY = ['ETH', 'BTC', 'LTC', 'BCH', 'BNB', 'XRP', 'TRX', 'USDT', 'USDC', 'DASH'];
    const VERSION = '1.0.3';
    const APP_VERSION = '^1.1.2';

    /* @function routes()  @version v1.0  @since 1.0.0 */
    public function routes()
    {
        Route::post('callback/coinpayments', 'Coinpayments\CoinpaymentsController@callback')->name('coinpayments.callback');
        Route::get('coinpayments/success', 'Coinpayments\CoinpaymentsController@success')->name('coinpayments.success');
    }

    /* @function admin_views()  @version v1.0  @since 1.0.0 */
    public function admin_views()
    {
        $pmData = PaymentMethod::get_data(self::SLUG, true);
        $name = self::SLUG;
    	return ModuleHelper::view('Coinpayments.views.card', compact('pmData', 'name'));
    }

    /* @function admin_views_details()  @version v1.0  @since 1.0.0 */
    public function admin_views_details()
    {
        $pmData = PaymentMethod::get_data(self::SLUG, true);
        return ModuleHelper::view('Coinpayments.views.admin', compact('pmData'));
    }

    /* @function show_action()  @version v1.0  @since 1.0.0 */
    public function show_action()
    {
        $pmData = PaymentMethod::get_data(self::SLUG, true);
        $html = '<li class="pay-item"><div class="input-wrap">
                    <input type="radio" class="pay-check" Value="'.self::SLUG.'" name="pay_option" required="required" id="pay-'.self::SLUG.'" data-msg-required="'.__('Select your payment method.').'">
                    <label class="pay-check-label" for="pay-'.self::SLUG.'"><span class="pay-check-text" title="'.$pmData->details.'">'.$pmData->title.'</span><img class="pay-check-img" src="'.asset('assets/images/pay-coinpayments.png').'" alt="'.ucfirst(self::SLUG).'"></label>
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
        return ModuleHelper::view('Coinpayments.views.tnx_details', compact('transaction'));
    }

    /* @function email_details()  @version v1.0  @since 1.0.0 */
    public function email_details($transaction){
        $data = json_decode($transaction->extra);
        $pm = get_pm(self::SLUG, true);
        $address = (isset($data->result->address) ? $data->result->address : '');
        
        $pay_address = '<tr><td>Payment to Address</td><td>:</td><td><strong>'.$address.' ('.strtoupper($transaction->currency).')</strong></td></tr>';
    }

    /* @function create_transaction()  @version v1.0  @since 1.0.0 */
    public function create_transaction(Request $request)
    {
    	$helper = new CoinpaymentsPay();
    	if(method_exists($helper, 'coinpay_pay')){
        	return $helper->coinpay_pay($request);
    	}
    	$response['msg'] = 'info';
        $response['message'] = __('messages.nothing');
    	return $response;
    }

    /* @function payment_address()  @version v1.0  @since 1.0.0 */
    public function payment_address()
    {
        $text = 'To your connected coinpayments wallet';
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
	        $coinpayments_data = [
                'publicApiKey' => $request->input('cnp_api'),
                'privateApiKey' => $request->input('cnp_api2'),
                'sandbox' => isset($request->cnp_sandbox),
            ];
	        $pmc = PaymentMethod::where('payment_method', 'coinpayments')->first();
            if (! $pmc) {
                $pmc = new PaymentMethod();
                $pmc->payment_method = 'coinpayments';
            }
            $pmc->title = $request->input('title');
            $pmc->description = $request->input('details');
            $pmc->status = isset($request->status) ? 'active' : 'inactive';
            $pmc->data = json_encode($coinpayments_data);
	            
	        if ($pmc->save()) {
	            $response['msg'] = 'success';
	            $response['message'] = __('messages.update.success', ['what' => 'CoinPayments payment information']);
	        }else{
	            $response['msg'] = 'error';
	            $response['message'] = __('messages.update.failed', ['what' => 'CoinPayments payment information']);
	        }
	    }
        return $response;
    }

    /* @function demo_data()  @version v1.0  @since 1.0.0 */
    public function demo_data()
    {
        $data = [
            'publicApiKey' => NULL,
            'privateApiKey' => NULL,
            'sandbox' => 0
        ];

        if (PaymentMethod::check(self::SLUG)) {
            $coinpayments = new PaymentMethod();
            $coinpayments->payment_method = self::SLUG;
            $coinpayments->title = 'Pay with CoinPayments';
            $coinpayments->description = 'You can pay with your CoinPayments wallet.';
            $coinpayments->data = json_encode($data);
            $coinpayments->status = 'inactive';
            $coinpayments->save();
        }
    }
}
