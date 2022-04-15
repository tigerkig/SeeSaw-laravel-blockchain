@extends('layouts.user')
@section('title', __('User Transactions'))

@push('header')
<script type="text/javascript">
    var view_transaction_url = "{{ route('user.ajax.transactions.view') }}";
</script>
@endpush

@section('content')
@include('layouts.messages')
<div class="card content-area content-area-mh">
    <div class="card-innr">
        <div class="card-head">
            <h4 class="card-title">{{__('Transactions list')}}</h4>
        </div>
        <div class="gaps-1x"></div>
        <div class="row">
            <div class="col-md-12">
                <div class="float-right position-relative">
                    <a href="#" class="btn btn-light-alt btn-xs dt-filter-text btn-icon toggle-tigger"> <em class="ti ti-settings"></em> </a>
                    <div class="toggle-class toggle-datatable-filter dropdown-content dropdown-dt-filter-text dropdown-content-top-left text-left">
                        <ul class="dropdown-list dropdown-list-s2">
                            <li><h6 class="dropdown-title">{{ __('Types') }}</h6></li>
                            <li>
                                <input class="data-filter input-checkbox input-checkbox-sm" type="radio" name="tnx-type" id="type-all" checked value="">
                                <label for="type-all">{{ __('Any Type') }}</label>
                            </li>
                            <li>
                                <input class="data-filter input-checkbox input-checkbox-sm" type="radio" name="tnx-type" id="type-purchase" value="Purchase">
                                <label for="type-purchase">{{ __('Purchase') }}</label>
                            </li>
                            @foreach($has_trnxs as $name => $has)
                            @if($has==1)
                            <li>
                                <input class="data-filter input-checkbox input-checkbox-sm" type="radio" name="tnx-type" id="type-{{ $name }}" value="{{ ucfirst($name) }}">
                                <label for="type-{{ $name }}">{{ ucfirst($name) }}</label>
                            </li>
                            @endif
                            @endforeach
                        </ul>
                        <ul class="dropdown-list dropdown-list-s2">
                            <li><h6 class="dropdown-title">{{ __('Status') }}</h6></li>
                            <li>
                                <input class="data-filter input-checkbox input-checkbox-sm" type="radio" name="tnx-status" id="status-all" checked value="">
                                <label for="status-all">{{ __('Show All') }}</label>
                            </li>
                            <li>
                                <input class="data-filter input-checkbox input-checkbox-sm" type="radio" name="tnx-status" id="status-approved" value="approved">
                                <label for="status-approved">{{ __('Approved') }}</label>
                            </li>
                            <li>
                                <input class="data-filter input-checkbox input-checkbox-sm" type="radio" name="tnx-status" value="pending" id="status-pending">
                                <label for="status-pending">{{ __('Pending') }}</label>
                            </li>
                            <li>
                                <input class="data-filter input-checkbox input-checkbox-sm" type="radio" name="tnx-status" value="canceled" id="status-canceled">
                                <label for="status-canceled">{{ __('Canceled') }}</label>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <table class="data-table dt-filter-init user-tnx">
            <thead>
                <tr class="data-item data-head">
                    <th class="data-col tnx-status dt-tnxno">{{ __('Tranx NO') }}</th>
                    <th class="data-col dt-token">{{ __('Tokens') }}</th>
                    <th class="data-col dt-amount">{{ __('Amount') }}</th>
                    <th class="data-col dt-base-amount">{{ base_currency(true) }} {{ __('Amount') }}</th>
                    <th class="data-col dt-account">{{ __('To') }}</th>
                    <th class="data-col dt-type tnx-type"><div class="dt-type-text">{{ __('Type') }}</div></th>
                    <th class="data-col"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($trnxs as $trnx)
                @php 
                    $text_danger = ( $trnx->tnx_type=='refund' || ($trnx->tnx_type=='transfer' && $trnx->extra=='sent') ) ? ' text-danger' : '';
                @endphp
                <tr class="data-item tnx-item-{{ $trnx->id }}">
                    <td class="data-col dt-tnxno">
                        <div class="d-flex align-items-center">
                            <div class="data-state data-state-{{ str_replace(['progress','canceled'], ['pending','canceled'], __status($trnx->status, 'icon')) }}">
                                <span class="d-none">{{ ($trnx->status=='onhold') ? ucfirst('pending') : ucfirst($trnx->status) }}</span>
                            </div>
                            <div class="fake-class">
                                <span class="lead tnx-id">{{ $trnx->tnx_id }}</span>
                                <span class="sub sub-date">{{_date($trnx->tnx_time)}}</span>
                            </div>
                        </div>
                    </td>
                    <td class="data-col dt-token">
                        <span class="lead token-amount{{ $text_danger }}">{{ (starts_with($trnx->total_tokens, '-') ? '' : '+').$trnx->total_tokens }}</span>
                        <span class="sub sub-symbol">{{ token_symbol() }}</span>
                    </td>
                    <td class="data-col dt-amount{{ $text_danger }}">
                        @if ($trnx->tnx_type=='referral'||$trnx->tnx_type=='bonus') 
                            <span class="lead amount-pay">{{ '~' }}</span>
                        @else 
                        <span class="lead amount-pay{{ $text_danger }}">{{ to_num($trnx->amount, 'max') }}</span>
                        <span class="sub sub-symbol">{{ strtoupper($trnx->currency) }} <em class="fas fa-info-circle" data-toggle="tooltip" data-placement="bottom" title="1 {{ token('symbol') }} = {{ to_num($trnx->currency_rate, 'max').' '.strtoupper($trnx->currency) }}"></em></span>
                        @endif
                    </td>
                    <td class="data-col dt-usd-amount">
                        @if ($trnx->tnx_type=='referral'||$trnx->tnx_type=='bonus') 
                            <span class="lead amount-pay">{{ '~' }}</span>
                        @else 
                        <span class="lead amount-pay{{ $text_danger }}">{{ to_num($trnx->base_amount, 'auto') }}</span>
                        <span class="sub sub-symbol">{{ base_currency(true) }} <em class="fas fa-info-circle" data-toggle="tooltip" data-placement="bottom" title="1 {{ token('symbol') }} = {{ to_num($trnx->base_currency_rate, 'max').' '.base_currency(true) }}"></em></span>
                        @endif
                    </td>
                    <td class="data-col dt-account">
                        @php 
                        $pay_to = ($trnx->payment_method=='system') ? '~' : ( ($trnx->payment_method=='bank') ? explode(',', $trnx->payment_to) : show_str($trnx->payment_to) );
                        $extra = ($trnx->tnx_type == 'refund') ? (is_json($trnx->extra, true) ?? $trnx->extra) : '';
                        @endphp
                        @if($trnx->tnx_type == 'refund')
                            <span class="sub sub-info">{{ $trnx->details }}</span>
                            @if($extra->trnx)
                            <span class="sub sub-view"><a href="javascript:void(0)" class="view-transaction" data-id="{{ $extra->trnx }}">View Transaction</a></span>
                            @endif
                        @else
                            @if($trnx->refund != null)
                            <span class="sub sub-info text-danger">{{ __('Refunded #:orderid', ['orderid' => set_id($trnx->refund, 'refund')]) }}</span>
                            @else
                            <span class="lead user-info">{{ ($trnx->payment_method=='bank') ? $pay_to[0] : ( ($pay_to) ? $pay_to : '~' ) }}</span>
                            @endif
                            <span class="sub sub-date">{{ ($trnx->checked_time) ? _date($trnx->checked_time) : _date($trnx->created_at) }}</span>
                        @endif
                    </td>
                    <td class="data-col dt-type">
                        <span class="dt-type-md badge badge-outline badge-md badge-{{ __(__status($trnx->tnx_type,'status')) }}">{{ ucfirst($trnx->tnx_type) }}</span>
                        <span class="dt-type-sm badge badge-sq badge-outline badge-md badge-{{ __(__status($trnx->tnx_type, 'status')) }}">{{ ucfirst(substr($trnx->tnx_type, 0,1)) }}</span>
                    </td>
                    <td class="data-col text-right">
                        @if($trnx->status == 'pending' || $trnx->status == 'onhold')
                            @if($trnx->tnx_type != 'transfer')
                            <div class="relative d-inline-block d-md-none">
                                <a href="#" class="btn btn-light-alt btn-xs btn-icon toggle-tigger"><em class="ti ti-more-alt"></em></a>
                                <div class="toggle-class dropdown-content dropdown-content-center-left pd-2x">
                                    <ul class="data-action-list">
                                        <li><a href="javascript:void(0)" class="btn btn-auto btn-primary btn-xs view-transaction" data-id="{{ $trnx->id }}"><span>{{__('Pay')}}</span><em class="ti ti-wallet"></em></a></li>
                                    </ul>
                                </div>
                            </div>

                            <ul class="data-action-list d-none d-md-inline-flex">
                                <li><a href="javascript:void(0)" class="btn btn-auto btn-primary btn-xs view-transaction" data-id="{{ $trnx->id }}"><span>{{__('Pay')}}</span><em class="ti ti-wallet"></em></a></li>
                            </ul>
                            @else 
                                <a href="javascript:void(0)" class="view-transaction btn btn-light-alt btn-xs btn-icon" data-id="{{ $trnx->id }}"><em class="ti ti-eye"></em></a>
                            @endif
                        @endif
                    </td>
                </tr>{{-- .data-item --}}
                @endforeach
            </tbody>
        </table>
    </div>{{-- .card-innr --}}
</div>{{-- .card --}}
@endsection