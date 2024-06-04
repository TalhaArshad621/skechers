@extends('layouts.app')
@section('title', __( 'Overview Report' ))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'Overview Report' )
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="print_section"><h2>{{session()->get('business.name')}} - @lang( 'Overview Report' )</h2></div>
    
    <div class="row no-print">
        <div class="col-md-3 col-md-offset-7 col-xs-6">
            <div class="input-group">
                <span class="input-group-addon bg-light-blue"><i class="fa fa-map-marker"></i></span>
                 <select class="form-control select2" id="overview_location_filter">
                    @foreach($business_locations as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-2 col-xs-6">
            <div class="form-group pull-right">
                <div class="input-group">
                  <button type="button" class="btn btn-primary" id="overview_date_filter">
                    <span>
                      <i class="fa fa-calendar"></i> {{ __('messages.filter_by_date') }}
                    </span>
                    <i class="fa fa-caret-down"></i>
                  </button>
                </div>
            </div>
        </div>
    </div>
    
{{-- 
    <div class="row no-print">
        <div class="col-sm-12">
            <button type="button" class="btn btn-primary pull-right" 
            aria-label="Print" onclick="window.print();"
            ><i class="fa fa-print"></i> @lang( 'messages.print' )</button>
        </div>
    </div> --}}
    <br>
    {{-- <div class="row no-print">
        <div class="col-xs-12">
            <div class="form-group pull-right">
                <div class="input-group">
                  <button type="button" class="btn btn-primary" id="profit_tabs_filter_overview">
                    <span>
                      <i class="fa fa-calendar"></i> {{ __('messages.filter_by_date') }}
                    </span>
                    <i class="fa fa-caret-down"></i>
                  </button>
                </div>
            </div>
        </div>
    </div> --}}
    <div class="row no-print">
        <div class="col-md-12">
           <!-- Custom Tabs -->
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#sell_overview" data-toggle="tab" aria-expanded="true"><i class="fa fa-cubes" aria-hidden="true"></i> @lang('Sell Overview')</a>
                    </li>
{{-- 
                    <li>
                        <a href="#buy_overview" data-toggle="tab" aria-expanded="true"><i class="fa fa-tags" aria-hidden="true"></i> @lang('Buy Overview')</a>
                    </li> --}}

                    <li>
                        <a href="#ecommerce" data-toggle="tab" aria-expanded="true"><i class="fa fa-diamond" aria-hidden="true"></i> @lang('Ecommerce')</a>
                    </li>

                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="sell_overview"> 
                        @include('report.partials.sell_overview_report')
                    </div>

                    {{-- <div class="tab-pane" id="buy_overview">
                        @include('report.partials.buy_overview_report')
                    </div> --}}

                    <div class="tab-pane" id="ecommerce">
                        @include('report.partials.ecommerce_overview_report')
                    </div>

                </div>
            </div>
        </div>
    </div>
	

</section>
<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>

<script type="text/javascript">
    $(document).ready( function() {
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

        // profit_by_products_table = $('#profit_by_products_table').DataTable({
        //         processing: true,
        //         serverSide: true,
        //         "ajax": {
        //             "url": "/reports/get-profit/product",
        //             "data": function ( d ) {
        //                 d.start_date = $('#profit_tabs_filter_overview')
        //                     .data('daterangepicker')
        //                     .startDate.format('YYYY-MM-DD');
        //                 d.end_date = $('#profit_tabs_filter_overview')
        //                     .data('daterangepicker')
        //                     .endDate.format('YYYY-MM-DD');
        //             }
        //         },
        //         columns: [
        //             { data: 'product', name: 'P.name'  },
        //             { data: 'gross_profit', "searchable": false},
        //         ],
        //         fnDrawCallback: function(oSettings) {
        //             var total_profit = sum_table_col($('#profit_by_products_table'), 'gross-profit');
        //             $('#profit_by_products_table .footer_total').text(total_profit);

        //             __currency_convert_recursively($('#profit_by_products_table'));
        //         },
        //     });


    function updateOverView() {
    var start = $('#overview_date_filter')
        .data('daterangepicker')
        .startDate.format('YYYY-MM-DD');
    var end = $('#overview_date_filter')
        .data('daterangepicker')
        .endDate.format('YYYY-MM-DD');
    var location_id = $('#profit_loss_location_filter').val();

    var data = { start_date: start, end_date: end, location_id: location_id };

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

            success: function (response) {
                var returnItemsFloat = parseFloat(response.return_items); // Convert float to integer
                var soldItemsFloat = parseFloat(response.total_item_sold); // Convert float to integer
                var giftItemsFloat = parseFloat(response.total_gift_items); // Convert float to integer

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

        // $.ajax({
        //     type: "GET",
        //     url: "/reports/get-sell-overview",
        //     data: function ( d ){
        //         d.start_date = $('#profit_tabs_filter_overview')
        //             .data('daterangepicker')
        //             .startDate.format('YYYY-MM-DD');
        //         d.end_date = $('#profit_tabs_filter_overview')
        //             .data('daterangepicker')
        //             .endDate.format('YYYY-MM-DD');
        //         d.location_id = $('#profit_loss_location_filter').val();
        //     },
        //     success: function (response) {
        //         var returnItemsFloat = parseFloat(response.return_items); // Convert float to integer
        //         var soldItemsFloat = parseFloat(response.total_item_sold); // Convert float to integer
        //         var giftItemsFloat = parseFloat(response.total_gift_items); // Convert float to integer

        //         $("#return-invoices").html(response.return_invoices);
        //         $("#return-amount").html(__currency_trans_from_en(response.return_amount));
        //         $("#return-items").html(returnItemsFloat);
        //         $("#total-items-sold").html(soldItemsFloat);
        //         $("#invoice-amount").html(__currency_trans_from_en(response.invoice_amount));
        //         $("#discount").html(__currency_trans_from_en(response.total_sell_discount));
        //         $("#cash-payment").html(__currency_trans_from_en(response.cash_amount));
        //         $("#card-payment").html(__currency_trans_from_en(response.card_amount));
        //         $("#total-received").html(__currency_trans_from_en(response.total_received));
        //         $("#profit-loss").html(__currency_trans_from_en(response.profit_loss));
        //         // $("#total-gift-amount").html(__currency_trans_from_en(response.total_gift_amount));
        //         $("#total-gift-amount").html(__currency_trans_from_en(response.amount));
        //         $("#total-gift-items").html(giftItemsFloat);
        //         $("#gst-tax").html(__currency_trans_from_en(response.gst_tax));
        //     },
        // });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr('href');
            if ( target == '#buy_overview') {
                if(typeof profit_by_categories_datatable == 'undefined') {
                    profit_by_categories_datatable =  $.ajax({
                        type: "GET",
                        url: "/reports/get-buy-overview",
                        data: function ( d ){
                            d.start_date = $('#profit_tabs_filter_overview')
                                .data('daterangepicker')
                                .startDate.format('YYYY-MM-DD');
                            d.end_date = $('#profit_tabs_filter_overview')
                                .data('daterangepicker')
                                .endDate.format('YYYY-MM-DD');
                            d.location_id = $('#profit_loss_location_filter').val();
                        },
                        success: function (response) {
                            $("#buy-total-items").html(response.total_items);
                            $("#buy-amount").html(__currency_trans_from_en(response.total_buy_amount));
                            $("#total-cost-amount").html(__currency_trans_from_en(response.total_cost_amount));
                            $("#total-purchase-amount").html(__currency_trans_from_en(response.total_purchase_amount));
                        },
                    });
                } else {
                    profit_by_categories_datatable.reload();
                }
            } else if (target == '#ecommerce') {
                if(typeof profit_by_brands_datatable == 'undefined') {
                    profit_by_brands_datatable = $.ajax({
                        type: "GET",
                        url: "/reports/get-ecommerce-overview",
                        data: function ( d ){
                            d.start_date = $('#profit_tabs_filter_overview')
                                .data('daterangepicker')
                                .startDate.format('YYYY-MM-DD');
                            d.end_date = $('#profit_tabs_filter_overview')
                                .data('daterangepicker')
                                .endDate.format('YYYY-MM-DD');
                        },
                        success: function (response) {
                           $("#total-orders").html(response.total_orders);
                           $("#total-ecommerce-items").html(response.total_items);
                           $("#new-orders").html(response.new_orders);
                           $("#total-received-amount").html(__currency_trans_from_en(response.total_received_amount));
                           $("#total-order-amount").html(__currency_trans_from_en(response.total_order_amount));
                           $("#ecommerce-profit-loss").html(__currency_trans_from_en(response.profit_loss));
                           $("#completed-orders").html(response.completed_orders);
                           $("#dispatched-orders").html(response.dispatched_order);
                           $("#exchanged-orders").html(response.exchanged_orders);
                           $("#cancelled-items").html(response.cancelled_items);
                           $("#ecommerce-discount-amount").html(__currency_trans_from_en(response.discount_amount));
                           $("#cancelled-orders").html(response.cancelled_orders);
                           $("#cancelled-orders-amount").html(__currency_trans_from_en(response.cancelled_order_amount));
                           $("#exchanged-orders-amount").html(__currency_trans_from_en(response.exchanged_order_amount));
                           $("#completed-orders-amount").html(__currency_trans_from_en(response.completed_order_amount));

                        },
                    });
                } else {
                    profit_by_brands_datatable.reload();
                }
            } else if (target == '#profit_by_locations') {
                if(typeof profit_by_locations_datatable == 'undefined') {
                    profit_by_locations_datatable = $('#profit_by_locations_table').DataTable({
                        processing: true,
                        serverSide: true,
                        "ajax": {
                            "url": "/reports/get-profit/location",
                            "data": function ( d ) {
                                d.start_date = $('#profit_tabs_filter_overview')
                                    .data('daterangepicker')
                                    .startDate.format('YYYY-MM-DD');
                                d.end_date = $('#profit_tabs_filter_overview')
                                    .data('daterangepicker')
                                    .endDate.format('YYYY-MM-DD');
                            }
                        },
                        columns: [
                            { data: 'location', name: 'L.name'  },
                            { data: 'gross_profit', "searchable": false},
                        ],
                        fnDrawCallback: function(oSettings) {
                            var total_profit = sum_table_col($('#profit_by_locations_table'), 'gross-profit');
                            $('#profit_by_locations_table .footer_total').text(total_profit);

                            __currency_convert_recursively($('#profit_by_locations_table'));
                        },
                    });
                } else {
                    profit_by_locations_datatable.ajax.reload();
                }
            } else if (target == '#profit_by_invoice') {
                if(typeof profit_by_invoice_datatable == 'undefined') {
                    profit_by_invoice_datatable = $('#profit_by_invoice_table').DataTable({
                        processing: true,
                        serverSide: true,
                        "ajax": {
                            "url": "/reports/get-profit/invoice",
                            "data": function ( d ) {
                                d.start_date = $('#profit_tabs_filter_overview')
                                    .data('daterangepicker')
                                    .startDate.format('YYYY-MM-DD');
                                d.end_date = $('#profit_tabs_filter_overview')
                                    .data('daterangepicker')
                                    .endDate.format('YYYY-MM-DD');
                            }
                        },
                        columns: [
                            { data: 'invoice_no', name: 'sale.invoice_no'  },
                            { data: 'gross_profit', "searchable": false},
                        ],
                        fnDrawCallback: function(oSettings) {
                            var total_profit = sum_table_col($('#profit_by_invoice_table'), 'gross-profit');
                            $('#profit_by_invoice_table .footer_total').text(total_profit);

                            __currency_convert_recursively($('#profit_by_invoice_table'));
                        },
                    });
                } else {
                    profit_by_invoice_datatable.ajax.reload();
                }
            } else if (target == '#profit_by_date') {
                if(typeof profit_by_date_datatable == 'undefined') {
                    profit_by_date_datatable = $('#profit_by_date_table').DataTable({
                        processing: true,
                        serverSide: true,
                        "ajax": {
                            "url": "/reports/get-profit/date",
                            "data": function ( d ) {
                                d.start_date = $('#profit_tabs_filter_overview')
                                    .data('daterangepicker')
                                    .startDate.format('YYYY-MM-DD');
                                d.end_date = $('#profit_tabs_filter_overview')
                                    .data('daterangepicker')
                                    .endDate.format('YYYY-MM-DD');
                            }
                        },
                        columns: [
                            { data: 'transaction_date', name: 'sale.transaction_date'  },
                            { data: 'gross_profit', "searchable": false},
                        ],
                        fnDrawCallback: function(oSettings) {
                            var total_profit = sum_table_col($('#profit_by_date_table'), 'gross-profit');
                            $('#profit_by_date_table .footer_total').text(total_profit);
                            __currency_convert_recursively($('#profit_by_date_table'));
                        },
                    });
                } else {
                    profit_by_date_datatable.ajax.reload();
                }
            } else if (target == '#profit_by_customer') {
                if(typeof profit_by_customers_table == 'undefined') {
                    profit_by_customers_table = $('#profit_by_customer_table').DataTable({
                        processing: true,
                        serverSide: true,
                        "ajax": {
                            "url": "/reports/get-profit/customer",
                            "data": function ( d ) {
                                d.start_date = $('#profit_tabs_filter_overview')
                                    .data('daterangepicker')
                                    .startDate.format('YYYY-MM-DD');
                                d.end_date = $('#profit_tabs_filter_overview')
                                    .data('daterangepicker')
                                    .endDate.format('YYYY-MM-DD');
                            }
                        },
                        columns: [
                            { data: 'customer', name: 'CU.name'  },
                            { data: 'gross_profit', "searchable": false},
                        ],
                        fnDrawCallback: function(oSettings) {
                            var total_profit = sum_table_col($('#profit_by_customer_table'), 'gross-profit');
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
                            var total_profit = sum_table_col($('#profit_by_day_table'), 'gross-profit');
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
