@extends('layouts.user')
@section('title', __('Purchase Token'))

@section('content')
@php
$has_sidebar = false;
$content_class = 'col-lg-8';

$current_date = time();
$upcoming = is_upcoming();

$_b = 0;
$bc = base_currency();
$base_currency = base_currency();

$default_method = token_method();
$symbol = token_symbol();
$method = strtolower($default_method);
$min_token = ($minimum) ? $minimum : active_stage()->min_purchase;

$sold_token = active_stage()->soldout;
$have_token = active_stage()->total_tokens - $sold_token;
$sales_ended = ($sold_token >= active_stage()->total_tokens) || ($have_token < $min_token);

$is_method = is_method_valid();

$sl_01 = ($is_method) ? '01 ' : '';
$sl_02 = ($sl_01) ? '02 ' : '';
$sl_03 = ($sl_02) ? '03 ' : '';


$exc_rate = (!empty($currencies)) ? json_encode($currencies) : '{}';
$token_price = (!empty($price)) ? json_encode($price) : '{}';
$amount_bonus = (!empty($bonus_amount)) ? json_encode($bonus_amount) : '{1 : 0}';
$decimal_min = (token('decimal_min')) ? token('decimal_min') : 0;
$decimal_max = (token('decimal_max')) ? token('decimal_max') : 0;

@endphp

<script>
    var access_url = "{{ route('user.ajax.token.access') }}";
    var minimum_token = {{ $min_token }}, maximum_token ={{ $stage->max_purchase }}, token_price = {!! $token_price !!}, token_symbol = "{{ $symbol }}",
    base_bonus = {!! $bonus !!}, amount_bonus = {!! $amount_bonus !!}, decimals = {"min":{{ $decimal_min }}, "max":{{ $decimal_max }} }, base_currency = "{{ base_currency() }}", base_method = "{{ $method }}", base_price = {{ $stage->base_price }};
    var max_token_msg = "{{ __('Maximum you can purchase :maximum_token token per contribution.', ['maximum_token' => to_num($stage->max_purchase, 'max', ',')]) }}";
    var max_price_limit_msg = "{{ __('Price impact limit of :price_change_limit% per contribution exceeded.', ['price_change_limit' => to_num($price_impact_limit, 'max', ',')]) }}";
    var max_tokens_limit_msg = "{{ __('Cannot exceed total number of available tokens.', []) }}";
    var min_token_msg = "{{__(':amount :symbol minimum contribution amount is required.', ['amount' => $min_token, 'symbol' => $symbol])}}";
</script>

@include('layouts.messages')
@if ($upcoming)
<div class="alert alert-dismissible fade show alert-info" role="alert">
    <a href="javascript:void(0)" class="close" data-dismiss="alert" aria-label="close">&nbsp;</a>
    {{ __('Sales Start at') }} - {{ _date(active_stage()->start_date) }}
