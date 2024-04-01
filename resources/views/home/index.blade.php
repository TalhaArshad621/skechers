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
        	<div class="col-md-3 col-sm-6 col-xs-12 col-custom">
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
    	    <div class="col-md-3 col-sm-6 col-xs-12 col-custom">
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
            {{-- sold unit --}}
            <div class="col-md-3 col-sm-6 col-xs-12 col-custom">
                <div class="info-box info-box-new-style">
                  <span class="info-box-icon bg-aqua"><i class="ion ion-ios-cart-outline"></i></span>
  
                  <div class="info-box-content">
                    <span class="info-box-text">{{ __('Total Items Sold	') }}</span>
                    <span class="info-box-number total_item_sold"><i class="fas fa-sync fa-spin fa-fw margin-bottom"></i></span>
                  </div>
                  <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
              </div>
    	    <!-- /.col -->
    	    <div class="col-md-3 col-sm-6 col-xs-12 col-custom">
    	      <div class="info-box info-box-new-style">
    	        <span class="info-box-icon bg-yellow">
    	        	<i class="fa fa-dollar"></i>
    				<i class="fa fa-exclamation"></i>
    	        </span>

    	        <div class="info-box-content">
    	          <span class="info-box-text">{{ __('Ecommerce Sales') }}</span>
    	          <span class="info-box-number ecommerce_sales"><i class="fas fa-sync fa-spin fa-fw margin-bottom"></i></span>
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
                                      <td>Exchange Invoices</td>
                                      <td id="return-invoices">0</td>
                                  </tr>
                                  <tr>
                                      <td>Exchange Items</td>
                                      <td id="return-items">0</td>
                                  </tr>
                                  <tr class="text-white" style="background-color: #343a40 !important;">
                                      <td style="font-weight: 700">Exchange Amount</td>
                                      <td style="font-weight: 700" id="return-amount">0</td>
                                  </tr>
                                  <tr>
                                      <td>Total Items Sold</td>
                                      <td id="total-items-sold">0</td>
                                  </tr>
                                  <tr class="text-white" style="background-color: #343a40 !important;">
                                      <td style="font-weight: 700">Invoice Amount</td>
                                      <td style="font-weight: 700" id="invoice-amount">0</td>
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
                                  <tr class="text-white" style="background-color: #343a40 !important;">
                                      <td style="font-weight: 700">Total Received</td>
                                      <td id="total-received" style="font-weight: 700">0</td>
                                  </tr>
                                  <tr>
                                      <td>Profit / Loss</td>
                                      <td id="profit-loss">0</td>
                                  </tr>
                                  <tr>
                                    <td>Total Gifts Items</td>
                                    <td id="total-gift-items">0</td>
                                </tr>
                                  <tr>
                                      <td>Total Gifts Amount</td>
                                      <td id="total-gift-amount">0</td>
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

        @can("user.update")
            <div class="row">
                <div class="@if((session('business.enable_product_expiry') != 1) && auth()->user()->can('stock_report.view')) col-sm-12 @else col-sm-6 @endif">
                    @component('components.widget', ['class' => 'box-warning'])
                    @slot('icon')
                        {{-- <i class="fa fa-exclamation-triangle text-yellow" aria-hidden="true"></i> --}}
                    @endslot
                    @slot('title')
                        {{ __('Stock Report') }}
                    @endslot
                    <table class="table table-bordered table-striped" id="dashboard_stock_report_table" style="width: 100%;">
                        <thead>
                        <tr>
                            <th>Categories</th>
                            <th>Available Qty</th>
                            <th>Sold Qty</th>
                            <th>Cost of Available Qty </th>
                            <th>Price of Available Qty</th>
                            <th>Cost of Sold Qty</th>
                            <th>Price of Sold Qty</th>
                        </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td><strong>@lang('sale.total'):</strong></td>
                                <td id="available_quantity"></td>
                                <td id="sold_quantity"></td>
                                <td id="cost_of_qty"></td>
                                <td id="price_of_qty"></td>
                                <td id="cost_of_sold"></td>
                                <td id="price_of_sold"></td>
                            </tr>
                        </tfoot>
                    </table>
                    @endcomponent
                </div>
            </div>
        @endcan

        <div class="row no-print" style="display: none;">
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
                  {{-- <div class="row no-print">
                      <div class="col-sm-12">
                          <button type="button" class="btn btn-primary pull-right" 
                          aria-label="Print" onclick="window.print();"
                          ><i class="fa fa-print"></i> @lang( 'messages.print' )</button>
                      </div>
                  </div> --}}
              @endcomponent
  
          </div>
      </div>

        {{-- hehe --}}
        <div class="row" style="display: none;">
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
                              id="product_sell_grouped_report_tablee" style="width: 100%;">
                                  <thead>
                                      <tr>
                                          <th>Image</th>
                                          <th>@lang('messages.date')</th>
                                          <th>Category</th>
                                          <th>Sub Category</th>
                                          <th>@lang('product.sku')</th>
                                          <th>@lang('report.total_unit_sold')</th>
                                          <th>@lang('sale.total')</th>
                                      </tr>
                                  </thead>
                                  <tfoot>
                                      <tr class="bg-gray font-17 footer-total text-center">
                                          <td colspan="5"><strong>@lang('sale.total'):</strong></td>
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
                                          <th>@lang('product.sku')</th>
                                          <th>Total Unit Returned</th>
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
                          <h3 style="margin-top:10px; margin-left:15px; margin-bottom:20px;">Product And Category Report</h3>
                          <div class="table-responsive">
                              <table class="table table-bordered table-striped" 
                              id="product_and_category_table" style="width: 100%;">
                                  <thead>
                                      <tr>
                                          <th>Image</th>
                                          <th>Category</th>
                                          <th>Sub Category</th>
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
                                          <td colspan="3"></td>
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
                  </div>
              </div>
          </div>
      </div>

        <div id="button-container" style="text-align: center;">
            <button class="btn btn-primary" onclick="window.open('/reports/product-sell-grouped-report-detailed', '_blank')">Product Sell Detailed Report</button>
            <button class="btn btn-primary" onclick="window.open('/reports/product-sell-report', '_blank')">Product Sell Report</button>
        </div>


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
              var returnItemsFloat = parseFloat(response.return_items); // Convert float to integer
              var soldItemsFloat = parseFloat(response.total_item_sold); // Convert float to integer
              var giftItemsFloat = parseFloat(response.total_gift_items); // Convert float to integer

              console.log(response);
                $("#return-invoices").html(response.return_invoices);
                $("#return-amount").html(__currency_trans_from_en(response.return_amount));
                $("#return-items").html(returnItemsFloat);
                $("#total-items-sold").html(soldItemsFloat);
                $("#invoice-amount").html(__currency_trans_from_en(response.invoice_amount));
                $("#discount").html(__currency_trans_from_en(response.total_sell_discount));
                $("#cash-payment").html(__currency_trans_from_en(response.cash_amount));
                $("#card-payment").html(__currency_trans_from_en(response.card_amount));
                $("#total-received").html(__currency_trans_from_en(response.total_received));
                $("#profit-loss").html(__currency_trans_from_en(response.profit_loss));
                $("#total-gift-amount").html(__currency_trans_from_en(response.total_gift_amount));
                $("#total-gift-items").html(giftItemsFloat);
                $("#gst-tax").html(__currency_trans_from_en(response.gst_tax));
            },
        });

    });
