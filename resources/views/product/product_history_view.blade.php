@extends('layouts.app')
@section('title', __('lang_v1.product_purchase_report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('lang_v1.product_purchase_report')}}</h1>
</section>

<!-- Main content -->
<section class="content">
    {{ dd("hehe") }}
    {{-- <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
          {!! Form::open(['url' => action('ReportController@getStockReport'), 'method' => 'get', 'id' => 'product_purchase_report_form' ]) !!}
            <div class="col-md-3">
                <div class="form-group">
                {!! Form::label('search_product', __('lang_v1.search_product') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-search"></i>
                        </span>
                        <input type="hidden" value="" id="variation_id">
                        {!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'), 'autofocus']); !!}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('supplier_id', __('purchase.supplier') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-user"></i>
                        </span>
                        {!! Form::select('supplier_id', $suppliers, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
                    </div>
                </div>
            </div>
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

                    {!! Form::label('product_pr_date_filter', __('report.date_range') . ':') !!}
                    {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'product_pr_date_filter', 'readonly']); !!}
                </div>
            </div>
            {!! Form::close() !!}
            @endcomponent
        </div>
    </div> --}}
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" 
                    id="product_purchase_report_table">
                        <thead>
                            <tr>
                                <th>@lang('product.sku')</th>
                                <th>@lang('Invoice No.')</th>
                                <th>@lang('sale.qty')</th>
                                <th>@lang('Date')</th>
                            </tr>
                        </thead>
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
    {{-- <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script> --}}
    <script>
$(document).ready(function() {
    product_purchase_report = $('table#product_purchase_report_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[4, 'desc']], // Assuming 'transaction_date' is the 5th column (index 4)
        ajax: {
            url: '/products/history-ajax',
            data: function(d) {
                var start = '';
                var end = '';
                if ($('#product_pr_date_filter').val()) {
                    start = $('input#product_pr_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#product_pr_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                }
                d.columns[4].search.value = start; // Assuming 'transaction_date' is the 5th column (index 4)
                d.columns[4].search.value = end;   // Assuming 'transaction_date' is the 5th column (index 4)
                d.columns[3].search.value = $('#variation_id').val(); // Assuming 'ref_no' is the 4th column (index 3)
                d.columns[2].search.value = $('select#supplier_id').val(); // Assuming 'supplier' is the 3rd column (index 2)
                d.columns[1].search.value = $('select#location_id').val(); // Assuming 'sub_sku' is the 2nd column (index 1)
            },
        },
        columns: [
            { data: 'product_name', name: 'p.name' },
            { data: 'sub_sku', name: 'v.sub_sku' },
            { data: 'supplier', name: 'c.name' },
            { data: 'ref_no', name: 't.ref_no' },
            { data: 'transaction_date', name: 't.transaction_date' },
            { data: 'purchase_qty', name: 'purchase_lines.quantity' },
            { data: 'quantity_adjusted', name: 'purchase_lines.quantity_adjusted' },
            { data: 'unit_purchase_price', name: 'purchase_lines.purchase_price_inc_tax' },
            { data: 'subtotal', name: 'subtotal', searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            $('#footer_subtotal').text(
                sum_table_col($('#product_purchase_report_table'), 'row_subtotal')
            );
            $('#footer_total_purchase').html(
                __sum_stock($('#product_purchase_report_table'), 'purchase_qty')
            );
            $('#footer_total_adjusted').html(
                __sum_stock($('#product_purchase_report_table'), 'quantity_adjusted')
            );
            __currency_convert_recursively($('#product_purchase_report_table'));
        },
    });
});


    </script>
@endsection