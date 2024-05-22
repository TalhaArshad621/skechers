@extends('layouts.app')
@section('title', __('Detailed Product Sell Report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>{{ __('Detailed Product Sell Report')}}</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row no-print">
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
                <div class="row no-print">
                    <div class="col-sm-12">
                        <button type="button" class="btn btn-primary pull-right" 
                        aria-label="Print" onclick="window.print();"
                        ><i class="fa fa-print"></i> @lang( 'messages.print' )</button>
                    </div>
                </div>
            @endcomponent

        </div>
    </div>
    <div class="row ">
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
                                        <th>Category</th>
                                        <th>Sub Category</th>
                                        <th>Gender</th>
                                        <th>@lang('product.sku')</th>
                                        <th>@lang('report.total_unit_sold')</th>
                                        <th>@lang('sale.total')</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        <td colspan="6"><strong>@lang('sale.total'):</strong></td>
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
                                        <th>Category</th>
                                        <th>Sub Category</th>
                                        <th>Gender</th>
                                        <th>@lang('product.sku')</th>
                                        <th>Total Unit Returned</th>
                                        <th>@lang('sale.total')</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        <td colspan="6"><strong>@lang('sale.total'):</strong></td>
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
                        <h3 style="margin-top:10px; margin-left:15px; margin-bottom:20px;">Detailed Return Report (International)</h3>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" 
                            id="product_sell_grouped_report_table_returned_international" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>@lang('messages.date')</th>
                                        <th>Category</th>
                                        <th>Sub Category</th>
                                        <th>Gender</th>
                                        <th>@lang('product.sku')</th>
                                        <th>Total Unit Returned</th>
                                        <th>@lang('sale.total')</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        <td colspan="6"><strong>@lang('sale.total'):</strong></td>
                                        <td id="footer_total_grouped_sold_return_international"></td>
                                        <td><span class="display_currency" id="footer_grouped_subtotal_return_international" data-currency_symbol ="true"></span></td>
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
                                        {{-- <th>Image</th> --}}
                                        <th>Category</th>
                                        <th>@lang('report.total_unit_sold')</th>
                                        <th>@lang('sale.total')</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        {{-- <td></td> --}}
                                        <td></td>
                                        <td id="footer_total_grouped_sold_category"></td>
                                        <td><span class="display_currency" id="footer_grouped_category_subtotal" data-currency_symbol ="true"></span></td>
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
                                        {{-- <th>Image</th> --}}
                                        <th>Category</th>
                                        <th>Total Unit Returned</th>
                                        <th>Total Unit Returned</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        {{-- <td></td> --}}
                                        <td></td>
                                        <td id="footer_total_grouped_sold_return_category"></td>
                                        <td><span class="display_currency" id="footer_total_grouped_sold_return_category_subtotal" data-currency_symbol ="true"></span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active" id="psr_grouped_tab">
                        <h3 style="margin-top:10px; margin-left:15px; margin-bottom:20px;">Category Return Report (International)</h3>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" 
                            id="category_wise_return_international" style="width: 100%;">
                                <thead>
                                    <tr>
                                        {{-- <th>Image</th> --}}
                                        <th>Category</th>
                                        <th>Total Unit Returned</th>
                                        <th>Total Unit Returned</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        {{-- <td></td> --}}
                                        <td></td>
                                        <td id="footer_total_grouped_sold_return_category_international"></td>
                                        <td><span class="display_currency" id="footer_total_grouped_sold_return_category_subtotal_international" data-currency_symbol ="true"></span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                {{-- <div class="tab-content">
                    <div class="tab-pane active" id="psr_grouped_tab">
                        <h3 style="margin-top:10px; margin-left:15px; margin-bottom:20px;">Product And Category Report</h3>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" 
                            id="product_and_category_table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Category</th>
                                        <th>Total Unit Sold</th>
                                        <th>Total Unit Returned</th>
                                        <th>Total Net Unit</th>
                                        <th>Total value sale</th>
                                        <th>Total value Return</th>
                                        <th>Net Value</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        <td colspan="2"></td>
                                        <td id="total_sold"></td>
                                        <td id="total_returned"></td>
                                        <td id="net_unit"></td>
                                        <td><span class="display_currency" id="sale_value" data-currency_symbol ="true"></span></td>
                                        <td id="return_value"></td>
                                        <td id="net_value"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div> --}}
                <div class="table-responsive">
                    <h3 style="margin-top:10px; margin-left:15px; margin-bottom:20px;">Product And Category Report</h3>
                    {{-- <table class="table table-bordered table-striped" id="product_and_category_table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Category</th>
                                <th>Total Unit Sold</th>
                                <th>Total Unit Returned</th>
                                <th>Total Net Unit</th>
                                <th>Total value sale</th>
                                <th>Total value Return</th>
                                <th>Net Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="2"></td>
                                <td id="total_sold"></td>
                                <td id="total_returned"></td>
                                <td id="net_unit"></td>
                                <td><span class="display_currency" id="sale_value" data-currency_symbol ="true"></span></td>
                                <td id="return_value"></td>
                                <td id="net_value"></td>
                            </tr>
                        </tfoot>
                    </table>                 --}}
                    <table class="table table-bordered table-striped" id="product_and_category_table">
                        <thead>
                            <tr>
                                {{-- <th>Product Image</th> --}}
                                <th>Category Name</th>
                                <th>Total Quantity Sold</th>
                                <th>Total Quantity Returned</th>
                                <th>Total Net Quantity</th>
                                <th>Sale Value</th>
                                <th>Return Value</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                        </tfoot>
                    </table>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active" id="psr_grouped_tab">
                        <h3 style="margin-top:10px; margin-left:15px; margin-bottom:20px;">Product And Category Report (International)</h3>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" 
                            id="product_and_category_international_table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Category</th>
                                        {{-- <th>Sub Category</th> --}}
                                        <th>Total Unit Sold</th>
                                        <th>Total Unit Returned</th>
                                        <th>Total Net Unit</th>
                                        <th>Total value sale</th>
                                        <th>Total value Return</th>
                                        <th>Net Value</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        <td colspan="2"></td>
                                        <td id="total_sold_int"></td>
                                        <td id="total_returned_int"></td>
                                        <td id="net_unit_int"></td>
                                        <td><span class="display_currency" id="sale_value_int" data-currency_symbol ="true"></span></td>
                                        <td id="return_value_int"></td>
                                        <td id="net_value_int"></td>
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
                // detail_product_and_category.ajax.reload();
                fetchCategories();
                detail_product_and_category_international.ajax.reload();
                product_sell_grouped_report_return_category_international.ajax.reload();
                product_sell_grouped_report_international.ajax.reload();
            }
        );
        $('#product_sr_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#product_sr_date_filter').val('');
            product_sell_grouped_report_2nd.ajax.reload();
            product_sell_grouped_report_category.ajax.reload();
            product_sell_report_with_purchase_table.ajax.reload();
            product_sell_grouped_report_return_category.ajax.reload();
            // detail_product_and_category.ajax.reload();
            fetchCategories();
            detail_product_and_category_international.ajax.reload();
            product_sell_grouped_report_return_category_international.ajax.reload();
            product_sell_grouped_report_international.ajax.reload();
        });

        $('#product_sr_start_time, #product_sr_end_time').datetimepicker({
            format: moment_time_format,
            ignoreReadonly: true,
        }).on('dp.change', function(ev){
            product_sell_grouped_report.ajax.reload();
            product_sell_grouped_report_2nd.ajax.reload();
            product_sell_grouped_report_category.ajax.reload();
            product_sell_grouped_report_return_category.ajax.reload();
            // detail_product_and_category.ajax.reload();
            fetchCategories();
            detail_product_and_category_international.ajax.reload();
            product_sell_grouped_report_return_category_international.ajax.reload();
            product_sell_grouped_report_international.ajax.reload();

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

            // if ($('#product_sr_date_filter').val()) {
            //     var selectedStartDate = $('input#product_sr_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            //     var selectedEndDate = $('input#product_sr_date_filter')
            //         .data('daterangepicker')
            //         .endDate.format('YYYY-MM-DD');

            //     // If selected start and end dates are today or yesterday, use specific time range
            //     if (selectedStartDate === currentDate) {
            //         start = moment().startOf('day').format('YYYY-MM-DD HH:mm'); // Today's start time (00:00:00)
            //         end = moment().endOf('day').format('YYYY-MM-DD HH:mm'); // Today's end time (23:59:59)
            //     } else if (selectedStartDate === yesterdayDate) {
            //         start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's start time (00:00:00)
            //         end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's end time (23:59:59)
            //     } else {
            //         start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
            //         end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
            //     }
            // }
            // d.start_date = start;
            // d.end_date = end;

            if ($('#product_sr_date_filter').val()) {
                var selectedStartDate = $('input#product_sr_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                var selectedEndDate = $('input#product_sr_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');

                var start_time = "00:00:00";
                var end_time = "23:59:59";

                var start, end;

                if (selectedStartDate === currentDate) {
                    start = moment().startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's start time (00:00:00)
                    end = moment().endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's end time (23:59:59)
                } else if (selectedStartDate === yesterdayDate) {
                    start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's start time (00:00:00)
                    end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's end time (23:59:59)
                } else {
                    start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');

                    if (selectedStartDate === selectedEndDate) {
                        end = moment(selectedEndDate).endOf('day').format('YYYY-MM-DD HH:mm:ss');
                    } else {
                        end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');
                    }
                }

                d.start_date = start;
                d.end_date = end;
            }



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
            { data: 'sub_category', name: 'c2.name' },
            { data: 'product_gender', name: 'p.gender' },
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
                    // if (selectedStartDate === currentDate) {
                    //     start = moment().startOf('day').format('YYYY-MM-DD HH:mm'); // Today's start time (00:00:00)
                    //     end = moment().endOf('day').format('YYYY-MM-DD HH:mm'); // Today's end time (23:59:59)
                    // } else if (selectedStartDate === yesterdayDate) {
                    //     start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's start time (00:00:00)
                    //     end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's end time (23:59:59)
                    // } else {
                    //     start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    //     end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    // }

                    var start_time = "00:00:00";
                    var end_time = "23:59:59";

                    var start, end;

                    if (selectedStartDate === currentDate) {
                        start = moment().startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's start time (00:00:00)
                        end = moment().endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's end time (23:59:59)
                    } else if (selectedStartDate === yesterdayDate) {
                        start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's start time (00:00:00)
                        end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's end time (23:59:59)
                    } else {
                        start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');

                        if (selectedStartDate === selectedEndDate) {
                            end = moment(selectedEndDate).endOf('day').format('YYYY-MM-DD HH:mm:ss');
                        } else {
                            end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');
                        }
                    }
                }
                d.start_date = start;
                d.end_date = end;

                d.variation_id = $('#variation_id').val();
                d.customer_id = $('select#customer_id').val();
                d.location_id = $('select#location_id').val();
                console.log(d.location_id);
            },
        },
        columns: [
            // { data: 'product_image', name: 'product_image' },
            { data: 'category_name', name: 'category_name' },
            { data: 'total_qty_sold', name: 'total_qty_sold', searchable: false },
            { data: 'subtotal', name: 'subtotal', searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            $('#footer_grouped_category_subtotal').text(
                sum_table_col($('#category_wise_sale'), 'row_subtotal')
            );
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

                    var start_time = "00:00:00";
                    var end_time = "23:59:59";

                    var start, end;

                    if (selectedStartDate === currentDate) {
                        start = moment().startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's start time (00:00:00)
                        end = moment().endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's end time (23:59:59)
                    } else if (selectedStartDate === yesterdayDate) {
                        start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's start time (00:00:00)
                        end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's end time (23:59:59)
                    } else {
                        start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');

                        if (selectedStartDate === selectedEndDate) {
                            end = moment(selectedEndDate).endOf('day').format('YYYY-MM-DD HH:mm:ss');
                        } else {
                            end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');
                        }
                    }

                    // if (selectedStartDate === currentDate) {
                    //     start = moment().startOf('day').format('YYYY-MM-DD HH:mm'); // Today's start time (00:00:00)
                    //     end = moment().endOf('day').format('YYYY-MM-DD HH:mm'); // Today's end time (23:59:59)
                    // } else if (selectedStartDate === yesterdayDate) {
                    //     start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's start time (00:00:00)
                    //     end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's end time (23:59:59)
                    // } else {
                    //     start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    //     end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    // }
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
            { data: 'sub_category', name: 'c2.name' },
            { data: 'product_gender', name: 'p.gender' },
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


    product_sell_grouped_report_international = $('table#product_sell_grouped_report_table_returned_international').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[1, 'desc']],
        ajax: {
            url: '/reports/product-sell-grouped-report-detailed-returns-international',
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

                    var start_time = "00:00:00";
                    var end_time = "23:59:59";

                    var start, end;

                    if (selectedStartDate === currentDate) {
                        start = moment().startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's start time (00:00:00)
                        end = moment().endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's end time (23:59:59)
                    } else if (selectedStartDate === yesterdayDate) {
                        start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's start time (00:00:00)
                        end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's end time (23:59:59)
                    } else {
                        start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');

                        if (selectedStartDate === selectedEndDate) {
                            end = moment(selectedEndDate).endOf('day').format('YYYY-MM-DD HH:mm:ss');
                        } else {
                            end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');
                        }
                    }
                    // if (selectedStartDate === currentDate) {
                    //     start = moment().startOf('day').format('YYYY-MM-DD HH:mm'); // Today's start time (00:00:00)
                    //     end = moment().endOf('day').format('YYYY-MM-DD HH:mm'); // Today's end time (23:59:59)
                    // } else if (selectedStartDate === yesterdayDate) {
                    //     start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's start time (00:00:00)
                    //     end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's end time (23:59:59)
                    // } else {
                    //     start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    //     end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    // }
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
            { data: 'sub_category', name: 'c2.name' },
            { data: 'product_gender', name: 'p.gender' },
            { data: 'sub_sku', name: 'v.sub_sku' },
            { data: 'total_qty_sold', name: 'total_qty_sold', searchable: false },
            { data: 'subtotal', name: 'subtotal', searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            $('#footer_grouped_subtotal_return_international').text(
                sum_table_col($('#product_sell_grouped_report_table_returned_international'), 'row_subtotal')
            );
            $('#footer_total_grouped_sold_return_international').html(
                __sum_stock($('#product_sell_grouped_report_table_returned_international'), 'sell_qty')
            );
            __currency_convert_recursively($('#product_sell_grouped_report_table_returned_international'));
        },
    });