</div>
@endif
<div class="content-area card">
    <div class="card-innr">
        <form action="{{ route('user.ajax.payment') }}" method="post" class="validate-modern token-purchase"  id="online_payment">
            @csrf
            <div class="card-head">
                <h4 class="card-title">
                {{ __('Step 1', ['symbol' => $symbol]) }}
                </h4>
            </div>
            <div class="card-text">
                <p>{{ __('Please select payment method', ['symbol' => $symbol]) }}</p>
            </div>

            @if($is_method==true)
                <div class="token-currency-choose payment-list">
                    <div class="row guttar-15px">
                        <div class="col-sm-9 col-md-6 col-12">
                            <select
                                class="select select-block select-bordered active_method pay-method"
                                name="pp_currency"
                                data-initial-value="{{$crypto_select}}"
                            >
                                @foreach($pm_currency as $gt => $full)
                                @if(token('purchase_'.$gt) == 1 || $method==$gt)
                                    <option data-label="{{$full}} ({{strtoupper($gt)}})">{{strtoupper($gt)}}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            @else
                <div class="token-currency-default payment-item-default">
                    <input class="pay-method" type="hidden" id="pay{{ base_currency() }}" name="pp_currency" value="{{ base_currency() }}" checked>
                </div>
            @endif

            <div class="card-head">
                <h4 class="card-title">{{ __('Step 2') }}</h4>
            </div>
            <div class="card-text">
                <p>{{ __('Enter the amount in dollars') }}</p>
            </div>
            @php
            $calc = token('calculate');
            $input_hidden_token = ($calc=='token' || $calc='normal') ? '<input class="pay-amount" type="hidden" id="pay-amount" value="">' : '';
            $input_hidden_amount = ($calc=='pay') ? '<input class="token-number" type="hidden" id="token-number" value="">' : '';
            $input_hidden_dollar = '<input type="hidden" class="token-number" id="token-number" value="' . $token_buy_amount . '">';

            $input_token_purchase = '<div class="token-pay-amount payment-get">'. $input_hidden_dollar . $input_hidden_token .'<input class="input-bordered input-with-hint " id="token-dollar-visible" value="' . $token_buy_amount . '"><div class="token-pay-currency"><span class="input-hint input-hint-sap payment-get-cur payment-cal-cur ucap">'.$bc .'</span></div></div>';
            $input_pay_amount = '<div class="token-pay-amount payment-from">'.$input_hidden_amount.'<input class="input-bordered input-with-hint pay-amount" type="text" id="pay-amount" value=""><div class="token-pay-currency"><span class="input-hint input-hint-sap payment-from-cur payment-cal-cur pay-currency ucap">'.$method.'</span></div></div>';
            $input_pay_amount = '<div class="token-pay-amount payment-from"><input class="input-bordered input-with-hint pay-amount" type="text" id="pay-amount" value=""><div class="token-pay-currency"><span class="input-hint input-hint-sap payment-from-cur payment-cal-cur pay-currency ucap">'.$method.'</span></div></div>';
            $input_token_purchase_num = '<div class="token-received"><div class="token-eq-sign">=</div><div class="token-received-amount"><h5 class="token-amount token-number-u">0</h5><div class="token-symbol">'.$symbol.'</div></div></div>';
            $input_pay_amount_num = '<div class="token-received token-received-alt"><div class="token-eq-sign">=</div><div class="token-received-amount"><h5 id="pay-amount-u" class="token-amount pay-amount-u">0</h5><div id="token-symbol" class="token-symbol pay-currency ucap">'.$method.'</div></div><div class="token-eq-sign">=</div><div class="token-received-amount"><input id="pay-amount-u-token" size="0" value="0" readonly class="token-amount pay-amount-u-token stealth" value="0"/><input name="pp_token" type="hidden"/><div id="token-symbol" class="token-symbol pay-currency ucap">'.$symbol.'</div></div></div>';
            $input_sep = '<div class="token-eq-sign"><em class="fas fa-exchange-alt"></em></div>';
            @endphp
            <span class="base-price" style="display: none;" id="base-price">{{ $stage->base_price }}</span>
            <div class="token-contribute">
                <div class="token-calc">{!! $input_token_purchase.$input_pay_amount_num !!}</div>

                <div class="token-calc-note note note-plane token-note">
                    <div class="note-box"></div>
                    <div class="note-text note-text-alert"></div>
                </div>
            </div>

            @if(!$sales_ended)
            <!-- <div class="token-overview-wrap">
                <div class="token-overview">
                    <div class="row">
                        <div class="col-md">
                            <div class="token-bonus token-bonus-sale">
                                <span class="token-overview-title">+ {{ __('Sale Bonus') . ' ' . (empty($active_bonus) ? 0 :  $active_bonus->amount) }}%</span>
                                <span class="token-overview-value bonus-on-sale tokens-bonuses-sale">0</span>
                            </div>
                        </div>
                        @if(!empty($bonus_amount) )
                        <div class="col-md">
                            <div class="token-bonus token-bonus-amount">
                                <span class="token-overview-title">+ {{__('Amount Bonus')}}</span>
                                <span class="token-overview-value bonus-on-amount tokens-bonuses-amount">0</span>
                            </div>
                        </div>
                        @endif
                        <div class="col-md">
                            <div class="token-total">
                                <span class="token-overview-title font-bold">{{__('Total') . ' '.$symbol }}</span>
                                <span class="token-overview-value token-total-amount text-primary payment-summary-amount tokens-total">0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="note note-plane note-danger note-sm pdt-1x pl-0">
                    <p>{{__('Your contribution will be calculated based on exchange rate at the moment when your transaction is confirmed.')}}</p>
                </div>
            </div> -->
            @endif

            <?php
                $currency = "eth";
                $default = [
                    'Manual' => array('type' =>'core', 'version' => '1.4.1'),
                    'Bank' => array('type' =>'core', 'version' => '1.3.1'),
                    'Paypal' => array('type' =>'core', 'version' => '1.3.1')
                ];
                $modules = get_setting('active_payment_modules', json_encode($default));
                $get_modules = json_decode(gws('active_payment_modules'), TRUE);
                $active_payment_methods = (!empty($get_modules) && is_array($get_modules)) ? array_keys($get_modules) : array_keys(json_decode($modules, TRUE));

                function getItemInstance($name, $active_payment_methods)
                {
                    $instance = null;
                    if (!in_array(ucfirst($name), $active_payment_methods)) {
                        return false;
                    }

                    try {
                        $name = ucfirst($name);
                        $path = 'App\PayModule'.'\\'.$name.'\\'.$name."Module";
                        if( class_exists($path) ){
                            $instance = new $path;
                        }
                    } catch (\Exception $e) {
                        $instance = false;
                        $message = $e->getMessage();
                        info($message);
                    }
                    return $instance;
                }

                $methods = $support = $activeMethods = [];

                foreach ($active_payment_methods as $item) {
                    $object = getItemInstance($item, $active_payment_methods);
                    $pm = get_pm(strtolower($item), true);
                    if(method_exists($object, 'show_action')){
                        $act = $object->show_action();
                        if(in_array(strtoupper($currency), $act['currency']) && $pm->status == 'active'){
                            $methods[] = $act['html'];
                        }
                    }
                }
            ?>

            @if(in_array("Stripe", $activeMethods))
            <script src="https://js.stripe.com/v3/"></script>
            @endif

            @php
            $pm_check = (!empty($methods) ? true : false);
            $dot_1 =  '.'; $dot_2 = '';
            if (!empty($active_bonus->amount)) {
                $dot_1 =  ''; $dot_2 = '.';
            }
            $activeMethods = data_get(get_defined_vars(), "activeMethods", []);
            @endphp

            @if($pm_check)
                <div class="card-head">
                    <h4 class="card-title">
                        {{ __('Step 3', ['symbol' => $symbol]) }}
                    </h4>
                </div>
                <div class="gaps-2x"></div>
                <ul class="pay-list guttar-12px">
                    @foreach($methods as $payment_method)
                        {{ $payment_method }}
                    @endforeach
                </ul>
                <p class="text-light font-italic mgb-1-5x"><small>* {{__('Payment gateway may charge you a processing fees.')}}</small></p>
                <div class="pdb-0-5x">
                    <div class="input-item text-left">
                        <input type="checkbox" data-msg-required="{{ __("You should accept our terms and policy.") }}" class="input-checkbox input-checkbox-md" id="agree-terms" name="agree" required>
                        <label for="agree-terms">{{ __('I hereby agree to the token purchase agreement and token sale term.') }}</label>
                    </div>
                </div>
            @else
                <div class="gaps-4x"></div>
                <div class="alert alert-danger text-center">{{ __('Sorry! There is no payment method available for this currency. Please choose another currency or contact our support team.') }}</div>
                <div class="gaps-5x"></div>
            @endif


            @if(is_payment_method_exist() && !$upcoming && ($stage->status != 'paused') && !$sales_ended)

            <div class="pay-buttons">
                <div class="pay-buttons pt-0">
                    <button
                        disabled
                        type="submit"
                        class="btn btn-primary token-payment btn-between payment-btn disabled offline_payment"
                    >{{__('Make Payment')}}&nbsp;<i class="ti ti-wallet"></i></button>
                </div>
                <div class="pay-notes">
                    <div class="note note-plane note-light note-md font-italic">
                        <em class="fas fa-info-circle"></em>
                        <p>{{__('Tokens will appear in your account after payment successfully made and approved by our team. Please note that, :SYMBOL token will be distributed after the token sales end-date.', ['symbol' => $symbol]) }}</p>
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-info alert-center">
                {{ ($sales_ended) ? __('Our token sales has been finished. Thank you very much for your contribution.') : __('Our sale will start soon. Please check back at a later date/time or feel free to contact us.') }}
            </div>
            @endif
            <input type="hidden" id="data_amount" value="0">
            <input type="hidden" id="data_currency" value="{{ $default_method }}">
        </form>
    </div> {{-- .card-innr --}}
