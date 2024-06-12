@extends('layouts.app')
@section('title', __('Ecommerce Sell Report'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('Ecommerce Sell Report')
        </h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="print_section">
            <h2>{{ session()->get('business.name') }} - @lang('Ecommerce Sell Report')</h2>
        </div>

        @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('sell_list_filter_location_id', __('purchase.business_location') . ':') !!}

                    {!! Form::select('sell_list_filter_location_id', $business_locations, null, [
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                        'placeholder' => __('lang_v1.all'),
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('sell_list_filter_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('sell_list_filter_date_range', null, [
                        'placeholder' => __('lang_v1.select_a_date_range'),
                        'class' => 'form-control',
                        'readonly',
                    ]) !!}
                </div>
            </div>
        @endcomponent
        @component('components.widget', ['class' => 'box-primary', 'title' => __('Ecommerce Sell Report')])
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id=" ecommerce_overview_table">
                    <thead>
                        <tr>
                            <th>Sr.</th>
                            <th>Date</th>
                            <th>Order ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>City</th>
                            <th>Gender</th>
                            <th>Article</th>
                            <th>Remaining (Name Wise)</th>
                            <th>Remaining (Color Wise)</th>
                            <th>Actual Price</th>
                            <th>Discount %</th>
                            <th>Discount Amount</th>
                            <th>Sell Price</th>
                            <th>Branch</th>
                            <th>Payment Type</th>
                            <th>Image</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
                    </tfoot>
                </table>
            </div>
        @endcomponent
    </section>
    <!-- /.content -->
@stop
@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            $('#profit_tabs_filter_overview').daterangepicker(dateRangeSettings, function(start, end) {
                $('#profit_tabs_filter_overview span').html(
                    start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                );
                $('.nav-tabs li.active').find('a[data-toggle="tab"]').trigger('shown.bs.tab');
            });
            $('#profit_tabs_filter_overview').on('cancel.daterangepicker', function(ev, picker) {
                $('#profit_tabs_filter_overview').html(
                    '<i class="fa fa-calendar"></i> ' + LANG.filter_by_date
                );
                $('.nav-tabs li.active').find('a[data-toggle="tab"]').trigger('shown.bs.tab');
            });

            function updateOverView() {
                var start = $('#overview_date_filter')
                    .data('daterangepicker')
                    .startDate.format('YYYY-MM-DD');
                var end = $('#overview_date_filter')
                    .data('daterangepicker')
                    .endDate.format('YYYY-MM-DD');
                var location_id = $('#profit_loss_location_filter').val();

                var data = {
                    start_date: start,
                    end_date: end,
                    location_id: location_id
                };

                var loader = __fa_awesome();
                $('.total_purchase').html(loader);
                $('.purchase_due').html(loader);
                $('.total_sell').html(loader);
                $('.invoice_due').html(loader);
                $('.purchase_return_inc_tax').html(loader);
                $('.total_sell_return').html(loader);



                $.ajax({
                    type: "GET",
                    url: "/reports/get-sell-overview",
                    dataType: 'json',
                    data: data,

                    success: function(response) {
                        var returnItemsFloat = parseFloat(response
                            .return_items); // Convert float to integer
                        var soldItemsFloat = parseFloat(response
                            .total_item_sold); // Convert float to integer
                        var giftItemsFloat = parseFloat(response
                            .total_gift_items); // Convert float to integer

                        $("#return-invoices").html(response.return_invoices);
                        $("#return-amount").html(__currency_trans_from_en(response.return_amount));
                        $("#return-items").html(returnItemsFloat);
                        $("#total-items-sold").html(soldItemsFloat);
                        $("#invoice-amount").html(__currency_trans_from_en(response.invoice_amount));
                        $("#purchase-amount").html(__currency_trans_from_en(response.buy_price));
                        $("#discount").html(__currency_trans_from_en(response.total_sell_discount));
                        $("#cash-payment").html(__currency_trans_from_en(response.cash_amount));
                        $("#card-payment").html(__currency_trans_from_en(response.card_amount));
                        $("#total-received").html(__currency_trans_from_en(response.total_received));
                        $("#profit-loss").html(__currency_trans_from_en(response.profit_loss));
                        // $("#total-gift-amount").html(__currency_trans_from_en(response.total_gift_amount));
                        $("#total-gift-amount").html(__currency_trans_from_en(response.amount));
                        $("#total-gift-items").html(giftItemsFloat);
                        $("#gst-tax").html(__currency_trans_from_en(response.gst_tax));
                    },
                });
            }

            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                var target = $(e.target).attr('href');
                if (target == '#buy_overview') {
                    if (typeof profit_by_categories_datatable == 'undefined') {
                        profit_by_categories_datatable = $.ajax({
                            type: "GET",
                            url: "/reports/get-buy-overview",
                            data: function(d) {
                                d.start_date = $('#profit_tabs_filter_overview')
                                    .data('daterangepicker')
                                    .startDate.format('YYYY-MM-DD');
                                d.end_date = $('#profit_tabs_filter_overview')
                                    .data('daterangepicker')
                                    .endDate.format('YYYY-MM-DD');
                                d.location_id = $('#profit_loss_location_filter').val();
                            },
                            success: function(response) {
                                $("#buy-total-items").html(response.total_items);
                                $("#buy-amount").html(__currency_trans_from_en(response
                                    .total_buy_amount));
                                $("#total-cost-amount").html(__currency_trans_from_en(response
                                    .total_cost_amount));
                                $("#total-purchase-amount").html(__currency_trans_from_en(
                                    response.total_purchase_amount));
                            },
                        });
                    } else {
                        profit_by_categories_datatable.reload();
                    }
                } else if (target == '#ecommerce') {
                    if (typeof profit_by_brands_datatable == 'undefined') {
                        profit_by_brands_datatable = $.ajax({
                            type: "GET",
                            url: "/reports/get-ecommerce-overview",
                            data: function(d) {
                                d.start_date = $('#profit_tabs_filter_overview')
                                    .data('daterangepicker')
                                    .startDate.format('YYYY-MM-DD');
                                d.end_date = $('#profit_tabs_filter_overview')
                                    .data('daterangepicker')
                                    .endDate.format('YYYY-MM-DD');
                            },
                            success: function(response) {
                                $("#total-orders").html(response.total_orders);
                                $("#total-ecommerce-items").html(response.total_items);
                                $("#new-orders").html(response.new_orders);
                                $("#total-received-amount").html(__currency_trans_from_en(
                                    response.total_received_amount));
                                $("#total-order-amount").html(__currency_trans_from_en(response
                                    .total_order_amount));
                                $("#ecommerce-profit-loss").html(__currency_trans_from_en(
                                    response.profit_loss));
                                $("#completed-orders").html(response.completed_orders);
                                $("#dispatched-orders").html(response.dispatched_order);
                                $("#exchanged-orders").html(response.exchanged_orders);
                                $("#cancelled-items").html(response.cancelled_items);
                                $("#ecommerce-discount-amount").html(__currency_trans_from_en(
                                    response.discount_amount));
                                $("#cancelled-orders").html(response.cancelled_orders);
                                $("#cancelled-orders-amount").html(__currency_trans_from_en(
                                    response.cancelled_order_amount));
                                $("#exchanged-orders-amount").html(__currency_trans_from_en(
                                    response.exchanged_order_amount));
                                $("#completed-orders-amount").html(__currency_trans_from_en(
                                    response.completed_order_amount));

                            },
                        });
                    } else {
                        profit_by_brands_datatable.reload();
                    }
                } else if (target == '#profit_by_locations') {
                    if (typeof profit_by_locations_datatable == 'undefined') {
                        profit_by_locations_datatable = $('#profit_by_locations_table').DataTable({
                            processing: true,
                            serverSide: true,
                            "ajax": {
                                "url": "/reports/get-profit/location",
                                "data": function(d) {
                                    d.start_date = $('#profit_tabs_filter_overview')
                                        .data('daterangepicker')
                                        .startDate.format('YYYY-MM-DD');
                                    d.end_date = $('#profit_tabs_filter_overview')
                                        .data('daterangepicker')
                                        .endDate.format('YYYY-MM-DD');
                                }
                            },
                            columns: [{
                                    data: 'location',
                                    name: 'L.name'
                                },
                                {
                                    data: 'gross_profit',
                                    "searchable": false
                                },
                            ],
                            fnDrawCallback: function(oSettings) {
                                var total_profit = sum_table_col($(
                                    '#profit_by_locations_table'), 'gross-profit');
                                $('#profit_by_locations_table .footer_total').text(
                                    total_profit);

                                __currency_convert_recursively($('#profit_by_locations_table'));
                            },
                        });
                    } else {
                        profit_by_locations_datatable.ajax.reload();
                    }
                } else if (target == '#profit_by_invoice') {
                    if (typeof profit_by_invoice_datatable == 'undefined') {
                        profit_by_invoice_datatable = $('#profit_by_invoice_table').DataTable({
                            processing: true,
                            serverSide: true,
                            "ajax": {
                                "url": "/reports/get-profit/invoice",
                                "data": function(d) {
                                    d.start_date = $('#profit_tabs_filter_overview')
                                        .data('daterangepicker')
                                        .startDate.format('YYYY-MM-DD');
                                    d.end_date = $('#profit_tabs_filter_overview')
                                        .data('daterangepicker')
                                        .endDate.format('YYYY-MM-DD');
                                }
                            },
                            columns: [{
                                    data: 'invoice_no',
                                    name: 'sale.invoice_no'
                                },
                                {
                                    data: 'gross_profit',
                                    "searchable": false
                                },
                            ],
                            fnDrawCallback: function(oSettings) {
                                var total_profit = sum_table_col($('#profit_by_invoice_table'),
                                    'gross-profit');
                                $('#profit_by_invoice_table .footer_total').text(total_profit);

                                __currency_convert_recursively($('#profit_by_invoice_table'));
                            },
                        });
                    } else {
                        profit_by_invoice_datatable.ajax.reload();
                    }
                } else if (target == '#profit_by_date') {
                    if (typeof profit_by_date_datatable == 'undefined') {
                        profit_by_date_datatable = $('#profit_by_date_table').DataTable({
                            processing: true,
                            serverSide: true,
                            "ajax": {
                                "url": "/reports/get-profit/date",
                                "data": function(d) {
                                    d.start_date = $('#profit_tabs_filter_overview')
                                        .data('daterangepicker')
                                        .startDate.format('YYYY-MM-DD');
                                    d.end_date = $('#profit_tabs_filter_overview')
                                        .data('daterangepicker')
                                        .endDate.format('YYYY-MM-DD');
                                }
                            },
                            columns: [{
                                    data: 'transaction_date',
                                    name: 'sale.transaction_date'
                                },
                                {
                                    data: 'gross_profit',
                                    "searchable": false
                                },
                            ],
                            fnDrawCallback: function(oSettings) {
                                var total_profit = sum_table_col($('#profit_by_date_table'),
                                    'gross-profit');
                                $('#profit_by_date_table .footer_total').text(total_profit);
                                __currency_convert_recursively($('#profit_by_date_table'));
                            },
                        });
                    } else {
                        profit_by_date_datatable.ajax.reload();
                    }
                } else if (target == '#profit_by_customer') {
                    if (typeof profit_by_customers_table == 'undefined') {
                        profit_by_customers_table = $('#profit_by_customer_table').DataTable({
                            processing: true,
                            serverSide: true,
                            "ajax": {
                                "url": "/reports/get-profit/customer",
                                "data": function(d) {
                                    d.start_date = $('#profit_tabs_filter_overview')
                                        .data('daterangepicker')
                                        .startDate.format('YYYY-MM-DD');
                                    d.end_date = $('#profit_tabs_filter_overview')
                                        .data('daterangepicker')
                                        .endDate.format('YYYY-MM-DD');
                                }
                            },
                            columns: [{
                                    data: 'customer',
                                    name: 'CU.name'
                                },
                                {
                                    data: 'gross_profit',
                                    "searchable": false
                                },
                            ],
                            fnDrawCallback: function(oSettings) {
                                var total_profit = sum_table_col($('#profit_by_customer_table'),
                                    'gross-profit');
                                $('#profit_by_customer_table .footer_total').text(total_profit);
                                __currency_convert_recursively($('#profit_by_customer_table'));
                            },
                        });
                    } else {
                        profit_by_customers_table.ajax.reload();
                    }
                } else if (target == '#profit_by_day') {
                    var start_date = $('#profit_tabs_filter_overview')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');

                    var end_date = $('#profit_tabs_filter_overview')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                    var url = '/reports/get-profit/day?start_date=' + start_date + '&end_date=' + end_date;
                    $.ajax({
                        url: url,
                        dataType: 'html',
                        success: function(result) {
                            $('#profit_by_day').html(result);
                            profit_by_days_table = $('#profit_by_day_table').DataTable({
                                "searching": false,
                                'paging': false,
                                'ordering': false,
                            });
                            var total_profit = sum_table_col($('#profit_by_day_table'),
                                'gross-profit');
                            $('#profit_by_day_table .footer_total').text(total_profit);
                            __currency_convert_recursively($('#profit_by_day_table'));
                        },
                    });
                } else if (target == '#profit_by_products') {
                    profit_by_products_table.ajax.reload();
                }
            });
        });
    </script>

@endsection
