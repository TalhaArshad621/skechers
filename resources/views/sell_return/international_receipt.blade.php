<!-- business information here -->
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <!-- <link rel="stylesheet" href="style.css"> -->
        <title>Receipt-{{$receipt_details->invoice_no}}</title>
    </head>
    <body>
        <div class="ticket">
        	
        	
        	@if(!empty($receipt_details->logo))
        		<div class="text-box centered">
        			<img style="max-height: 100px; width: auto;" src="{{$receipt_details->logo}}" alt="Logo">
        		</div>
        	@endif
        	<div class="text-box">
        	<!-- Logo -->
            <p class="centered">
            	<!-- Header text -->
            	@if(!empty($receipt_details->header_text))
            		<span class="headings">{!! $receipt_details->header_text !!}</span>
					<br/>
				@endif

				<!-- business information here -->
				@if(!empty($receipt_details->display_name))
					<span class="headings">
						{{$receipt_details->display_name}}
					</span>
					<br/>
				@endif
				
				@if(!empty($receipt_details->address))
					{!! $receipt_details->address !!}
					<br/>
				@endif

				@if(!empty($receipt_details->contact))
					{!! $receipt_details->contact !!}
				@endif
				@if(!empty($receipt_details->contact) && !empty($receipt_details->website))
					, 
				@endif
				@if(!empty($receipt_details->website))
					{{ $receipt_details->website }}
				@endif
				@if(!empty($receipt_details->location_custom_fields))
					<br>{{ $receipt_details->location_custom_fields }}
				@endif

				@if(!empty($receipt_details->sub_heading_line1))
					{{ $receipt_details->sub_heading_line1 }}<br/>
				@endif
				@if(!empty($receipt_details->sub_heading_line2))
					{{ $receipt_details->sub_heading_line2 }}<br/>
				@endif
				@if(!empty($receipt_details->sub_heading_line3))
					{{ $receipt_details->sub_heading_line3 }}<br/>
				@endif
				@if(!empty($receipt_details->sub_heading_line4))
					{{ $receipt_details->sub_heading_line4 }}<br/>
				@endif		
				@if(!empty($receipt_details->sub_heading_line5))
					{{ $receipt_details->sub_heading_line5 }}<br/>
				@endif

				@if(!empty($receipt_details->tax_info1))
					<br><b>{{ $receipt_details->tax_label1 }}</b> {{ $receipt_details->tax_info1 }}
				@endif

				@if(!empty($receipt_details->tax_info2))
					<b>{{ $receipt_details->tax_label2 }}</b> {{ $receipt_details->tax_info2 }}
				@endif

				<!-- Title of receipt -->
				@if(!empty($receipt_details->invoice_heading))
					<br/><span class="sub-headings">{!! $receipt_details->invoice_heading !!}</span>
				@endif
			</p>
			</div>
			<div class="border-top textbox-info">
				<p class="f-left"><strong>Invoice No:</strong></p>
				<p class="f-right">
					{{$receipt_details->invoice_no}}
				</p>
			</div>
			<div class="textbox-info">
				<p class="f-left"><strong>{!! $receipt_details->date_label !!}</strong></p>
				<p class="f-right">
					{{$receipt_details->invoice_date}}
				</p>
			</div>

			<div class="textbox-info">
				<p class="f-left"><strong>STRN</strong></p>
				<p class="f-right">
					3277876333218
				</p>
			</div>
			<div class="textbox-info">
				<p class="f-left"><strong>NTN</strong></p>
				<p class="f-right">
					D094665-3
				</p>
			</div>
			
			@if(!empty($receipt_details->due_date_label))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->due_date_label}}</strong></p>
					<p class="f-right">{{$receipt_details->due_date ?? ''}}</p>
				</div>
			@endif
			
			<div class="textbox-info">
				<p class="f-left"><strong>Sales person</strong></p>
			
				<p class="f-right">{{$receipt_details->commission_agent_name}}</p>
			</div>

			@if(!empty($receipt_details->sales_person_label))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->sales_person_label}}</strong></p>
				
					<p class="f-right">{{$receipt_details->sales_person}}</p>
				</div>
			@endif

			@if(!empty($receipt_details->brand_label) || !empty($receipt_details->repair_brand))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->brand_label}}</strong></p>
				
					<p class="f-right">{{$receipt_details->repair_brand}}</p>
				</div>
			@endif

			@if(!empty($receipt_details->device_label) || !empty($receipt_details->repair_device))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->device_label}}</strong></p>
				
					<p class="f-right">{{$receipt_details->repair_device}}</p>
				</div>
			@endif
			
			@if(!empty($receipt_details->model_no_label) || !empty($receipt_details->repair_model_no))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->model_no_label}}</strong></p>
				
					<p class="f-right">{{$receipt_details->repair_model_no}}</p>
				</div>
			@endif
			
			@if(!empty($receipt_details->serial_no_label) || !empty($receipt_details->repair_serial_no))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->serial_no_label}}</strong></p>
				
					<p class="f-right">{{$receipt_details->repair_serial_no}}</p>
				</div>
			@endif

			@if(!empty($receipt_details->repair_status_label) || !empty($receipt_details->repair_status))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{!! $receipt_details->repair_status_label !!}
					</strong></p>
					<p class="f-right">
						{{$receipt_details->repair_status}}
					</p>
				</div>
        	@endif

        	@if(!empty($receipt_details->repair_warranty_label) || !empty($receipt_details->repair_warranty))
	        	<div class="textbox-info">
	        		<p class="f-left"><strong>
	        			{!! $receipt_details->repair_warranty_label !!}
	        		</strong></p>
	        		<p class="f-right">
	        			{{$receipt_details->repair_warranty}}
	        		</p>
	        	</div>
        	@endif

        	<!-- Waiter info -->
			@if(!empty($receipt_details->service_staff_label) || !empty($receipt_details->service_staff))
	        	<div class="textbox-info">
	        		<p class="f-left"><strong>
	        			{!! $receipt_details->service_staff_label !!}
	        		</strong></p>
	        		<p class="f-right">
	        			{{$receipt_details->service_staff}}
					</p>
	        	</div>
	        @endif

	        @if(!empty($receipt_details->table_label) || !empty($receipt_details->table))
	        	<div class="textbox-info">
	        		<p class="f-left"><strong>
	        			@if(!empty($receipt_details->table_label))
							<b>{!! $receipt_details->table_label !!}</b>
						@endif
	        		</strong></p>
	        		<p class="f-right">
	        			{{$receipt_details->table}}
	        		</p>
	        	</div>
	        @endif

	        <!-- customer info -->
	        <div class="textbox-info">
	        	<p class="f-left" style="vertical-align: top;"><strong>
	        		{{$receipt_details->customer_label ?? ''}}
	        	</strong></p>

	        	<p class="f-right">
	        		{{ $receipt_details->customer_name ?? '' }}
	        		@if(!empty($receipt_details->customer_info))
	        			<div class="bw">
						{!! $receipt_details->customer_info !!}
						</div>
					@endif
	        	</p>
	        </div>
			
			@if(!empty($receipt_details->client_id_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{{ $receipt_details->client_id_label }}
					</strong></p>
					<p class="f-right">
						{{ $receipt_details->client_id }}
					</p>
				</div>
			@endif
			
			@if(!empty($receipt_details->customer_tax_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{{ $receipt_details->customer_tax_label }}
					</strong></p>
					<p class="f-right">
						{{ $receipt_details->customer_tax_number }}
					</p>
				</div>
			@endif

			@if(!empty($receipt_details->customer_custom_fields))
				<div class="textbox-info">
					<p class="centered">
						{!! $receipt_details->customer_custom_fields !!}
					</p>
				</div>
			@endif
			
			@if(!empty($receipt_details->customer_rp_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{{ $receipt_details->customer_rp_label }}
					</strong></p>
					<p class="f-right">
						{{ $receipt_details->customer_total_rp }}
					</p>
				</div>
			@endif
				@php
					$total_new_discount = 0;
					$total_ex_discount = 0;
				@endphp
            <table style="margin-top: 10px !important" class="border-bottom width-100 table-f-12 mb-10">
                <thead class="border-bottom-dotted">
                    <tr>
                        <th class="serial_number">#</th>
                        <th class="description" width="25%">
                        	{{$receipt_details->table_product_label}}
                        </th>
                        <th class="quantity text-right">
                        	{{$receipt_details->table_qty_label}}
                        </th>
                        @if(empty($receipt_details->hide_price))
                        <th class="unit_price text-right">
                        	{{$receipt_details->table_unit_price_label}}
                        </th>
						<th class="discount text-right">
                        	Disc.
                        </th>
                        <th class="price text-right">{{$receipt_details->table_subtotal_label}}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    {{-- {{dd($receipt_details->lines)}}     --}}
                	@forelse($receipt_details->lines as $line)
						@if ($line['quantity'] > 0)
						@php
						$total_new_discount += $line['new_discount_amount'];
						@endphp
	                    <tr>
	                        <td class="serial_number" style="vertical-align: top;">
	                        	{{$loop->iteration}}
	                        </td>
	                        <td class="description">
	                        	@if(!empty($line['sub_sku'])) {{$line['sub_sku']}} @endif
	                        </td>
	                        <td class="quantity text-right">{{$line['quantity']}}</td>
	                        @if(empty($receipt_details->hide_price))
	                        <td class="unit_price text-right">{{$line['unit_price_inc_tax']}}</td>
							<td class="discount text-right">{{(int)$line['new_discount_amount']}}</td>
	                        <td class="price text-right">{{$line['line_total']}}</td>
	                        @endif
	                    </tr>
						@endif
						@if ($line['quantity_returned'] > 0)
	                    <tr>
	                        <td class="serial_number" style="vertical-align: top;">
	                        	{{$loop->iteration}}
	                        </td>
	                        <td class="description">
	                        	@if(!empty($line['sub_sku'])) {{$line['sub_sku']}} - (EX) @endif
	                        </td>
	                        <td class="quantity text-right">{{$line['quantity_returned']}}</td>
	                        @if(empty($receipt_details->hide_price))
	                        <td class="unit_price text-right">{{$line['unit_price_inc_tax']}}</td>
							<td class="discount text-right">{{(int)$line['new_discount_amount']}}</td>
	                        <td class="price text-right">{{$line['original_price'] * $line['quantity_returned'] }}</td>
	                        @endif
	                    </tr>
						@endif
                    @endforeach
                	
                    <tr>
                    	<td colspan="5">&nbsp;</td>
                    </tr>
                </tbody>
            </table>
			@if(!empty($receipt_details->total_quantity_label))
				<div class="flex-box">
					<p class="left text-right">
						{!! $receipt_details->total_quantity_label !!}
					</p>
					<p class="width-50 text-right">
						{{$receipt_details->total_quantity}}
					</p>
				</div>
			@endif
			@if(empty($receipt_details->hide_price))
                <div class="flex-box">
                    <p class="left text-right sub-headings">
                    	{!! $receipt_details->subtotal_label !!}
                    </p>
                    <p class="width-50 text-right sub-headings">
                    	{{$receipt_details->subtotal}}
                    </p>
                </div>

                <!-- Shipping Charges -->
				@if(!empty($receipt_details->shipping_charges))
					<div class="flex-box">
						<p class="left text-right">
							{!! $receipt_details->shipping_charges_label !!}
						</p>
						<p class="width-50 text-right">
							{{$receipt_details->shipping_charges}}
						</p>
					</div>
				@endif

				@if(!empty($receipt_details->packing_charge))
					<div class="flex-box">
						<p class="left text-right">
							{!! $receipt_details->packing_charge_label !!}
						</p>
						<p class="width-50 text-right">
							{{$receipt_details->packing_charge}}
						</p>
					</div>
				@endif

				<!-- Discount -->
				@if( !empty($receipt_details->discount) )
					<div class="flex-box">
						<p class="width-50 text-right">
							{!! $receipt_details->discount_label !!}
						</p>

						<p class="width-50 text-right">
							(-) {{$receipt_details->discount}}
						</p>
					</div>
				@endif

				@if(!empty($receipt_details->reward_point_label) )
					<div class="flex-box">
						<p class="width-50 text-right">
							{!! $receipt_details->reward_point_label !!}
						</p>

						<p class="width-50 text-right">
							(-) {{$receipt_details->reward_point_amount}}
						</p>
					</div>
				@endif

				<div class="flex-box">
					<p class="width-50 text-right">
						GST:
					</p>
					<p class="width-50 text-right">
						 {{$receipt_details->total_uf / 5}}
					</p>
				</div>

				<div class="flex-box">
					<p class="width-50 text-right">
						Total Amount EXC Tax:
					</p>
					<p class="width-50 text-right">
						 {{ $receipt_details->total_uf - ($receipt_details->total_uf / 5)}}
					</p>
				</div>

				@if( !empty($receipt_details->tax) )
					<div class="flex-box">
						<p class="width-50 text-right">
							{!! $receipt_details->tax_label !!}
						</p>
						<p class="width-50 text-right">
							(+) {{$receipt_details->tax}}
						</p>
					</div>
				@endif

				@if( $receipt_details->round_off_amount > 0)
					<div class="flex-box">
						<p class="width-50 text-right">
							{!! $receipt_details->round_off_label !!} 
						</p>
						<p class="width-50 text-right">
							{{$receipt_details->round_off}}
						</p>
					</div>
				@endif

				<div class="flex-box">
					<p class="width-50 text-right sub-headings">
						{!! $receipt_details->total_label !!}
					</p>
					<p class="width-50 text-right sub-headings">
						{{$receipt_details->total}}
					</p>
				</div>
				@if(!empty($receipt_details->total_in_words))
				<p colspan="2" class="text-right mb-0">
					<small>
					({{$receipt_details->total_in_words}})
					</small>
				</p>
				@endif
				@if(!empty($receipt_details->payments))
					@foreach($receipt_details->payments as $payment)
						<div class="flex-box">
							<p class="width-50 text-right">{{$payment['method']}} ({{$payment['date']}}) </p>
							<p class="width-50 text-right">{{$payment['amount']}}</p>
						</div>
					@endforeach
				@endif

				<!-- Total Paid-->
				@if(!empty($receipt_details->total_paid))
					<div class="flex-box">
						<p class="width-50 text-right">
							{!! $receipt_details->total_paid_label !!}
						</p>
						<p class="width-50 text-right">
							{{$receipt_details->total_paid}}
						</p>
					</div>
				@endif

				<!-- Total Due-->
				@if(!empty($receipt_details->total_due))
					<div class="flex-box">
						<p class="width-50 text-right">
							{!! $receipt_details->total_due_label !!}
						</p>
						<p class="width-50 text-right">
							{{$receipt_details->total_due}}
						</p>
					</div>
				@endif

				@if(!empty($receipt_details->all_due))
					<div class="flex-box">
						<p class="width-50 text-right">
							{!! $receipt_details->all_bal_label !!}
						</p>
						<p class="width-50 text-right">
							{{$receipt_details->all_due}}
						</p>
					</div>
				@endif
			@endif
            <div class="border-bottom width-100">&nbsp;</div>
            @if(empty($receipt_details->hide_price))
	            <!-- tax -->
	            @if(!empty($receipt_details->taxes))
	            	<table class="border-bottom width-100 table-f-12">
	            		@foreach($receipt_details->taxes as $key => $val)
	            			<tr>
	            				<td class="left">{{$key}}</td>
	            				<td class="right">{{$val}}</td>
	            			</tr>
	            		@endforeach
	            	</table>
	            @endif
            @endif


            @if(!empty($receipt_details->additional_notes))
	            <p class="centered" >
	            	{!! nl2br($receipt_details->additional_notes) !!}
	            </p>
            @endif

            {{-- Barcode --}}
			@if($receipt_details->show_barcode)
				<br/>
				<img class="center-block" src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2,30,array(39, 48, 54), true)}}">
			@endif

			@if(!empty($receipt_details->footer_text))
				<p class="centered">
					{!! $receipt_details->footer_text !!}
				</p>
			@endif

			<P class="centered" style="margin-top: 10px">
				<span style="font-size: 20px; font-weight:700">FBR Invoice #</span> 
			   <br>
			   {!! $receipt_details->fbr_id !!}
		   </P>
        </div>
        <!-- <button id="btnPrint" class="hidden-print">Print</button>
        <script src="script.js"></script> -->
    </body>
</html>

<style type="text/css">
	.f-8 {
		font-size: 8px !important;
	}
	@media print {
		* {
			font-size: 12px;
			font-family: 'Times New Roman';
			word-break: break-all;
		}
		.f-8 {
			font-size: 8px !important;
		}
		
	.headings{
		font-size: 16px;
		font-weight: 700;
		text-transform: uppercase;
		white-space: nowrap;
	}
	
	.sub-headings{
		font-size: 15px !important;
		font-weight: 700 !important;
	}
	
	.border-top{
		border-top: 1px solid #242424;
	}
	.border-bottom{
		border-bottom: 1px solid #242424;
	}
	
	.border-bottom-dotted{
		border-bottom: 1px dotted darkgray;
	}
	
	td.serial_number, th.serial_number{
		width: 5%;
		max-width: 5%;
	}
	
	td.description,
	th.description {
		width: 25%;
		max-width: 25%;
	}
	
	td.quantity,
	th.quantity {
		width: 8%;
		max-width: 8%;
		word-break: break-all;
	}
	td.unit_price, th.unit_price{
		width: 25%;
		max-width: 25%;
		word-break: break-all;
	}
	td.discount, th.discount{
		width: 20%;
		max-width: 20%;
		word-break: break-all;
	}
	
	td.discount,
	th.discount {
		width: 15%;
		max-width: 15%;
		word-break: break-all;
	}
	
	.centered {
		text-align: center;
		align-content: center;
	}
	
	.ticket {
		width: 100%;
		max-width: 100%;
	}
	
	img {
		max-width: inherit;
		width: auto;
	}
	
		.hidden-print,
		.hidden-print * {
			display: none !important;
		}
	}
	.table-info {
		width: 100%;
	}
	.table-info tr:first-child td, .table-info tr:first-child th {
		padding-top: 8px;
	}
	.table-info th {
		text-align: left;
	}
	.table-info td {
		text-align: right;
	}
	.logo {
		float: left;
		width:35%;
		padding: 10px;
	}
	
	.text-with-image {
		float: left;
		width:65%;
	}
	.text-box {
		width: 100%;
		height: auto;
	}
	
	.textbox-info {
		clear: both;
	}
	.textbox-info p {
		margin-bottom: 0px
	}
	.flex-box {
		display: flex;
		width: 100%;
	}
	.flex-box p {
		width: 50%;
		margin-bottom: 0px;
		white-space: nowrap;
	}
	
	.table-f-12 th, .table-f-12 td {
		font-size: 12px;
		word-break: break-word;
	}
	
	.bw {
		word-break: break-word;
	}
	</style>