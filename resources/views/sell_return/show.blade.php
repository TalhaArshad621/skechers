<div class="modal-dialog modal-xl no-print" role="document">
  <div class="modal-content">
    <div class="modal-header">
      {{-- {{ dd($sell) }} --}}
    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title" id="modalTitle"> @lang('lang_v1.sell_return') (<b>@lang('sale.invoice_no'):</b> {{ $sell->return_parent->invoice_no }})
    </h4>
</div>
<div class="modal-body">
   <div class="row">
      <div class="col-sm-6 col-xs-6">
        <h4>@lang('lang_v1.sell_return_details'):</h4>
        <strong>@lang('lang_v1.return_date'):</strong> {{@format_date($sell->return_parent->transaction_date)}}<br>
        <strong>@lang('contact.customer'):</strong> {{ $sell->contact->name }} <br>
        <strong>@lang('purchase.business_location'):</strong> {{ $sell->location->name }}
      </div>
      <div class="col-sm-6 col-xs-6">
        <h4>@lang('lang_v1.sell_details'):</h4>
        <strong>@lang('sale.invoice_no'):</strong> {{ $sell->invoice_no }} <br>
        @php
          use Carbon\Carbon;
          $formattedDate = Carbon::parse($sell->transaction_date)->format('d/m/Y g:i A');
        @endphp
        <strong>@lang('messages.date'):</strong> {{$formattedDate}}<br>
        <b>{{ __('Employee Name') }}:</b> {{ $agent_name->full_name }}<br>

      </div>
    </div>
    <br>
    @php
    $total_before_tax = 0;
    $exchange_total = 0;
  @endphp
    <div class="row">
      <div class="col-sm-12">
        <br>
        <h4>Returned Product(s):</h4>
        @if ($saleReturn->isEmpty())
        <table class="table bg-gray">
          <thead>
            <tr class="bg-green">
                <th>#</th>
                <th>@lang('product.product_name')</th>
                <th>@lang('sale.unit_price')</th>
                <th>@lang('lang_v1.return_quantity')</th>
                <th>@lang('lang_v1.return_subtotal')</th>
            </tr>
        </thead>
        <tbody>
          
            @foreach($sell->sell_lines as $sell_line)

            @if($sell_line->quantity_returned == 0)
                @continue
            @endif

            @php
              $unit_name = $sell_line->product->unit->short_name;

              if(!empty($sell_line->sub_unit)) {
                $unit_name = $sell_line->sub_unit->short_name;
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
                <td>{{@format_quantity($sell_line->quantity_returned)}} {{$unit_name}}</td>
                <td>
                  @php
                    $line_total = $sell_line->unit_price_inc_tax * $sell_line->quantity_returned;
                    $total_before_tax += $line_total ;
                  @endphp
                  <span class="display_currency" data-currency_symbol="true">{{$line_total}}</span>
                </td>
            </tr>
            @endforeach
          </tbody>
      </table>

          @else
          <table class="table bg-gray">
           
            <tr class="bg-green">
            <th>#</th>
            <th>{{ __('SKU') }}</th>
            @if( session()->get('business.enable_lot_number') == 1)
                <th>{{ __('lang_v1.lot_n_expiry') }}</th>
            @endif
            <th>{{ __('sale.qty') }}</th>
            @if(!empty($pos_settings['inline_service_staff']))
                <th>
                    @lang('restaurant.service_staff')
                </th>
            @endif
            <th>{{ 'Price' }}</th>
            <th>{{ __('sale.discount') }}</th>
      
            <th>{{ __('sale.subtotal') }}</th>
        </tr>
        @foreach($saleReturn as $sell_line)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                    {{ $sell_line->sku }}
                </td>
                <td>
                    <span class="display_currency" data-currency_symbol="false" data-is_quantity="true">{{ $sell_line->quantity_returned }}</span>
                </td>
                @if(!empty($pos_settings['inline_service_staff']))
                    <td>
                    {{ $sell_line->service_staff->user_full_name ?? '' }}
                    </td>
                @endif
                <td>
                    <span class="display_currency" data-currency_symbol="true">{{ $sell_line->sell_price_inc_tax }}</span>
                </td>
                <td>
                  <span class="display_currency" data-currency_symbol="true">{{ $sell_line->total_sell_discount }}</span> ({{intval($sell_line->line_discount_amount)}}%)
                </td>
                <td>
                  @php
                     $line_total = ($sell_line->quantity_returned * $sell_line->sell_price_inc_tax) - $sell_line->total_sell_discount;
                     $total_before_tax += $line_total ;
                 @endphp
                    <span class="display_currency" data-currency_symbol="true">{{ ($sell_line->quantity_returned * $sell_line->sell_price_inc_tax) - $sell_line->total_sell_discount }}</span>
                </td>
            </tr>
        @endforeach
    </table>
                
        @endif
    </div>
  </div>
  <div class="row">
    <div class="col-sm-12">
      <br>
      <h4>Exchanged Product(s):</h4>
      <table class="table bg-gray">
        <tr class="bg-green">
        <th>#</th>
        <th>{{ __('SKU') }}</th>
        @if( session()->get('business.enable_lot_number') == 1)
            <th>{{ __('lang_v1.lot_n_expiry') }}</th>
        @endif
        <th>{{ __('sale.qty') }}</th>
        @if(!empty($pos_settings['inline_service_staff']))
            <th>
                @lang('restaurant.service_staff')
            </th>
        @endif
        <th>{{ 'Price' }}</th>
        <th>{{ __('sale.discount') }}</th>
        <th>{{ __('sale.subtotal') }}</th>
    </tr>
    @foreach($exchangedSale as $sell_line)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>
                {{ $sell_line->sku }}
            </td>
            <td>
                <span class="display_currency" data-currency_symbol="false" data-is_quantity="true">{{ $sell_line->sold_quantity }}</span>
            </td>
            @if(!empty($pos_settings['inline_service_staff']))
                <td>
                {{ $sell_line->service_staff->user_full_name ?? '' }}
                </td>
            @endif
            <td>
                <span class="display_currency" data-currency_symbol="true">{{ (int)$sell_line->sell_price_inc_tax }}</span>
            </td>
            <td>
              <span class="display_currency" data-currency_symbol="true">{{ $sell_line->total_sell_discount }}</span> ({{intval($sell_line->line_discount_amount)}}%)
            </td>
            <td>
              @php
                $exchange_line_total = ($sell_line->sold_quantity * $sell_line->sell_price_inc_tax) - $sell_line->total_sell_discount;
                $exchange_total += $exchange_line_total ;
              @endphp
                <span class="display_currency" data-currency_symbol="true">{{ ($sell_line->sold_quantity * $sell_line->sell_price_inc_tax) - $sell_line->total_sell_discount }}</span>
            </td>
        </tr>
        @if(!empty($sell_line->modifiers))
        @foreach($sell_line->modifiers as $modifier)
            <tr>
                <td>&nbsp;</td>
                <td>
                    {{ $modifier->product->name }} - {{ $modifier->variations->name ?? ''}},
                    {{ $modifier->variations->sub_sku ?? ''}}
                </td>
                @if( session()->get('business.enable_lot_number') == 1)
                    <td>&nbsp;</td>
                @endif
                <td>{{ $modifier->quantity }}</td>
                <td>
                    <span class="display_currency" data-currency_symbol="true">{{ $modifier->unit_price }}</span>
                </td>
                <td>
                    &nbsp;
                </td>
                <td>
                    <span class="display_currency" data-currency_symbol="true">{{ $modifier->item_tax }}</span> 
                    @if(!empty($taxes[$modifier->tax_id]))
                    ( {{ $taxes[$modifier->tax_id]}} )
                    @endif
                </td>
                <td>
                    <span class="display_currency" data-currency_symbol="true">{{ $modifier->unit_price_inc_tax }}</span>
                </td>
                <td>
                    <span class="display_currency" data-currency_symbol="true">{{ $modifier->quantity * $modifier->unit_price_inc_tax }}</span>
                </td>
            </tr>
            @endforeach
        @endif
    @endforeach
</table>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-6 col-sm-offset-6 col-xs-6 col-xs-offset-6">
      <table class="table">
        <tr>
          <th>Exchange Amount: </th>
          <td></td>
          <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $exchange_total }}</span></td>
        </tr>
        <tr>
          <th>Return Amount: </th>
          <td></td>
          <td><span class="display_currency pull-right" data-currency_symbol="true">-{{ $total_before_tax }}</span></td>
        </tr>
        

        {{-- <tr>
          <th>@lang('lang_v1.return_discount'): </th>
          <td><b>(-)</b></td>
          <td class="text-right">@if($sell->return_parent->discount_type == 'percentage')
              @<strong><small>{{$sell->return_parent->discount_amount}}%</small></strong> -
              @endif
          <span class="display_currency pull-right" data-currency_symbol="true">{{ $total_discount }}</span></td>
        </tr> --}}
        
        {{-- <tr>
          <th>@lang('lang_v1.total_return_tax'):</th>
          <td><b>(+)</b></td>
          <td class="text-right">
              @if(!empty($sell_taxes))
                @foreach($sell_taxes as $k => $v)
                  <strong><small>{{$k}}</small></strong> - <span class="display_currency pull-right" data-currency_symbol="true">{{ $v }}</span><br>
                @endforeach
              @else
              0.00
              @endif
            </td>
        </tr> --}}
        <tr>
          <th>@lang('lang_v1.return_total'):</th>
          <td></td>
          <td><span class="display_currency pull-right" data-currency_symbol="true" >{{ $sell->return_parent->final_total }}</span></td>
        </tr>
      </table>
    </div>
  </div>
  <div class="row">
    <div class="col-md-12">
          <strong>{{ __('repair::lang.activities') }}:</strong><br>
          @includeIf('activity_log.activities', ['activity_type' => 'sell'])
      </div>
  </div>
</div>
<div class="modal-footer">
    <a href="#" class="print-invoice btn btn-primary" data-href="{{action('SellReturnController@printInvoice', [$sell->return_parent->id])}}"><i class="fa fa-print" aria-hidden="true"></i> @lang("messages.print")</a>
      <button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function(){
    var element = $('div.modal-xl');
    __currency_convert_recursively(element);
  });
</script>