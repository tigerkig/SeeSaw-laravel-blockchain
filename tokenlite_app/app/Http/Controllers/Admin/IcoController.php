<?php

namespace App\Http\Controllers\Admin;
/**
 * ICO Controller
 *
 * @package TokenLite
 * @author Softnio
 * @version 1.1.6
 */
use Validator;
use App\Models\IcoMeta;
use App\Models\Setting;
use App\Models\IcoStage;
use App\Helpers\IcoHandler;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Http\Controllers\Controller;


class IcoController extends Controller
{
    protected $handler;
    public function __construct(IcoHandler $handler)
    {
        $this->handler = $handler;
    }
    public function index()
    {
        $stages = IcoStage::whereNotIn('status', ['deleted'])->get();
        return view('admin.ico-stage', compact('stages'));
    }

    public function edit_stage($id)
    {
        $ico = IcoStage::findOrFail($id);
        $prices = IcoMeta::get_data($ico->id, 'price_option');
        $bonuses = IcoMeta::get_data($ico->id, 'bonus_option');
        return view('admin.ico-stage-edit', compact('ico', 'prices', 'bonuses'));
    }

    /**
     * Display ICO Stage Settings
     *
     * @return \Illuminate\Http\Response
     * @version 1.0.1
     * @since 1.0
     * @return void
     */
    public function settings()
    {
        if (get_setting('actived_stage') != '') {
            $ico = IcoStage::where('status', '!=', 'deleted')->where('id', get_setting('actived_stage'))->first();
            if (!$ico) {
                $ico = IcoStage::where('status', '!=', 'deleted')->orderBy('id', 'DESC')->first();
            }
        } else {
            $ico = IcoStage::where('status', '!=', 'deleted')->first();
        }
        $stages = IcoStage::whereNotIn('status', ['deleted'])->get();
        $prices = IcoMeta::get_data($ico->id, 'price_option');
        $bonuses = IcoMeta::get_data($ico->id, 'bonus_option');
        $pm_gateways = PaymentMethod::Currency;
        $supported_wallets = Setting::WALLETS;
        $modules = nio_module()->admin_modules();
        return view('admin.ico-setting', compact('ico', 'prices', 'bonuses', 'pm_gateways', 'supported_wallets', 'stages', 'modules'));
    }

    /**
     * Get overview of each or all stage
     *
     * @return \Illuminate\Http\Response
     * @version 1.0
     * @since 1.1.2
     * @return void
     */
    public function stages_action(Request $request)
    {
        $ret['msg'] = 'info';
        $ret['icon'] = 'ti ti-info-alt';
        $ret['message'] = __('Unable to proceed request!');

        $stage_id   = (int)$request->input('stage');
        $action     = $request->input('action');
        $view       = $request->input('view');

        $stage      = IcoStage::findOrFail($stage_id);
         // IcoStage::with()->findOrFail($stage_id);
        // dd($stage);
        if ($stage && !empty(env_file()) && str_contains(app_key(), $this->handler->find_the_path($this->handler->getDomain())) ) {
            if($action && $action=='overview' && $view=='modal'){
                $total = $purchased = $purchased_bonus = $referral = $bonuses = $contributed = $contribute_in = $pending = 0;
                $stage_data = IcoStage::get_stages($stage->id);
                $overview = (object) [
                    'sold' => $stage_data->sold,
                    'unsold' => $stage_data->unsold,
                    'pending' => $stage_data->pending,
                    'percent' => $stage_data->percent,

                    'bonus'    => $stage_data->bonus,
                    'purchase' => $stage_data->purchase,
                    'referral' => $stage_data->referral,

                    'token_sale' => $stage_data->token_sale,
                    'token_bonus_bb' => $stage_data->token_bonus_bb,
                    'token_bonus_ta' => $stage_data->token_bonus_ta,
                    'purchase_bonus' => $stage_data->purchase_bonus,
                    'contribute' => $stage_data->contribute,
                    'contribute_in' => $stage_data->contribute_in,
                ];
                $symbol = token_symbol();
                $base_symbol = base_currency(1);
                // dd($overview);
                return response()->json(['modal' => view('modals.overview-stage', compact('stage', 'overview', 'symbol', 'base_symbol'))->render()]);
            }
        }

        if ($request->ajax()) { return response()->json($ret); }
        return back()->with([$ret['msg'] => $ret['message']]);
    }