// Product sell reutrn category
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

                    var start_time = "00:00:00";
                    var end_time = "23:59:59";

                    var start, end;

                    if (selectedStartDate === currentDate) {
                        start = moment().startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's start time (00:00:00)
                        end = moment().endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's end time (23:59:59)
                    } else if (selectedStartDate === yesterdayDate) {
                        start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's start time (00:00:00)
                        end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's end time (23:59:59)
                    } else {
                        start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');

                        if (selectedStartDate === selectedEndDate) {
                            end = moment(selectedEndDate).endOf('day').format('YYYY-MM-DD HH:mm:ss');
                        } else {
                            end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');
                        }
                    }
                    // if (selectedStartDate === currentDate) {
                    //     start = moment().startOf('day').format('YYYY-MM-DD HH:mm'); // Today's start time (00:00:00)
                    //     end = moment().endOf('day').format('YYYY-MM-DD HH:mm'); // Today's end time (23:59:59)
                    // } else if (selectedStartDate === yesterdayDate) {
                    //     start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's start time (00:00:00)
                    //     end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's end time (23:59:59)
                    // } else {
                    //     start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    //     end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    // }
                }
                d.start_date = start;
                d.end_date = end;

                d.variation_id = $('#variation_id').val();
                d.customer_id = $('select#customer_id').val();
                d.location_id = $('select#location_id').val();
            },
        },
        columns: [
            // { data: 'product_image', name: 'product_image' },
            { data: 'category_name', name: 'category_name' },
            { data: 'total_qty_sold', name: 'total_qty_sold', searchable: false },
            { data: 'subtotal', name: 'subtotal', searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            $('#footer_total_grouped_sold_return_category_subtotal').text(
                sum_table_col($('#category_wise_return'), 'row_subtotal')
            );
            $('#footer_total_grouped_sold_return_category').html(
                __sum_stock($('#category_wise_return'), 'sell_qty')
            );
            __currency_convert_recursively($('#category_wise_return'));
        },
    });

    product_sell_grouped_report_return_category_international = $('table#category_wise_return_international').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[1, 'desc']],
        ajax: {
            url: '/reports/product-sell-grouped-report-detailed-returns-category-international',
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

                    var start_time = "00:00:00";
                    var end_time = "23:59:59";

                    var start, end;

                    if (selectedStartDate === currentDate) {
                        start = moment().startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's start time (00:00:00)
                        end = moment().endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's end time (23:59:59)
                    } else if (selectedStartDate === yesterdayDate) {
                        start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's start time (00:00:00)
                        end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's end time (23:59:59)
                    } else {
                        start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');

                        if (selectedStartDate === selectedEndDate) {
                            end = moment(selectedEndDate).endOf('day').format('YYYY-MM-DD HH:mm:ss');
                        } else {
                            end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');
                        }
                    }
                    // if (selectedStartDate === currentDate) {
                    //     start = moment().startOf('day').format('YYYY-MM-DD HH:mm'); // Today's start time (00:00:00)
                    //     end = moment().endOf('day').format('YYYY-MM-DD HH:mm'); // Today's end time (23:59:59)
                    // } else if (selectedStartDate === yesterdayDate) {
                    //     start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's start time (00:00:00)
                    //     end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's end time (23:59:59)
                    // } else {
                    //     start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    //     end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    // }
                }
                d.start_date = start;
                d.end_date = end;

                d.variation_id = $('#variation_id').val();
                d.customer_id = $('select#customer_id').val();
                d.location_id = $('select#location_id').val();
            },
        },
        columns: [
            // { data: 'product_image', name: 'product_image' },
            { data: 'category_name', name: 'category_name' },
            { data: 'total_qty_sold', name: 'total_qty_sold', searchable: false },
            { data: 'subtotal', name: 'subtotal', searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            $('#footer_total_grouped_sold_return_category_subtotal_international').text(
                sum_table_col($('#category_wise_return_international'), 'row_subtotal')
            );
            $('#footer_total_grouped_sold_return_category_international').html(
                __sum_stock($('#category_wise_return_international'), 'sell_qty')
            );
            __currency_convert_recursively($('#category_wise_return_international'));
        },
    });

    
    // $.ajax({
    //         type: "GET",
    //         url: "/reports/detailed_product_category",
    //         dataType: 'json',
    //         data: data,

    //         success: function (response) {
    //             console.log(data);
    //             var returnItemsFloat = parseFloat(response.return_items); // Convert float to integer
    //             var soldItemsFloat = parseFloat(response.total_item_sold); // Convert float to integer
    //             var giftItemsFloat = parseFloat(response.total_gift_items); // Convert float to integer

    //             $("#return-invoices").html(response.return_invoices);
    //             $("#return-amount").html(__currency_trans_from_en(response.return_amount));
    //             $("#return-items").html(returnItemsFloat);
    //             $("#total-items-sold").html(soldItemsFloat);
    //             $("#invoice-amount").html(__currency_trans_from_en(response.invoice_amount));
    //             $("#purchase-amount").html(__currency_trans_from_en(response.buy_price));
    //             $("#discount").html(__currency_trans_from_en(response.total_sell_discount));
    //             $("#cash-payment").html(__currency_trans_from_en(response.cash_amount));
    //             $("#card-payment").html(__currency_trans_from_en(response.card_amount));
    //             $("#total-received").html(__currency_trans_from_en(response.total_received));
    //             $("#profit-loss").html(__currency_trans_from_en(response.profit_loss));
    //             // $("#total-gift-amount").html(__currency_trans_from_en(response.total_gift_amount));
    //             $("#total-gift-amount").html(__currency_trans_from_en(response.amount));
    //             $("#total-gift-items").html(giftItemsFloat);
    //             $("#gst-tax").html(__currency_trans_from_en(response.gst_tax));
    //         },
    //     });


    // detail_product_and_category = $('table#product_and_category_table').DataTable({
    //     processing: true,
    //     serverSide: true,
    //     aaSorting: [[1, 'desc']],
    //     ajax: {
    //         url: '/reports/detailed_product_category',
    //         data: function(d) {
    //             var start = '';
    //             var end = '';
    //             var start_time = $('#product_sr_start_time').val();
    //             var end_time = $('#product_sr_end_time').val();
    //             var currentDate = moment().format('YYYY-MM-DD'); // Get current date in 'YYYY-MM-DD' format
    //             var yesterdayDate = moment().subtract(1, 'days').format('YYYY-MM-DD'); // Get yesterday's date in 'YYYY-MM-DD' format

    //             if ($('#product_sr_date_filter').val()) {
    //                 var selectedStartDate = $('input#product_sr_date_filter')
    //                     .data('daterangepicker')
    //                     .startDate.format('YYYY-MM-DD');
    //                 var selectedEndDate = $('input#product_sr_date_filter')
    //                     .data('daterangepicker')
    //                     .endDate.format('YYYY-MM-DD');

    //                 // If selected start and end dates are today or yesterday, use specific time range
    //                 var start_time = "00:00:00";
    //                 var end_time = "23:59:59";

    //                 var start, end;

    //                 if (selectedStartDate === currentDate) {
    //                     start = moment().startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's start time (00:00:00)
    //                     end = moment().endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's end time (23:59:59)
    //                 } else if (selectedStartDate === yesterdayDate) {
    //                     start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's start time (00:00:00)
    //                     end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's end time (23:59:59)
    //                 } else {
    //                     start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');

    //                     if (selectedStartDate === selectedEndDate) {
    //                         end = moment(selectedEndDate).endOf('day').format('YYYY-MM-DD HH:mm:ss');
    //                     } else {
    //                         end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');
    //                     }
    //                 }
    //                 // if (selectedStartDate === currentDate) {
    //                 //     start = moment().startOf('day').format('YYYY-MM-DD HH:mm'); // Today's start time (00:00:00)
    //                 //     end = moment().endOf('day').format('YYYY-MM-DD HH:mm'); // Today's end time (23:59:59)
    //                 // } else if (selectedStartDate === yesterdayDate) {
    //                 //     start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's start time (00:00:00)
    //                 //     end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's end time (23:59:59)
    //                 // } else {
    //                 //     start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
    //                 //     end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
    //                 // }
    //             }
    //             d.start_date = start;
    //             d.end_date = end;

    //             d.variation_id = $('#variation_id').val();
    //             d.customer_id = $('select#customer_id').val();
    //             d.location_id = $('select#location_id').val();
    //         },
    //     },
    //     columns: [
    //         { data: 'product_image', name: 'product_image' },
    //         { data: 'category_name', name: 'category_name' },
    //         // { data: 'sub_category', name: 'sub_category' },
    //         { data: 'total_qty_sold', name: 'total_qty_sold', searchable: false },
    //         { data: 'total_qty_returned', name: 'total_qty_returned', searchable: false },
    //         { data: 'total_net_qty', name: 'total_net_qty', searchable: false },
    //         { data: 'sale_value', name: 'sale_value', searchable: false },
    //         { data: 'return_value', name: 'return_value', searchable: false },
    //         { data: 'subtotal', name: 'subtotal', searchable: false },
            
    //     ],
            
    //     fnDrawCallback: function(oSettings) {
    //         $('#sale_value').text(
    //             sum_table_col($('#product_and_category_table'), 'sale_value')
    //         );
    //         $('#return_value').text(
    //             sum_table_col($('#product_and_category_table'), 'return_value')
    //         );
    //         $('#total_sold').html(
    //             __sum_stock($('#product_and_category_table'), 'total_qty_sold')
    //         );
    //         $('#total_returned').html(
    //             __sum_stock($('#product_and_category_table'), 'total_qty_returned')
    //         );
    //         $('#net_unit').html(
    //             __sum_stock($('#product_and_category_table'), 'total_net_qty')
    //         );
    //         $('#net_value').text(
    //             sum_table_col($('#product_and_category_table'), 'subtotal')
    //         );

    //         __currency_convert_recursively($('#product_and_category_table'));
    //     },
    //     // fnDrawCallback: function(oSettings) {
    //     //     $('#footer_total_grouped_sold_return_category').html(
    //     //         __sum_stock($('#category_wise_return'), 'sell_qty')
    //     //     );
    //     //     __currency_convert_recursively($('#category_wise_return'));
    //     // },
    // });



    
    function fetchCategories() {
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

        var start_time = "00:00:00";
        var end_time = "23:59:59";

        if (selectedStartDate === currentDate) {
            start = moment().startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's start time (00:00:00)
            end = moment().endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's end time (23:59:59)
        } else if (selectedStartDate === yesterdayDate) {
            start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's start time (00:00:00)
            end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's end time (23:59:59)
        } else {
            start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');

            if (selectedStartDate === selectedEndDate) {
                end = moment(selectedEndDate).endOf('day').format('YYYY-MM-DD HH:mm:ss');
            } else {
                end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');
            }
        }
    }

    $.ajax({
        url: '/reports/detailed_product_category', // Adjust the URL if needed
        method: 'GET',
        data: {
            start_date: start,
            end_date: end,
            variation_id: $('#variation_id').val(),
            customer_id: $('select#customer_id').val(),
            location_id: $('select#location_id').val()
        },
        success: function(response) {
            console.log(response); // Log the response
            populateCategoryTable(response.categories, response.total_quantity_sold, response.total_quantity_return, response.total_sale_values, response.total_return_values);
        },
        error: function(xhr, status, error) {
            console.error(error);
        }
    });
}

