@extends('layouts.app')
@section('title', __('International Exchange'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('International Exchange') <i class="fa fa-keyboard-o hover-q text-muted" aria-hidden="true" data-container="body" data-toggle="popover" data-placement="bottom" data-content="@include('purchase.partials.keyboard_shortcuts_details')" data-html="true" data-trigger="hover" data-original-title="" title=""></i></h1>
</section>

<!-- Main content -->
<section class="content">

	<!-- Page level currency setting -->
	<input type="hidden" id="p_code" value="{{$currency_details->code}}">
	<input type="hidden" id="p_symbol" value="{{$currency_details->symbol}}">
	<input type="hidden" id="p_thousand" value="{{$currency_details->thousand_separator}}">
	<input type="hidden" id="p_decimal" value="{{$currency_details->decimal_separator}}">

	@include('layouts.partials.error')

	
	{{-- {!! Form::open(['url' => action('InternationalExchangeController@store'), 'method' => 'post', 'id' => 'add_purchase_form', 'files' => true ]) !!} --}}
    <div style="display: none;"> 
        @component('components.widget', ['class' => 'box-primary'])
            <div class="row">
                <div class="@if(!empty($default_purchase_status)) col-sm-4 @else col-sm-3 @endif">
                    <div class="form-group">
                        {!! Form::label('supplier_id', __('purchase.supplier') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            {!! Form::select('contact_id', [], null, ['class' => 'form-control', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'supplier_id']); !!}
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default bg-white btn-flat add_new_supplier" data-name=""><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                            </span>
                        </div>
                    </div>
                    <div id="supplier_address_div"></div>
                </div>
                <div class="@if(!empty($default_purchase_status)) col-sm-4 @else col-sm-3 @endif">
                    <div class="form-group">
                        {!! Form::label('ref_no', __('purchase.ref_no').':') !!}
                        @show_tooltip(__('lang_v1.leave_empty_to_autogenerate'))
                        {!! Form::text('ref_no', null, ['class' => 'form-control']); !!}
                    </div>
                </div>
                <div class="@if(!empty($default_purchase_status)) col-sm-4 @else col-sm-3 @endif">
                    <div class="form-group">
                        {!! Form::label('transaction_date', __('purchase.purchase_date') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                            {!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required']); !!}
                        </div>
                    </div>
                </div>
                <div class="col-sm-3 @if(!empty($default_purchase_status)) hide @endif" style="display: none;">
                    <div class="form-group">
                        {!! Form::label('status', __('purchase.purchase_status') . ':*') !!} @show_tooltip(__('tooltip.order_status'))
                        {!! Form::select('status', $orderStatuses, 'Received', ['class' => 'form-control select2', 'required']); !!}
                    </div>
                </div>			
                @if(count($business_locations) == 1)
                    @php 
                        $default_location = current(array_keys($business_locations->toArray()));
                        $search_disable = false; 
                    @endphp
                @else
                    @php $default_location = null;
                    $search_disable = true;
                    @endphp
                @endif
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location').':*') !!}
                        @show_tooltip(__('tooltip.purchase_location'))
                        {!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required'], $bl_attributes); !!}
                    </div>
                </div>

                <!-- Currency Exchange Rate -->
                <div class="col-sm-3 @if(!$currency_details->purchase_in_diff_currency) hide @endif">
                    <div class="form-group">
                        {!! Form::label('exchange_rate', __('purchase.p_exchange_rate') . ':*') !!}
                        @show_tooltip(__('tooltip.currency_exchange_factor'))
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-info"></i>
                            </span>
                            {!! Form::number('exchange_rate', $currency_details->p_exchange_rate, ['class' => 'form-control', 'required', 'step' => 0.001]); !!}
                        </div>
                        <span class="help-block text-danger">
                            @lang('purchase.diff_purchase_currency_help', ['currency' => $currency_details->name])
                        </span>
                    </div>
                </div>

                <div class="col-md-3" style="display: none;">
                    <div class="form-group">
                        <div class="multi-input">
                        {!! Form::label('pay_term_number', __('contact.pay_term') . ':') !!} @show_tooltip(__('tooltip.pay_term'))
                        <br/>
                        {!! Form::number('pay_term_number', null, ['class' => 'form-control width-40 pull-left', 'placeholder' => __('contact.pay_term')]); !!}

                        {!! Form::select('pay_term_type', 
                            ['months' => __('lang_v1.months'), 
                                'days' => __('lang_v1.days')], 
                                null, 
                            ['class' => 'form-control width-60 pull-left','placeholder' => __('messages.please_select'), 'id' => 'pay_term_type']); !!}
                        </div>
                    </div>
                </div>

                <div class="col-sm-3" style="display: none;">
                    <div class="form-group">
                        {!! Form::label('document', __('purchase.attach_document') . ':') !!}
                        {!! Form::file('document', ['id' => 'upload_document', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
                        <p class="help-block">
                            @lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                            @includeIf('components.document_help_text')
                        </p>
                    </div>
                </div>
            </div>
        @endcomponent
    </div>

	@component('components.widget', ['class' => 'box-primary'])
		@if(count($business_locations) > 0)
		<div class="row">
			<div class="col-sm-3">
				<div class="form-group">
					<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-map-marker"></i>
						</span>
					{!! Form::select('location_id', $business_locations, $default_location->id ?? null, ['class' => 'form-control input-sm',
					'id' => 'location_id_new', 
					'required', 'autofocus'], $bl_attributes); !!}
					<span class="input-group-addon">
							@show_tooltip(__('tooltip.sale_location'))
						</span> 
					</div>
				</div>
			</div>
		</div>
		@endif
		<hr/>

		<div class="row">
			<div class="col-sm-8 col-sm-offset-2">
				<div class="form-group">
					<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-search"></i>
						</span>
						{!! Form::text('search_product', null, ['class' => 'form-control mousetrap', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'), 'disabled' => $search_disable]); !!}
					</div>
				</div>
			</div>
			<div class="col-sm-2">
				<div class="form-group">
					<button tabindex="-1" type="button" class="btn btn-link btn-modal"data-href="{{action('ProductController@quickAdd')}}" 
            	data-container=".quick_add_product_modal"><i class="fa fa-plus"></i> @lang( 'product.add_new_product' ) </button>
				</div>
			</div>
		</div>
		@php
			$hide_tax = '';
			if( session()->get('business.enable_inline_tax') == 0){
				$hide_tax = 'hide';
			}
		@endphp
		<div class="row">
			<div class="col-sm-12">
				<div class="table-responsive">
					<table class="table table-condensed table-bordered table-th-green text-center table-striped" id="purchase_entry_table">
						<thead>
							<tr>
								<th>#</th>
								<th>@lang( 'product.product_name' )</th>
								<th>@lang( 'purchase.purchase_quantity' )</th>
								<th>@lang( 'lang_v1.unit_cost_before_discount' )</th>
								<th>@lang( 'lang_v1.discount_percent' )</th>
								<th>@lang( 'purchase.unit_cost_before_tax' )</th>
								<th class="{{$hide_tax}}">@lang( 'purchase.subtotal_before_tax' )</th>
								<th class="{{$hide_tax}}">@lang( 'purchase.product_tax' )</th>
								<th class="{{$hide_tax}}">@lang( 'purchase.net_cost' )</th>
								<th>@lang( 'purchase.line_total' )</th>
								<th class="@if(!session('business.enable_editing_product_from_purchase')) hide @endif">
									@lang( 'lang_v1.profit_margin' )
								</th>
								<th>
									@lang( 'purchase.unit_selling_price' )
									<small>(@lang('product.inc_of_tax'))</small>
								</th>
								@if(session('business.enable_lot_number'))
									<th>
										@lang('lang_v1.lot_number')
									</th>
								@endif
								@if(session('business.enable_product_expiry'))
									<th>
										@lang('product.mfg_date') / @lang('product.exp_date')
									</th>
								@endif
								<th><i class="fa fa-trash" aria-hidden="true"></i></th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
				<hr/>
				<div class="pull-right col-md-5">
					<table class="pull-right col-md-12">
						<tr>
							<th class="col-md-7 text-right">@lang( 'lang_v1.total_items' ):</th>
							<td class="col-md-5 text-left">
								<span id="total_quantity" class="display_currency" data-currency_symbol="false"></span>
							</td>
						</tr>
						<tr class="hide">
							<th class="col-md-7 text-right">@lang( 'purchase.total_before_tax' ):</th>
							<td class="col-md-5 text-left">
								<span id="total_st_before_tax" class="display_currency"></span>
								<input type="hidden" id="st_before_tax_input" value=0>
							</td>
						</tr>
						<tr>
							<th class="col-md-7 text-right">@lang( 'purchase.net_total_amount' ):</th>
							<td class="col-md-5 text-left">
								<span id="total_subtotal" class="display_currency"></span>
								<!-- This is total before purchase tax-->
								<input type="hidden" id="total_subtotal_input" value=0  name="total_before_tax">
							</td>
						</tr>
					</table>
				</div>

				<input type="hidden" id="row_count" value="0">
			</div>
			<br>
			<div class="row">
				{{-- <div class="col-sm-12">
					<button type="button" id="submit_purchase_form" class="btn btn-primary pull-right btn-flat">@lang('messages.save')</button>
				</div> --}}
			</div>
		</div>
	@endcomponent

	<div style="display: none;">
		@component('components.widget', ['class' => 'box-primary'])
			<div class="row">
				<div class="col-sm-12">
				<table class="table">
					<tr>
						<td class="col-md-3">
							<div class="form-group">
								{!! Form::label('discount_type', __( 'purchase.discount_type' ) . ':') !!}
								{!! Form::select('discount_type', [ '' => __('lang_v1.none'), 'fixed' => __( 'lang_v1.fixed' ), 'percentage' => __( 'lang_v1.percentage' )], '', ['class' => 'form-control select2']); !!}
							</div>
						</td>
						<td class="col-md-3">
							<div class="form-group">
							{!! Form::label('discount_amount', __( 'purchase.discount_amount' ) . ':') !!}
							{!! Form::text('discount_amount', 0, ['class' => 'form-control input_number', 'required']); !!}
							</div>
						</td>
						<td class="col-md-3">
							&nbsp;
						</td>
						<td class="col-md-3">
							<b>@lang( 'purchase.discount' ):</b>(-) 
							<span id="discount_calculated_amount" class="display_currency">0</span>
						</td>
					</tr>
					<tr>
						<td>
							<div class="form-group">
							{!! Form::label('tax_id', __('purchase.purchase_tax') . ':') !!}
							<select name="tax_id" id="tax_id" class="form-control select2" placeholder="'Please Select'">
								<option value="" data-tax_amount="0" data-tax_type="fixed" selected>@lang('lang_v1.none')</option>
								@foreach($taxes as $tax)
									<option value="{{ $tax->id }}" data-tax_amount="{{ $tax->amount }}" data-tax_type="{{ $tax->calculation_type }}">{{ $tax->name }}</option>
								@endforeach
							</select>
							{!! Form::hidden('tax_amount', 0, ['id' => 'tax_amount']); !!}
							</div>
						</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>
							<b>@lang( 'purchase.purchase_tax' ):</b>(+) 
							<span id="tax_calculated_amount" class="display_currency">0</span>
						</td>
					</tr>

					<tr>
						<td>
							<div class="form-group">
							{!! Form::label('shipping_details', __( 'purchase.shipping_details' ) . ':') !!}
							{!! Form::text('shipping_details', null, ['class' => 'form-control']); !!}
							</div>
						</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>
							<div class="form-group">
							{!! Form::label('shipping_charges','(+) ' . __( 'purchase.additional_shipping_charges' ) . ':') !!}
							{!! Form::text('shipping_charges', 0, ['class' => 'form-control input_number', 'required']); !!}
							</div>
						</td>
					</tr>

					<tr>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>
							{!! Form::hidden('final_total', 0 , ['id' => 'grand_total_hidden']); !!}
							<b>@lang('purchase.purchase_total'): </b><span id="grand_total" class="display_currency" data-currency_symbol='true'>0</span>
						</td>
					</tr>
					<tr>
						<td colspan="4">
							<div class="form-group">
								{!! Form::label('additional_notes',__('purchase.additional_notes')) !!}
								{!! Form::textarea('additional_notes', null, ['class' => 'form-control', 'rows' => 3]); !!}
							</div>
						</td>
					</tr>

				</table>
				</div>
			</div>
		@endcomponent
	</div>
	<div style="display: none;">
		@component('components.widget', ['class' => 'box-primary', 'title' => __('purchase.add_payment')])
			<div class="box-body payment_row">
				<div class="row">
					<div class="col-md-12">
						<strong>@lang('lang_v1.advance_balance'):</strong> <span id="advance_balance_text">0</span>
						{!! Form::hidden('advance_balance', null, ['id' => 'advance_balance', 'data-error-msg' => __('lang_v1.required_advance_balance_not_available')]); !!}
					</div>
				</div>
				@include('sale_pos.partials.payment_row_form', ['row_index' => 0, 'show_date' => true])
				<hr>
				<div class="row" style="display: none;">
					<div class="col-sm-12">
						<div class="pull-right"><strong>@lang('purchase.payment_due'):</strong> <span id="payment_due">0.00</span></div>
					</div>
				</div>
			</div>
		@endcomponent
	</div>

    <div class="box box-solid">
		<div class="box-body">
			<div class="row">
				<div class="col-sm-4" style="display: none">
					<div class="form-group">
						{!! Form::label('invoice_no', __('sale.invoice_no').':') !!}
                        <input id="invoice_no" class="form-control" name="invoice_no" type="text">
						{{-- {!! Form::text('invoice_no', !empty($sell->return_parent->invoice_no) ? $sell->return_parent->invoice_no : null, ['class' => 'form-control']); !!} --}}
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
			<input type="hidden" name="final_total" 
												id="final_total_input" value=0>
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
						{!! Form::text('search_product_sell', null, ['class' => 'form-control mousetrap', 'id' => 'search_product_sell', 'placeholder' => __('lang_v1.search_product_placeholder'),
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
					{{-- <button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button> --}}
			@php
				$is_mobile = false;
			@endphp
			<button type="button" class="btn bg-navy btn-default @if(!$is_mobile) @endif btn-flat no-print @if($pos_settings['disable_pay_checkout'] != 0) hide @endif @if($is_mobile) col-xs-6 @endif" id="pos-finalize" title="@lang('Payment')"><i class="fas fa-money-check-alt" aria-hidden="true"></i> @lang('Payment') </button>	
				</div>
			</div>
		</div>
	</div>

	@include('sale_pos.partials.payment_modal')

{!! Form::close() !!}
</section>
<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>
<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
	@include('contact.create', ['quick_add' => true])
</div>
<!-- /.content -->
@endsection

@section('javascript')
	{{-- <script src="{{ asset('js/purchase.js?v=' . $asset_v) }}"></script> --}}
    <script src="{{ asset('js/pos_for_return_international.js') }}"></script>
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/international_exchange.js?v=' . $asset_v) }}"></script>
	<script type="text/javascript">
		$(document).ready( function(){
      		__page_leave_confirmation('#add_purchase_form');
      		$('.paid_on').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true,
            });
    	});
    	$(document).on('change', '.payment_types_dropdown, #location_id', function(e) {
		    var default_accounts = $('select#location_id').length ? 
		                $('select#location_id')
		                .find(':selected')
		                .data('default_payment_accounts') : [];
		    var payment_types_dropdown = $('.payment_types_dropdown');
		    var payment_type = payment_types_dropdown.val();
		    var payment_row = payment_types_dropdown.closest('.payment_row');
	        var row_index = payment_row.find('.payment_row_index').val();

	        var account_dropdown = payment_row.find('select#account_' + row_index);
		    if (payment_type && payment_type != 'advance') {
		        var default_account = default_accounts && default_accounts[payment_type]['account'] ? 
		            default_accounts[payment_type]['account'] : '';
		        if (account_dropdown.length && default_accounts) {
		            account_dropdown.val(default_account);
		            account_dropdown.change();\
		        }
		    }

		    if (payment_type == 'advance') {
		        if (account_dropdown) {
		            account_dropdown.prop('disabled', true);
		            account_dropdown.closest('.form-group').addClass('hide');
		        }
		    } else {
		        if (account_dropdown) {
		            account_dropdown.prop('disabled', false); 
		            account_dropdown.closest('.form-group').removeClass('hide');
		        }    
		    }
		});
	</script>
<script>

	// Assuming you have included jQuery in your project

	// Function to extract and store the dynamically populated value
	function extractAndStoreValue() {
		// Get the dynamically populated value
		var totalSubtotalValue = $('#total_subtotal').text().trim();

		// Check if the value is not empty
		if (totalSubtotalValue !== "") {
			// Remove currency symbol and comma, then convert the value to a numeric format
			var numericTotalSubtotalValue = parseFloat(totalSubtotalValue.replace(/[^0-9.-]+/g, ''));

			// Check if the conversion was successful and the result is a valid number and non-zero
			if (!isNaN(numericTotalSubtotalValue) && numericTotalSubtotalValue !== 0) {
				// Now, 'numericTotalSubtotalValue' contains the extracted and non-zero numeric value
				console.log(numericTotalSubtotalValue);
				$(".payment-amount").val(numericTotalSubtotalValue);

				// You can store the value in a variable or use it as needed
			}
		}
	}

	// Create a MutationObserver to listen for changes in the subtree of the span
	var observer = new MutationObserver(extractAndStoreValue);

	// Configure and start observing the target span
	observer.observe(document.getElementById('total_subtotal'), { subtree: true, childList: true });

	// Trigger the function initially if the value is dynamically populated on page load
	$(document).ready(function() {
		extractAndStoreValue();
});

</script>
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
	@include('purchase.partials.keyboard_shortcuts')
@endsection
