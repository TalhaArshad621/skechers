@extends('layouts.app')
@section('title', __('lang_v1.stock_transfers'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang('lang_v1.stock_transfers')
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('stock_transfer_list_filter_location_id',  __('purchase.business_location') . ':') !!}
    
                {!! Form::select('stock_transfer_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('stock_transfer_list_filter_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('stock_transfer_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
            </div>
        </div>

    </div>
    @endcomponent
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_stock_transfers')])
        @slot('tool')
            <div class="box-tools">
                <a class="btn btn-block btn-primary" href="{{action('StockTransferController@create')}}">
                <i class="fa fa-plus"></i> @lang('messages.add')</a>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped ajax_view" id="stock_transfer_table">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('purchase.ref_no')</th>
                        <th>@lang('lang_v1.location_from')</th>
                        <th>@lang('lang_v1.location_to')</th>
                        <th>@lang('sale.status')</th>
                        {{-- <th>@lang('lang_v1.shipping_charges')</th> --}}
                        {{-- <th>@lang('stock_adjustment.total_amount')</th> --}}
                        {{-- <th>@lang('purchase.additional_notes')</th> --}}
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>

@include('stock_transfer.partials.update_status_modal')

<section id="receipt_section" class="print_section"></section>

<!-- /.content -->
@stop
@section('javascript')
	<script src="{{ asset('js/stock_transfer.js?v=' . $asset_v) }}"></script>
@endsection