@extends('layouts.app')
@section('title', __('Ecommerce Sell Report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('Ecommerce Sell Report')}}</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" 
                    id="ecommerce_report_table">
                        <thead>
                            <tr>
                                <th>@lang('Transaction Date')</th>
                                <th>@lang('sale.product')</th>
                                <th>@lang('Exchange')</th>
                                <th>@lang('Product Category')</th>
                                <th>@lang('Quantity Ordered')</th>
                                <th>Sell</th>
                                <th>Available Stock</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 text-center footer-total">
                                <td colspan="5"><strong>@lang('sale.total'):</strong></td>
                                <td id="footer_total"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->
<div class="modal fade view_register" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

@endsection

@section('javascript')
    <script>
    $(document).ready(function() {

        ecommerce_report_table = $('#ecommerce_report_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/reports/ecommerce-sell-report'
            },
            columns: [
                { data: 'transaction_date', name: 'ecommerce_transactions.transaction_date' },
                { data: 'product_name', name: 'products.name', searchable: true },
                { data: 'quantity_returned', name: 'ESL.quantity_returned' },
                { data: 'category_name', name: 'categories.name' },
                { data: 'quantity', name: 'ESL.quantity' },
                { data: 'sell_price', name: 'ESL.unit_price_inc_tax' },
                { data: 'qty_available', name: 'VLD.qty_available' }
            ],
            fnDrawCallback: function(oSettings) {
            var api = this.api();
            var sellPriceColumn = api.column(5, { page: 'current' });
            var sum = sellPriceColumn.data().reduce(function (acc, val) {
                return acc + parseFloat(val);
            }, 0);
            $('#footer_total').text(sum.toFixed(2));
            __currency_convert_recursively($('#ecommerce_report_table'));
        },
        });
    });

    </script>
@endsection