function populateCategoryTable(categories, total_quantity_sold, total_quantity_return, total_sale_values, total_return_values) {
    // var tbody = $('#product_and_category_table tbody');
    // tbody.empty(); // Clear any existing rows

    var tbody = $('#product_and_category_table tbody');
    var tfoot = $('#product_and_category_table tfoot');
    tbody.empty(); // Clear any existing rows
    tfoot.empty(); // Clear any existing footer rows

    console.log('Categories:', categories);
    console.log('Total Quantity Sold:', total_quantity_sold);
    console.log('Total Sale:', total_sale_values);

    // Initialize sums for each column
    var totalQtySoldSum = 0;
    var totalQtyReturnSum = 0;
    var netQtySum = 0;
    var saleValueSum = 0;
    var returnValueSum = 0;
    var netTotalSum = 0;

    categories.forEach(function(category) {
        console.log('Processing category:', category);

        var total_qty_sold = total_quantity_sold[category.name] || '0.00';
        var total_qty_return = total_quantity_return[category.name] || '0.00';
        var net_quantity = total_qty_sold - total_qty_return;
        // var sale_value = total_sale_values[category.name] || '0.00';
        var sale_value = total_sale_values[category.name];
        var return_value = total_return_values[category.name];
        // total_return_values
        // var sale_value = total_sale_values[category.name];
        if (!isNaN(parseFloat(sale_value)) && isFinite(sale_value)) {
            sale_value = parseFloat(sale_value).toFixed(2);
        } else {
            sale_value = '0.00';
        }

        if (!isNaN(parseFloat(total_qty_sold)) && isFinite(total_qty_sold)) {
            total_qty_sold = parseFloat(total_qty_sold).toFixed(2);
        } else {
            total_qty_sold = '0.00';
        }

        if (!isNaN(parseFloat(total_qty_return)) && isFinite(total_qty_return)) {
            total_qty_return = parseFloat(total_qty_return).toFixed(2);
        } else {
            total_qty_return = '0.00';
        }

        if (!isNaN(parseFloat(return_value)) && isFinite(return_value)) {
            return_value = parseFloat(return_value).toFixed(2);
        } else {
            return_value = '0.00';
        }

        var net_total = sale_value - return_value;

        totalQtySoldSum += parseFloat(total_qty_sold);
        totalQtyReturnSum += parseFloat(total_qty_return);
        netQtySum += parseFloat(net_quantity);
        saleValueSum += parseFloat(sale_value);
        returnValueSum += parseFloat(return_value);
        netTotalSum += parseFloat(net_total);


        var row = '<tr>' +
            '<td>' + category.name + '</td>' +
            '<td>' + total_qty_sold + '</td>' +
            '<td>' + total_qty_return + '</td>' +
            '<td>' + net_quantity.toFixed(2) + '</td>' +
            '<td>' + sale_value + '</td>' +
            '<td>' + return_value + '</td>' +
            '<td>' + net_total.toFixed(2) + '</td>' +
            '</tr>';
        tbody.append(row);
    });
var footerRow = '<tr class="bg-gray font-17 footer-total text-center">' +
        '<td><strong>Total</strong></td>' +
        '<td><strong>' + totalQtySoldSum.toFixed(2) + '</strong></td>' +
        '<td><strong>' + totalQtyReturnSum.toFixed(2) + '</strong></td>' +
        '<td><strong>' + netQtySum.toFixed(2) + '</strong></td>' +
        '<td><strong>' + saleValueSum.toFixed(2) + '</strong></td>' +
        '<td><strong>' + returnValueSum.toFixed(2) + '</strong></td>' +
        '<td><strong>' + netTotalSum.toFixed(2) + '</strong></td>' +
        '</tr>';
        tfoot.append(footerRow);
}


