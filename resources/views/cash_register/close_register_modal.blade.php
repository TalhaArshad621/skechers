<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action('CashRegisterController@postCloseRegister'), 'method' => 'post' ]) !!}

    {!! Form::hidden('user_id', $register_details->user_id); !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h3 class="modal-title">@lang( 'cash_register.current_register' ) ( {{ \Carbon::createFromFormat('Y-m-d H:i:s', $register_details->open_time)->format('jS M, Y h:i A') }} - {{ \Carbon::now()->format('jS M, Y h:i A') }})</h3>
    </div>

    <div class="modal-body">
      <div class="row">
        <div class="col-sm-12">
          <table class="table">
            <tr>
              <td>
                Opening Balance:
              </td>
              <td>
                <span class="display_currency" data-currency_symbol="true">{{ 12000 }}</span>
              </td>
            </tr>
            @php
              $cash_in_hand = $register_details->total_cash + 12000;
            @endphp
            {!! Form::hidden('cash_in_hand', $cash_in_hand); !!}
            <tr>
              <td>
                @lang('cash_register.cash_in_hand'):
              </td>
              <td>
                {{-- {{ dd($register_details) }} --}}
                <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash + 12000 }}</span>
              </td>
            </tr>
            @php
              $cash_sale = $register_details->total_cash;
            @endphp
            {!! Form::hidden('cash_sale', $cash_sale); !!}
            <tr>
              <td>
                @lang('cash_register.cash_payment'):
              </th>
              <td>
                <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash }}</span>
              </td>
            </tr>
            <tr style="display: none;">

              <td>
                @lang('cash_register.checque_payment'):
              </td>
              <td>
                <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cheque }}</span>
              </td>
            </tr>
            @php
              $card_sale = $register_details->total_card;
            @endphp
            {!! Form::hidden('card_sale', $card_sale); !!}
            <tr>
              <td>
                @lang('cash_register.card_payment'):
              </td>
              <td>
                <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_card }}</span>
              </td>
            </tr>
            <tr>
              <td>
                @lang('Exchange Amount'):
              </td>
              <td>
                <span class="display_currency" data-currency_symbol="true">{{ $sell_return }}</span>
              </td>
            </tr>
            <tr>
              @php
                  $returned_quantity = 0;
              @endphp
          
              @foreach($details['return_product_details'] as $detail)
                  @php
                      $returned_quantity += $detail->returned_quantity;
                  @endphp
              @endforeach
              @foreach($details['return_product_details_international'] as $detail)
              @php
                  $returned_quantity += $detail->returned_quantity;
              @endphp
              @endforeach
          
              <td>
                  @lang('Returned Items'):
              </td>
              
              <td>
                  <span>{{ $returned_quantity }}</span>
              </td>
            </tr>

            <tr>
              @php
                  $total_quantity = 0;
              @endphp
          
              @foreach($details['product_details'] as $detail)
                  @php
                    $total_quantity += $detail->total_quantity;
                  @endphp
              @endforeach
              @foreach($details['product_details_international'] as $detail)
                  @php
                    $total_quantity += $detail->total_quantity;
                  @endphp
              @endforeach
          
              <td>
                  @lang('Sold Items'):
              </td>
              
              <td>
                  <span>{{ $total_quantity }}</span>
              </td>
            </tr>
            {{-- <tr>
              <td>
                @lang('cash_register.bank_transfer'):
              </td>
              <td>
                <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_bank_transfer }}</span>
              </td>
            </tr>
            <tr style="display: none;">
              <td>
                @lang('lang_v1.advance_payment'):
              </td>
              <td>
                <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_advance }}</span>
              </td>
            </tr> --}}
            {{-- @if(array_key_exists('custom_pay_1', $payment_types))
              <tr>
                <td>
                  {{$payment_types['custom_pay_1']}}:
                </td>
                <td>
                  <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_1 }}</span>
                </td>
              </tr>
            @endif
            @if(array_key_exists('custom_pay_2', $payment_types))
              <tr>
                <td>
                  {{$payment_types['custom_pay_2']}}:
                </td>
                <td>
                  <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_2 }}</span>
                </td>
              </tr>
            @endif
            @if(array_key_exists('custom_pay_3', $payment_types))
              <tr>
                <td>
                  {{$payment_types['custom_pay_3']}}:
                </td>
                <td>
                  <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_3 }}</span>
                </td>
              </tr>
            @endif
            @if(array_key_exists('custom_pay_4', $payment_types))
              <tr>
                <td>
                  {{$payment_types['custom_pay_4']}}:
                </td>
                <td>
                  <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_4 }}</span>
                </td>
              </tr>
            @endif
            @if(array_key_exists('custom_pay_5', $payment_types))
              <tr>
                <td>
                  {{$payment_types['custom_pay_5']}}:
                </td>
                <td>
                  <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_5 }}</span>
                </td>
              </tr>
            @endif
            @if(array_key_exists('custom_pay_6', $payment_types))
              <tr>
                <td>
                  {{$payment_types['custom_pay_6']}}:
                </td>
                <td>
                  <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_6 }}</span>
                </td>
              </tr>
            @endif
            @if(array_key_exists('custom_pay_7', $payment_types))
              <tr>
                <td>
                  {{$payment_types['custom_pay_7']}}:
                </td>
                <td>
                  <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_7 }}</span>
                </td>
              </tr>
            @endif
            <tr style="display: none;">
              <td>
                @lang('cash_register.other_payments'):
              </td>
              <td>
                <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_other }}</span>
              </td>
            </tr>
            <tr style="display: none;">
              <td>
                @lang('cash_register.total_sales'):
              </td>
              <td>
                <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_sale }}</span>
              </td>
            </tr>
            <tr class="success" style="display: none;">
              <th>
                @lang('cash_register.total_refund')
              </th>
              <td>
                <b><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_refund }}</span></b><br>
                <small>
                @if($register_details->total_cash_refund != 0)
                  Cash: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_refund }}</span><br>
                @endif
                @if($register_details->total_cheque_refund != 0) 
                  Cheque: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cheque_refund }}</span><br>
                @endif
                @if($register_details->total_card_refund != 0) 
                  Card: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_card_refund }}</span><br> 
                @endif
                @if($register_details->total_bank_transfer_refund != 0)
                  Bank Transfer: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_bank_transfer_refund }}</span><br>
                @endif
                @if(array_key_exists('custom_pay_1', $payment_types) && $register_details->total_custom_pay_1_refund != 0)
                    {{$payment_types['custom_pay_1']}}: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_1_refund }}</span>
                @endif
                @if(array_key_exists('custom_pay_2', $payment_types) && $register_details->total_custom_pay_2_refund != 0)
                    {{$payment_types['custom_pay_2']}}: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_2_refund }}</span>
                @endif
                @if(array_key_exists('custom_pay_3', $payment_types) && $register_details->total_custom_pay_3_refund != 0)
                    {{$payment_types['custom_pay_3']}}: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_3_refund }}</span>
                @endif
                @if($register_details->total_other_refund != 0)
                  Other: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_other_refund }}</span>
                @endif
                </small>
              </td>
            </tr>
            <tr class="success">
              <th>
                @lang('lang_v1.total_payment')
              </th>
              <td>
                <b><span class="display_currency" data-currency_symbol="true">{{ $register_details->cash_in_hand + $register_details->total_cash - $register_details->total_cash_refund }}</span></b>
              </td>
            </tr>
            {{-- <tr class="success">
              <th>
                @lang('lang_v1.credit_sales'):
              </th>
              <td>
                <b><span class="display_currency" data-currency_symbol="true">{{ $details['transaction_details']->total_sales - $register_details->total_sale }}</span></b>
              </td>
            </tr> --}}
            @php
              $total_sales = $register_details->total_cash + $register_details->total_card;
            @endphp
            {!! Form::hidden('total_sales', $total_sales); !!}

            <tr class="success">
              <th>
                @lang('cash_register.total_sales'):
              </th>
              <td>
                <b><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash + $register_details->total_card  }}</span></b>
              </td>
            </tr>
            <tr>
              <th>
                Bank Transfer:
              </th>
              <td>
                <b><input type="number" name="bank_transfer" id="bank_transfer"></input</b>
              </td>
            </tr>
          </table>
        </div>
      </div>

      {{-- @include('cash_register.register_product_details') --}}

      <div class="row" style="display: none;">
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('closing_amount', __( 'cash_register.total_cash' ) . ':*') !!}
              {!! Form::text('closing_amount', @num_format($register_details->total_cash + $details['exchanged_product_details']->total_amount ), ['class' => 'form-control input_number', 'required', 'placeholder' => __( 'cash_register.total_cash' ) ]); !!}
          </div>
        </div>
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('total_card_slips', __( 'cash_register.total_card_slips' ) . ':*') !!} @show_tooltip(__('tooltip.total_card_slips'))
              {!! Form::number('total_card_slips', $register_details->total_card_slips, ['class' => 'form-control', 'required', 'placeholder' => __( 'cash_register.total_card_slips' ), 'min' => 0 ]); !!}
          </div>
        </div> 
        <div class="col-sm-4" style="display: none;">
          <div class="form-group">
            {!! Form::label('total_cheques', __( 'cash_register.total_cheques' ) . ':*') !!} @show_tooltip(__('tooltip.total_cheques'))
              {!! Form::number('total_cheques', $register_details->total_cheques, ['class' => 'form-control', 'required', 'placeholder' => __( 'cash_register.total_cheques' ), 'min' => 0 ]); !!}
          </div>
        </div> 
        <div class="col-sm-12">
          <div class="form-group">
            {!! Form::label('closing_note', __( 'cash_register.closing_note' ) . ':') !!}
              {!! Form::textarea('closing_note', null, ['class' => 'form-control', 'placeholder' => __( 'cash_register.closing_note' ), 'rows' => 3 ]); !!}
          </div>
        </div>
      </div> 

      <div class="row">
        <div class="col-xs-6">
          <b>@lang('report.user'):</b> {{ $register_details->user_name}}<br>
          <b>@lang('business.email'):</b> {{ $register_details->email}}<br>
          <b>@lang('business.business_location'):</b> {{ $register_details->location_name}}<br>
        </div>
        @if(!empty($register_details->closing_note))
          <div class="col-xs-6">
            <strong>@lang('cash_register.closing_note'):</strong><br>
            {{$register_details->closing_note}}
          </div>
        @endif
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.cancel' )</button>
      <button type="submit" class="btn btn-primary">@lang( 'cash_register.close_register' )</button>
    </div>
    {!! Form::close() !!}
  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->