@extends('layouts.app')
@section('title', __('Product Exchange'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang('Product Exchange')</h1>
</section>

<!-- Main content -->
{{-- {{ dd($sell) }} --}}
<section class="content no-print">

{!! Form::hidden('location_id', null, ['id' => 'location_id', 'data-receipt_printer_type' => 'browser' ]); !!}

	{!! Form::open(['url' => action('SellReturnController@store'), 'method' => 'post', 'id' => 'sell_return_form_new' ]) !!}
	{{-- {!! Form::hidden('transaction_id', $sell->id); !!} --}}
        <input id="transaction_id" name="transaction_id" type="hidden">
	<div class="box box-solid">
		<div class="box-header">
			<h3 class="box-title">@lang('lang_v1.parent_sale')</h3>
		</div>
		<div class="box-body">
			<div class="row">
				<div class="col-sm-4">
					<strong>@lang('sale.invoice_no'):</strong> <input type="text" name="old_invoice_no" id="old_invoice_number" class="form-control" placeholder="Old Invoice No" required><br>
                    <button type="button" id="sale_invoice" class="btn sm-btn btn-primary">Click</button>

				</div>
				<div class="col-sm-4">
					<strong>@lang('contact.customer'):</strong> <span id="customer"></span> <br>
					<strong>@lang('purchase.business_location'):</strong> <span id="business_location"></span>
				</div>
				<div class="col-md-4">
					<strong>@lang('messages.date'):</strong><span id="transaction_date"></span>
				</div>
			</div>
		</div>
	</div>
	<div class="box box-solid">
		<div class="box-body">
			<div class="row">
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('invoice_no', __('sale.invoice_no').':') !!}
                        <input id="invoice_no" class="form-control" name="invoice_no" type="text">
						{{-- {!! Form::text('invoice_no', !empty($sell->return_parent->invoice_no) ? $sell->return_parent->invoice_no : null, ['class' => 'form-control']); !!} --}}
					</div>
				</div>
				<div class="col-sm-3">
					<div class="form-group">
						{!! Form::label('transaction_date', __('messages.date') . ':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
                            <input id="return_parent_transaction_date" name="transaction_date" class="form-control">
							@php
								// $transaction_date = !empty($sell->return_parent->transaction_date) ? $sell->return_parent->transaction_date : 'now';
							@endphp
							{{-- {!! Form::text('transaction_date', @format_datetime($transaction_date), ['class' => 'form-control', 'readonly', 'required']); !!} --}}
						</div>
					</div>
				</div>
				<div class="col-sm-12">
					<table class="table bg-gray" id="sell_return_table">
			          	<thead>
				            <tr class="bg-green">
				              	<th>#</th>
				              	<th>@lang('product.product_name')</th>
				              	<th>@lang('sale.unit_price')</th>
				              	<th>@lang('lang_v1.sell_quantity')</th>
				              	<th>@lang('lang_v1.return_quantity')</th>
				              	<th>@lang('lang_v1.return_subtotal')</th>
				            </tr>
				        </thead>
				        <tbody id="sell_lines_container">
				          	{{-- @foreach($sell->sell_lines as $sell_line)
				          		@php
					                $check_decimal = 'false';
					                if($sell_line->product->unit->allow_decimal == 0){
					                    $check_decimal = 'true';
					                }

					                $unit_name = $sell_line->product->unit->short_name;

					                if(!empty($sell_line->sub_unit)) {
					                	$unit_name = $sell_line->sub_unit->short_name;

					                	if($sell_line->sub_unit->allow_decimal == 0){
					                    	$check_decimal = 'true';
					                	} else {
					                		$check_decimal = 'false';
					                	}
					                }

					            @endphp
				            <tr>
				              	<td>{{ $loop->iteration }}</td>
				              	<td>
				                	{{ $sell_line->product->name }}
				                 	@if( $sell_line->product->type == 'variable')
				                  	- {{ $sell_line->variations->product_variation->name}}
				                  	- {{ $sell_line->variations->name}}
				                 	@endif
				              	</td>
				              	<td><span class="display_currency" data-currency_symbol="true">{{ $sell_line->unit_price_inc_tax }}</span></td>
				              	<td>{{ $sell_line->formatted_qty }} {{$unit_name}}</td>
				              	
				              	<td>
						            <input type="text" name="products[{{$loop->index}}][quantity]" value="{{@format_quantity($sell_line->quantity_returned)}}"
						            class="form-control input-sm input_number return_qty input_quantity"
						            data-rule-abs_digit="{{$check_decimal}}" 
						            data-msg-abs_digit="@lang('lang_v1.decimal_value_not_allowed')"
			              			data-rule-max-value="{{$sell_line->quantity}}"
			              			data-msg-max-value="@lang('validation.custom-messages.quantity_not_available', ['qty' => $sell_line->formatted_qty, 'unit' => $unit_name ])" 
						            >
						            <input name="products[{{$loop->index}}][unit_price_inc_tax]" type="hidden" class="unit_price" value="{{@num_format($sell_line->unit_price_inc_tax)}}">
						            <input name="products[{{$loop->index}}][sell_line_id]" type="hidden" value="{{$sell_line->id}}">
				              	</td>
				              	<td>
				              		<div class="return_subtotal"></div>
				              	</td>
				            </tr>
				          	@endforeach --}}
			          	</tbody>
			        </table>
				</div>
			</div>
			<div class="row">
				@php
					// $discount_type = !empty($sell->return_parent->discount_type) ? $sell->return_parent->discount_type : $sell->discount_type;
					// $discount_amount = !empty($sell->return_parent->discount_amount) ? $sell->return_parent->discount_amount : $sell->discount_amount;
				@endphp
				<div class="col-sm-4" style="display: none;">
					<div class="form-group">
						{!! Form::label('discount_type', __( 'purchase.discount_type' ) . ':') !!}
                        <input class="form-control" name="discount_type" id="discount_type" type="text">
						{{-- {!! Form::select('discount_type', [ '' => __('lang_v1.none'), 'fixed' => __( 'lang_v1.fixed' ), 'percentage' => __( 'lang_v1.percentage' )], $discount_type, ['class' => 'form-control']); !!} --}}
					</div>
				</div>
				<div class="col-sm-4" style="display: none;">
					<div class="form-group">
						{!! Form::label('discount_amount', __( 'purchase.discount_amount' ) . ':') !!}
						{{-- {!! Form::text('discount_amount', @num_format($discount_amount), ['class' => 'form-control input_number']); !!} --}}
                        <input class="form-control" name="discount_amount" id="discount_amount" type="number">

					</div>
				</div>
			</div>
			@php
				$tax_percent = 0;
				// if(!empty($sell->tax)){
				// 	$tax_percent = $sell->tax->amount;
				// }
			@endphp
            <input type="hidden" name="tax_id" id="tax_id">
			{{-- {!! Form::hidden('tax_id', $sell->tax_id); !!} --}}
			{!! Form::hidden('tax_amount', 0, ['id' => 'tax_amount']); !!}
			{!! Form::hidden('tax_percent', $tax_percent, ['id' => 'tax_percent']); !!}
		</div>
	</div>
	<div class="box box-solid">
		<div class="box-body">
			<div class="col-sm-10 col-sm-offset-1">
				<div class="form-group">
					<div class="input-group">
						<div class="input-group-btn">
							<button type="button" class="btn btn-default bg-white btn-flat" data-toggle="modal" data-target="#configure_search_modal" title="{{__('lang_v1.configure_product_search')}}"><i class="fa fa-barcode"></i></button>
						</div>
						{!! Form::text('search_product', null, ['class' => 'form-control mousetrap', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'),
						// 'disabled' => is_null($default_location)? true : false,
						'autofocus' => is_null($default_location)? false : true,
						]); !!}
						<span class="input-group-btn">
							<button type="button" class="btn btn-default bg-white btn-flat pos_add_quick_product" data-href="{{action('ProductController@quickAdd')}}" data-container=".quick_add_product_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
						</span>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12 pos_product_div">
					<input type="hidden" name="sell_price_tax" id="sell_price_tax" value="{{$business_details->sell_price_tax}}">
			
					<!-- Keeps count of product rows -->
					<input type="hidden" id="product_row_count" 
						value="0">
					@php
						$hide_tax = '';
						if( session()->get('business.enable_inline_tax') == 0){
							$hide_tax = 'hide';
						}
					@endphp
					<table class="table table-condensed table-bordered table-striped table-responsive" id="pos_table">
						<thead>
							<tr>
								<th class="tex-center @if(!empty($pos_settings['inline_service_staff'])) col-md-3 @else col-md-4 @endif">	
									@lang('sale.product') @show_tooltip(__('lang_v1.tooltip_sell_product_column'))
								</th>
								<th class="text-center col-md-2">
									@lang('sale.qty')
								</th>
								@if(!empty($pos_settings['inline_service_staff']))
									<th class="text-center col-md-2">
										@lang('restaurant.service_staff')
									</th>
								@endif
								<th class="text-center col-md-2">
									@lang('Original Amount')
								</th>
								<th class="text-center col-md-2">
									@lang('Discount Amount')
								</th>
								<th class="text-center col-md-2 {{$hide_tax}}">
									@lang('sale.price_inc_tax')
								</th>
								<th class="text-center col-md-2">
									@lang('sale.subtotal')
								</th>
								<th class="text-center"><i class="fas fa-times" aria-hidden="true"></i></th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<table class="table table-condensed">
						<tr>
							<td><b>@lang('sale.item'):</b>&nbsp;
								<span class="total_quantity">0</span>
							</td>
							<td>
								<b>@lang('sale.total'):</b> &nbsp;
								<span class="price_total" id="sale_total">0</span>
							</td>
						</tr>
						<tr style="display: none;">
							<td>
								<b>
									{{-- @if($is_discount_enabled)
										@lang('sale.discount')
										@show_tooltip(__('tooltip.sale_discount'))
									@endif --}}
									{{-- @if($is_rp_enabled)
										{{session('business.rp_name')}}
									@endif --}}
									(-):
									<i class="fas fa-edit cursor-pointer" id="pos-edit-discount" title="@lang('sale.edit_discount')" aria-hidden="true" data-toggle="modal" data-target="#posEditDiscountModal"></i>
										<span id="total_discount">0</span>
										<input type="hidden" name="discount_type" id="discount_type" value="@if(empty($edit)){{'percentage'}}@else{{$transaction->discount_type}}@endif" data-default="percentage">
			
										<input type="hidden" name="discount_amount" id="discount_amount" value="@if(empty($edit)) {{@num_format($business_details->default_sales_discount)}} @else {{@num_format($transaction->discount_amount)}} @endif" data-default="{{$business_details->default_sales_discount}}">
			
										<input type="hidden" name="rp_redeemed" id="rp_redeemed" value="@if(empty($edit)){{'0'}}@else{{$transaction->rp_redeemed}}@endif">
			
										<input type="hidden" name="rp_redeemed_amount" id="rp_redeemed_amount" value="@if(empty($edit)){{'0'}}@else {{$transaction->rp_redeemed_amount}} @endif">
			
										</span>
								</b> 
							</td>
							<td class="@if($pos_settings['disable_order_tax'] != 0) hide @endif">
								<span>
									<b>@lang('sale.order_tax')(+): @show_tooltip(__('tooltip.sale_tax'))</b>
									<i class="fas fa-edit cursor-pointer" title="@lang('sale.edit_order_tax')" aria-hidden="true" data-toggle="modal" data-target="#posEditOrderTaxModal" id="pos-edit-tax" ></i> 
									<span id="order_tax">
										@if(empty($edit))
											0
										@else
											{{$transaction->tax_amount}}
										@endif
									</span>
			
									<input type="hidden" name="tax_rate_id" 
										id="tax_rate_id" 
										value="@if(empty($edit)) {{$business_details->default_sales_tax}} @else {{$transaction->tax_id}} @endif" 
										data-default="{{$business_details->default_sales_tax}}">
			
									<input type="hidden" name="tax_calculation_amount" id="tax_calculation_amount" 
										value="@if(empty($edit)) {{@num_format($business_details->tax_calculation_amount)}} @else {{@num_format(optional($transaction->tax)->amount)}} @endif" data-default="{{$business_details->tax_calculation_amount}}">
			
								</span>
							</td>
							<td class="@if($pos_settings['disable_discount'] != 0) hide @endif">
								<span>
			
									<b>@lang('sale.shipping')(+): @show_tooltip(__('tooltip.shipping'))</b> 
									<i class="fas fa-edit cursor-pointer"  title="@lang('sale.shipping')" aria-hidden="true" data-toggle="modal" data-target="#posShippingModal"></i>
									<span id="shipping_charges_amount">0</span>
									<input type="hidden" name="shipping_details" id="shipping_details" value="@if(empty($edit)){{''}}@else{{$transaction->shipping_details}}@endif" data-default="">
			
									<input type="hidden" name="shipping_address" id="shipping_address" value="@if(empty($edit)){{''}}@else{{$transaction->shipping_address}}@endif">
			
									<input type="hidden" name="shipping_status" id="shipping_status" value="@if(empty($edit)){{''}}@else{{$transaction->shipping_status}}@endif">
			
									<input type="hidden" name="delivered_to" id="delivered_to" value="@if(empty($edit)){{''}}@else{{$transaction->delivered_to}}@endif">
			
									<input type="hidden" name="shipping_charges" id="shipping_charges" value="@if(empty($edit)){{@num_format(0.00)}} @else{{@num_format($transaction->shipping_charges)}} @endif" data-default="0.00">
								</span>
							</td>
							@if(in_array('types_of_service', $enabled_modules))
								<td class="col-sm-3 col-xs-6 d-inline-table">
									<b>@lang('lang_v1.packing_charge')(+):</b>
									<i class="fas fa-edit cursor-pointer service_modal_btn"></i> 
									<span id="packing_charge_text">
										0
									</span>
								</td>
							@endif
							@if(!empty($pos_settings['amount_rounding_method']) && $pos_settings['amount_rounding_method'] > 0)
							<td>
								<b id="round_off">@lang('lang_v1.round_off'):</b> <span id="round_off_text">0</span>								
								<input type="hidden" name="round_off_amount" id="round_off_amount" value=0>
							</td>
							@endif
						</tr>
					</table>
				</div>
			</div>
		</div>
	</div>
	<div class="box box-solid">
		<div class="box-body">
			<div class="row">
				{{-- <div class="col-sm-12 text-right">
					<strong>@lang('lang_v1.total_return_discount'):</strong> 
					&nbsp;(-) <span id="total_return_discount"></span>
				</div>
				<div class="col-sm-12 text-right">
					<strong>@lang('lang_v1.total_return_tax') - @if(!empty($sell->tax))({{$sell->tax->name}} - {{$sell->tax->amount}}%)@endif : </strong> 
					&nbsp;(+) <span id="total_return_tax"></span>
				</div> --}}
				<div class="col-sm-12 text-right">
					<strong>@lang('lang_v1.return_total'): </strong>&nbsp;
					<span id="net_return">0</span> 
				</div>
				<div class="col-sm-12 text-right"><b>Exchange Total:</b>&nbsp;
					<span id="exchange_total" class="price_total">0</span>
				</div>
				<div class="col-sm-12 text-right"><b>Sub Total:</b>&nbsp;
					<span id="subtotal_field"></span>
				</div>
				<input name="sub_total" type="hidden" type="text" id="subtotal_input">

				{{-- <div class="col-sm-12 text-right"><b>Sub Total:</b>&nbsp;
					<span id="sub_total" class="price_total">0</span>
				</div> --}}
			</div>
			<br>
			<div class="row">
				<div class="col-sm-12">
					<button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
				</div>
			</div>
		</div>
	</div>
	{{-- <div class="box box-solid">
		<div class="box-body">
			<div class="row">
				<div class="col-sm-12 text-right">
					<strong>@lang('lang_v1.total_return_discount'):</strong> 
					&nbsp;(-) <span id="total_return_discount"></span>
				</div>
				<div class="col-sm-12 text-right">
					<strong>@lang('lang_v1.total_return_tax') - @if(!empty($sell->tax))({{$sell->tax->name}} - {{$sell->tax->amount}}%)@endif : </strong> 
					&nbsp;(+) <span id="total_return_tax"></span>
				</div>
				<div class="col-sm-12 text-right">
					<strong>@lang('lang_v1.return_total'): </strong>&nbsp;
					<span id="net_return">0</span> 
				</div>
			</div>
			<br>
			<div class="row">
				<div class="col-sm-12">
					<button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
				</div>
			</div>
		</div>
	</div> --}}
	{!! Form::close() !!}

</section>
@stop
@section('javascript')
<script src="{{ asset('js/pos_for_return.js?v=' . $asset_v) }}"></script>
{{-- <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script> --}}
{{-- <script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/sell_return.js?v=' . $asset_v) }}"></script> --}}
<script type="text/javascript">
	$(document).ready( function(){
		$('form#sell_return_form_new').validate();
		update_sell_return_total();
		//Date picker
	    // $('#transaction_date').datepicker({
	    //     autoclose: true,
	    //     format: datepicker_date_format
	    // });
	});
	$(document).on('change', 'input.return_qty, #discount_amount, #discount_type', function(){
		update_sell_return_total()
	});

	function update_sell_return_total(){
		var net_return = 0;
		$('table#sell_return_table tbody tr').each( function(){
			var quantity = __read_number($(this).find('input.return_qty'));
			var unit_price = __read_number($(this).find('input.unit_price'));
			var subtotal = quantity * unit_price;
			$(this).find('.return_subtotal').text(__currency_trans_from_en(subtotal, true));
			net_return += subtotal;
		});
		var discount = 0;
		if($('#discount_type').val() == 'fixed'){
			discount = __read_number($("#discount_amount"));
		} else if($('#discount_type').val() == 'percentage'){
			var discount_percent = __read_number($("#discount_amount"));
			discount = __calculate_amount('percentage', discount_percent, net_return);
		}
		discounted_net_return = net_return - discount;

		var tax_percent = $('input#tax_percent').val();
		var total_tax = __calculate_amount('percentage', tax_percent, discounted_net_return);
		var exchanged_amount = get_subtotal();
		var net_return_inc_tax = total_tax + discounted_net_return;
		$('input#tax_amount').val(total_tax);
		$('span#total_return_discount').text(__currency_trans_from_en(discount, true));
		$('span#total_return_tax').text(__currency_trans_from_en(total_tax, true));
		$('span#net_return').text(__currency_trans_from_en(net_return_inc_tax, true));
	}
</script>
<script>
    $(document).ready(function() {
        $('#sale_invoice').on('click', function() {
            // Get the invoice number from the input field
            var invoiceNumber = $('#old_invoice_number').val();

            // Send an Ajax request to your controller
			$.ajax({
				type: 'POST',
				url: '/get-sell-return-data',
				data: {
					invoice_number: invoiceNumber
					// Add any other data you want to send to the controller
				},
				success: function(response) {
					// Handle the success response from the controller
					if (response.success) {
						// Update your view with the received data
						updateView(response.sell);
						update_sell_return_total();
					} else {
						// Display error message using confirm dialog
						var confirmation = confirm(response.message);
						if (confirmation) {
							location.reload(); // Reload the page if user clicks OK
						}
					}
				},
				error: function(xhr, status, error) {
					// Handle the error response
					console.log(error);
				}
			});

			// $.ajax({
			// 	type: 'POST',
			// 	url: '/get-sell-return-data',
			// 	data: {
			// 		invoice_number: invoiceNumber
			// 		// Add any other data you want to send to the controller
			// 	},
			// 	success: function(response) {
			// 		// Handle the success response from the controller
			// 		console.log(response);
			// 		if (response.success) {
			// 			// Update your view with the received data
			// 			updateView(response.sell);
			// 			update_sell_return_total();
			// 		} else {
			// 			// Display error message
			// 			alert(response.message);
			// 		}
			// 	},
			// 	error: function(xhr, status, error) {
			// 		// Handle the error response
			// 		console.log(error);
			// 	}
			// });

            // $.ajax({
            //     type: 'POST', // or 'GET' depending on your controller method
            //     url: '/get-sell-return-data', // Replace with the actual route
            //     data: {
            //         invoice_number: invoiceNumber
            //         // Add any other data you want to send to the controller
            //     },
            //     success: function(response) {
            //         // Handle the success response from the controller
            //         console.log(response);
            //         if (response.success) {
            //         // Update your view with the received data
            //         // Assuming you have a function to update the view, you can replace it with your logic
            //         updateView(response.sell);
			// 		update_sell_return_total();
            //     } else {
            //         // Handle the case where the transaction is not found
            //         console.log(response.message);
            //     }

            //     },
            //     error: function(error) {
            //         // Handle the error response
            //         console.log(error);
            //     }
            // });
        });
        function formatDate(dateString) {
        var date = new Date(dateString);
        var month = (date.getMonth() + 1).toString().padStart(2, '0');
        var day = date.getDate().toString().padStart(2, '0');
        var year = date.getFullYear();

        return month + '/' + day + '/' + year;
        }

        function format_quantity(quantity) {
            return parseFloat(quantity).toFixed(2);
        }

        function num_format(number) {
            return parseFloat(number).toFixed(2);
        }

        function formatDateToISO(dateString) {
            // Split the date and time parts
            var [datePart, timePart] = dateString.split(' ');

            // Split the date into year, month, and day
            var [year, month, day] = datePart.split('-');

            // Ensure that all date parts exist
            if (year && month && day) {
                // Construct the ISO-formatted date
                return year + '-' + month.padStart(2, '0') + '-' + day.padStart(2, '0');
            } else {
                // Handle the case where the date parts are not well-formed
                console.error('Invalid date format:', dateString);
                return '';
            }
        }

        function formatDateToCustomFormat(dateString) {
            // Convert the input date string to a Date object
            var dateObj = new Date(dateString);

            // Check if the dateObj is valid
            if (!isNaN(dateObj.getTime())) {
                // Extract the date and time components
                var month = (dateObj.getMonth() + 1).toString().padStart(2, '0');
                var day = dateObj.getDate().toString().padStart(2, '0');
                var year = dateObj.getFullYear();
                var hours = dateObj.getHours().toString().padStart(2, '0');
                var minutes = dateObj.getMinutes().toString().padStart(2, '0');

                // Construct the formatted date and time
                return month + '/' + day + '/' + year + ' ' + hours + ':' + minutes;
            } else {
                // Handle the case where the date is not valid
                console.error('Invalid date format:', dateString);
                return '';
            }
        }

        function formatTransactionDate(dateString) {
            // Convert the input date string to a Date object
            var dateObj = new Date(dateString);

            // Check if the dateObj is valid
            if (!isNaN(dateObj.getTime())) {
                // Extract the date and time components
                var month = (dateObj.getMonth() + 1).toString().padStart(2, '0');
                var day = dateObj.getDate().toString().padStart(2, '0');
                var year = dateObj.getFullYear();
                var hours = dateObj.getHours().toString().padStart(2, '0');
                var minutes = dateObj.getMinutes().toString().padStart(2, '0');

                // Construct the formatted date and time
                return month + '/' + day + '/' + year + ' ' + hours + ':' + minutes;
            } else {
                // Handle the case where the date is not valid
                console.error('Invalid date format:', dateString);
                return '';
            }
        }

    function updateView(sellData) {
            // Implement your logic to update the view with the received sellData
            // console.log(sellData);
            // console.log(sellData.id)
            $('#transaction_id').val(sellData.id ? sellData.id : '');
            $('#transaction_date').text(sellData.transaction_date ? formatDate(sellData.transaction_date) : '');
            $('#business_location').text(sellData.location ? sellData.location.name : '');
            $('#customer').text(sellData.contact ? sellData.contact.name : '');
            $('#invoice_no').val(sellData.return_parent ? sellData.return_parent.invoice_no : '');
            $('#return_parent_transaction_date').val(sellData.return_parent ? formatTransactionDate(sellData.return_parent.transaction_date) : '');
            $('#discount_type').val(sellData.discount_type ? sellData.discount_type : '');
            $('#discount_amount').val(sellData.discount_amount ? sellData.discount_amount : '');
            $('#tax_id').val(sellData.tax_id ? sellData.tax_id : '');
			// $('#business_locationnn').text(sellData.location ? sellData.location.name : '');
			var locationId = sellData.location ? sellData.location.id : '';

			// Update the value of the hidden input field with the retrieved location id
			$('#location_id').val(locationId);

            var sellLinesHtml = '';

            if (sellData.sell_lines && sellData.sell_lines.length > 0) {
                sellLinesHtml += '<table class="table bg-gray" id="sell_return_table">';
                // sellLinesHtml += '<thead><tr class="bg-green">';
                // sellLinesHtml += '<th>#</th>';
                // sellLinesHtml += '<th>Product Name</th>'; // Replace with the actual translated text
                // sellLinesHtml += '<th>Unit Price</th>'; // Replace with the actual translated text
                // sellLinesHtml += '<th>Sell Quantity</th>'; // Replace with the actual translated text
                // sellLinesHtml += '<th>Return Quantity</th>'; // Replace with the actual translated text
                // sellLinesHtml += '<th>Return Subtotal</th>'; // Replace with the actual translated text
                // sellLinesHtml += '</tr></thead>';
                sellLinesHtml += '<tbody id="sell_lines_container">';

                $.each(sellData.sell_lines, function (index, sellLine) {
                    sellLinesHtml += '<tr>';
                    sellLinesHtml += '<td>' + (index + 1) + '</td>';
                    sellLinesHtml += '<td>' + sellLine.product.name;

                    if (sellLine.product.type == 'variable') {
                        sellLinesHtml += ' - ' + sellLine.variations.product_variation.name;
                        sellLinesHtml += ' - ' + sellLine.variations.name;
                    }

                    sellLinesHtml += '</td>';
                    sellLinesHtml += '<td><span class="display_currency" data-currency_symbol="true">' + sellLine.unit_price_inc_tax + '</span></td>';
                    sellLinesHtml += '<td>' + sellLine.quantity + ' ' + (sellLine.sub_unit ? sellLine.sub_unit.short_name : sellLine.product.unit.short_name) + '</td>';
                    sellLinesHtml += '<td>';
                    sellLinesHtml += '<input type="text" name="products[' + index + '][quantity]" value="' + format_quantity(sellLine.quantity_returned) + '"';
                    sellLinesHtml += 'class="form-control input-sm input_number return_qty input_quantity"';
                    sellLinesHtml += 'data-rule-abs_digit="' + (sellLine.product.unit.allow_decimal == 0 ? 'true' : 'false') + '"';
                    sellLinesHtml += 'data-msg-abs_digit="Decimal value not allowed"';
                    sellLinesHtml += 'data-rule-max-value="' + sellLine.quantity + '"';
                    sellLinesHtml += 'data-msg-max-value="Validation message for maximum value"';
                    sellLinesHtml += '>';
                    sellLinesHtml += '<input name="products[' + index + '][unit_price_inc_tax]" type="hidden" class="unit_price" value="' + num_format(sellLine.unit_price_inc_tax) + '">';
                    sellLinesHtml += '<input name="products[' + index + '][sell_line_id]" type="hidden" value="' + sellLine.id + '">';
                    sellLinesHtml += '</td>';
                    sellLinesHtml += '<td><div class="return_subtotal"></div></td>';
                    sellLinesHtml += '</tr>';
                });

                sellLinesHtml += '</tbody></table>';
            } else {
                sellLinesHtml = '<td colspan="6" style="color:black; font-size: 18px; align-items:center; padding:5px; text-align:center;"><marquee> No sell lines available.</marquee></td>';
            }

            $('#sell_lines_container').html(sellLinesHtml);
        }
    });
</script>
<script>
    $('#sell_return_form_new').submit(function(event) {
        event.preventDefault();
		var data = $(this).serialize();
		var url = $(this).attr('action');

        $.ajax({
            method: 'POST',
			url: url,
            data: data,
			dataType: 'json',
			success: function(result) {
				console.log(result);
				if (result.success == 1) {
                            toastr.success(result.msg);
                            //Check if enabled or not

							if (result.receipt.is_enabled) {
                                pos_print(result.receipt);
                            }

							setTimeout(function() {
								window.location.href = '/sell-return';
							}, 3000); // 3000 milliseconds = 3 seconds

                        } else {
                            toastr.error(result.msg);
                        }

                // console.log(response);
            },
            error: function(error) {
                console.error('Error:', error);
            }
        });
    });
</script>
{{-- <script type="text/javascript">
	$(document).ready( function(){
		$('form#sell_return_form').validate();
		update_sell_return_total();
		//Date picker
	    // $('#transaction_date').datepicker({
	    //     autoclose: true,
	    //     format: datepicker_date_format
	    // });
	});
	$(document).on('change', 'input.return_qty, #discount_amount, #discount_type', function(){
		update_sell_return_total()
	});

	function update_sell_return_total(){
		var net_return = 0;
		$('table#sell_return_table tbody tr').each( function(){
			var quantity = __read_number($(this).find('input.return_qty'));
			var unit_price = __read_number($(this).find('input.unit_price'));
			var subtotal = quantity * unit_price;
			$(this).find('.return_subtotal').text(__currency_trans_from_en(subtotal, true));
			net_return += subtotal;
		});
		var discount = 0;
		if($('#discount_type').val() == 'fixed'){
			discount = __read_number($("#discount_amount"));
		} else if($('#discount_type').val() == 'percentage'){
			var discount_percent = __read_number($("#discount_amount"));
			discount = __calculate_amount('percentage', discount_percent, net_return);
		}
		discounted_net_return = net_return - discount;

		var tax_percent = $('input#tax_percent').val();
		var total_tax = __calculate_amount('percentage', tax_percent, discounted_net_return);
		var net_return_inc_tax = total_tax + discounted_net_return;

		$('input#tax_amount').val(total_tax);
		$('span#total_return_discount').text(__currency_trans_from_en(discount, true));
		$('span#total_return_tax').text(__currency_trans_from_en(total_tax, true));
		$('span#net_return').text(__currency_trans_from_en(net_return_inc_tax, true));
	}
</script> --}}

<script>
    var saleTotal;
    var netReturnCleaned; // Define netReturnCleaned outside the MutationObserver

    $('#search_product').on('focus', function() {
        // Fetch the value from the span element
        saleTotal = parseFloat($('#sale_total').text().replace(/[^\d.]/g, '').trim());
        
        // Check if both netReturnCleaned and saleTotal are available
        if (netReturnCleaned !== undefined && saleTotal !== undefined) {
            calculateSubTotal(netReturnCleaned, saleTotal);
        }
    });

    // // Select the target node
    // var targetNode = document.getElementById('net_return');

    // // Callback function to execute when mutations are observed
    // var callback = function(mutationsList, observer) {
    //     for (var mutation of mutationsList) {
    //         if (mutation.type === 'childList' || mutation.type === 'characterData') {
    //             // Fetch the value from the span element
    //             var netReturnValue = $('#net_return').text().trim();
    //             netReturnCleaned = parseFloat(netReturnValue.replace(/[^\d.]/g, '').trim());

    //             // Check if both netReturnCleaned and saleTotal are available
    //             if (netReturnCleaned !== undefined && saleTotal !== undefined) {
    //                 calculateSubTotal(netReturnCleaned, saleTotal);
    //             }
    //         }
    //     }
    // };

    // // Create an observer instance linked to the callback function
    // var observer = new MutationObserver(callback);

    // // Configuration of the observer
    // var config = { attributes: true, childList: true, subtree: true, characterData: true };

    // // Start observing the target node for configured mutations
    // observer.observe(targetNode, config);

    function calculateSubTotal(netReturnCleaned, saleTotal) {
        var sub_total =  saleTotal - netReturnCleaned;
		sub_total = sub_total.toFixed(2);
        // console.log("Sub Total value:", sub_total);
		$('#subtotal_input').val(sub_total);
		$('#subtotal_field').text(sub_total);

    }
	$(document).ready(function() {
		// Initially disable the button
		$('button[type="submit"]').prop('disabled', true);

		// Monitor changes to the content of the span element
		$('#subtotal_field').on('DOMSubtreeModified', function() {
			var subTotal = parseFloat($(this).text()); // Get the content of the span and convert to float
			if (subTotal >= 0) {
				$('button[type="submit"]').prop('disabled', false); // Enable the button
			} else {
				$('button[type="submit"]').prop('disabled', true); // Disable the button
			}
		});
	});

</script>
@endsection
