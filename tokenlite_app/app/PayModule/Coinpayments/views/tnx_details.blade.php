@php
$data = json_decode($transaction->extra);
$cur = strtolower($transaction->currency);
$_CUR = strtoupper($cur);
$_gateway = ucfirst($transaction->payment_method);
@endphp
<div class="modal fade" id="transaction-details" tabindex="-1">
    <div class="modal-dialog modal-dialog-md modal-dialog-centered">
        <div class="modal-content">
            @if($transaction)
            <a href="#" class="modal-close" data-dismiss="modal" aria-label="Close"><em class="ti ti-close"></em></a>
            @endif
            <div class="popup-body">
            @if($transaction)
                @if($transaction->status=='pending' || $transaction->status == 'onhold')
                    <h4 class="popup-title">{{__('Confirmation Your Payment')}}</h4>
                    <div class="content-area popup-content">
                        <form action="{{ route('payment.bank.update') }}" method="POST" id="payment-confirm" class="validate" autocomplete="off">
                            @csrf
                            <input type="hidden" name="trnx_id" value="{{ $transaction->id }}">
                            <p class="lead-lg text-primary">{!! __('Your Order no. :orderid has been placed & waiting for payment.', ['orderid' => '<strong>'.$transaction->tnx_id.'</strong>' ]) !!}</p>
                            <p class="lead">{!! __('To receive :token :symbol token, please make your payment of :amount :currency through :gateway. The token balance will appear in your account once we received your payment.', ['amount' => '<strong class="text-primary">'.$transaction->amount.'</strong>', 'currency' => '<strong class="text-primary">'.$_CUR.'</strong>', 'token' => '<strong><span class="token-total">'.$transaction->total_tokens.'<span></strong>', 'symbol' => '<strong class="text-primary">'.token('symbol').'</strong>', 'gateway' => '<strong>'.ucfirst($_gateway.'</strong>')]) !!}</p>
                            <div class="gaps-0-5x"></div>
                            <div class="pay-wallet-address pay-wallet-{{ $cur }}">
                                <h6 class="text-head font-bold">{{ __('Make your payment to the below address') }}</h6>
                                <div class="row guttar-1px guttar-vr-15px">
                                    @if(isset($data->result->qrcode_url))
                                    <div class="col-sm-3">
                                        <p class="text-center text-sm-left"><img title="{{ __('Scan QR code to payment') }}" class="img-thumbnail" width="120" src="{{ $data->result->qrcode_url }}" alt="QR"></p>
                                    </div>
                                    @endif
                                    <div class="col-sm-9">
                                        <div class="fake-class pl-sm-3">
                                            @if(isset($data->result->address))
                                            <div class="copy-wrap mgb-0-5x">
                                                <span class="copy-feedback"></span>
                                                <em class="copy-icon ikon ikon-sign-{{ $cur }}"></em>
                                                <input type="text" class="copy-address ignore" value="{{ $data->result->address }}" disabled="" readonly="">
                                                <button type="button" class="copy-trigger copy-clipboard" data-clipboard-text="{{ $data->result->address }}"><em class="ti ti-files"></em></button>
                                            </div>
                                            <div class="gaps-2x"></div>
                                            @endif
                                            <ul class="d-flex flex-wrap align-items-center guttar-30px">
                                                @if(isset($data->result->status_url))
                                                <li><a href="{{ $data->result->status_url }}" target="_blank" class="btn btn-primary">{{ __('Check Status') }}</a></li>
                                                @endif
                                                <li class="pdt-1x pdb-1x"><button type="submit" name="action" value="cancel" class="btn btn-cancel btn-danger-alt payment-cancel-btn payment-btn btn-simple">{{__('Cancel Order')}}</button></li>
                                            </ul>
                                            <p class="mt-2"><a class="link" href="{{ route('payment.coinpayments.success', ['tnx_id' => $transaction->id]) }}">{{ __('Click here if you already paid') }}</a></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="content-area popup-content">
                        @include('layouts.token-details', ['transaction' => $transaction, 'details' => true])
                    </div>
                @endif
            @else
            <div class="content-area popup-content text-center">
                <div class="status status-error">
                    <em class="ti ti-alert"></em>
                </div>
                <h3>{{__('Oops!!!')}}</h3>
                <p>{!! __('Sorry, seems there is an issues occurred and we couldn’t process your request. Please contact us with your order no. :orderid, if you continue to having the issues.', ['orderid' => '<strong>'.$transaction->tnx_id.'</strong>']) !!}</p>
                <div class="gaps-2x"></div>
                <a href="#" data-dismiss="modal" data-toggle="modal" class="btn btn-light-alt">{{__('Close')}}</a>
                <div class="gaps-3x"></div>
            </div>
            @endif

            </div>
        </div>
    </div>
    <script type="text/javascript">
        (function($) {
            var $_p_form = $('form#payment-confirm');
            if ($_p_form.length > 0) { purchase_form_submit($_p_form); }
        })(jQuery);
    </script>
</div>