</div> {{-- .content-area --}}
@push('sidebar')
<div class="aside sidebar-right col-lg-4">
    @if(!has_wallet() && gws('token_wallet_req')==1 && !empty(token_wallet()))
    <div class="d-none d-lg-block">
        {!! UserPanel::add_wallet_alert() !!}
    </div>
    @endif
    {!! UserPanel::user_balance_card($contribution, ['vers' => 'side']) !!}
    <div class="token-sales card">
        <div class="card-innr">
            <div class="card-head">
                <h5 class="card-title card-title-sm">{{__('Token Sales')}}</h5>
            </div>
            <div class="token-rate-wrap row">
                <div class="token-rate col-md-6 col-lg-12">
                    <span class="card-sub-title">{{ $symbol }} {{__('Token Price')}}</span>
                    <h4 class="font-mid text-dark">1 {{ $symbol }} = <span class="token-base-price-value">{{ to_num($token_prices->$bc, 'max', ',') }}</span> {{ base_currency(true) }}</h4>
                </div>
                <div class="token-rate col-md-6 col-lg-12">
                    <span class="card-sub-title">{{__('Exchange Rate')}}</span>
                    @php
                    $exrpm = collect($pm_currency);
                    $exrpm = $exrpm->forget(base_currency())->take(2);
                    $exc_rate = '<span>1 '.base_currency(true) .' ';
                    foreach ($exrpm as $cur => $name) {
                        if($cur != base_currency() && get_exc_rate($cur) != '') {
                            $exc_rate .= ' = '.to_num(get_exc_rate($cur), 'max', ',') . ' ' . strtoupper($cur);
                        }
                    }
                    $exc_rate .= '</span>';
                    @endphp
                    {!! $exc_rate !!}
                </div>
            </div>
            @if(!empty($active_bonus))
            <div class="token-bonus-current">
                <div class="fake-class">
                    <span class="card-sub-title">{{__('Current Bonus')}}</span>
                    <div class="h3 mb-0">{{ $active_bonus->amount }} %</div>
                </div>
                <div class="token-bonus-date">{{__('End at')}}<br>{{ _date($active_bonus->end_date, get_setting('site_date_format')) }}</div>
            </div>
            @endif
        </div>
    </div>
    <div class="token-price-graph card">
        <div class="card-innr">
            <div class="card-head has-aside">
                <div>
                    <h4 class="card-title card-title-sm">Token Price Graph</h4>
                    <div class="price-chart-price">
                        <div class="price-chart-label-container">
                            <p class="price" id="price">${{token_calc(1, 'price')->$base_currency}}</p><span class="currency">USD</span>
                        </div>
                        <span class="change positive" id="price-change">$0.00 (+0.00%)</span>
                    </div>
                </div>
            </div>
            <div class="chart-tokensale chart-tokensale-long">
                <canvas id="tknPrice"></canvas>
            </div>
            <div class="price-chart-buttons">
                <div class="chart-buttons">
                    <a href="{{ url()->current() }}?chart=1440">1 Day</a>
                    <a href="{{ url()->current() }}?chart=10080">1 Week</a>
                    <a class="active" href="{{ url()->current() }}?chart=43200">1 Month</a>
                </div>
            </div>
        </div>
    </div>
    @if(gws('user_sales_progress', 1)==1)
    {!! UserPanel::token_sales_progress('',  ['class' => 'mb-0']) !!}
    @endif