     /**
     * Active the Stage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @version 1.0.1
     * @since 1.0
     * @return void
     */

    public function active(Request $request)
    {
        $ret['msg'] = 'info';
        $ret['message'] = __('messages.nothing');

        if ($request->input('id') && $request->input('type')) {
            try{
                $status = Setting::updateValue('actived_stage', $request->input('id'));
                $ret['msg'] = 'success';
                $ret['message'] = __('messages.stage_update', ['status' => 'Activated']);
            }catch(\Exception $e){
                $ret['msg'] = 'warning';
                $ret['message'] = __('messages.form.wrong');
            }

        }

        if ($request->ajax()) {
            return response()->json($ret);
        }
        return back()->with([$ret['msg'] => $ret['message']]);
    }

     /**
     * Pause the Stage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @version 1.0.1
     * @since 1.0
     * @return void
     */
    public function pause(Request $request)
    {
        $ret['msg'] = 'info';
        $ret['message'] = __('messages.nothing');
        if ($request->input('id') && $request->input('type')) {
               try{
                $stage = IcoStage::findOrFail($request->input('id'));
                $stage->status = ($request->input('type') == 'resume_stage')?'active':'paused';
                $stage->save();

                $status = ($stage->status == 'active') ? 'Resume' : (($stage->status == 'paused') ? 'Paused' : ""); 
                $ret['msg'] = 'success';
                $ret['message'] = __('messages.stage_update', ['status' => $status]);
            }
            catch(\Exception $e){
                $ret['msg'] = 'warning';
                $ret['message'] = __('messages.form.wrong');
            }
        }

        if ($request->ajax()) {
            return response()->json($ret);
        }
        return back()->with([$ret['msg'] => $ret['message']]);
    }

