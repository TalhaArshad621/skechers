@extends('layouts.app')
@section('title', __('BrandFolio Report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>{{ __('BrandFolio Report')}}</h1>
</section>

<!-- Main content -->
<section class="content no-print">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
              {!! Form::open(['url' => action('ReportController@getStockReport'), 'method' => 'get', 'id' => 'product_sell_report_form' ]) !!}
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location').':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-map-marker"></i>
                            </span>
                            {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('product_sr_date_filter', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'product_sr_date_filter', 'readonly']); !!}
                    </div>
                </div>
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs" style="display: none;">
                    <li class="active">
                        <a href="#psr_grouped_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-bars" aria-hidden="true"></i> @lang('lang_v1.grouped')</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="psr_grouped_tab">
                        <h3 style="margin-top:10px; margin-left:15px; margin-bottom:20px;">BrandFolio Report</h3>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="brandfolio_report_table">
                                <thead>
                                    <tr>
                                        <th>Sl.No.</th>
                                        <th>Style No</th>
                                        <th>Color</th>
                                        <th>Size</th>
                                        <th>Distributor</th>
                                        <th>Country</th>
                                        <th>Transaction Date</th>
                                        <th>Store</th>
                                        <th>Category</th>
                                        <th>Sale Unit</th>
                                        <th>Total Sale Amount</th>
                                        <th>Closing Stock</th>
                                    </tr>
                                </thead>
                               
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->


@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
@endsection