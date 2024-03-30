<div class="table-responsive">
    <table class="table table-bordered table-striped ajax_view" id="sell_return_table">
        <thead>
            <tr>
                <th>@lang('messages.date')</th>
                <th>@lang('sale.invoice_no')</th>
                <th>Old Invoice No.</th>
                <th>@lang('sale.customer_name')</th>
                <th>@lang('sale.location')</th>
                {{-- <th>@lang('purchase.payment_status')</th> --}}
                <th>Sub Total</th>
                <th>Discount Amount</th>
                <th>Invoice Amount</th>    
                {{-- <th>@lang('purchase.payment_due')</th> --}}
                <th>@lang('lang_v1.payment_method')</th>
                <th>@lang('messages.action')</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 text-center footer-total">
                <td colspan="5"><strong>@lang('sale.total'):</strong></td>
                {{-- <td id="footer_payment_status_count_sr"></td> --}}
                <td><span class="display_currency" id="footer_sell_return_total" data-currency_symbol ="true"></span></td>
                {{-- <td><span class="display_currency" id="footer_total_due_sr" data-currency_symbol ="true"></span></td> --}}
                <td><span class="display_currency" id="footer_discount_total" data-currency_symbol ="true"></span></td>
                <td><span class="display_currency" id="footer_invoice_total" data-currency_symbol ="true"></span></td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>