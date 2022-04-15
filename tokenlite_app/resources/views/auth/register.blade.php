@extends('layouts.auth')
@section('title', __('Sign up'))
@section('content')

@php
use App\Helpers\IcoHandler;
use App\Models\PaymentMethod;
$check_users = \App\Models\User::count();
$pm_currency = PaymentMethod::Currency
@endphp
@if( recaptcha() )
@push('header')
<script>
    grecaptcha.ready(function () { grecaptcha.execute('{{ recaptcha('site') }}', { action: 'register' }).then(function (token) { if(token) { document.getElementById('recaptcha').value = token; } }); });
</script>
@endpush
@endif
<div class="page-ath-form">
    <h2 class="page-ath-heading">{{__('Sign up')}} <small>The wait is over. Presale now live!</small></h2>
    <form class="register-form" method="POST" action="{{ route('register') }}" id="register">
        @csrf
        @include('layouts.messages')
        @if(! is_maintenance() && application_installed(true) && ($check_users == 0) )
        <div class="alert alert-info-alt">
            Please register first your Super Admin account with adminstration privilege.
        </div>
        @endif
        <div class="input-item">
            <input type="text" placeholder="{{__('Your Name')}}" class="input-bordered{{ $errors->has('name') ? ' input-error' : '' }}" name="name" value="{{ old('name') }}" minlength="3" data-msg-required="{{ __('Required.') }}" data-msg-minlength="{{ __('At least :num chars.', ['num' => 3]) }}" required>
        </div>
        <div class="input-item">
            <input type="email" placeholder="{{__('Your Email')}}" class="input-bordered{{ $errors->has('email') ? ' input-error' : '' }}" name="email" value="{{ old('email') }}" data-msg-required="{{ __('Required.') }}" data-msg-email="{{ __('Enter valid email.') }}" required>
        </div>
        <div class="input-item">
            <input type="tel" placeholder="{{__('Phone Number (optional)')}}" class="input-bordered{{ $errors->has('mobile') ? ' input-error' : '' }}" name="mobile-input" id="mobile-input" value="{{ old('mobile') }}" data-msg-required="{{ __('Required.') }}">
        </div>
        <div class="input-item">
            <div class="input-wrap">
                <select class="select-bordered select-block" name="nationality" id="nationality" required="required" data-dd-class="search-on">
                    <option value="" selected>{{__('Nationality')}}</option>
                    @foreach($countries as $country)
                    <option value="{{ $country }}">{{ $country }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="input-item">
            <input type="password" placeholder="{{__('Password')}}" class="input-bordered{{ $errors->has('password') ? ' input-error' : '' }}" name="password" id="password" minlength="6" data-msg-required="{{ __('Required.') }}" data-msg-minlength="{{ __('At least :num chars.', ['num' => 6]) }}" required>
        </div>
        <div class="input-item">
            <input type="password" placeholder="{{__('Repeat Password')}}" class="input-bordered{{ $errors->has('password_confirmation') ? ' input-error' : '' }}" name="password_confirmation" data-rule-equalTo="#password" minlength="6" data-msg-required="{{ __('Required.') }}" data-msg-equalTo="{{ __('Enter the same value.') }}" data-msg-minlength="{{ __('At least :num chars.', ['num' => 6]) }}" required>
        </div>
        <div>
            <h2 class="page-ath-heading"><small>Purchase Details</small></h2>
        </div>
        <div class="input-item">
			<select name="crypto-select" class="select select-block select-bordered active_method pay-method" name="pp_currency">
				@foreach($pm_currency as $gt => $full)
				@if(IcoHandler::get_token_settings('purchase_'.$gt) == 1)
				<option data-label="{{$full}} ({{strtoupper($gt)}})">{{strtoupper($gt)}}</option>
				@endif
				@endforeach
			</select>
		</div>
        <div class="input-item">
            <input
				type="text"
				min="1"
				placeholder="{{__('Purchase Amount $')}}"
				class="input-bordered{{ $errors->has('token-buy') ? ' input-error' : '' }}"
				id="token-buy-input"
				value="${{ old('token-buy') }}"
				data-msg-required="{{ __('Required.') }}"
				required
			>
            <input
				type="hidden"
				id="token-buy-input-hidden"
				name="token-buy"
				value="{{ old('token-buy') }}"
				data-msg-required="{{ __('Required.') }}"
				required
			>
        </div>
        @if( gws('referral_info_show')==1 && get_refer_id() )
        <div class="input-item">
            <input type="text" class="input-bordered" value="{{ __('Your were invited by :userid', ['userid' => get_refer_id(true)]) }}" disabled readonly>
        </div>
        @endif
        
        @if(( application_installed(true)) && ($check_users > 0))
            @if(get_page_link('terms') || get_page_link('policy'))
            <div class="input-item text-left">
                <input name="terms" class="input-checkbox input-checkbox-md" id="agree" type="checkbox" required="required" data-msg-required="{{ __("You should accept our terms and policy.") }}">
                <label for="agree">{!! __('I agree to the') . ' ' .get_page_link('terms', ['target'=>'_blank', 'name' => true, 'status' => true]) . ((get_page_link('terms', ['status' => true]) && get_page_link('policy', ['status' => true])) ? ' '.__('and').' ' : '') . get_page_link('policy', ['target'=>'_blank', 'name' => true, 'status' => true]) !!}.</label>
            </div>
            @else
            <div class="input-item text-left">
                <label for="agree">{{__('By registering you agree to the terms and conditions.')}}</label>
            </div>
            @endif
        @else
            <input name="terms" value="1" type="hidden">
        @endif
        @if( recaptcha() )
        <input type="hidden" name="recaptcha" id="recaptcha">
        @endif
        <button type="submit" class="btn btn-primary btn-block">{{ ( application_installed(true) && ($check_users == 0) ) ? __('Complete Installation') : __('Create Account') }}</button>
    </form>

    @if(application_installed(true) && ($check_users > 0) && Schema::hasTable('settings'))
        @if (
        (get_setting('site_api_fb_id', env('FB_CLIENT_ID', '')) != '' && get_setting('site_api_fb_secret', env('FB_CLIENT_SECRET', '')) != '') ||
        (get_setting('site_api_google_id', env('GOOGLE_CLIENT_ID', '')) != '' && get_setting('site_api_google_secret', env('GOOGLE_CLIENT_SECRET', '')) != '')
        )
        <div class="sap-text"><span>{{__('Or Sign up with')}}</span></div>
        <ul class="row guttar-20px guttar-vr-20px">
            <li class="col"><a href="{{ route('social.login', 'facebook') }}" class="btn btn-outline btn-dark btn-facebook btn-block"><em class="fab fa-facebook-f"></em><span>{{__('Facebook')}}</span></a></li>
            <li class="col"><a href="{{ route('social.login', 'google') }}" class="btn btn-outline btn-dark btn-google btn-block"><em class="fab fa-google"></em><span>{{__('Google')}}</span></a></li>
        </ul>
        @endif

        <div class="gaps-4x"></div>
        <div class="form-note">
            {{__('Already have an account ?')}} <a href="{{ route('login') }}"> <strong>{{__('Sign in instead')}}</strong></a>
        </div>
    @endif
</div>
@endsection
@push('footer')
<script type="text/javascript">
    const phoneInputField = document.querySelector("#mobile-input");
    const phoneInput = window.intlTelInput(phoneInputField, {
        utilsScript: "{{ asset('assets/js/intl-tel-input-utils.js').css_js_ver() }}",
        initialCountry: "auto",
        geoIpLookup: function(success, failure) {
            $.get("https://ipinfo.io", function() {}, "jsonp").always(function(resp) {
                var countryCode = (resp && resp.country) ? resp.country : "us";
                success(countryCode);
            });
        },
        hiddenInput: "mobile",
        separateDialCode: true
    });

	const isPartialPositiveNumber = (str) => {
		return /^(\d+\.?)?(\d+)?$/.test(str)
	}

	const parsePartialPositiveNumber = (str) => {
		if (!isPartialPositiveNumber(str)) return null
		if (str.charAt(str.length - 1) === ".") str = str.substring(0, str.length - 1)
		return Number.parseFloat(str)
	}

	var dollarInputHidden = document.getElementById("token-buy-input-hidden")
	var dollarInput = document.getElementById("token-buy-input")

	let prevValue = dollarInputHidden.value
	if (!isPartialPositiveNumber(prevValue)) {
		prevValue = "0"
		dollarInput.value = prevValue
	}
	function onChange(e) {
		e.preventDefault()
		e.stopPropagation()
		let val = dollarInput.value
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
	}

	dollarInput.addEventListener("input", onChange)
	dollarInput.addEventListener("change", onChange)
</script>
@endpush