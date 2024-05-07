<div class="modal-dialog modal-xl no-print" role="document">
    <div class="modal-content">
      <div class="modal-header">
        {{-- {{ dd($sell) }} --}}
      <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title" id="modalTitle"> Product Adjustment (<b>Reference No.:</b> {{ $sell->ref_no }})
      </h4>
  </div>
  <div class="modal-body">
     <div class="row">
        <div class="col-sm-6 col-xs-6">
          <h4>Product Adjustment Details:</h4>
          <strong>Adjustment Type:</strong> {{ strtoupper($sell->adjustment_type) }} <br>
          <strong>@lang('purchase.business_location'):</strong> {{ $sell->location->name }} <br>
          <strong>Reason:</strong> {{ $sell->additional_notes }}
        </div>
        <div class="col-sm-6 col-xs-6">
          <strong>Reference No.:</strong> {{ $sell->ref_no }} <br>
          <strong>@lang('messages.date'):</strong> {{@format_date($sell->transaction_date)}} <br>
          <strong>Recovered Amount:</strong> {{ $sell->total_amount_recovered }} <br>
        </div>
      </div>
      <br>
      <div class="row">
        <div class="col-sm-12">
          <br>
          <h4>Product(s):</h4>
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
              @foreach($sell->product_adjustment_lines as $adjustment_line)
              @php
                $unit_name = "Pc(s)";  
              @endphp
  
              <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>
                    {{ $adjustment_line->product->sub_sku }}
                  </td>
                  <td><span class="display_currency" data-currency_symbol="true">{{ $adjustment_line->unit_price }}</span></td>
                  <td>{{@format_quantity($adjustment_line->quantity)}} {{$unit_name}}</td>
                  <td>
                    @php
                      $line_total = $adjustment_line->unit_price * $adjustment_line->quantity;
                      $total_before_tax += $line_total;
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
            <th>Total:</th>
            <td></td>
            <td><span class="display_currency pull-right" data-currency_symbol="true" >{{ $sell->final_total }}</span></td>
          </tr>

        </table>
      </div>
    </div>
  </div>
    </div>
  </div>
  
  <script type="text/javascript">
    $(document).ready(function(){
      var element = $('div.modal-xl');
      __currency_convert_recursively(element);
    });
  </script>