// function populateCategoryTable(categories, total_quantity_sold) {
//     var tbody = $('#product_and_category_table tbody');
//     tbody.empty(); // Clear any existing rows

//     console.log('Categories:', categories);
//     console.log('Total Quantity Sold:', total_quantity_sold);

//     categories.forEach(function(category) {
//         console.log('Processing category:', category);

//         var matchingQuantity = total_quantity_sold.find(item => 
//             item.category_id === category.id
//         );
//         console.log('Matching quantity:', matchingQuantity);

//         var total_qty_sold = matchingQuantity ? matchingQuantity.total_qty_sold : '0.0000';

//         var row = '<tr>' +
//             '<td>' + category.name + '</td>' +
//             '<td>' + total_qty_sold + '</td>' +
//             '</tr>';
//         tbody.append(row);
//     });
// }



$(document).ready(function() {
    fetchCategories();
});




//     function fetchData() {
//     var start = '';
//     var end = '';
//     var start_time = $('#product_sr_start_time').val();
//     var end_time = $('#product_sr_end_time').val();
//     var currentDate = moment().format('YYYY-MM-DD');
//     var yesterdayDate = moment().subtract(1, 'days').format('YYYY-MM-DD');

//     if ($('#product_sr_date_filter').val()) {
//         var selectedStartDate = $('input#product_sr_date_filter')
//             .data('daterangepicker')
//             .startDate.format('YYYY-MM-DD');
//         var selectedEndDate = $('input#product_sr_date_filter')
//             .data('daterangepicker')
//             .endDate.format('YYYY-MM-DD');