</script>
{{-- @endsection --}}

{{-- @section('javascript') --}}
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
                product_sell_grouped_reportt.ajax.reload();
                product_sell_grouped_report_2nd.ajax.reload();
                product_sell_grouped_report_category.ajax.reload();
                product_sell_grouped_report_return_category.ajax.reload();
                detail_product_and_category.ajax.reload();
            }
        );
        $('#product_sr_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#product_sr_date_filter').val('');
            product_sell_grouped_report_2nd.ajax.reload();
            product_sell_grouped_report_category.ajax.reload();
            product_sell_report_with_purchase_table.ajax.reload();
            product_sell_grouped_report_return_category.ajax.reload();
            detail_product_and_category.ajax.reload();
            product_sell_grouped_reportt.ajax.reload();

        });

        $('#product_sr_start_time, #product_sr_end_time').datetimepicker({
            format: moment_time_format,
            ignoreReadonly: true,
        }).on('dp.change', function(ev){
            product_sell_grouped_reportt.ajax.reload();
            product_sell_grouped_report_2nd.ajax.reload();
            product_sell_grouped_report_category.ajax.reload();
            product_sell_grouped_report_return_category.ajax.reload();
            detail_product_and_category.ajax.reload();

        });
    }

    product_sell_grouped_reportt = $('table#product_sell_grouped_report_tablee').DataTable({
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
            { data: 'sub_category', name: 'c2.name' },
            { data: 'sub_sku', name: 'v.sub_sku' },
            { data: 'total_qty_sold', name: 'total_qty_sold', searchable: false },
            { data: 'subtotal', name: 'subtotal', searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            $('#footer_grouped_subtotal').text(
                sum_table_col($('#product_sell_grouped_report_tablee'), 'row_subtotal')
            );
            $('#footer_total_grouped_sold').html(
                __sum_stock($('#product_sell_grouped_report_tablee'), 'sell_qty')
            );
            __currency_convert_recursively($('#product_sell_grouped_report_tablee'));
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

    detail_product_and_category = $('table#product_and_category_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[1, 'desc']],
        ajax: {
            url: '/reports/detailed_product_category',
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
        },
        columns: [
            { data: 'product_image', name: 'product_image' },
            { data: 'category_name', name: 'category_name' },
            { data: 'sub_category', name: 'sub_category' },
            { data: 'total_qty_sold', name: 'total_qty_sold', searchable: false },
            { data: 'total_qty_returned', name: 'total_qty_returned', searchable: false },
            { data: 'total_net_qty', name: 'total_net_qty', searchable: false },
            { data: 'sale_value', name: 'sale_value', searchable: false },
            { data: 'return_value', name: 'return_value', searchable: false },
            { data: 'subtotal', name: 'subtotal', searchable: false },
            
        ],
        fnDrawCallback: function(oSettings) {
            $('#sale_value').text(
                sum_table_col($('#product_and_category_table'), 'sale_value')
            );
            $('#return_value').text(
                sum_table_col($('#product_and_category_table'), 'return_value')
            );
            $('#net_value').text(
                sum_table_col($('#product_and_category_table'), 'subtotal')
            );
            $('#total_sold').html(
                __sum_stock($('#product_and_category_table'), 'sell_qty')
            );
            $('#total_returned').html(
                __sum_stock($('#product_and_category_table'), 'sell_qty')
            );
            $('#net_unit').html(
                __sum_stock($('#product_and_category_table'), 'sell_qty')
            );

            __currency_convert_recursively($('#product_and_category_table'));
        },

    });



    $(
        '#product_sell_report_form #variation_id, #product_sell_report_form #location_id, #product_sell_report_form #customer_id'
    ).change(function() {
        product_sell_grouped_reportt.ajax.reload();
        product_sell_grouped_report_2nd.ajax.reload();
        product_sell_grouped_report_category.ajax.reload();
        product_sell_grouped_report_return_category.ajax.reload();
        detail_product_and_category.ajax.reload();

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