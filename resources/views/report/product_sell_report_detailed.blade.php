@extends('layouts.app')
@section('title', __('Detailed Product Sell Report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>{{ __('Detailed Product Sell Report')}}</h1>
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
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs" style="display: none;">
                    <li class="active">
                        <a href="#psr_grouped_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-bars" aria-hidden="true"></i> @lang('lang_v1.grouped')</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="psr_grouped_tab">
                        <h3 style="margin-top:10px; margin-left:15px; margin-bottom:20px;">Detailed Sell Report</h3>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" 
                            id="product_sell_grouped_report_table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('Category')</th>
                                        <th>@lang('product.sku')</th>
                                        <th>@lang('report.total_unit_sold')</th>
                                        <th>@lang('sale.total')</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                                        <td id="footer_total_grouped_sold"></td>
                                        <td><span class="display_currency" id="footer_grouped_subtotal" data-currency_symbol ="true"></span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active" id="psr_grouped_tab">
                        <h3 style="margin-top:10px; margin-left:15px; margin-bottom:20px;">Detailed Return Report</h3>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" 
                            id="product_sell_grouped_report_table_returned" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('Category')</th>
                                        <th>@lang('product.sku')</th>
                                        <th>@lang('Total Unit Returned')</th>
                                        <th>@lang('sale.total')</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                                        <td id="footer_total_grouped_sold_return"></td>
                                        <td><span class="display_currency" id="footer_grouped_subtotal_return" data-currency_symbol ="true"></span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active" id="psr_grouped_tab">
                        <h3 style="margin-top:10px; margin-left:15px; margin-bottom:20px;">Category Sell Report</h3>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" 
                            id="category_wise_sale" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>@lang('Category')</th>
                                        <th>@lang('report.total_unit_sold')</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        <td></td>
                                        <td></td>
                                        <td id="footer_total_grouped_sold_category"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active" id="psr_grouped_tab">
                        <h3 style="margin-top:10px; margin-left:15px; margin-bottom:20px;">Category Return Report</h3>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" 
                            id="category_wise_return" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>@lang('Category')</th>
                                        <th>@lang('Total Unit Returned')</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        <td></td>
                                        <td></td>
                                        <td id="footer_total_grouped_sold_return_category"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<div class="modal fade view_register" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

@endsection

@section('javascript')
    {{-- <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script> --}}
    <script>
        $(document).ready(function() {

    if ($('#product_sr_date_filter').length == 1) {
        $('#product_sr_date_filter').daterangepicker(
            dateRangeSettings, 
            function(start, end) {
                $('#product_sr_date_filter').val(
                    start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                );
                product_sell_grouped_report.ajax.reload();
                product_sell_grouped_report_2nd.ajax.reload();
                product_sell_grouped_report_category.ajax.reload();
                product_sell_grouped_report_return_category.ajax.reload();
            }
        );
        $('#product_sr_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#product_sr_date_filter').val('');
            product_sell_grouped_report_2nd.ajax.reload();
            product_sell_grouped_report_category.ajax.reload();
            product_sell_report_with_purchase_table.ajax.reload();
            product_sell_grouped_report_return_category.ajax.reload();
        });

        $('#product_sr_start_time, #product_sr_end_time').datetimepicker({
            format: moment_time_format,
            ignoreReadonly: true,
        }).on('dp.change', function(ev){
            product_sell_grouped_report.ajax.reload();
            product_sell_grouped_report_2nd.ajax.reload();
            product_sell_grouped_report_category.ajax.reload();
            product_sell_grouped_report_return_category.ajax.reload();

        });
    }

    product_sell_grouped_report = $('table#product_sell_grouped_report_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[1, 'desc']],
        ajax: {
            url: '/reports/product-sell-grouped-report-detailed',

            data: function(d) {
            var start = '';
            var end = '';
            var start_time = $('#product_sr_start_time').val();
            var end_time = $('#product_sr_end_time').val();
            var currentDate = moment().format('YYYY-MM-DD'); // Get current date in 'YYYY-MM-DD' format
            var yesterdayDate = moment().subtract(1, 'days').format('YYYY-MM-DD'); // Get yesterday's date in 'YYYY-MM-DD' format

            if ($('#product_sr_date_filter').val()) {
                var selectedStartDate = $('input#product_sr_date_filter')
                    .data('daterangepicker')
                    .startDate.format('YYYY-MM-DD');
                var selectedEndDate = $('input#product_sr_date_filter')
                    .data('daterangepicker')
                    .endDate.format('YYYY-MM-DD');

                // If selected start and end dates are today or yesterday, use specific time range
                if (selectedStartDate === currentDate) {
                    start = moment().startOf('day').format('YYYY-MM-DD HH:mm'); // Today's start time (00:00:00)
                    end = moment().endOf('day').format('YYYY-MM-DD HH:mm'); // Today's end time (23:59:59)
                } else if (selectedStartDate === yesterdayDate) {
                    start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's start time (00:00:00)
                    end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's end time (23:59:59)
                } else {
                    start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                }
                console.log(start, end);
            }
            d.start_date = start;
            d.end_date = end;

            d.variation_id = $('#variation_id').val();
            d.customer_id = $('select#customer_id').val();
            d.location_id = $('select#location_id').val();
            },





            // data: function(d) {
            //     var start = '';
            //     var end = '';
            //     var start_time = $('#product_sr_start_time').val();
            //     var end_time = $('#product_sr_end_time').val();
            //     if ($('#product_sr_date_filter').val()) {
            //         start = $('input#product_sr_date_filter')
            //             .data('daterangepicker')
            //             .startDate.format('YYYY-MM-DD');
            //         end = $('input#product_sr_date_filter')
            //             .data('daterangepicker')
            //             .endDate.format('YYYY-MM-DD');

            //         start = moment(start + " " + start_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
            //         end = moment(end + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
            //         console.log(start,end);
            //     }
            //     d.start_date = start;
            //     d.end_date = end;

            //     d.variation_id = $('#variation_id').val();
            //     d.customer_id = $('select#customer_id').val();
            //     d.location_id = $('select#location_id').val();
            // },
        },
        columns: [
            { data: 'product_image', name: 'product_image' },
            { data: 'transaction_date', name: 't.transaction_date' },
            { data: 'category_name', name: 'category_name' },
            { data: 'sub_sku', name: 'v.sub_sku' },
            { data: 'total_qty_sold', name: 'total_qty_sold', searchable: false },
            { data: 'subtotal', name: 'subtotal', searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            $('#footer_grouped_subtotal').text(
                sum_table_col($('#product_sell_grouped_report_table'), 'row_subtotal')
            );
            $('#footer_total_grouped_sold').html(
                __sum_stock($('#product_sell_grouped_report_table'), 'sell_qty')
            );
            __currency_convert_recursively($('#product_sell_grouped_report_table'));
        },
    });

    product_sell_grouped_report_category = $('table#category_wise_sale').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[1, 'desc']],
        ajax: {
            url: '/reports/product-sell-grouped-report-detailed-category',
            data: function(d) {
                var start = '';
                var end = '';
                var start_time = $('#product_sr_start_time').val();
                var end_time = $('#product_sr_end_time').val();
                if ($('#product_sr_date_filter').val()) {
                    start = $('input#product_sr_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#product_sr_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');

                    start = moment(start + " " + start_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    end = moment(end + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                }
                d.start_date = start;
                d.end_date = end;

                d.variation_id = $('#variation_id').val();
                d.customer_id = $('select#customer_id').val();
                d.location_id = $('select#location_id').val();
            },
        },
        columns: [
            { data: 'product_image', name: 'product_image' },
            { data: 'category_name', name: 'category_name' },
            { data: 'total_qty_sold', name: 'total_qty_sold', searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            $('#footer_total_grouped_sold_category').html(
                __sum_stock($('#category_wise_sale'), 'sell_qty')
            );
            __currency_convert_recursively($('#category_wise_sale'));
        },
    });


    product_sell_grouped_report_2nd = $('table#product_sell_grouped_report_table_returned').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[1, 'desc']],
        ajax: {
            url: '/reports/product-sell-grouped-report-detailed-returns',
            data: function(d) {
                var start = '';
                var end = '';
                var start_time = $('#product_sr_start_time').val();
                var end_time = $('#product_sr_end_time').val();
                if ($('#product_sr_date_filter').val()) {
                    start = $('input#product_sr_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#product_sr_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');

                    start = moment(start + " " + start_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    end = moment(end + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                }
                d.start_date = start;
                d.end_date = end;

                d.variation_id = $('#variation_id').val();
                d.customer_id = $('select#customer_id').val();
                d.location_id = $('select#location_id').val();
            },
        },
        columns: [
            { data: 'product_image', name: 'product_image' },
            { data: 'transaction_date', name: 't.transaction_date' },
            { data: 'category_name', name: 'category_name' },
            { data: 'sub_sku', name: 'v.sub_sku' },
            { data: 'total_qty_sold', name: 'total_qty_sold', searchable: false },
            { data: 'subtotal', name: 'subtotal', searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            $('#footer_grouped_subtotal_return').text(
                sum_table_col($('#product_sell_grouped_report_table_returned'), 'row_subtotal')
            );
            $('#footer_total_grouped_sold_return').html(
                __sum_stock($('#product_sell_grouped_report_table_returned'), 'sell_qty')
            );
            __currency_convert_recursively($('#product_sell_grouped_report_table_returned'));
        },
    });

    product_sell_grouped_report_return_category = $('table#category_wise_return').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[1, 'desc']],
        ajax: {
            url: '/reports/product-sell-grouped-report-detailed-returns-category',
            data: function(d) {
                var start = '';
                var end = '';
                var start_time = $('#product_sr_start_time').val();
                var end_time = $('#product_sr_end_time').val();
                if ($('#product_sr_date_filter').val()) {
                    start = $('input#product_sr_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#product_sr_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');

                    start = moment(start + " " + start_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    end = moment(end + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                }
                d.start_date = start;
                d.end_date = end;

                d.variation_id = $('#variation_id').val();
                d.customer_id = $('select#customer_id').val();
                d.location_id = $('select#location_id').val();
            },
        },
        columns: [
            { data: 'product_image', name: 'product_image' },
            { data: 'category_name', name: 'category_name' },
            { data: 'total_qty_sold', name: 'total_qty_sold', searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            $('#footer_total_grouped_sold_return_category').html(
                __sum_stock($('#category_wise_return'), 'sell_qty')
            );
            __currency_convert_recursively($('#category_wise_return'));
        },
    });


    $(
        '#product_sell_report_form #variation_id, #product_sell_report_form #location_id, #product_sell_report_form #customer_id'
    ).change(function() {
        product_sell_grouped_report.ajax.reload();
        product_sell_grouped_report_2nd.ajax.reload();
        product_sell_grouped_report_category.ajax.reload();
        product_sell_grouped_report_return_category.ajax.reload();

    });

    $('#product_sell_report_form #search_product').keyup(function() {
        if (
            $(this)
                .val()
                .trim() == ''
        ) {
            $('#product_sell_report_form #variation_id')
                .val('')
                .change();
        }
    });

    $(document).on('click', '.remove_from_stock_btn', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $.ajax({
                    method: 'GET',
                    url: $(this).data('href'),
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            stock_expiry_report_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
    });
    </script>
@endsection