//         start_time = "00:00:00";
//         end_time = "23:59:59";

//         if (selectedStartDate === currentDate) {
//             start = moment().startOf('day').format('YYYY-MM-DD HH:mm:ss');
//             end = moment().endOf('day').format('YYYY-MM-DD HH:mm:ss');
//         } else if (selectedStartDate === yesterdayDate) {
//             start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm:ss');
//             end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm:ss');
//         } else {
//             start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');
//             end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');
//         }
//     }

//     var data = {
//         start_date: start,
//         end_date: end,
//         variation_id: $('#variation_id').val(),
//         customer_id: $('select#customer_id').val(),
//         location_id: $('select#location_id').val()
//     };

//     $.ajax({
//         url: '/reports/detailed_product_category',
//         method: 'GET',
//         data: data,
//         success: function(response) {
//             populateTable(response.data);
//             updateSummary(response.data);
//         },
//         error: function(xhr, status, error) {
//             console.error(error);
//         }
//     });
// }

// function populateTable(data) {
//     var tbody = $('#product_and_category_table tbody');
//     tbody.empty();

//     data.forEach(function(item) {
//         var row = '<tr>' +
//             '<td>' + item.product_image + '</td>' +
//             '<td>' + item.category_name + '</td>' +
//             '<td>' + item.total_qty_sold + '</td>' +
//             '<td>' + item.total_qty_returned + '</td>' +
//             '<td>' + item.total_net_qty + '</td>' +
//             '<td>' + item.sale_value + '</td>' +
//             '<td>' + item.return_value + '</td>' +
//             '<td>' + item.subtotal + '</td>' +
//             '</tr>';
//         tbody.append(row);
//     });
// }