</div>{{-- .col.aside --}}
@endpush
@endsection
@section('modals')
<div class="modal fade modal-payment @if(\Session::has('modal')) show @endif" id="payment-modal" tabindex="-1" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-md modal-dialog-centered">
        <div class="modal-content">
            <a href="#" class="modal-close" data-dismiss="modal" aria-label="Close"><em class="ti ti-close"></em></a>
            @if (\Session::has('modal'))
                {!! \Session::get('modal') !!}
            @endif
        </div>
    </div>
</div>
@endsection
@push('footer')
<script>
    $(".modal-close").on("click", function () {
        $(".modal").removeClass("show")
    })
    var price_update_url = "{{ route('user.ajax.token.prices') }}";
    var is_dynamic = !!('{{ $stage->price_type }}' == 'dynamic');
    var available_tokens = {{ $stage->total_tokens - $stage->sales_token }};
    var liquidity = {{ $stage->liquidity }};
    var token_bonus_percent = {{ empty($active_bonus) ? 0 : $active_bonus->amount }};
    var price_impact_limit = {{ to_num($price_impact_limit, 'max', ',') }};
    var payment_button = $('.payment-btn');
    var min_token_amount = {{ $min_token }};
    var max_token_amount = {{ $stage->max_purchase }};

    var token_prices = {
        @foreach($pm_currency as $gt => $full)
        @if(token('purchase_'.$gt) == 1 || $method==$gt)
            {{$gt}}: @if($is_price_show==1 && isset($token_prices->$gt)){{ to_num($token_prices->$gt, 'max', '') }}@endif,
        @endif
        @endforeach
        <?php
            echo "base: " . to_num($token_prices->$bc, 'max', ',');
        ?>
    }


    function checkValidity () {
        var inputs = $("form select, form input").toArray();

        var valid = true;
        inputs.forEach(function (input) {
            let inputValid = input.checkValidity();
            if (!inputValid) valid = false;
        })
        return valid;
    }

    function fetchPriceData() {
        $.get(price_update_url).done((result) => {
            if (!result) return;
            if (result.stage) {
                base_price = result.stage.base_price;
                is_dynamic = result.stage.price_type == 'dynamic';
                liquidity = result.stage.liquidity;
                available_tokens = result.stage.total_tokens - result.stage.sales_token;
            }
            if (result.active_bonus && result.active_bonus.amount) {
                token_bonus_percent = result.active_bonus.amount;
            }
            if (result.price_impact_limit) {
                price_impact_limit = parseInt(result.price_impact_limit);
            }
            if (result && result.token_prices) token_prices = result.token_prices;

            var price_elements = $('.token-base-price-value');
            price_elements.each((index) => {
                var element = price_elements[index];
                element.innerText = result.stage.base_price;
            });
        });
    }
    fetchPriceData();

    setInterval((e) => {
        fetchPriceData(e);
        updatePriceText(e);
    }, 5000);

    var loading = false;
    var buy_button_disabled = false;
    var buying = false;

    function updatePriceText(event) {
        if (!loading) $(".page-overlay").hide();
        if (event) event.preventDefault();

        buy_button_disabled = false;// Set the target currency symbol, ETH for example
        // This value will represent how many tokens the user will actually get, will be stored in the transaction
        var tnx_tokens = 0;
        // This value represents the base amount of tokens (same as tnx_tokens, but without liqudity fee and bonus)
        var base_tokens = 0;
        var pay_currency_amount = 0;
        var current_token = $("select[name=pp_currency]")[0].value;

        // Get the actual input of how much base currency (USD for example) they want to buy
        // This value represents how much base currency the purchase is worth (in USD for example)
        var tnx_contribute = Number.parseFloat($("input#token-number")[0].value);

        if (!Number.isNaN(tnx_contribute)) {
            // Represents the number of tokens the user is buying.
            // If base price is 10 USD/Token, and base_input_num = 100 USD, then this value would be 10
            base_tokens = tnx_contribute / token_prices.base;
            // if selected currency is ETH and base currency is USD, this value represents X ETH for 1 USD
            pay_currency_amount = token_prices[current_token.toLowerCase()] * base_tokens;
            // This value will represent how many tokens the user will actually get, will be stored in the transaction
            tnx_tokens = base_tokens;

            if (tnx_tokens > available_tokens) {
                token_alert($('.token-note'), max_tokens_limit_msg, "text");
                buy_button_disabled = true;
            } else if (tnx_tokens < min_token_amount) {
                token_alert($('.token-note'), min_token_msg, "text");
                buy_button_disabled = true;
            } else {
                token_alert($('.token-note'), '', "text");
            }

            // This adds in the bonus amount, based on our current stage settings
            if (tnx_tokens && token_bonus_percent) {
                tnx_tokens += (token_bonus_percent / 100) * tnx_tokens;
            }

            if (is_dynamic) { // When price is dynamic, we need to incorporate the liquidity fee
                var constant_product = tnx_tokens * liquidity;
                var new_liquidity = (tnx_contribute + liquidity);
                tnx_tokens = constant_product / new_liquidity;
            }
        } else {
            buy_button_disabled = true;
        }

        // Sets the amount of tokens the user will receive, including liqudity fee and bonus tokens
        var tokens_to_receive = $("#pay-amount-u-token")[0];
        tokens_to_receive.value = tnx_tokens.toFixed(6);
        tokens_to_receive.style.width = `${tnx_tokens.toFixed(6).length}ch`;

        // Set the amount of currency you need to pay (ETH for example)
        var pay_amount_you_element = $("#pay-amount-u")[0];
        pay_amount_you_element.innerText = (pay_currency_amount).toFixed(6);

        // Sets the token symbol based on the pay currency you're using (ETH for example)
        var token_symbol_element = $("#token-symbol")[0];
        token_symbol_element.innerText = current_token;

        // Sets the base amount of tokens the user is buying,
        // IE the number of tokens they are getting before bonuses and liqudity fees.
        // This is used to recalculate the correct values in the backend for obv reasons
        var base_token_element = $('input[name=pp_token]')[0]; // This is hidden, used for form purposes only
        base_token_element.value = base_tokens.toFixed(6);

        if (buy_button_disabled || buying) {
            payment_button.addClass("disabled").attr("disabled", true);
        } else {
            payment_button.removeClass("disabled").removeAttr("disabled");
        }
    }

    updatePriceText();
    $("select[name=pp_currency]").on("change", updatePriceText);
    $("input#token-number").on("input", updatePriceText);
    $("input#token-number").on("change", updatePriceText);

    var valid = checkValidity();
    if (!valid) {
        payment_button.attr("disabled", true).addClass("disabled");
    }

    const onChangeClass = () => {
        if (loading) {
            payment_button.addClass("disabled").attr("disabled", true);
            return $(".page-overlay").addClass("is-loading").show();
        }
        $("page-overlay").removeClass("is-loading").hide();
        if (!buy_button_disabled && !buying) payment_button.removeClass("disabled").removeAttr("disabled");
    }
    $(".page-overlay").on("classChange", onChangeClass);
    $(".page-overlay").on("change", onChangeClass);

    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutationRecord) {
            onChangeClass();
        });
    });
    observer.observe($(".page-overlay")[0], { attributes : true });

    $("form").submit(() => {
        if (!checkValidity()) return;
        loading = true;
        buying = true;
        onChangeClass();
    });

    let elementId = "token-dollar-visible";
    let hiddenElementId = "token-number";
    function func() {
        setTimeout(function () {
            if (!document.getElementById(elementId)) return func()

            const isPartialPositiveNumber = (str) => {
                return /^(\d+\.?)?(\d+)?$/.test(str)
            }

            const parsePartialPositiveNumber = (str) => {
                if (!isPartialPositiveNumber(str)) return null
                if (str.charAt(str.length - 1) === ".") str = str.substring(0, str.length - 1)
                return Number.parseFloat(str)
            }

            var dollarInputHidden = document.getElementById(hiddenElementId)
            var dollarInput = document.getElementById(elementId)

            var prevValue = dollarInputHidden.value
            if (!isPartialPositiveNumber(prevValue)) {
                prevValue = "0"
                dollarInput.value = `$${prevValue}`
                dollarInputHidden.value = prevValue
            }
            function onChange(e) {
                let val = dollarInput.value
                dollarInput.value = `${val}`
                if (val.startsWith("$")) val = val.substring(1)
                if (val.startsWith("0")) val = ""
                if (val === "") {
                    dollarInput.value = ""
                    dollarInputHidden.value = null
                    return
                }
                if (!isPartialPositiveNumber(val)) {
                    dollarInput.value = `$${prevValue}`
                    return
                }
                let parsedNum = parsePartialPositiveNumber(val)
                dollarInputHidden.value = parsedNum
                dollarInput.value = `$${val}`
                prevValue = val
                triggerRecalc()
                updatePriceText()
            }

            function triggerRecalc() {
                updatePriceText()
                if (window.token_calc) window.token_calc(document.querySelector(".token-calc"))
            }
            function recalcFunc() {
                setTimeout(() => {
                    if (!window.token_calc) return recalcFunc()
                    triggerRecalc()
                }, 100);
            }
            recalcFunc()

            onChange()
            dollarInput.addEventListener("input", onChange)
            dollarInput.addEventListener("change", onChange)
        }, 100)
    }

    func()

    // Token price graph stuff

    var tnx_labels = {!! json_encode($transactions['chart']['time_alt']) !!};
    var tnx_data = {!! json_encode($transactions['chart']['price_alt']) !!};
    var theme_color = {
        base:"<?=theme_color('base')?>",
        text: "<?=theme_color('text')?>",
        heading: "<?=theme_color('heading')?>"
    };

    var minDecimalPlaces = 2
    var maxDecimalPrecision = 4

    function removeTrailingZeros(numStr, minDecimals = minDecimalPlaces) {
        numStr = numStr.toString()
        var numSplit = numStr.split(".")
        if (numSplit.length >= 2) return numStr
        var integerStr = numSplit[0]
        var trailingZerosMatch = integerStr.match(/0*$/)
        var trailingZerosStr = trailingZerosMatch[0]
        var precisionDecimalStr = integerStr.substring(0, trailingZerosMatch.index)
        trailingZerosStr = trailingZerosStr.substring(0, minDecimals - (integerStr.length - trailingZerosStr.length))
        return `${precisionDecimalStr}${trailingZerosStr}`
    }

    function formatNumber(num, minDecimals = minDecimalPlaces, maxPrecision = maxDecimalPrecision) {
        var numStr = Number.parseFloat(num).toString()
        var decimals = 0
        if (numStr.includes(".")) {
            decimals = numStr.split(".")[1].length
        }
        if (decimals < minDecimals) {
            if (decimals === 0) numStr = numStr + "."
            for (var i = decimals; i < minDecimals; i++) {
                numStr = numStr + "0"
            }
        }
        if (decimals > 0) {
            var numSplit = numStr.split(".")
            var integerStr = numSplit[0]
            var decimalStr = numSplit[1]
            var nonZeroDecimals = decimalStr.match(/[1-9][0-9]*/)[0]
            var nonZeroDecimalCount = nonZeroDecimals.length
            var zeroDecimals = decimalStr.match(/0*/)[0]
            var decimals = zeroDecimals + nonZeroDecimals
            if (integerStr !== "0" && decimals > maxPrecision) {
                decimals = decimals.substring(0, maxPrecision)
                numStr = `${integerStr}.${removeTrailingZeros(decimals, minDecimals)}`
            } else if (nonZeroDecimalCount > maxPrecision) {
                nonZeroDecimals = nonZeroDecimals.substring(0, maxPrecision)
                numStr = `${integerStr}.${zeroDecimals}${removeTrailingZeros(nonZeroDecimals, minDecimals)}`
            }
        }

        return numStr
    }

    var tnx_labels = {!! json_encode($transactions['chart']['time_alt']) !!};
    var tnx_data = {!! json_encode($transactions['chart']['price_alt']) !!};
    var theme_color = {
        base:"<?=theme_color('base')?>",
        text: "<?=theme_color('text')?>",
        heading: "<?=theme_color('heading')?>"
    };

    if ($("#tknPrice")) {
        document.getElementById("price").innerHTML = `$${formatNumber({{token_calc(1, 'price')->$base_currency}})}`

        var canvas = document.getElementById("tknPrice")
        var chart_element = canvas.getContext("2d");
        var priceChange = document.getElementById("price-change")

        function updateChangeText(prices) {
            var first = Number.parseFloat(prices[0])
            var last = Number.parseFloat(prices[prices.length - 1])
            var changeAbsolute = last - first
            var changeRelative = changeAbsolute * 100 / first
            var sign = changeAbsolute >= 0 ? "+" : "-"
            priceChange.innerHTML = `$${formatNumber(Math.abs(changeAbsolute))} (${sign}${formatNumber(Math.abs(changeRelative), 2, 2)}%)`
            priceChange.classList.remove("negative")
            priceChange.classList.remove("positive")
            if (changeAbsolute >= 0) {
                priceChange.classList.add("positive")
            } else {
                priceChange.classList.add("negative")
            }
        }

        var transparency = "44"
        function getBackgroundGradient() {
            var height = canvas.getBoundingClientRect().height
            var backgroundGradient = chart_element.createLinearGradient(0, 0, 0, height - 30)
            backgroundGradient.addColorStop(0, `${theme_color.base}${transparency}`)
            backgroundGradient.addColorStop(1, `transparent`)
            return backgroundGradient
        }
        updateChangeText(tnx_data)
        var chart = new Chart(chart_element, {
            type: "line",
            data: {
                labels: tnx_labels.map((date) => new Date(date).toLocaleDateString()),
                datasets: [
                    {
                        label: "",
                        tension: 0,
                        backgroundColor: getBackgroundGradient(),
					    borderWidth: 3,
                        borderColor: theme_color.base,
                        pointRadius: 0,
                        pointHitRadius: 10,
                        data: tnx_data,
                    },
                ],
            },
            options: {
                legend: { display: false },
                maintainAspectRatio: false,
                tooltips: {
                    callbacks: {
                        title: function (a, b) {
                            return "Date : " + b.labels[a[0].index];
                        },
                        label: function (a, b) {
							return `$${formatNumber( b.datasets[0].data[a.index])}`
						}
                    },
                    backgroundColor: "#f2f4f7",
                    titleFontSize: 13,
                    titleFontColor: theme_color.heading,
                    titleMarginBottom: 10,
                    bodyFontColor: theme_color.text,
                    bodyFontSize: 14,
                    bodySpacing: 4,
                    yPadding: 15,
                    xPadding: 15,
                    footerMarginTop: 5,
                    displayColors: false,
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            fontSize: 12,
                            fontColor: theme_color.text,
                            callback: (value, i, ticks) => {
                                return [0, ticks.length - 1].includes(i) ? `$${formatNumber(value)}` : undefined
                            }
                        },
                        gridLines: {
						    drawOnChartArea: false,
                            color: "#e9edf3",
                            tickMarkLength: 0,
                            zeroLineColor: "#e9edf3"
                        }
                    }],
                    xAxes: [{
                        ticks: {
                            display: false,
                            autoSkip: true,
                            maxRotation: 0,
                            minRotation: 0,
                            fontSize: 12,
                            fontColor: theme_color.text,
                            source: "auto",
                            callback: (value, i, ticks, data) => {
                                return [0, ticks.length - 1].includes(i) ? value : undefined
                            }
                        },
                        gridLines: {
						    drawOnChartArea: false,
                            color: "transparent",
                            tickMarkLength: 20,
                            zeroLineColor: "#e9edf3"
                        }
                    }],
                }
            }
        });

        function resizeChart() {
            chart.data.datasets[0].backgroundColor = getBackgroundGradient()
            chart.update()
        }

        window.addEventListener("resize", resizeChart)
        setTimeout(resizeChart, 500)

        $(".token-price-graph li a, .chart-buttons a").on("click", function (event) {
            $(".chart-buttons a").removeClass("active")
            $(this).addClass("active")

            event.preventDefault();
            var d = $(this),
                e = $(this).attr("href");
            $.get(e).done((a) => {
                chart.data.labels = Object.values(a.chart.time_alt);
                chart.data.datasets[0].data = Object.values(a.chart.price_alt);
                chart.update();
                d.parents(".token-price-graph").find("a.toggle-tigger").text(d.text());
                d.closest(".toggle-class").toggleClass("active");
                updateChangeText(a.chart.price_alt)
            });
        });
    };
</script>
<script defer type="text/javascript">
    var count = 0
    const triggerFunc = () => {
        setTimeout(() => {
            count++
            $(".token-number").trigger("change")
            if (count !== 10) triggerFunc()
        }, 300)
    }
    triggerFunc()
</script>
@endpush

@if(in_array("Stripe", $activeMethods))
<script src="https://js.stripe.com/v3/"></script>
@endif
