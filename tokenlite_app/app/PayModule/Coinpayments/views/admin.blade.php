<div class="page-content">
    <div class="container">
        <div class="card content-area">
            <div class="card-innr">
                <div class="card-head d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">CoinPayments API <br class="d-block d-sm-none">Setting <span class="badge badge-xs badge-purple ucap">Add-ons</span></h4>
                    <a href="{{ route('admin.payments.setup') }}" class="btn btn-sm btn-auto btn-outline btn-primary d-sm-inline-block"><em class="fas fa-arrow-left"></em><span class="d-none d-sm-inline-block">Back</span></a>
                </div>
                <div class="card-text wide-max-md">
                    <p>CoinPayments is online crypto payment gateway that helps to accept payments from your contributors.</p>
                    <p>These currencies 'ETH', 'BTC', 'LTC', 'BCH', 'BNB', 'XRP', 'TRX', 'USDT', 'USDC', 'DASH' are supports through this payment gateway.</p>
                </div>
                <div class="gaps-3x"></div>
                <div class="row">
                    <div class="col-12">
                        <form action="{{ route('admin.ajax.payments.update') }}" method="POST" class="payment_methods_form validate-modern">
                            @csrf
                            <input type="hidden" name="req_type" value="coinpayments">
                            <div class="row align-items-center">
                                <div class="col-sm col-md-3">
                                    <label class="card-title card-title-sm">Active or Deactive</label>
                                </div>
                                <div class="col-sm col-md-3">
                                    <div class="fake-class">
                                        <div class="input-wrap input-wrap-switch">
                                            <input class="input-switch" {{ $pmData->status == 'active' ? 'checked' : '' }} id="status" name="status" type="checkbox">
                                            <label for="status">
                                                <span class="over">Inactive</span><span>Active Gateway</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="gaps-1x"></div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-item input-with-label">
                                        <label class="input-item-label">Method Title</label>
                                        <div class="input-wrap">
                                            <input class="input-bordered" value="{{ $pmData->title }}" type="text" name="title" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-item input-with-label">
                                        <label class="input-item-label">Description</label>
                                        <div class="input-wrap">
                                            <input class="input-bordered" value="{{ $pmData->details }}" placeholder="You can send paymeny direct to our wallets; We will manually verify" type="text" name="details">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <h5 class="card-title card-title-sm pdt-1x pdb-1x text-primary">API Credentials</h5>
                            <p>Enter your CoinPayments public & private API credentials to receive payments and verify automatically.</p>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="input-item input-with-label">
                                        <label class="input-item-label">Private API Key</label>
                                        <div class="input-wrap">
                                            <input class="input-bordered" name="cnp_api2" autocomplete="new-password" value="{{ (is_demo_user() || is_demo_preview()) ? show_str($pmData->secret->privateApiKey, 10) : $pmData->secret->privateApiKey }}" type="text" placeholder="Place here your Private Key">
                                        </div>
                                    </div>
                                    <div class="input-item input-with-label">
                                        <label class="input-item-label">Public API Key</label>
                                        <div class="input-wrap">
                                            <input class="input-bordered" name="cnp_api" autocomplete="new-password" value="{{ (is_demo_user() || is_demo_preview()) ? show_str($pmData->secret->publicApiKey, 10) : $pmData->secret->publicApiKey }}" type="text" placeholder="Place here your Public Key">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="input-item input-with-label">
                                        <label class="input-item-label">Test Mode <small>(Support only LTCT-Test net)</small></label>
                                        <div class="input-wrap input-wrap-switch">
                                            <input class="input-switch" id="cp-sandbox" {{ $pmData->secret->sandbox == '1' ? 'checked' : '' }} type="checkbox" name="cnp_sandbox">
                                            <label for="cp-sandbox">
                                                <span class="over"></span><span>Enable</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="gaps-1x"></div>
                            <div class="d-flex pb-1">
                                <button class="btn btn-md btn-primary save-disabled" type="submit">UPDATE</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>