// function updateSummary(data) {
//     var sale_value = 0;
//     var return_value = 0;
//     var total_sold = 0;
//     var total_returned = 0;
//     var total_net_qty = 0;
//     var net_value = 0;

//     data.forEach(function(item) {
//         sale_value += parseFloat(item.sale_value);
//         return_value += parseFloat(item.return_value);
//         total_sold += parseFloat(item.total_qty_sold);
//         total_returned += parseFloat(item.total_qty_returned);
//         total_net_qty += parseFloat(item.total_net_qty);
//         net_value += parseFloat(item.subtotal);
//     });

//     $('#sale_value').text(sale_value.toFixed(2));
//     $('#return_value').text(return_value.toFixed(2));
//     $('#total_sold').text(total_sold);
//     $('#total_returned').text(total_returned);
//     $('#net_unit').text(total_net_qty);
//     $('#net_value').text(net_value.toFixed(2));
// }

// $(document).ready(function() {
//     $('#product_sr_date_filter').on('apply.daterangepicker', function() {
//         fetchData();
//     });

//     fetchData(); // Initial fetch
// });



    detail_product_and_category_international = $('table#product_and_category_international_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[1, 'desc']],
        ajax: {
            url: '/reports/detailed_product_category_international',
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
                    var start_time = "00:00:00";
                    var end_time = "23:59:59";

                    var start, end;

                    if (selectedStartDate === currentDate) {
                        start = moment().startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's start time (00:00:00)
                        end = moment().endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Today's end time (23:59:59)
                    } else if (selectedStartDate === yesterdayDate) {
                        start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's start time (00:00:00)
                        end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm:ss'); // Yesterday's end time (23:59:59)
                    } else {
                        start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');

                        if (selectedStartDate === selectedEndDate) {
                            end = moment(selectedEndDate).endOf('day').format('YYYY-MM-DD HH:mm:ss');
                        } else {
                            end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD HH:mm:ss").format('YYYY-MM-DD HH:mm:ss');
                        }
                    }
                    // if (selectedStartDate === currentDate) {
                    //     start = moment().startOf('day').format('YYYY-MM-DD HH:mm'); // Today's start time (00:00:00)
                    //     end = moment().endOf('day').format('YYYY-MM-DD HH:mm'); // Today's end time (23:59:59)
                    // } else if (selectedStartDate === yesterdayDate) {
                    //     start = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's start time (00:00:00)
                    //     end = moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm'); // Yesterday's end time (23:59:59)
                    // } else {
                    //     start = moment(selectedStartDate + " " + start_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    //     end = moment(selectedEndDate + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    // }
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
            // { data: 'sub_category', name: 'sub_category' },
            { data: 'total_qty_sold', name: 'total_qty_sold', searchable: false },
            { data: 'total_qty_returned', name: 'total_qty_returned', searchable: false },
            { data: 'total_net_qty', name: 'total_net_qty', searchable: false },
            { data: 'sale_value', name: 'sale_value', searchable: false },
            { data: 'return_value', name: 'return_value', searchable: false },
            { data: 'subtotal', name: 'subtotal', searchable: false },
            
        ],
            
        fnDrawCallback: function(oSettings) {
            $('#sale_value_int').text(
                sum_table_col($('#product_and_category_international_table'), 'sale_value')
            );
            $('#return_value_int').text(
                sum_table_col($('#product_and_category_international_table'), 'return_value')
            );
            $('#total_sold_int').html(
                __sum_stock($('#product_and_category_international_table'), 'total_qty_sold')
            );
            $('#total_returned_int').html(
                __sum_stock($('#product_and_category_international_table'), 'total_qty_returned')
            );
            $('#net_unit_int').html(
                __sum_stock($('#product_and_category_international_table'), 'total_net_qty')
            );
            $('#net_value_int').text(
                sum_table_col($('#product_and_category_international_table'), 'subtotal')
            );

            __currency_convert_recursively($('#product_and_category_international_table'));
        },
        // fnDrawCallback: function(oSettings) {
        //     $('#footer_total_grouped_sold_return_category').html(
        //         __sum_stock($('#category_wise_return'), 'sell_qty')
        //     );
        //     __currency_convert_recursively($('#category_wise_return'));
        // },
    });

    $(
        '#product_sell_report_form #variation_id, #product_sell_report_form #location_id, #product_sell_report_form #customer_id'
    ).change(function() {
        product_sell_grouped_report.ajax.reload();
        product_sell_grouped_report_2nd.ajax.reload();
        product_sell_grouped_report_category.ajax.reload();
        product_sell_grouped_report_return_category.ajax.reload();
        // detail_product_and_category.ajax.reload();
        fetchCategories();
        detail_product_and_category_international.ajax.reload();
        product_sell_grouped_report_return_category_international.ajax.reload();
        product_sell_grouped_report_international.ajax.reload();

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
    });
    </script>
@endsection