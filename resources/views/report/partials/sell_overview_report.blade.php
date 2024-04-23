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
                <td>Exchange Invoices	</td>
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
            {{-- <tr>
                <td>Exchange Amount</td>
                <td id="return-amount">0</td>
            </tr> --}}
            <tr>
                <td>Total Items Sold</td>
                <td id="total-items-sold">0</td>
            </tr>
            <tr class="text-white" style="background-color: #343a40 !important;">
                <td style="font-weight: 700">Invoice Amount</td>
                <td style="font-weight: 700" id="invoice-amount">0</td>
            </tr>
            {{-- <tr>
                <td>Invoice Amount</td>
                <td id="invoice-amount">0</td>
            </tr> --}}
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
            {{-- <tr>
                <td>Total Received</td>
                <td id="total-received">0</td>
            </tr> --}}
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
    </table>

    {{-- <p class="text-muted">
        @lang('lang_v1.profit_note')
    </p> --}}
</div>