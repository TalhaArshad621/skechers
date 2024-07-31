@extends('layouts.app')
@section('title', __('Monthly Report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('Monthly Report')}}</h1>
</section>

<!-- Main content -->
<section class="content">
    {{-- <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
              {!! Form::open(['url' => action('ReportController@getStockReport'), 'method' => 'get', 'id' => 'register_report_filter_form' ]) !!}
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('register_user_id',  __('report.user') . ':') !!}
                        {!! Form::select('register_user_id', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all_users')]); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('register_status',  __('sale.status') . ':') !!}
                        {!! Form::select('register_status', ['open' => __('cash_register.open'), 'close' => __('cash_register.close')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all')]); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('register_report_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('register_report_date_range', null , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'register_report_date_range', 'readonly']); !!}
                    </div>
                </div>
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div> --}}
    @component('components.filters', ['title' => __('report.filters')])
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('monthly_report_filter_date_range', __('report.date_range') . ':') !!}
            {!! Form::text('monthly_report_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
        </div>
    </div>
@endcomponent
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="monthly_report_table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Cash Amount</th>
                                <th>Card Amount</th>
                                <th>Toal Card And Cash</th>
                                <th>Merchant Tax</th>
                                <th>Merchant Tax Rate</th>
                                <th>Card Amount After Tax</th>
                                <th>Bank Trasnfer</th>
                                <th>Total Net Amount</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                {{-- <td></td> --}}
                                <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                                <td><span class="display_currency" id="cash_amount" data-currency_symbol ="true"></span></td>
                                <td><span class="display_currency" id="card_amount" data-currency_symbol ="true"></span></td>
                                <td><span class="display_currency" id="cash_and_card" data-currency_symbol ="true"></span></td>
                                <td><span class="display_currency" id="mechant_tax" data-currency_symbol ="true"></span></td>
                                <td></td>
                                <td><span class="display_currency" id="card_amount_after_tax" data-currency_symbol ="true"></span></td>
                                <td><span class="display_currency" id="bank_transfer" data-currency_symbol ="true"></span></td>
                                <td><span class="display_currency" id="total_net_amount" data-currency_symbol ="true"></span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->
<div class="modal fade view_register" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
@endsection