@extends('layouts.app')
@section('title', __('Gift Return'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang('Gift Return')</h1>
</section>

<!-- Main content -->
<section class="content no-print">

{!! Form::hidden('location_id', null, ['id' => 'location_id', 'data-receipt_printer_type' => 'browser' ]); !!}

	{!! Form::open(['url' => action('SellReturnController@storeGiftReturn'), 'method' => 'post', 'id' => 'sell_return_form_new' ]) !!}
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
				<div class="col-sm-4" style="display: none">
					<div class="form-group">
						{!! Form::label('invoice_no', __('sale.invoice_no').':') !!}
                        <input id="invoice_no" class="form-control" name="invoice_no" type="text">
					</div>
				</div>
				<div class="col-sm-3" style="display: none">
					<div class="form-group">
						{!! Form::label('transaction_date', __('messages.date') . ':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
                            <input id="return_parent_transaction_date" name="transaction_date" class="form-control">
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
			          	</tbody>
			        </table>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-4" style="display: none;">
					<div class="form-group">
						{!! Form::label('discount_type', __( 'purchase.discount_type' ) . ':') !!}
                        <input class="form-control" name="discount_type" id="discount_type" type="text">
					</div>
				</div>
				<div class="col-sm-4" style="display: none;">
					<div class="form-group">
						{!! Form::label('discount_amount', __( 'purchase.discount_amount' ) . ':') !!}
                        <input class="form-control" name="discount_amount" id="discount_amount" type="number">

					</div>
				</div>
			</div>
			@php
				$tax_percent = 0;
			@endphp
            <input type="hidden" name="tax_id" id="tax_id">
			{{-- {!! Form::hidden('tax_id', $sell->tax_id); !!} --}}
			{!! Form::hidden('tax_amount', 0, ['id' => 'tax_amount']); !!}
			{!! Form::hidden('tax_percent', $tax_percent, ['id' => 'tax_percent']); !!}
			<input type="hidden" name="final_total" id="final_total_input" value=0>
		</div>
	</div>
	<div class="box box-solid">
		<div class="box-body">
			<div class="row">
				<div class="col-sm-12 text-right">
					<strong>@lang('lang_v1.return_total'): </strong>&nbsp;
					<span id="net_return">0</span> 
				</div>
				<div style="display: none;" class="col-sm-12 text-right"><b>Exchange Total:</b>&nbsp;
					<span id="exchange_total" class="price_total">0</span>
				</div>
				<div style="display: none;" class="col-sm-12 text-right"><b>Sub Total:</b>&nbsp;
					<span id="subtotal_field"></span>
				</div>
				<input name="sub_total" type="hidden" type="text" id="subtotal_input">
			</div>
			<br>
            <div class="row">
                @php
				    $is_mobile = false;
                @endphp
				<div class="col-sm-12">
					<button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
				</div>
			</div>
		</div>
	</div>

	@include('sale_pos.partials.payment_modal')

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
            var invoiceNumber = $('#old_invoice_number').val();

			$.ajax({
				type: 'POST',
				url: '/get-sell-return-data-for-gift',
				data: {
					invoice_number: invoiceNumber
				},
				success: function(response) {
					if (response.success) {
						updateView(response.sell);
						update_sell_return_total();
					} else {
						var confirmation = confirm(response.message);
						if (confirmation) {
							location.reload();
						}
					}
				},
				error: function(xhr, status, error) {
					console.log(error);
				}
			});
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
            $('#transaction_id').val(sellData.id ? sellData.id : '');
            $('#transaction_date').text(sellData.transaction_date ? formatDate(sellData.transaction_date) : '');
            $('#business_location').text(sellData.location ? sellData.location.name : '');
            $('#customer').text(sellData.contact ? sellData.contact.name : '');
            $('#invoice_no').val(sellData.return_parent ? sellData.return_parent.invoice_no : '');
            $('#return_parent_transaction_date').val(sellData.return_parent ? formatTransactionDate(sellData.return_parent.transaction_date) : '');
            $('#discount_type').val(sellData.discount_type ? sellData.discount_type : '');
            $('#discount_amount').val(sellData.discount_amount ? sellData.discount_amount : '');
            $('#tax_id').val(sellData.tax_id ? sellData.tax_id : '');
			var locationId = sellData.location ? sellData.location.id : '';

			// Update the value of the hidden input field with the retrieved location id
			$('#location_id').val(locationId);

            var sellLinesHtml = '';

            if (sellData.sell_lines && sellData.sell_lines.length > 0) {
                sellLinesHtml += '<table class="table bg-gray" id="sell_return_table">';

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

							// if (result.receipt.is_enabled) {
                            //     pos_print(result.receipt);
                            // }

							setTimeout(function() {
								window.location.href = '/gift-index';
							}, 3000); // 3000 milliseconds = 3 seconds

                        } else {
                            toastr.error(result.msg);
                        }
            },
            error: function(error) {
                console.error('Error:', error);
            }
        });
    });
</script>
@endsection