    /**
     * Update the Stage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @version 1.2
     * @since 1.0
     * @return void
     */
    public function update(Request $request)
    {
        $ret['msg'] = 'info';
        $ret['message'] = __('messages.nothing');

        # Validation
        $validator = Validator::make($request->all(), [
            'name'        => 'required|min:3',
            'start_date'   => 'required|date_format:"m/d/Y"|date',
            'end_date'     => 'required|date_format:"m/d/Y"|date|after:start_date',
            'liquidity'   => ['required_if:price_type,on', 'numeric', 'gt:0', 'regex:/^\d+(\.\d{1,8})?$/'],
            'base_price'   => ['required', 'numeric', 'gt:0', 'regex:/^\d+(\.\d{1,8})?$/'],
            'price_type'   => 'nullable',
            'total_tokens' => 'required|integer|gt:0',
            'min_purchase'   => 'required|numeric|min:1',
            'max_purchase'   => 'required|numeric|min:5',
            'soft_cap'   => 'nullable|numeric|min:1',
            'hard_cap'   => 'nullable|numeric|min:5|max:total_tokens',
            'display_mode'   => 'required|string',
        ],[
            'base_price.regex' => __('Allow only :num digit after decimal point in :label. Recommended to use up-to 6 digit after decimal point.', ['label' => __("Base Price"), 'num' => '8']),
            'liquidity.regex' => __('Allow only :num digit after decimal point in :label. Recommended to use up-to 6 digit after decimal point.', ['label' => __("Base Price"), 'num' => '8']),
        ]);
        if ($validator->fails()) {
            $msg = '';
            if ($validator->errors()->hasAny(['name', 'start_date', 'end_date', 'total_tokens', 'liquidity', 'base_price', 'display_mode', 'min_purchase', 'max_purchase', 'soft_cap', 'hard_cap', 'price_type'])) {
                $msg = $validator->errors()->first();
            } else {
                $msg = __('messages.form.wrong');
            }

            $ret['msg'] = 'warning';
            $ret['message'] = $msg;
            return response()->json($ret);
        } else {
            $id = $request->input('ico_id');
            $ico = IcoStage::find($id);
            if ($ico == null) {
                $ico = new IcoStage();
            }
            if ($ico && $this->handler->cris_cros($this->handler->getDomain(), app_key(2)) && !empty(env_file()) ) {
                $re_start_date = ($request->input('start_date')) ? $request->input('start_date') : def_datetime('date');
                $re_start_time = ($request->input('start_time')) ? $request->input('start_time') : def_datetime('time');

                $re_end_date = ($request->input('end_date')) ? $request->input('end_date') : def_datetime('date');
                $re_end_time = ($request->input('end_time')) ? $request->input('end_time') : def_datetime('time_e');

                $start_date = _date($re_start_date.' '.$re_start_time, 'Y-m-d H:i:s');
                $end_date = _date($re_end_date.' '.$re_end_time, 'Y-m-d H:i:s');
                // Update or Create
                $ico->name              = $request->input('name');
                $ico->start_date        = $start_date;
                $ico->end_date          = $end_date;
                $ico->total_tokens      = (double)$request->input('total_tokens'); // Disable to change total tokens, to change need to deep more.
                $ico->price_type        = ($request->input('price_type')==NULL)?'static':'dynamic';
                $ico->base_price        = (double)$request->input('base_price');
                $ico->liquidity         = (double)$request->input('liquidity');
                $ico->min_purchase      = (double)$request->input('min_purchase');
                $ico->max_purchase      = (double)$request->input('max_purchase');
                $ico->soft_cap          = (double)$request->input('soft_cap');
                $ico->hard_cap          = (double)$request->input('hard_cap');
                $ico->display_mode      = $request->input('display_mode');

                $ico->status            = ($request->input('sale_pause')==NULL)?'paused':'active';
                $ico->is_tracked        = ($request->input('is_tracked')==NULL) ? 0 : 1;

                $ret['ico'] = $ico;
                //check validity
                $save = $ico->save();
                if ($save) {
                    Setting::updateValue( 'token_all_price', json_encode(token_calc(1, 'price')) ); //v1.1.1
                }
                if ($save) {
                    $ret['msg'] = 'success';
                    $ret['message'] = __('messages.update.success', ['what' => 'ICO Stage']);
                } else {
                    $ret['msg'] = 'warning';
                    $ret['message'] = __('messages.update.failed', ['what' => 'ICO Stage']);
                }
            } else {
                $ret['msg'] = 'warning';
                $ret['message'] = __('messages.errors');
            }
        }

        if ($request->ajax()) {
            return response()->json($ret);
        }
        return back()->with([$ret['msg'] => $ret['message']]);
    }

