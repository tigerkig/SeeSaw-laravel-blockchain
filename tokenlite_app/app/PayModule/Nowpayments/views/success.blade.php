<div class="popup-body">
    <h4 class="popup-title">{{ __('Confirmation Your Payment') }}</h4>
    <div class="popup-content">
        <form action="{{ route('payment.bank.update') }}" method="POST" id="payment-confirm" class="validate-modern" autocomplete="off">
            @csrf
            @php 
            $cur = strtolower($transaction->currency);
            $_CUR = strtoupper($cur);
            $_gateway = ucfirst($transaction->payment_method);
            @endphp
            <input type="hidden" name="trnx_id" value="{{ $transaction->id }}">
            <p class="lead-lg text-primary">{!! __('Your Order no. :orderid has been placed successfully.', ['orderid' => '<strong>'.$transaction->tnx_id.'</strong>' ]) !!}</p>

            <p>{!! __('Please send :amount :currency to the address below.', ['amount' => '<strong class="text-primary">'.$transaction->amount.'</strong>', 'currency' => '<strong class="text-primary">'.$_CUR.'</strong>'])  !!} {!! __('The token balance will appear in your account only after your transaction gets approved.', [ 'gateway' => '<strong>'.$_gateway.'</strong>' ]) !!}</p>
            
            <div class="pay-wallet-address pay-wallet-{{ $cur }}">
                <h6 class="text-head font-bold">{{ __('Make your payment to the below address') }}</h6>
                <p>
                <div class="row guttar-1px guttar-vr-15px">
                    @if(isset($data['pay_address']))
                    <div class="col-sm-12">
                        <input id="pay-address" type="hidden" value="{{ $data['pay_address'] }}"/>
                        <div id="qr-code" alt="QR"></div>
                    </div>
                    @endif
                    @if(isset($data['payin_extra_id']))
                    <div class="col-sm-12">
                        <div class="font-bold">Memo</div>
                        <div class="copy-wrap mgb-0-5x">
                            <span class="copy-feedback"></span>
                            <em class="copy-icon ikon ikon-sign-{{ $cur }}"></em>
                            <input type="text" class="copy-address" value="{{ $data['payin_extra_id'] }}" disabled="">
                            <button type="button" class="copy-trigger copy-clipboard" data-clipboard-text="{{ $data['payin_extra_id'] }}"><em class="ti ti-files"></em></button>
                        </div>
                        <div class="gaps-2x"></div>
                    </div>
                    @endif
                    @if(isset($data['pay_address']))
                    <div class="col-sm-12">
                        <div class="font-bold">Address</div>
                        <div class="copy-wrap mgb-0-5x">
                            <span class="copy-feedback"></span>
                            <em class="copy-icon ikon ikon-sign-{{ $cur }}"></em>
                            <input type="text" class="copy-address" value="{{ $data['pay_address'] }}" disabled="">
                            <button type="button" class="copy-trigger copy-clipboard" data-clipboard-text="{{ $data['pay_address'] }}"><em class="ti ti-files"></em></button>
                        </div>
                    </div>
                    @endif
                    @if(isset($transaction))
                    <div class="col-sm-12">
                        <div class="font-bold">Amount</div>
                        <div class="copy-wrap mgb-0-5x">
                            <span class="copy-feedback"></span>
                            <em class="copy-icon ikon ikon-sign-{{ $cur }}"></em>
                            <input type="text" class="copy-address" value="{{ $transaction->amount }}" disabled="">
                            <button type="button" class="copy-trigger copy-clipboard" data-clipboard-text="{{ $transaction->amount }}"><em class="ti ti-files"></em></button>
                        <div class="gaps-2x"></div>
                        </div>
                    </div>
                    @endif
                    <div class="col-sm-12">
                        <ul class="d-flex flex-wrap align-items-center guttar-20px guttar-vr-15px">
                            <li><a href="{{ route('user.transactions') }}" class="btn btn-primary">{{ __('View Transaction') }}</a></li> 
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<script defer type="text/javascript">
	console.log("HERE 1")
	document.addEventListener("DOMContentLoaded", function() {
	console.log("HERE 2")
        var address = $('#pay-address').val();
		console.log("Address", address)
        $('#qr-code').qrcode({
            render: 'div',
            minVersion: 10,
            ecLevel: 'L',
            left: 0,
            top: 0,
            size: 200,
            fill: '#000',
            text: address,
            radius: 0,
            quiet: 0,
            mode: 0,
            mSize: 0.1,
            mPosX: 0.5,
            mPosY: 0.5,
            label: '',
            fontname: 'sans',
            fontcolor: '#000',
            image: null
        });
        var $_p_form = $('form#payment-confirm');
        if ($_p_form.length > 0) { purchase_form_submit($_p_form); }
        var clipboardModal = new ClipboardJS('.copy-trigger', { container: document.querySelector('.modal') });
        clipboardModal.on('success', function(e) { feedback(e.trigger, 'success'); e.clearSelection(); }).on('error', function(e) { feedback(e.trigger, 'fail'); });
    });
</script>