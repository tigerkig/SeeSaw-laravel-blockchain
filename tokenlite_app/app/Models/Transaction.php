<?php
/**
 * Transaction Model
 *
 *  Manage the Transactions
 *
 * @package TokenLite
 * @author Softnio
 * @version 1.1.6
 */
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{

    /*
     * Table Name Specified
     */
    protected $table = 'transactions';

    protected $fillable = ['tnx_id', 'tnx_type', 'tnx_time', 'tokens', 'bonus_on_base', 'bonus_on_token', 'total_bonus', 'total_tokens', 'stage', 'user', 'amount', 'receive_amount', 'receive_currency', 'base_amount', 'base_currency', 'base_currency_rate', 'currency', 'currency_rate', 'all_currency_rate', 'wallet_address', 'payment_method', 'payment_id', 'payment_to', 'checked_by', 'added_by', 'checked_time', 'details', 'extra', 'status', 'dist'
    ];

    /**
     *
     * Relation with user
     *
     * @version 1.0.1
     * @since 1.0
     * @return void
     */
    public function tnxUser()
    {
        return $this->belongsTo(User::class, 'user', 'id');
    }


    /**
     *
     * Relation with auth user
     *
     * @version 1.0.0
     * @since 1.1.2
     * @return void
     */
    public function user_tnx()
    {
        return $this->belongsTo(User::class, 'user', 'id')->where('user', auth()->id());
    }

    /**
     *
     * Relation with receiver
     *
     * @version 1.0.0
     * @since 1.0
     * @return void
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'payment_to', 'email');
    }

    /**
     *
     * Relation with user by id
     *
     * @version 1.0.0
     * @since 1.0
     * @return void
     */
    public function user($id)
    {
        return \App\Models\User::find($id);
    }

    /**
     *
     * Relation with stage
     *
     * @version 1.0.0
     * @since 1.0
     * @return void
     */
    public function ico_stage()
    {
        return $this->belongsTo(IcoStage::class, 'stage', 'id');
    }
    
    /**
     *
     * Get transaction by on current user
     *
     * @version 1.0.0
     * @since 1.1.2
     * @return \Illuminate\Database\Eloquent
     */
    public static function get_by_own($where=null, $where_not=null) {
        // $return = (empty($where)) ? self::has('user_tnx') : self::has('user_tnx')->where($where);
        $by_user = self::has('user_tnx');
        if(!empty($where)) {
            $by_user->where($where);
        }
        if(!empty($where_not)) {
            $by_user->whereNotIn($where_not);
        }
        return $by_user;
    }
    
    /**
     *
     * Get transaction by user
     *
     * @version 1.0.0
     * @since 1.1.2
     * @return \Illuminate\Database\Eloquent
     */
    public static function by_user($user, $where=null, $where_not=null) {
        $by_user = self::where('user', $user);
        if(!empty($where)) {
            $by_user->where($where);
        }
        if(!empty($where_not)) {
            $by_user->whereNotIn($where_not);
        }
        return $by_user;
    }
    
    /**
     *
     * Get transaction by stage
     *
     * @version 1.0.0
     * @since 1.1.2
     * @return \Illuminate\Database\Eloquent
     */
    public static function by_stage($stage, $where=null, $where_not=null) {
        $by_stage = self::where('stage', $stage);
        if(!empty($where)) {
            $by_stage->where($where);
        }
        if(!empty($where_not)) {
            $by_stage->whereNotIn($where_not);
        }
        return $by_stage;
    }
    
    /**
     *
     * Get transaction by type
     *
     * @version 1.0.0
     * @since 1.1.2
     * @return \Illuminate\Database\Eloquent
     */
    public static function by_type($type, $where=null, $where_not=null) {
        $by_type = self::where('tnx_type', $type);
        if(!empty($where)) {
            $by_type->where($where);
        }
        if(!empty($where_not)) {
            $by_type->whereNotIn($where_not);
        }
        return $by_type;
    }
    
    /**
     *
     * Get transaction by type
     *
     * @version 1.0.0
     * @since 1.1.2
     * @return \Illuminate\Database\Eloquent
     */
    public static function by_status($status, $where=null, $where_not=null) {
        $by_status = self::where('status', $status);
        if(!empty($where)) {
            $by_status->where($where);
        }
        if(!empty($where_not)) {
            $by_status->whereNotIn($where_not);
        }
        return $by_status;
    }

    /**
     *
     * Advanced Filter Method
     *
     * @version 1.0.0
     * @since 1.1.0
     * @return void
     */
    public static function AdvancedFilter(Request $request) {
        if($request->s){
            $trnxs  = Transaction::whereNotIn('status', ['deleted', 'new'])->whereNotIn('tnx_type', ['withdraw'])
                        ->where(function($q) use ($request){
                            $id_num = (int)(str_replace(config('icoapp.tnx_prefix'), '', $request->s));
                            $q->orWhere('id', $id_num)->orWhere('tnx_id', 'like', '%'.$request->s.'%')->orWhere('wallet_address', 'like', '%'.$request->s.'%')->orWhere('payment_to', 'like', '%'.$request->s.'%');
                        });
            return $trnxs;
        }
        if($request->filter){
            $deleted    = ($request->state=='deleted') ? 'blank' : 'deleted';

            $trnxs = Transaction::whereNotIn('status', [$deleted, 'new'])->whereNotIn('tnx_type', ['withdraw'])
                        ->where(self::keys_in_filter( $request->only(['type', 'state', 'stg', 'pmg', 'pmc']) ))
                        ->when($request->search, function($q) use ($request){
                            $is_user    = (isset($request->by) && $request->by == 'usr') ? true : false;
                            $has_wallet  = (isset($request->by) && $request->by == 'wallet_address') ? true : false;
                            if ($has_wallet) {
                                $where  = (isset($request->by) && $request->by != '') ? strtolower($request->by) : 'tnx_id';
                                $search = $request->search;
                                $q->where($where, 'like', '%'.$search.'%');
                            } else {
                                $prefix     = ($is_user) ? config('icoapp.user_prefix') : config('icoapp.tnx_prefix');
                                $id_num     = (int)(str_replace($prefix, '', $request->search));
                                $where_in   = ($is_user) ? 'user' : 'id';
                                $q->where($where_in, $id_num);
                            }
                        })
                        ->when($request->date, function($q) use ($request){
                            $dates = self::date_in_filter($request);
                            $q->whereBetween('tnx_time', $dates);
                        });
            return $trnxs;
        }
    }

    /**
    * Search/Filter parametter exchnage with database value
    *
    * @version 1.0.0
    * @since 1.1.0
    * @return void
    */
    protected static function keys_in_filter($request) {
        $result = [];
        $find = ['type', 'state', 'stg', 'pmg', 'pmc'];
        $replace = ['tnx_type', 'status', 'stage', 'payment_method', 'currency'];
        foreach($request as $key => $values) {
            $set_key = str_replace($find, $replace, $key);
            $result[$set_key] = trim($values);

            if(empty($result[$set_key])) {
                unset($result[$set_key]);
            }
        }

        return $result;
    }

    /**
    * Date filter value set for search 
    *
    * @version 1.0.0
    * @since 1.1.0
    * @return void
    */
    protected static function date_in_filter($request) {
        $app_start = Setting::where('field', 'site_name')->value('created_at');
        $date = [$app_start, now()->toDateTimeString()];
        $get_date = $request->date;

        if($get_date == 'custom'){
            $from = $request->get('from', $app_start);
            $to = $request->get('to', date('m/d/Y'));
            $date = [
                _cdate($from)->toDateTimeString(),
                _cdate($to)->endOfDay()->toDateTimeString(),
            ];
        }
        if($get_date == 'today'){
            $today = Carbon::now()->today();
            $now = Carbon::now()->today()->endOfDay();
            $date = [
                $today,
                $now
            ];
        }

        if($get_date == '7day'){
            $first = new Carbon();
            $last = new Carbon();
            $date = [
                $first->subDays(7)->startOfDay(),
                $last->today()->subDay()->endOfDay()
            ];
        }

        if($get_date == '15day'){
            $first = new Carbon();
            $last = new Carbon();
            $date = [
                $first->subDays(15)->startOfDay(),
                $last->today()->subDay()->endOfDay()
            ];
        }

        if($get_date == '30day'){
            $first = new Carbon();
            $last = new Carbon();
            $date = [
                $first->subDays(30)->startOfDay(),
                $last->today()->subDay()->endOfDay()
            ];
        }
        
        if($get_date == '90day'){
            $first = new Carbon();
            $last = new Carbon();
            $date = [
                $first->subDays(90)->startOfDay(),
                $last->today()->subDay()->endOfDay()
            ];
        }

        if($get_date == 'this-month'){
            $first =  new Carbon();
            $last = new Carbon();
            $date = [
                $first->firstOfMonth()->startOfDay(),
                $last->lastOfMonth()->endOfDay()
            ];
        }

        if($get_date == 'last-month'){
            $first = new Carbon();
            $last = new Carbon();
            $date = [
                $first->firstOfMonth()->subMonths(1)->startOfDay(),
                $last->lastOfMonth()->subMonths(1)->endOfDay()
            ];
        }

        if($get_date == 'this-year'){
            $first = Carbon::now();
            $last = Carbon::now();
            $date = [
                $first->setDate($first->year, 1, 1)->startOfDay(),
                $last->setDate($last->year, 12, 31)->endOfDay()
            ];
        }

        if($get_date == 'last-year'){
            $first = Carbon::now();
            $last = Carbon::now();
            $date = [
                $first->setDate($first->year, 1, 1)->startOfDay()->subYears(1),
                $last->setDate($last->year, 12, 31)->endOfDay()->subYears(1)
            ];
        }

        return $date;
    }

    /**
     *
     * Dashboard data
     *
     * @version 1.4.1
     * @since 1.0
     * @return void
     */
    public static function dashboard($chart = 7) {
        $base_amount = 0; $max = max_decimal();
        $all_base = self::where(['status' => 'approved', 'tnx_type' => 'purchase', 'refund' => null])->get();
        foreach ($all_base as $item) {
            $base_amount += $item->base_amount;
        }

        $data['currency'] = (object) [
            'usd' => round(self::amount_count('USD')->total, $max),
            'eur' => round(self::amount_count('EUR')->total, $max),
            'gbp' => round(self::amount_count('GBP')->total, $max),
            'cad' => round(self::amount_count('CAD')->total, $max),
            'aud' => round(self::amount_count('AUD')->total, $max),
            'try' => round(self::amount_count('TRY')->total, $max),
            'rub' => round(self::amount_count('RUB')->total, $max),
            'inr' => round(self::amount_count('INR')->total, $max),
            'brl' => round(self::amount_count('BRL')->total, $max),
            'nzd' => round(self::amount_count('NZD')->total, $max),
            'pln' => round(self::amount_count('PLN')->total, $max),
            'jpy' => round(self::amount_count('JPY')->total, $max),
            'myr' => round(self::amount_count('MYR')->total, $max),
            'idr' => round(self::amount_count('IDR')->total, $max),
            'ngn' => round(self::amount_count('NGN')->total, $max),
            'mxn' => round(self::amount_count('MXN')->total, $max),
            'php' => round(self::amount_count('PHP')->total, $max),
            'chf' => round(self::amount_count('CHF')->total, $max),
            'thb' => round(self::amount_count('THB')->total, $max),
            'sgd' => round(self::amount_count('SGD')->total, $max),
            'czk' => round(self::amount_count('CZK')->total, $max),
            'nok' => round(self::amount_count('NOK')->total, $max),
            'zar' => round(self::amount_count('ZAR')->total, $max),
            'sek' => round(self::amount_count('SEK')->total, $max),
            'kes' => round(self::amount_count('KES')->total, $max),
            'nad' => round(self::amount_count('NAD')->total, $max),
            'dkk' => round(self::amount_count('DKK')->total, $max),
            'hkd' => round(self::amount_count('HKD')->total, $max),
            'huf' => round(self::amount_count('HUF')->total, $max),
            'pkr' => round(self::amount_count('PKR')->total, $max),
            'egp' => round(self::amount_count('EGP')->total, $max),
            'clp' => round(self::amount_count('CLP')->total, $max),
            'cop' => round(self::amount_count('COP')->total, $max),
            'jmd' => round(self::amount_count('JMD')->total, $max),
            'eth' => round(self::amount_count('ETH')->total, $max),
            'ltc' => round(self::amount_count('LTC')->total, $max),
            'btc' => round(self::amount_count('BTC')->total, $max),
            'xrp' => round(self::amount_count('XRP')->total, $max),
            'xlm' => round(self::amount_count('XLM')->total, $max),
            'bch' => round(self::amount_count('BCH')->total, $max),
            'bnb' => round(self::amount_count('BNB')->total, $max),
            'trx' => round(self::amount_count('TRX')->total, $max),
            'usdt' => round(self::amount_count('USDT')->total, $max),
            'usdc' => round(self::amount_count('USDC')->total, $max),
            'dash' => round(self::amount_count('DASH')->total, $max),
            'waves' => round(self::amount_count('WAVES')->total, $max),
            'xmr' => round(self::amount_count('XMR')->total, $max),
            'busd' => round(self::amount_count('BUSD')->total, $max),
            'ada' => round(self::amount_count('ADA')->total, $max),
            'doge' => round(self::amount_count('DOGE')->total, $max),
            'sol' => round(self::amount_count('SOL')->total, $max),
            'uni' => round(self::amount_count('UNI')->total, $max),
            'link' => round(self::amount_count('LINK')->total, $max),
            'cake' => round(self::amount_count('CAKE')->total, $max),
            'base' => round($base_amount, $max)
        ];
        $data['chart'] = self::chart($chart);

        $data['all'] = self::whereNotIn('status', ['deleted', 'new'])->whereNotIn('tnx_type', ['withdraw'])->orderBy('created_at', 'DESC')->limit(4)->get();


        return (object) $data;
    }

    /**
     *
     * Dashboard data
     *
     * @version 1.4.1
     * @since 1.0
     * @return void
     */
    public static function user_dashboard($chart = 1440) {
        $data['chart'] = self::price_chart($chart);
        return $data;
    }

    /**
     *
     * Count the amount
     *
     * @version 1.2
     * @since 1.0
     * @return void
     */
    public static function amount_count($currency='', $extra=null) {
        $data['total'] = $data['base'] = 0;
        $currency = strtolower($currency);

        if(!empty($extra)) {
            $all = self::where(['status'=>'approved', 'tnx_type'=>'purchase', 'refund'=>null, 'currency'=>$currency])->where($extra)->get();
        } else {
            $all = self::where(['status'=>'approved', 'tnx_type'=>'purchase', 'refund'=>null, 'currency'=>$currency])->get();
        }
        foreach ($all as $tnx) {
            $data['total'] += $tnx->amount;
            $data['base'] += $tnx->base_amount;
        }
        return (object) $data;
    }
    /**
     *
     * Chart data
     *
     * @version 1.1
     * @since 1.0
     * @return void
     */
    public static function chart($get = 6) {
        $cd = Carbon::now(); //->toDateTimeString();
        $lw = $cd->copy()->subDays($get);

        $cd = $cd->copy()->addDays(1);
        $df = $cd->diffInDays($lw);
        $transactions = self::where(['status'=>'approved', 'tnx_type'=>'purchase'])
            ->whereBetween('created_at', [$lw, $cd])
            ->orderBy('created_at', 'DESC')
            ->get();
        $data['days'] = null;
        $data['data'] = null;
        $data['data_alt'] = null;
        $data['days_alt'] = null;
        for ($i = 1; $i <= $df; $i++) {
            $tokens = 0;
            foreach ($transactions as $tnx) {
                $tnxDate = date('Y-m-d', strtotime($tnx->tnx_time));
                if ($lw->format('Y-m-d') == $tnxDate) {
                    $tokens += $tnx->total_tokens;
                } else {
                    $tokens += 0;
                }
            }
            $data['data'] .= $tokens . ",";
            $data['data_alt'][$i] = $tokens;
            $data['days_alt'][$i] = ($get > 27 ? $lw->format('d M Y') : $lw->format('d M'));
            $data['days'] .= '"' . $lw->format('d M') . '",';
            $lw->addDay();
        }
        return (object) $data;
    }

    /**
     *
     * Price Chart data
     *
     * @version 1.1
     * @since 1.0
     * @return void
     */
    public static function price_chart($minutes = 60) {
        // get stage ids to track, figure out 'all time' minutes if mins = 0
        $now = Carbon::now();
        $tracked_stages = tracked_stages();
        $stage_ids = [];
        $end_date = false;
        for ($i = 0; $i < count($tracked_stages); $i++) {
            $stage = $tracked_stages[$i];
            $stage_ids[] = $stage->id;
            if ($i == count($tracked_stages) - 1) {
                $start_date = $stage->start_date;
            }
        }
        // If 'all time' minutes are 0, calcuate the minutes based on earliest start date
        if (!empty($start_date) && $minutes == -1) {
            $start_date = Carbon::createFromFormat('Y-m-d H:i:s', $start_date);
            $minutes = $now->diffInMinutes($start_date);
        }

        $date_format = 'd M Y';
        if ($minutes > 1440) { // more than a day
            $date_format = 'd M Y H:00';
        } else if ($minutes > 60) { // less than a day, more than an hour
            $date_format = 'd M Y H:00';
        } else { // less than an hour
            $date_format = 'd M Y H:i';
        }

        $data['time'] = '';
        $data['time_alt'] = [];
        $data['price'] = '';
        $data['price_alt'] = [];
        $chart_index = 0;
        $latest_price = false;
        $seconds = $minutes * 60;
        $time_interval_seconds = $seconds / 50;
        $time_periods = [];
        for ($i = 0; $i <= $seconds; $i += $time_interval_seconds) {
            $time_periods[] = \Carbon\CarbonPeriod::create(
                $now->copy()->subSeconds($i),
                $now->copy()->subSeconds($i + $time_interval_seconds)
            );
        }
        foreach ($time_periods as $index => $time_period) {
            $start_date = $time_period->getStartDate();
            $end_date = $time_period->getEndDate();
            $price = false;
            if ($index == 0) {
                $price = active_stage()->base_price;
            } else {
                $latest_transaction = self::where([
                    'status' => 'approved',
                    'tnx_type' => 'purchase',
                    'refund' => null
                ])->whereBetween('created_at', [
                    $end_date,
                    $start_date
                ])->whereIn('stage', 
                    $stage_ids
                )->orderBy(
                    'created_at',
                    'DESC'
                )->limit(1);
                $latest_transaction = $latest_transaction->get();
                if (!empty($latest_transaction[0])) {
                    $price = $latest_transaction[0]->base_currency_rate;
                }
            }
            if (!empty($price)) {
                $latest_price = $price;
            }
            if (!empty($latest_price)) {
                $time = $end_date->format($date_format);

                $data['time'] = $time . ',' . $data['time'];
                $data['time_alt'][$chart_index] = $time;

                $data['price'] = number_format($latest_price, 6) . ',' . $data['price'];
                $data['price_alt'][$chart_index] = number_format($latest_price, 6);

                $chart_index += 1;
            }
        }

        $data['time_alt'] = array_reverse($data['time_alt']);
        $data['price_alt'] = array_reverse($data['price_alt']);
        $data['test'] = $stage_ids;

        return $data;
    }

    public static function recent()
    {
        $now = Carbon::now();

        $period = \Carbon\CarbonPeriod::create(
            $now->copy()->subHours(12),
            $now->copy()->addDays(1)
        );

        $transactions = Transaction::where([
            'tnx_type' => 'purchase'
        ])->whereIn('status', [
            'approved',
            'pending'
        ])->whereBetween('updated_at', [
            $period->getStartDate(),
            $period->getEndDate()
        ])->orderBy(
            'updated_at',
            'DESC'
        )->get([
            'status',
            'tnx_type',
            'created_at',
            'updated_at',
            'base_amount',
            'total_tokens',
            'amount',
            'receive_amount',
            'receive_currency',
        ]);
        return $transactions;
    }
    
    public static function transaction_data()
    {
        $data = [];
        $today = Carbon::today();
        // Define the time periods that need to be checked and aggregated
        $periods = [
            'today' => \Carbon\CarbonPeriod::create(
                $today,
                $today->copy()->addDays(1)
            ),
            'yesterday' => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(1),
                $today
            ),
            $today->copy()->subDays(2)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(2),
                $today->copy()->subDays(1)
            ),
            $today->copy()->subDays(3)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(3),
                $today->copy()->subDays(2)
            ),
            $today->copy()->subDays(4)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(4),
                $today->copy()->subDays(3)
            ),
            $today->copy()->subDays(5)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(5),
                $today->copy()->subDays(4)
            ),
            $today->copy()->subDays(6)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(6),
                $today->copy()->subDays(5)
            ),
            'last_7_days' => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(7),
                $today->copy()->addDays(1)
            ),
            'last_30_days' => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(30),
                $today->copy()->addDays(1)
            ),
            'this_month' => \Carbon\CarbonPeriod::create(
                $today->copy()->day(1),
                $today->copy()->day(1)->addMonth()
            ),
            'all_time' => \Carbon\CarbonPeriod::create(
                $today->copy()->subYears(20), // not really ALL TIME, but 20 years is good enough
                $today->copy()->addDays(1)
            ),
        ];

        // make the queries to aggregate the transactions on the defined time periods
        foreach ($periods as $title => $period) {
            $transactions = Transaction::where([
                'tnx_type' => 'purchase',
                'stage' => active_stage()->id
            ])->whereIn('status', [
                'approved',
                'pending'
            ])->whereBetween('updated_at', [
                $period->getStartDate(),
                $period->getEndDate()
            ])->orderBy(
                'updated_at',
                'DESC'
            )->get([
                'status',
                'tnx_type',
                'created_at',
                'updated_at',
                'base_amount',
                'total_tokens',
                'amount',
                'receive_amount',
                'receive_currency',
            ]);

            $data[$title] = [
                'count' => count($transactions),
                'base_total' => $transactions->sum('base_amount'),
                'token_total' => $transactions->sum('total_tokens'),
                'transactions' => $transactions
            ];
        }

        return $data;
    }

    public static function price_data()
    {
        $data = [];
        $today = Carbon::today();
        // Define the time periods that need to be checked and aggregated
        $periods = [
            $today->copy()->toDateString() => \Carbon\CarbonPeriod::create(
                $today,
                $today->copy()->addDays(1)
            ),
            $today->copy()->subDays(1)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(1),
                $today
            ),
            $today->copy()->subDays(2)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(2),
                $today->copy()->subDays(1)
            ),
            $today->copy()->subDays(3)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(3),
                $today->copy()->subDays(2)
            ),
            $today->copy()->subDays(4)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(4),
                $today->copy()->subDays(3)
            ),
            $today->copy()->subDays(5)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(5),
                $today->copy()->subDays(4)
            ),
            $today->copy()->subDays(6)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(6),
                $today->copy()->subDays(5)
            ),
            $today->copy()->subDays(7)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(7),
                $today->copy()->subDays(6)
            ),
            $today->copy()->subDays(30)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(30),
                $today->copy()->subDays(29)
            ),
        ];

        // make the queries to aggregate the transactions on the defined time periods
        foreach ($periods as $title => $period) {
            $transactions = Transaction::where([
                'status' => 'approved',
                'tnx_type' => 'purchase'
            ])->whereBetween('created_at', [
                $period->getStartDate(),
                $period->getEndDate()
            ])->orderBy(
                'created_at',
                'DESC'
            )->get();
            $data[$title] = $transactions->max('base_currency_rate');
        }

        return $data;
    }

    /**
     *
     * User contribution
     *
     * @version 1.2
     * @since 1.0
     * @return void
     */
    public static function user_contribution()
    {
        $data = [];
        $curs = array_keys(PaymentMethod::Currency);
        $user_tnx = self::get_by_own(['status' => 'approved', 'tnx_type' => 'purchase', 'refund' => null])->get();
        $total_base_amount = $user_tnx->sum('base_amount');
        foreach ($curs as $cur) {
            $data[$cur] = Setting::getValue('pmc_auto_rate_' . $cur) * $total_base_amount;
        }
        $data['base'] = $total_base_amount;
        return (object) $data;
    }

    /**
     * gets the data for the admin progress dashboard panel
     *
     * @return array table data
     */
    public static function progress()
    {
        $data = [];
        $today = Carbon::today();
        // Define the time periods that need to be checked and aggregated
        $periods = [
            'Today' => \Carbon\CarbonPeriod::create(
                $today,
                $today->copy()->addDays(1)
            ),
            'Yesterday' => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(1),
                $today
            ),
            $today->copy()->subDays(2)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(2),
                $today->copy()->subDays(1)
            ),
            $today->copy()->subDays(3)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(3),
                $today->copy()->subDays(2)
            ),
            $today->copy()->subDays(4)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(4),
                $today->copy()->subDays(3)
            ),
            $today->copy()->subDays(5)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(5),
                $today->copy()->subDays(4)
            ),
            $today->copy()->subDays(6)->toDateString() => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(6),
                $today->copy()->subDays(5)
            ),
            'Last 7 Days' => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(7),
                $today->copy()->addDays(1)
            ),
            'Last 30 Days' => \Carbon\CarbonPeriod::create(
                $today->copy()->subDays(30),
                $today->copy()->addDays(1)
            ),
            'This Month' => \Carbon\CarbonPeriod::create(
                $today->copy()->day(1),
                $today->copy()->day(1)->addMonth()
            ),
            'All Time' => \Carbon\CarbonPeriod::create(
                $today->copy()->subYears(20), // not really ALL TIME, but 20 years is good enough
                $today->copy()->addDays(1)
            ),
        ];

        // make the queries to aggregate the transactions on the defined time periods
        foreach ($periods as $title => $period) {
            $transactions = Transaction::where([
                'status' => 'approved',
                'tnx_type' => 'purchase'
            ])->whereBetween('created_at', [
                $period->getStartDate(),
                $period->getEndDate()
            ])->orderBy(
                'created_at',
                'DESC'
            )->get();
            $data[$title] = [
                'count' => count($transactions),
                'base_total' => $transactions->sum('base_amount'),
                'total' => $transactions->sum('total_tokens'),
            ];
        }

        // Get all the users who are missing their wallet addresses
        $invalid_wallet_users = [];
        $invalid_wallet_users = User::where('walletAddress', NULL)
            ->where('tokenBalance', '>', '0')
            ->where('contributed', '>', '0')
            ->get();
        $data['Missing Wallets'] = [
            'count' => count($invalid_wallet_users),
            'base_total' => $invalid_wallet_users->sum('contributed'),
            'total' => $invalid_wallet_users->sum('tokenBalance'),
        ];

        return $data;
    }

    /**
     *
     * Transaction Overview
     *
     * @version 1.0
     * @since 1.1.2
     * @return void
     */
    public static function user_mytoken($type='balance')
    {
        if($type=='balance') {
            $user = auth()->user();
            $user_tnx = self::get_by_own(['status'=>'approved','refund'=>null])->whereNotIn('tnx_type', ['refund','withdraw','transfer'])->get();
            $user_wd = self::get_by_own(['tnx_type' => 'withdraw'])->get();
            $user_tf = self::get_by_own(['tnx_type' => 'transfer'])->get();
            
            $wd_pending = $user_wd->where('status', 'pending')->sum('total_tokens');
            $ts_pending = $user_tf->where('status', 'pending')->sum('total_tokens');

            $balance_sum = (object) [
                'current'   => $user->tokenBalance,
                'current_in_base' => token_price($user->tokenBalance, base_currency()),
                'total'         => $user_tnx->sum('total_tokens'),
                'purchased'     => $user_tnx->where('tnx_type', 'purchase')->sum('total_tokens'),
                'referral'      => $user_tnx->where('tnx_type', 'referral')->sum('total_tokens'),
                'bonuses'       => $user_tnx->where('tnx_type', 'bonus')->sum('total_tokens'),
                'contributed'   => $user_tnx->where('tnx_type', 'purchase')->sum('base_amount'),
                'contribute_in' => self::in_currency($user_tnx->where('tnx_type', 'purchase')),
                'has_withdraw'  => ($user_wd->count() > 0) ? true : false,
                'withdraw'      => $user_wd->where('status', 'approved')->sum('total_tokens'),
                'has_transfer'  => ($user_tf->count() > 0) ? true : false,
                'transfer'      => $user_tf->where('status', 'approved')->sum('tokens'),
                'pending'       => ($wd_pending + $ts_pending)
            ];
            return $balance_sum; 

        } elseif($type='stages') {
            $get_stages = IcoStage::with([ 'tnx_by_user'=> function($q) { $q->where(['refund' => null, 'status' => 'approved'])->whereNotIn('tnx_type', ['refund'])->has('user_tnx'); } ])->get();
            $stages_sum = [];
            if($get_stages->count() > 0) {
                foreach ($get_stages as $stage) {
                    if($stage->tnx_by_user->count() > 0) {
                        $stages_sum[] = (object) [
                            'stage' => $stage->id,
                            'name' => $stage->name,
                            'token' => $stage->tnx_by_user->sum('total_tokens'),
                            'purchase' => $stage->tnx_by_user->where('tnx_type', 'purchase')->sum('total_tokens'),
                            'bonus' => $stage->tnx_by_user->where('tnx_type', 'bonus')->sum('total_tokens'),
                            'referral' => $stage->tnx_by_user->where('tnx_type', 'referral')->sum('total_tokens'),
                            'contribute' => $stage->tnx_by_user->where('tnx_type', 'purchase')->sum('base_amount'),
                            'contribute_in' => self::in_currency($stage->tnx_by_user->where('tnx_type', 'purchase'))
                        ];
                    }
                }
            }
            return $stages_sum;
        }
        return false;        
    }

    /**
     *
     * Transaction in Sumation 
     *
     * @version 1.0
     * @since 1.1.2
     * @return void
     */
    public static function in_currency($all_tnx, $sum='amount')
    {
        $amounts = [];
        if(!empty($all_tnx) && $all_tnx->count() > 0){
            $currencies = $all_tnx->unique('currency')->pluck('currency');
            foreach ($currencies as $cur) {
                $amounts[$cur] = $all_tnx->where('currency', $cur)->sum($sum);
            }
        }
        return $amounts;
    }
}