    /**
     * Update the Stage Options.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @version 1.0.1
     * @since 1.0
     * @return void
     */
    public function update_options(Request $request)
    {
        $ret['msg'] = 'info';
        $ret['message'] = __('messages.nothing');
        $type = $request->input('req_type');
        $stage = IcoStage::find($request->input('ico_id'));
        // Update ICO Price Options
        if ($type == 'price_option') {
            $price_data = [];
            for ($i=1; $i <= 3; $i++) {

                $in = $request->input('ptire_'.$i);
                $price = (double)$request->input('ptire_'.$i.'_token_price');
                $min_purchase = (int)$request->input('ptire_'.$i.'_min_purchase');
                $start_date = $request->input('ptire_'.$i.'_start_date') ? $request->input('ptire_'.$i.'_start_date') : def_datetime('date');
                $start_time = $request->input('ptire_'.$i.'_start_time') ? $request->input('ptire_'.$i.'_start_time') : def_datetime('time_s');
                $end_date = $request->input('ptire_'.$i.'_end_date') ? $request->input('ptire_'.$i.'_end_date') : def_datetime('date');
                $end_time = $request->input('ptire_'.$i.'_end_time') ? $request->input('ptire_'.$i.'_end_time') : def_datetime('time_e');

                $validator = Validator::make($request->all(), [
                    'ptire_'.$i.'_token_price'   => ['bail', 'nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,8})?$/'],
                ],[
                    'ptire_'.$i.'_token_price.regex' => __('Allow only :num digit after decimal point in :label. Recommended to use up-to 6 digit after decimal point.', ['label' => __("Tier ". $i), 'num' => '8']),
                ]);

                if ($validator->fails()) {
                    $ret['msg'] = 'warning';
                    $ret['message'] = $validator->errors()->first();
                    return response()->json($ret);
                }
                
                if($in && $price <= 0) {
                    $ret['msg'] = 'warning';
                    $ret['message'] = 'Token price should be grater than 0. (In Tier '.$i.')';
                    return response()->json($ret);
                }elseif(strtotime($start_date.' '.$start_time) >= strtotime($end_date.' '.$end_time)) {
                    $ret['msg'] = 'warning';
                    $ret['message'] = 'Start date can not be equal or greater than end date. (In Tier '.$i.')';
                    return response()->json($ret);
                } elseif ($min_purchase > $stage->max_purchase) {
                    $ret['msg'] = 'warning';
                    $ret['message'] = 'Min purchase must be greater then ICO Stage max purchase. (In Tier '.$i.')';
                    return response()->json($ret);
                }elseif ($in && $min_purchase <= 0) {
                    $ret['msg'] = 'warning';
                    $ret['message'] = 'Min purchase should be grater than 0 (In Tier '.$i.')';
                    return response()->json($ret);
                }
                $price_data['tire_'.$i] = [
                    'price' => $price ? $price : 0,
                    'min_purchase' => $min_purchase ? $min_purchase : 0,
                    'start_date' => _date($start_date.' '.$start_time, 'Y-m-d H:i:s'),
                    'end_date' => _date($end_date.' '.$end_time, 'Y-m-d H:i:s'),
                    'status' => ($in ? 1 : 0)
                ];
            }
            $json_data = json_encode($price_data);
            $save = IcoMeta::UpdateOrCreate(['stage_id' => $request->input('ico_id'), 'option_name' => 'price_option'], [
                'stage_id' => $request->input('ico_id'),
                'option_name' => 'price_option',
                'option_value' => $json_data,
                'status' => 1
            ]);
            if ($save) {
                $ret['msg'] = 'success';
                $ret['message'] = __('messages.update.success', ['what'=>'Stage Price Option']);
            } else {
                $ret['msg'] = 'error';
                $ret['message'] = __('messages.update.failed', ['what'=>'Stage Price Option']);
            }
        }
        // Update ICO Bonus Options
        if ($type == 'bonus_option') {
            $bonus_data = $bamount = [];

            # Entire Code execute here
            $start_date = $request->input('bb_start_date') ? $request->input('bb_start_date') : def_datetime('date');
            $start_time = $request->input('bb_start_time') ? $request->input('bb_start_time') : def_datetime('time_s');
            $end_date = $request->input('bb_end_date') ? $request->input('bb_end_date') : def_datetime('date');
            $end_time = $request->input('bb_end_time') ? $request->input('bb_end_time') : def_datetime('time_e');

            if (strtotime($start_date.' '.$start_time) >= strtotime($end_date.' '.$end_time)) {
                $ret['msg'] = 'warning';
                $ret['message'] = 'Start date can not be equal or greater than end date. (In Base Tier)';
                return response()->json($ret);
            }elseif ($request->input('bb_amount') < 0) {
                $ret['msg'] = 'warning';
                $ret['message'] = 'Base bonus amount can not be less than 0 (In Tier '.$i.')';
                return response()->json($ret);
            }
            $bonus_data['base'] = [
                'amount' => $request->input('bb_amount') ? (int)$request->input('bb_amount') : 0,
                'start_date' => _date($start_date.' '.$start_time, 'Y-m-d H:i:s'),
                'end_date' => _date($end_date.' '.$end_time, 'Y-m-d H:i:s'),
                'status' => ($request->input('bb_amount') >= 1 ? 1 : 0)
            ];
            for ($i=1; $i <= 3; $i++) {
                if ($request->input('ba_amount_'.$i) < 0) {
                    $ret['msg'] = 'warning';
                    $ret['message'] = 'Amount bonus can not be less than 0 (In Tier '.$i.')';
                    return response()->json($ret);
                }

                $bamount['tire_'.$i] = [
                    'amount' => $request->input('ba_amount_'.$i) ? (int)$request->input('ba_amount_'.$i) : '',
                    'token' => $request->input('ba_token_'.$i) ? (int)$request->input('ba_token_'.$i) : ''
                ];
            }
            $bamount['status'] = $request->input('bonus_amount') ? 1 : 0;
            $bonus_data['bonus_amount'] = $bamount;

            $json_data = json_encode($bonus_data);
            $save = IcoMeta::UpdateOrCreate(['stage_id' => $request->input('ico_id'), 'option_name' => 'bonus_option'], [
                'stage_id' => $request->input('ico_id'),
                'option_name' => 'bonus_option',
                'option_value' => $json_data,
                'status' => 1
            ]);
            if ($save) {
                $ret['msg'] = 'success';
                $ret['message'] = __('messages.update.success', ['what'=>'Stage bonus Option']);
            } else {
                $ret['msg'] = 'error';
                $ret['message'] = __('messages.update.failed', ['what'=>'Stage bonus Option']);
            }
        }


        if ($request->ajax()) {
            return response()->json($ret);
        }
        return back()->with([$ret['msg'] => $ret['message']]);
    }

