<div class="popup-body">
    <h4 class="popup-title">{{ __('Confirmation Your Payment') }}</h4>
    <div class="popup-content">
        <form action="{{ route('payment.bank.update') }}" method="POST" id="payment-confirm" class="validate-modern" autocomplete="off">
            @csrf
            @php 
            $num = isset($coinpay['result']['confirms_needed']) && !empty($coinpay['result']['confirms_needed']) ? $coinpay['result']['confirms_needed'] : 2;
            $cur = strtolower($transaction->currency);
            $_CUR = strtoupper($cur);
            $_gateway = ucfirst($transaction->payment_method);
            @endphp
            <input type="hidden" name="trnx_id" value="{{ $transaction->id }}">
            <p class="lead-lg text-primary">{!! __('Your Order no. :orderid has been placed successfully.', ['orderid' => '<strong>'.$transaction->tnx_id.'</strong>' ]) !!}</p>

            <p>{!! __('Please send :amount :currency to the address below.', ['amount' => '<strong class="text-primary">'.$transaction->amount.'</strong>', 'currency' => '<strong class="text-primary">'.$_CUR.'</strong>'])  !!} {!! __('The token balance will appear in your account only after you transaction gets :num confirmations and approved by :gateway.', [ 'num' => '<strong>'.$num.'</strong>', 'gateway' => '<strong>'.$_gateway.'</strong>' ]) !!}</p>
            
            <div class="pay-wallet-address pay-wallet-{{ $cur }}">
                <h6 class="text-head font-bold">{{ __('Make your payment to the below address') }}</h6>
                <p>
                <div class="row guttar-1px guttar-vr-15px">
                    @if(isset($coinpay['result']['qrcode_url']))
                    <div class="col-sm-3">
                        <p class="text-center"><img title="{{ __('Scan QR code to payment') }}" class="img-thumbnail" width="120" src="{{ $coinpay['result']['qrcode_url'] }}" alt="QR"></p>
                    </div>
                    @endif
                    <div class="col-sm-9">
                        <div class="fake-class pl-sm-3">
                            @if(isset($coinpay['result']['address']))
                            <div class="copy-wrap mgb-0-5x">
                                <span class="copy-feedback"></span>
                                <em class="copy-icon ikon ikon-sign-{{ $cur }}"></em>
                                <input type="text" class="copy-address" value="{{ $coinpay['result']['address'] }}" disabled="">
                                <button type="button" class="copy-trigger copy-clipboard" data-clipboard-text="{{ $coinpay['result']['address'] }}"><em class="ti ti-files"></em></button>
                            </div>
                            <div class="gaps-2x"></div>
                            @endif
                            <ul class="d-flex flex-wrap align-items-center guttar-20px guttar-vr-15px">
                                <li><a href="{{ route('user.transactions') }}" class="btn btn-primary">{{ __('View Transaction') }}</a></li> 
                                <li><button type="submit" name="action" value="cancel" class="btn btn-cancel btn-danger-alt payment-cancel-btn payment-btn btn-simple">{{ __('Cancel Order') }}</button></li>
                            </ul>
                            @if(isset($coinpay['result']['status_url']))
                            <p class="mt-2"><a class="link" target="_blank" href="{{ $coinpay['result']['status_url'] }}">{{ __('Check status on :gateway', ['gateway' => $_gateway]) }}</a></p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<script type="text/javascript">
    (function($) {
        var $_p_form = $('form#payment-confirm');
        if ($_p_form.length > 0) { purchase_form_submit($_p_form); }
        var clipboardModal = new ClipboardJS('.copy-trigger', { container: document.querySelector('.modal') });
        clipboardModal.on('success', function(e) { feedback(e.trigger, 'success'); e.clearSelection(); }).on('error', function(e) { feedback(e.trigger, 'fail'); });
    })(jQuery);
</script>