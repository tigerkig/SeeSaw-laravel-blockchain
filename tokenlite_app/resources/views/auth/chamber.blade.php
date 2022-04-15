@extends('layouts.admin')
@section('title', 'Product Register')
@section('content')

<div class="page-content">
    <div class="container">
        <div class="row">
            <div class="main-content col-lg-12">
                @if (session('thanks'))
                <div class="alert alert-dismissible fade show alert-warning" role="alert">
                    <a href="javascript:void(0)" class="close" data-dismiss="alert" aria-label="close">&nbsp;</a>
                    {!! session('thanks') !!}
                </div>
                @endif
                <div class="content-area card">
                    <div class="card-innr">
                        <div class="card-head wide-max-lg pb-0">
                            <h4 class="card-title card-title-lg">Register the Product</h4>
                            <p class="mt-2">TokenLite is now installed and ready to use. <strong>Your application must be registered to unlock all the features and activate the app.</strong> Please follow the instruction below to provide your purchase code and register the application.</p>
                            <p>Contact our <strong><a href="https://softnio.com/contact/" target="_blank">support team</a></strong>, if you need any kind of help. 
                                <br>Check out <a href="{{ route('admin.system') }}">application system information</a>. We hope you enjoy it!</p>
                        </div>
                        <div class="sap sap-gap"></div>
                        <div class="card-text">
                            <div class="row guttar-50px guttar-vr-30px">
                                <div class="col-lg-4 order-lg-last">
                                    <p class="alert alert-danger fs-13"><strong>Important:</strong> As per <a href="https://codecanyon.net/licenses/standard" target="_blank">Envato License</a> terms, one purchase code is valid for install to one domain. So please install the TokenLite into correct domain to avoid any kind of issues.</p>
                                    <div class="card pd-2x mb-0 bg-light rounded">
                                        <p class="text-head">Following data is sent to Softnio server to ensure that purchase code is valid with your install &amp; activate the application.</p>
                                        <table class="table fs-12">
                                            <tr>
                                                <td width="120">Registration Info:</td>
                                                <td>Purchase Code, <br>Username & Email</td>
                                            </tr>
                                            <tr>
                                                <td>Site/App Name:</td>
                                                <td><span class="text-wrap wide-120px">{{ base64_encode(site_info('name')) }}</span></td>
                                            </tr>
                                            <tr>
                                                <td>Site/App URL: </td>
                                                <td>
                                                    <span class="text-wrap wide-120px">{{ site_info('url_only') }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Installed Version:</td>
                                                <td>{{ app_info('version'). ' / ' .app_info('key') }}</td>
                                            </tr>
                                        </table>
                                        <p class="alert alert-warning fs-13"><em><strong>Please Note:</strong> We will never collect any confidential data such as transactions, email addresses or usernames.</em></p>
                                    </div>
                                </div>
                                <div class="col-lg-8">
                                    <h4 class="text-primary">Enter your purchase details</h4>
                                    <form class="validate-modern register-product" action="{{ url()->current() }}" method="POST">
                                        {!! (!nio_status() && !empty(app_key(2)) && gws('env_pcode')) ? '<p class="alert alert-xs alert-warning">Your purchase code is invalid or already used in another domain.</p>' : '' !!}
                                        <div class="register-result"></div>
                                        <div class="input-item input-with-label">
                                            <label class="input-item-label">Envato Purchase Code</label>
                                            <div class="input-wrap">
                                                <input class="input-bordered" type="text" minlength="24" name="purchase_code" placeholder="10101010-10ab-0102-02cb-a1b1c101a201" value="{{ gws('env_pcode') }}" required>
                                            </div>
                                            <span class="input-note">Please enter your valid purchase code. <a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-" target="_blank">Click here</a> to see where to find code.</span>
                                        </div>
                                        <div class="input-item input-with-label">
                                            <label class="input-item-label">Envato Username</label>
                                            <div class="input-wrap">
                                                <input class="input-bordered" type="text" name="name" placeholder="Your envato username" required value="{{ gws('env_uname') }}">
                                            </div>
                                            <span class="input-note">Please enter your envato username that purchased the product/script.</span>
                                        </div>
                                        <div class="input-item input-with-label">
                                            <label class="input-item-label">Email Address</label>
                                            <div class="input-wrap">
                                                <input class="input-bordered" type="email" name="email" placeholder="Your email address" value="{{ gws('nio_email') }}" required>
                                            </div>
                                            <span class="input-note">Please enter valid email address. This will required if you want to change your domain.</span>
                                        </div>
                                        <div class="gaps-1x"></div>
                                        <div class="d-flex">
                                            @csrf
                                            <button class="btn btn-primary" type="submit">Submit</button>
                                        </div>
                                        <p class="mt-2"><small>By clicking the 'Submit' button to agree with <a href="https://codecanyon.net/licenses/standard" target="_blank">Envato Standard License</a> Terms &amp; as well as our Terms and condition.</small></p>
                                        <div class="gaps-0-5x"></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>{{-- .card --}}
            </div>{{-- .col --}}
        </div>{{-- .container --}}
    </div>{{-- .container --}}
</div>{{-- .page-content --}}

@endsection

@push('footer')
{{-- <script src="{{ asset('assets/js/public.app.js').css_js_ver() }}"></script> --}}
<script type="text/javascript">
    (function($){
        var $regpro = $('.register-product');
        $regpro.validate({
            submitHandler: function(form) {
                var $this = $(form);
                $.post($this.attr('action'), $this.serialize())
                .done(function(rs){
                    var _rs_s = (typeof rs.status != undefined && rs.status == true) ? true : false, _ms_t = (rs.msg=='success'&&_rs_s==true) ? 'success' : 'error', _ms_i = (_ms_t=='success') ? 'ti ti-unlock' : 'ti ti-lock';
                    if(rs.status == true){
                        $('.register-result').html('<div class="alert alert-'+_ms_t+'">'+rs.text+'</div>');
                        if(_rs_s){
                            setTimeout(function(){
                                window.location = "{{ route('admin.home') }}";
                            }, 5000);
                        }
                    }
                    show_toast(_ms_t, rs.message, _ms_i);
                });
            }
        });
        
    })(jQuery)
</script>
@endpush