    /**
     * Update ICO Stage Settings
     *
     * @return \Illuminate\Http\Response
     * @version 1.4
     * @since 1.0
     * @return void
     */
    public function update_settings(Request $request)
    {
        $type = $request->input('req_type');
        $ret['msg'] = 'info';
        $ret['message'] = __('messages.nothing');

        if ($type == 'token_details') {
            $validator = Validator::make($request->all(), [
                'token_name' => 'required|min:3',
                'token_symbol' => 'required|min:2'
            ]);

            if ($validator->fails()) {
                $msg = '';
                if ($validator->errors()->hasAny(['token_name', 'token_symbol'])) {
                    $msg = $validator->errors()->first();
                } else {
                    $msg = __('messages.update.failed', ['what' => 'Token Details']);
                }

                $ret['msg'] = 'warning';
                $ret['message'] = $msg;
            } else {
                if ($request->input('token_name') != null) {
                    Setting::updateValue('token_name', $request->input('token_name'));
                }
                if ($request->input('token_symbol') != null) {
                    Setting::updateValue('token_symbol', $request->input('token_symbol'));
                }
                if ($request->input('token_decimal_min') != null) {
                    Setting::updateValue('token_decimal_min', $request->input('token_decimal_min'));
                }
                if ($request->input('token_decimal_max') != null) {
                    Setting::updateValue('token_decimal_max', $request->input('token_decimal_max'));
                }
                if ($request->input('token_decimal_show') != null) {
                    Setting::updateValue('token_decimal_show', $request->input('token_decimal_show'));
                }

                $ret['msg'] = 'success';
                $ret['message'] = __('messages.update.success', ['what' => 'Token Details']);
            }
        } elseif($type == 'token_purchase') {
            $default = 'token_purchase_'.strtolower($request->input('token_default_method'));

            # Checkbox value set
            $token_price = isset($request->token_price_show) ? 1 : 0;
            $token_format = isset($request->token_number_format) ? 1 : 0;
            $before_kyc = isset($request->token_before_kyc) ? 1 : 0;

            Setting::updateValue('token_price_show', $token_price);
            Setting::updateValue('token_number_format', $token_format);
            Setting::updateValue('token_before_kyc', $before_kyc);

            $currencies = PaymentMethod::Currency;
            foreach ($currencies as $token_short => $token_long) {
                $value = 0;
                $setting_key = 'token_purchase_' . $token_short;
                if (isset($request->$setting_key)) {
                    $value = 1;
                }
                Setting::updateValue($setting_key, $value);
            }
            Setting::updateValue($default, 1);

            if ($request->input('token_default_method') != '') {
                Setting::updateValue('token_default_method', $request->input('token_default_method'));
            }
            if ($request->input('token_default_in_userpanel') != '') {
                Setting::updateValue('token_default_in_userpanel', $request->input('token_default_in_userpanel'));
            }
            if ($request->input('token_sales_raised') != '') {
                Setting::updateValue('token_sales_raised', $request->input('token_sales_raised'));
            }
            if ($request->input('token_sales_total') != '') {
                Setting::updateValue('token_sales_total', $request->input('token_sales_total'));
            }
            if ($request->input('token_sales_cap') != '') {
                Setting::updateValue('token_sales_cap', $request->input('token_sales_cap'));
            }
            if ($request->input('token_price_impact_limit') != NULL) {
                Setting::updateValue('token_price_impact_limit', $request->input('token_price_impact_limit'));
            }

            $ret['msg'] = 'success';
            $ret['message'] = __('messages.update.success', ['what' => 'Purchase Token Settings']);
        } elseif($type == 'user_panel') {
            //v1.1.0 -> v1.1.2
            $wallet_opt = json_encode(array('wallet_opt' => $request->token_wallet_opt));
            Setting::updateValue('token_wallet_opt', $wallet_opt);

            $wallet_opt_custom = json_encode(array('cw_name' => $request->token_wallet_custom[0], 'cw_text' => $request->token_wallet_custom[1]));
            Setting::updateValue('token_wallet_custom', $wallet_opt_custom);

            if ($request->input('token_wallet_note') != null) {
                Setting::updateValue('token_wallet_note', $request->input('token_wallet_note'));
            }
            Setting::updateValue('token_wallet_req', (isset($request->token_wallet_req) ? 1 : 0));

            //v1.1.2
            if ($request->input('user_in_cur1') != '') {
                Setting::updateValue('user_in_cur1', $request->input('user_in_cur1'));
            }
            if ($request->input('user_in_cur2') != '') {
                Setting::updateValue('user_in_cur2', $request->input('user_in_cur2'));
            }
            Setting::updateValue('user_mytoken_page', (isset($request->user_mytoken_page) ? 1 : 0));
            Setting::updateValue('user_mytoken_stage', (isset($request->user_mytoken_stage) ? 1 : 0));
            //v1.2.0
            Setting::updateValue('kyc_opt_hide', (isset($request->kyc_opt_hide) ? 1 : 0));
            Setting::updateValue('opt_count_hide', (isset($request->opt_count_hide) ? 1 : 0));
            Setting::updateValue('user_sales_progress', (isset($request->user_sales_progress) ? 1 : 0));
            Setting::updateValue('welcome_img_hide', (isset($request->welcome_img_hide) ? 1 : 0));

            $ret['msg'] = 'success';
            $ret['message'] = __('messages.update.success', ['what' => 'User Panel Settings']);
        }

        if ($request->ajax()) {
            return response()->json($ret);
        }
        return back()->with([$ret['msg'] => $ret['message']]);
    }
}
