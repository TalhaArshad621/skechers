<div class="table-responsive">
<table class="table table-bordered table-striped" id="sr_sales_with_commission_table" style="width: 100%;">
    <thead>
        <tr>
            <th>Employee Name</th>
            <th>Total Invoices</th>
            <th>Total Items</th>
            <th>Total Sales</th>
        </tr>
    </thead>
    <tfoot>
        <tr class="bg-gray font-17 footer-total text-center">
            <td><strong>@lang('sale.total'):</strong></td>
            <td id="footer_total_invoices"></td>
            <td><span class="display_currency" id="footer_total_items" data-currency_symbol ="false"></span></td>
            <td><span class="display_currency" id="footer_total_sales" data-currency_symbol ="true"></span></td>
        </tr>
    </tfoot>
</table>
</div>