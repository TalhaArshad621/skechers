@extends('layouts.app')
@section('title', __('home.home'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header content-header-custom">
    <h1>{{ __('home.welcome_message', ['name' => Session::get('user.first_name')]) }}
    </h1>
</section>
<!-- Main content -->
<section class="content content-custom no-print">
  <br>
    @if(auth()->user()->can('dashboard.data'))
    	<div class="row">
            <div class="col-md-4 col-xs-12">
              @if(count($all_locations) > 1)
                {!! Form::select('dashboard_location', $all_locations, null, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.select_location'), 'id' => 'dashboard_location']); !!}
              @endif
            </div>
    		<div class="col-md-8 col-xs-12">
    			<div class="btn-group pull-right" data-toggle="buttons">
    				<label class="btn btn-info active">
        				<input type="radio" name="date-filter"
        				data-start="{{ date('Y-m-d') }}" 
        				data-end="{{ date('Y-m-d') }}"
        				checked> {{ __('home.today') }}
      				</label>
      				<label class="btn btn-info">
        				<input type="radio" name="date-filter"
        				data-start="{{ $date_filters['this_week']['start']}}" 
        				data-end="{{ $date_filters['this_week']['end']}}"
        				> {{ __('home.this_week') }}
      				</label>
      				<label class="btn btn-info">
        				<input type="radio" name="date-filter"
        				data-start="{{ $date_filters['this_month']['start']}}" 
        				data-end="{{ $date_filters['this_month']['end']}}"
        				> {{ __('home.this_month') }}
      				</label>
      				<label class="btn btn-info">
        				<input type="radio" name="date-filter" 
        				data-start="{{ $date_filters['this_fy']['start']}}" 
        				data-end="{{ $date_filters['this_fy']['end']}}" 
        				> {{ __('home.this_fy') }}
      				</label>
                </div>
    		</div>
    	</div>
    	<br>
    	<div class="row row-custom">
        	<div class="col-md-4 col-sm-6 col-xs-12 col-custom">
    	      <div class="info-box info-box-new-style">
    	        <span class="info-box-icon bg-aqua"><i class="ion ion-cash"></i></span>

    	        <div class="info-box-content">
    	          <span class="info-box-text">{{ __('home.total_purchase') }}</span>
    	          <span class="info-box-number total_purchase"><i class="fas fa-sync fa-spin fa-fw margin-bottom"></i></span>
    	        </div>
    	        <!-- /.info-box-content -->
    	      </div>
    	      <!-- /.info-box -->
    	    </div>
    	    <!-- /.col -->
    	    <div class="col-md-4 col-sm-6 col-xs-12 col-custom">
    	      <div class="info-box info-box-new-style">
    	        <span class="info-box-icon bg-aqua"><i class="ion ion-ios-cart-outline"></i></span>

    	        <div class="info-box-content">
    	          <span class="info-box-text">{{ __('home.total_sell') }}</span>
    	          <span class="info-box-number total_sell"><i class="fas fa-sync fa-spin fa-fw margin-bottom"></i></span>
    	        </div>
    	        <!-- /.info-box-content -->
    	      </div>
    	      <!-- /.info-box -->
    	    </div>
    	    <!-- /.col -->
    	    <div class="col-md-4 col-sm-6 col-xs-12 col-custom">
    	      <div class="info-box info-box-new-style">
    	        <span class="info-box-icon bg-yellow">
    	        	<i class="fa fa-dollar"></i>
    				<i class="fa fa-exclamation"></i>
    	        </span>

    	        <div class="info-box-content">
    	          <span class="info-box-text">{{ __('Ecommerce Sales') }}</span>
    	          <span class="info-box-number purchase_due"><i class="fas fa-sync fa-spin fa-fw margin-bottom"></i></span>
    	        </div>
    	        <!-- /.info-box-content -->
    	      </div>
    	      <!-- /.info-box -->
    	    </div>
    	    <!-- /.col -->

    	    <!-- fix for small devices only -->
    	    <!-- <div class="clearfix visible-sm-block"></div> -->
    	    {{-- <div class="col-md-3 col-sm-6 col-xs-12 col-custom">
    	      <div class="info-box info-box-new-style">
    	        <span class="info-box-icon bg-yellow">
    	        	<i class="ion ion-ios-paper-outline"></i>
    	        	<i class="fa fa-exclamation"></i>
    	        </span>

    	        <div class="info-box-content">
    	          <span class="info-box-text">{{ __('home.invoice_due') }}</span>
    	          <span class="info-box-number invoice_due"><i class="fas fa-sync fa-spin fa-fw margin-bottom"></i></span>
    	        </div>
    	        <!-- /.info-box-content -->
    	      </div>
    	      <!-- /.info-box -->
    	    </div> --}}
    	    <!-- /.col -->
      	</div>
      	{{-- <div class="row row-custom">
            <!-- expense -->
            <div class="col-md-3 col-sm-6 col-xs-12 col-custom">
              <div class="info-box info-box-new-style">
                <span class="info-box-icon bg-red">
                  <i class="fas fa-minus-circle"></i>
                </span>

                <div class="info-box-content">
                  <span class="info-box-text">
                    {{ __('lang_v1.expense') }}
                  </span>
                  <span class="info-box-number total_expense"><i class="fas fa-sync fa-spin fa-fw margin-bottom"></i></span>
                </div>
                <!-- /.info-box-content -->
              </div>
              <!-- /.info-box -->
            </div>
        </div> --}}
        @if(!empty($widgets['after_sale_purchase_totals']))
            @foreach($widgets['after_sale_purchase_totals'] as $widget)
                {!! $widget !!}
            @endforeach
        @endif

      
        {{-- @if(!empty($all_locations)) --}}
          	<!-- sales chart start -->
          	<div class="row">
          		<div class="col-sm-12">
                  @component('components.widget', ['class' => 'box-primary', 'title' => session('business.name') . ' - ' . __('Overview Report')])
                    <div class="row">
                      <div class="col-sm-12">
                          <div class="row no-print">
                              {{-- <div><h3>{{ session()->get('business.name') }} - @lang('Overview Report')</h3></div> --}}
                  
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
                  
                          <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="sell_over_view_table">
                              <thead>
                                  <tr>
                                      <th>@lang('Head')</th>
                                      <th>@lang('Value')</th>
                                  </tr>
                              </thead>
                              <tbody>
                                  <tr>
                                      <td>Return Invoices</td>
                                      <td id="return-invoices">0</td>
                                  </tr>
                                  <tr>
                                      <td>Returned Amount</td>
                                      <td id="return-amount">0</td>
                                  </tr>
                                  <tr>
                                      <td>Return Items</td>
                                      <td id="return-items">0</td>
                                  </tr>
                                  <tr>
                                      <td>Total Items Sold</td>
                                      <td id="total-items-sold">0</td>
                                  </tr>
                                  <tr>
                                      <td>Invoice Amount</td>
                                      <td id="invoice-amount">0</td>
                                  </tr>
                                  <tr>
                                      <td>Discount</td>
                                      <td id="discount">0</td>
                                  </tr>
                                  <tr>
                                      <td>Cash Payment</td>
                                      <td id="cash-payment">0</td>
                                  </tr>
                                  <tr>
                                      <td>Credit Card Payment</td>
                                      <td id="card-payment">0</td>
                                  </tr>
                                  <tr>
                                      <td>Total Received</td>
                                      <td id="total-received">0</td>
                                  </tr>
                                  <tr>
                                      <td>Profit / Loss</td>
                                      <td id="profit-loss">0</td>
                                  </tr>
                                  <tr>
                                      <td>Total Gifts Amount</td>
                                      <td id="total-gift-amount">0</td>
                                  </tr>
                                  <tr>
                                      <td>Total Gifts Items</td>
                                      <td id="total-gift-items">0</td>
                                  </tr>
                                  <tr>
                                      <td>GST Tax</td>
                                      <td id="gst-tax">0</td>
                                  </tr>
                                  <tr>
                                      <td>Store To Store Transfer</td>
                                      <td id="store-store-transfer">0</td>
                                  </tr>
                              </tbody>
                            </table
                          </div>
                      </div>
                  </div>
                    @endcomponent
          		</div>
          	</div>
        {{-- @endif --}}
        @if(!empty($widgets['after_sales_last_30_days']))
            @foreach($widgets['after_sales_last_30_days'] as $widget)
                {!! $widget !!}
            @endforeach
        @endif
        {{-- @if(!empty($all_locations))
          	<div class="row">
          		<div class="col-sm-12">
                    @component('components.widget', ['class' => 'box-primary', 'title' => __('home.sells_current_fy')])
                      {!! $sells_chart_2->container() !!}
                    @endcomponent
          		</div>
          	</div>
        @endif --}}
      	<!-- sales chart end -->
        @if(!empty($widgets['after_sales_current_fy']))
            @foreach($widgets['after_sales_current_fy'] as $widget)
                {!! $widget !!}
            @endforeach
        @endif
      	<!-- products less than alert quntity -->
      	{{-- <div class="row">
            <div class="col-sm-6">
                @component('components.widget', ['class' => 'box-warning'])
                  @slot('icon')
                    <i class="fa fa-exclamation-triangle text-yellow" aria-hidden="true"></i>
                  @endslot
                  @slot('title')
                    {{ __('lang_v1.sales_payment_dues') }} @show_tooltip(__('lang_v1.tooltip_sales_payment_dues'))
                  @endslot
                  <table class="table table-bordered table-striped" id="sales_payment_dues_table">
                    <thead>
                      <tr>
                        <th>@lang( 'contact.customer' )</th>
                        <th>@lang( 'sale.invoice_no' )</th>
                        <th>@lang( 'home.due_amount' )</th>
                        <th>@lang( 'messages.action' )</th>
                      </tr>
                    </thead>
                  </table>
                @endcomponent
            </div>
            <div class="col-sm-6">
                @component('components.widget', ['class' => 'box-warning'])
                @slot('icon')
                <i class="fa fa-exclamation-triangle text-yellow" aria-hidden="true"></i>
                @endslot
                @slot('title')
                {{ __('lang_v1.purchase_payment_dues') }} @show_tooltip(__('tooltip.payment_dues'))
                @endslot
                <table class="table table-bordered table-striped" id="purchase_payment_dues_table">
                    <thead>
                      <tr>
                        <th>@lang( 'purchase.supplier' )</th>
                        <th>@lang( 'purchase.ref_no' )</th>
                        <th>@lang( 'home.due_amount' )</th>
                        <th>@lang( 'messages.action' )</th>
                      </tr>
                    </thead>
                </table>
                @endcomponent
            </div>
        </div> --}}
        {{-- <div class="row">
            <div class="@if((session('business.enable_product_expiry') != 1) && auth()->user()->can('stock_report.view')) col-sm-12 @else col-sm-6 @endif">
                @component('components.widget', ['class' => 'box-warning'])
                  @slot('icon')
                    <i class="fa fa-exclamation-triangle text-yellow" aria-hidden="true"></i>
                  @endslot
                  @slot('title')
                    {{ __('home.product_stock_alert') }} @show_tooltip(__('tooltip.product_stock_alert'))
                  @endslot
                  <table class="table table-bordered table-striped" id="stock_alert_table" style="width: 100%;">
                    <thead>
                      <tr>
                        <th>@lang( 'sale.product' )</th>
                        <th>@lang( 'business.location' )</th>
                        <th>@lang( 'report.current_stock' )</th>
                      </tr>
                    </thead>
                  </table>
                @endcomponent
            </div>
            @can('stock_report.view')
                @if(session('business.enable_product_expiry') == 1)
                  <div class="col-sm-6">
                      @component('components.widget', ['class' => 'box-warning'])
                          @slot('icon')
                            <i class="fa fa-exclamation-triangle text-yellow" aria-hidden="true"></i>
                          @endslot
                          @slot('title')
                            {{ __('home.stock_expiry_alert') }} @show_tooltip( __('tooltip.stock_expiry_alert', [ 'days' =>session('business.stock_expiry_alert_days', 30) ]) )
                          @endslot
                          <input type="hidden" id="stock_expiry_alert_days" value="{{ \Carbon::now()->addDays(session('business.stock_expiry_alert_days', 30))->format('Y-m-d') }}">
                          <table class="table table-bordered table-striped" id="stock_expiry_alert_table">
                            <thead>
                              <tr>
                                  <th>@lang('business.product')</th>
                                  <th>@lang('business.location')</th>
                                  <th>@lang('report.stock_left')</th>
                                  <th>@lang('product.expires_in')</th>
                              </tr>
                            </thead>
                          </table>
                      @endcomponent
                  </div>
                @endif
            @endcan
      	</div> --}}
        @if(!empty($widgets['after_dashboard_reports']))
          @foreach($widgets['after_dashboard_reports'] as $widget)
            {!! $widget !!}
          @endforeach
        @endif
    @endif
</section>
<!-- /.content -->
<div class="modal fade payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>
@stop
@section('javascript')
    <script src="{{ asset('js/home.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    @if(!empty($all_locations))
        {!! $sells_chart_1->script() !!}
        {!! $sells_chart_2->script() !!}
    @endif

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
        $.ajax({
            type: "GET",
            url: "/reports/get-sell-overview",
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
                $("#return-invoices").html(__currency_trans_from_en(response.return_invoices));
                $("#return-amount").html(__currency_trans_from_en(response.return_amount));
                $("#return-items").html(response.return_items);
                $("#total-items-sold").html(response.total_item_sold);
                $("#invoice-amount").html(__currency_trans_from_en(response.invoice_amount));
                $("#discount").html(__currency_trans_from_en(response.total_sell_discount));
                $("#cash-payment").html(__currency_trans_from_en(response.cash_amount));
                $("#card-payment").html(__currency_trans_from_en(response.card_amount));
                $("#total-received").html(__currency_trans_from_en(response.total_received));
                $("#profit-loss").html(__currency_trans_from_en(response.profit_loss));
                $("#total-gift-amount").html(__currency_trans_from_en(response.total_gift_amount));
                $("#total-gift-items").html(response.total_gift_items);
                $("#gst-tax").html(__currency_trans_from_en(response.gst_tax));
            },
        });

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

