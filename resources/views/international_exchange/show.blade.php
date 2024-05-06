<div class="modal-dialog modal-xl no-print" role="document">
    <div class="modal-content">
      <div class="modal-header">
        {{-- {{ dd($sell) }} --}}
      <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title" id="modalTitle"> Gift (<b>@lang('sale.invoice_no'):</b> {{ $sell->invoice_no }})
      </h4>
  </div>
  <div class="modal-body">
     <div class="row">
        <div class="col-sm-6 col-xs-6">
          <h4>Gift Details:</h4>
          <strong>@lang('contact.customer'):</strong> {{ $sell->contact->name }} <br>
          <strong>@lang('purchase.business_location'):</strong> {{ $sell->location->name }}
        </div>
        <div class="col-sm-6 col-xs-6">
          <strong>@lang('sale.invoice_no'):</strong> {{ $sell->invoice_no }} <br>
          <strong>@lang('messages.date'):</strong> {{@format_date($sell->transaction_date)}}
        </div>
      </div>
      <br>
      <div class="row">
        <div class="col-sm-12">
          <br>
          <h4>Returned Product(s):</h4>
          <table class="table bg-gray">
            <thead>
              <tr class="bg-green">
                  <th>#</th>
                  <th>@lang('product.product_name')</th>
                  <th>@lang('sale.unit_price')</th>
                  <th>Returned Quantity</th>
                  <th>Subtotal</th>
              </tr>
          </thead>
          <tbody>
              @php
                $total_before_tax_return = 0;
              @endphp
              {{-- {{ dd($sell->sell_lines) }} --}}
              @foreach($sell->sell_lines as $sell_line)
              {{-- {{ dd($sell_line) }} --}}
  
              @if($sell_line->sell_line_note == null)
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
                      $total_before_tax_return += $line_total ;
                    @endphp
                    <span class="display_currency" data-currency_symbol="true">{{$line_total}}</span>
                  </td>
              </tr>
              @endforeach
            </tbody>
        </table>
      </div>

      <div class="col-sm-12">
        <br>
        <h4>Exchanged Product(s):</h4>
        <table class="table bg-gray">
          <thead>
            <tr class="bg-green">
                <th>#</th>
                <th>@lang('product.product_name')</th>
                <th>@lang('sale.unit_price')</th>
                <th>Quantity</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php
              $total_before_tax = 0;
              $exchange_total = 0;
            @endphp
            {{-- {{ dd($sell->sell_lines) }} --}}
            @foreach($sell->sell_lines as $sell_line)
            {{-- {{ dd($sell_line) }} --}}

            @if($sell_line->sell_line_note != null)
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
                <td>{{@format_quantity($sell_line->quantity)}} {{$unit_name}}</td>
                <td>
                {{-- @php
                    $exchange_line_total = $sell_line->sold_quantity * $sell_line->sell_price_inc_tax;
                    $exchange_total += $exchange_line_total ;
                  @endphp --}}
    
                  @php
                    $line_total = $sell_line->unit_price_inc_tax * $sell_line->quantity;
                    $total_before_tax += $line_total ;
                  @endphp
                  <span class="display_currency" data-currency_symbol="true">{{$line_total}}</span>
                </td>
            </tr>
            @endforeach
          </tbody>
      </table>
    </div>
    </div>
    <div class="row">
      <div class="col-sm-6 col-sm-offset-6 col-xs-6 col-xs-offset-6">
        <table class="table">
            <tr>
                <th>Exchange Amount: </th>
                <td></td>
                <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $total_before_tax }}</span></td>
            </tr>
            <tr>
                <th>Return Amount: </th>
                <td></td>
                <td><span class="display_currency pull-right" data-currency_symbol="true">-{{ $total_before_tax_return }}</span></td>
            </tr>
            
          <tr>
            <th>Total:</th>
            <td></td>
            <td><span class="display_currency pull-right" data-currency_symbol="true" >{{ $sell->final_total }}</span></td>
          </tr>
        </table>
      </div>
    </div>
  </div>
  <div class="modal-footer">
      <a href="#" class="print-invoice btn btn-primary" data-href="{{action('SellReturnController@printInvoice', [$sell->id])}}"><i class="fa fa-print" aria-hidden="true"></i> @lang("messages.print")</a>
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