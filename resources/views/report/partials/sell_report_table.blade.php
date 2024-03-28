<table class="table table-bordered table-striped" id="sell_report_table">
    <thead>
        <tr>
            <th>SKU</th>
            <th>Buying Date</th>
            {{-- <th>@lang('business.product')</th> --}}
            <th>@lang('sale.location')</th>
            <th>Sell Price</th>
            <th class="stock_price">Buy Price</th>
            @can('view_product_stock_value')
            {{-- <th>@lang('lang_v1.total_stock_price') <br><small>(@lang('lang_v1.by_sale_price'))</small></th> --}}
            <th>Discount Amount</th>
            <th>@lang('lang_v1.potential_profit')</th>
            @endcan
            <th>@lang('report.total_unit_sold')</th>
            <th>Remaining Articles</th>
            {{-- <th>@lang('lang_v1.total_unit_transfered')</th> --}}
            {{-- <th>@lang('lang_v1.total_unit_adjusted')</th> --}}
            @if($show_manufacturing_data)
                <th class="current_stock_mfg">@lang('manufacturing::lang.current_stock_mfg') @show_tooltip(__('manufacturing::lang.mfg_stock_tooltip'))</th>
            @endif
        </tr>
    </thead>
    <tfoot>
        <tr class="bg-gray font-17 text-center footer-total">
            <td colspan="4 "><strong>@lang('sale.total'):</strong></td>
            <td class="footer_total_stock_price"></td>
            @can('view_product_stock_value')
            {{-- <td class="footer_stock_value_by_sale_price"></td> --}}
            <td class="footer_discount_amount"></td>
            <td class="footer_potential_profit"></td>
            @endcan
            <td class="footer_total_sold"></td>
            <td class="footer_total_stock"></td>
            {{-- <td class="footer_total_transfered"></td> --}}
            {{-- <td class="footer_total_adjusted"></td> --}}
            @if($show_manufacturing_data)
                <td class="footer_total_mfg_stock"></td>
            @endif
        </tr>
    </tfoot>
</table>