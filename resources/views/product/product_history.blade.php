@extends('layouts.app')
@section('title', __('Product History'))

<style>
    .purchase-wise-heading {
        text-decoration: underline;
    }
</style>

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('Product History')</h1>
</section>

<!-- Main content -->
<section class="content">
<div class="row">
    <div class="col-md-12">
        <h4>@lang($product->name)</h4>
    </div>
    <input type="hidden" id="row_id" value="{{ $id }}">
    <div style="display: none;">
        <div class="col-md-12">
            @component('components.widget', ['title' => $product->name])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                @if($product->type == 'variable')
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="variation_id">@lang('product.variations'):</label>
                            <select class="select2 form-control" name="variation_id" id="variation_id">
                                @foreach($product->variations as $variation)
                                    <option value="{{$variation->id}}">{{$variation->product_variation->name}} - {{$variation->name}} ({{$variation->sub_sku}})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @else
                    <input type="hidden" id="variation_id" name="variation_id" value="{{$product->variations->first()->id}}">
                @endif
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="table-responsive">
                    <h4 class="purchase-wise-heading text-secondary font-weight-bold mb-4">Purchase Wise</h4>
                    <table class="table table-bordered table-striped" id="product_purchase_report_table">
                        <thead>
                            <tr>
                                <th>@lang('Image')</th>
                                <th>@lang('product.sku')</th>
                                <th>@lang('Invoice No.')</th>
                                <th>@lang('sale.qty')</th>
                                <th>@lang('Date')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="3"><strong>@lang('sale.total'):</strong></td>
                                <td id="product_purchase_report_table_footer"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="table-responsive">
                    <h4 class="purchase-wise-heading text-secondary font-weight-bold mb-4">Sell Wise</h4>
                    <table class="table table-bordered table-striped" 
                    id="product_sell_report_table">
                        <thead>
                            <tr>
                                <th>@lang('Image')</th>
                                <th>@lang('product.sku')</th>
                                <th>@lang('Invoice No.')</th>
                                <th>@lang('sale.qty')</th>
                                <th>@lang('Store')</th>
                                <th>@lang('Date')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="3"><strong>@lang('sale.total'):</strong></td>
                                <td id="product_sell_report_table_footer"></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="table-responsive">
                    <h4 class="purchase-wise-heading text-secondary font-weight-bold mb-4">Gift Wise</h4>
                    <table class="table table-bordered table-striped" 
                    id="product_gift_report_table">
                        <thead>
                            <tr>
                                <th>@lang('Image')</th>
                                <th>@lang('product.sku')</th>
                                <th>@lang('Invoice No.')</th>
                                <th>@lang('sale.qty')</th>
                                <th>@lang('Returned')</th>
                                <th>@lang('Date')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="3"><strong>@lang('sale.total'):</strong></td>
                                <td id="product_gift_report_table_footer"></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="table-responsive">
                    <h4 class="purchase-wise-heading text-secondary font-weight-bold mb-4">E-commerce Sell Wise</h4>
                    <table class="table table-bordered table-striped" 
                    id="product_ecommerce_report_table">
                        <thead>
                            <tr>
                                <th>@lang('Image')</th>
                                <th>@lang('product.sku')</th>
                                <th>@lang('Invoice No.')</th>
                                <th>@lang('sale.qty')</th>
                                <th>@lang('Store')</th>
                                <th>@lang('POS Status')</th>
                                <th>@lang('Date')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="3"><strong>@lang('sale.total'):</strong></td>
                                <td id="product_ecommerce_report_table_footer"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="table-responsive">
                    <h4 class="purchase-wise-heading text-secondary font-weight-bold mb-4">E-commerce Return Wise</h4>
                    <table class="table table-bordered table-striped" 
                    id="product_ecommerce_return_report_table">
                        <thead>
                            <tr>
                                <th>@lang('Image')</th>
                                <th>@lang('product.sku')</th>
                                <th>@lang('Parent Invoice No.')</th>
                                <th>@lang('Invoice No.')</th>
                                <th>@lang('Quantity Returned')</th>
                                <th>@lang('Store')</th>
                                <th>@lang('POS Status')</th>
                                <th>@lang('Date')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                                <td id="product_ecommerce_return_report_table_footer"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="table-responsive">
                    <h4 class="purchase-wise-heading text-secondary font-weight-bold mb-4">Store To Store Transfer Wise</h4>
                    <table class="table table-bordered table-striped" 
                    id="store_to_store_transfer_report_table">
                        <thead>
                            <tr>
                                <th>@lang('Image')</th>
                                <th>@lang('product.sku')</th>
                                <th>@lang('Transfer Date')</th>
                                <th>@lang('Quantity')</th>
                                <th>@lang('Sender')</th>
                                <th>@lang('Receiver')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                                <td id="store_to_store_transfer_report_table_footer"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="table-responsive">
                    <h4 class="purchase-wise-heading text-secondary font-weight-bold mb-4">Store To Warehouse Transfer Wise</h4>
                    <table class="table table-bordered table-striped" 
                    id="store_to_warehouse_transfer_report_table">
                        <thead>
                            <tr>
                                <th>@lang('Image')</th>
                                <th>@lang('product.sku')</th>
                                <th>@lang('Transfer Date')</th>
                                <th>@lang('Quantity')</th>
                                <th>@lang('Sender')</th>
                                <th>@lang('Receiver')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                                <td id="store_to_warehouse_transfer_report_table_footer"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="table-responsive">
                    <h4 class="purchase-wise-heading text-secondary font-weight-bold mb-4">Warehouse To Store Transfer Wise</h4>
                    <table class="table table-bordered table-striped" 
                    id="warehouse_to_store_transfer_report_table">
                        <thead>
                            <tr>
                                <th>@lang('Image')</th>
                                <th>@lang('product.sku')</th>
                                <th>@lang('Transfer Date')</th>
                                <th>@lang('Quantity')</th>
                                <th>@lang('Sender')</th>
                                <th>@lang('Receiver')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                                <td id="warehouse_to_store_transfer_report_table_footer"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="table-responsive">
                    <h4 class="purchase-wise-heading text-secondary font-weight-bold mb-4">Product Adjustment Wise</h4>
                    <table class="table table-bordered table-striped" 
                    id="product_adjustment_report_table">
                        <thead>
                            <tr>
                                <th>@lang('Image')</th>
                                <th>@lang('product.sku')</th>
                                <th>@lang('Invoice ID')</th>
                                <th>@lang('Type')</th>
                                <th>@lang('Quantity')</th>
                                <th>@lang('Store')</th>
                                <th>@lang('Transaction Date')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                                <td id="product_adjustment_report_table_footer"></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                {{-- <div class="table-responsive">
                    <table class="table table-bordered table-striped" 
                    id="product_sell_grouped_report_table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>@lang('sale.product')</th>
                                <th>@lang('product.sku')</th>
                                <th>@lang('messages.date')</th>
                                <th>@lang('report.current_stock')</th>
                                <th>@lang('report.total_unit_sold')</th>
                                <th>@lang('sale.total')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                                <td id="footer_total_grouped_sold"></td>
                                <td><span class="display_currency" id="footer_grouped_subtotal" data-currency_symbol ="true"></span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div> --}}
            @endcomponent
        </div>
    </div>
    {{-- @component('components.widget')
        <div id="product_stock_history" style="display: none;"></div>
    @endcomponent --}}
    </div>
</div>

</section>
<!-- /.content -->
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        var productId = $("#row_id").val();
        console.log(productId);

        //purchase table js code
        product_purchase_report = $('table#product_purchase_report_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/products/history/',
                data: function (d) {
                d.id = productId;
                },
            },
            columns: [
                { data: 'product_image', name: 'product_image' },
                { data: 'product_sku', name: 'product_sku' },
                { data: 'invoice_id', name: 'invoice_id' },
                { data: 'purchase_quantity', name: 'purchase_lines.quantity' },
                { data: 'transaction_date', name: 'transaction_date' }
            ],
            fnDrawCallback: function(oSettings) {
                var totalPurchaseQuantity = sum_table_col($('#product_purchase_report_table'), 'purchase_quantity');
                $('#product_purchase_report_table_footer').text(totalPurchaseQuantity);                
            },
        });

        //sell table js code
        product_sell_report_table = $('table#product_sell_report_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[1, 'desc']],
            ajax: {
                url: '/products/sell-history/',
                data: function (d) {
                d.id = productId;
                },
            },
            columns: [
                { data: 'product_image', name: 'product_image' },
                { data: 'product_sku', name: 'product_sku' },
                { data: 'invoice_no', name: 'invoice_no' },
                { data: 'sell_quantity', name: 'sell_quantity' },
                { data: 'store_name', name: 'store_name' },
                { data: 'transaction_date', name: 'transaction_date' },
            ],
            fnDrawCallback: function(oSettings) {
                $('#product_sell_report_table_footer').text(
                    sum_table_col($('#product_sell_report_table'), 'sell_quantity')
                );
            },
        });

        //gift table js code
        product_gift_report_table = $('table#product_gift_report_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[1, 'desc']],
            ajax: {
                url: '/products/gift-history',
                data: function (d) {
                d.id = productId;
                },
            },
            columns: [
                { data: 'product_image', name: 'product_image' },
                { data: 'product_sku', name: 'product_sku' },
                { data: 'invoice_no', name: 'invoice_no' },
                { data: 'sell_quantity', name: 'sell_quantity' },
                { data: 'return_quantity', name: 'return_quantity' },
                { data: 'transaction_date', name: 'transaction_date' },
            ],
            // fnDrawCallback: function(oSettings) {
            //     $('#product_gift_report_table_footer').text(
            //         sum_table_col($('#product_gift_report_table'), 'sell_quantity')
            //     );
            // },
            fnDrawCallback: function(oSettings) {
                $('#product_gift_report_table_footer').text(
                    sum_table_col($('#product_gift_report_table'), 'row_subtotal')
                );
                $('#product_gift_report_table_footer').html(
                    __sum_stock($('#product_gift_report_table'), 'sell_qty')
                );
                __currency_convert_recursively($('#product_gift_report_table'));
            },
        });

        //ecommerce table js code
        product_ecommerce_report_table = $('table#product_ecommerce_report_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[1, 'desc']],
            ajax: {
                url: '/products/ecommerce-history',
                data: function (d) {
                d.id = productId;
                },
            },
            columns: [
                { data: 'product_image', name: 'product_image' },
                { data: 'product_sku', name: 'product_sku' },
                { data: 'invoice_no', name: 'invoice_no' },
                { data: 'quantity', name: 'quantity' },
                { data: 'store_name', name: 'store_name' },
                { data: 'shipping_status', name: 'shipping_status' },
                { data: 'transaction_date', name: 'transaction_date' },
            ],
            // fnDrawCallback: function(oSettings) {
            //     $('#product_ecommerce_report_table_footer').text(
            //         sum_table_col($('#product_ecommerce_report_table'), 'sell_quantity')
            //     );
            // },
            fnDrawCallback: function(oSettings) {
                $('#product_ecommerce_report_table_footer').text(
                    sum_table_col($('#product_ecommerce_report_table'), 'row_subtotal')
                );
                $('#product_ecommerce_report_table_footer').html(
                    __sum_stock($('#product_ecommerce_report_table'), 'sell_qty')
                );
                __currency_convert_recursively($('#product_ecommerce_report_table'));
            },
        });

        //ecommerce return table js code
        product_ecommerce_return_report_table = $('table#product_ecommerce_return_report_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[1, 'desc']],
            ajax: {
                url: '/products/ecommerce-return-history',
                data: function (d) {
                d.id = productId;
                },
            },
            columns: [
                { data: 'product_image', name: 'product_image' },
                { data: 'product_sku', name: 'product_sku' },
                { data: 'sell_invoice_no', name: 'sell_invoice_no' },
                { data: 'return_invoice_no', name: 'return_invoice_no' },
                { data: 'quantity', name: 'quantity' },
                { data: 'store_name', name: 'store_name' },
                { data: 'shipping_status', name: 'shipping_status' },
                { data: 'transaction_date', name: 'transaction_date' },
            ],
            fnDrawCallback: function(oSettings) {
                $('#product_ecommerce_return_report_table_footer').text(
                    sum_table_col($('#product_ecommerce_return_report_table'), 'row_subtotal')
                );
                $('#product_ecommerce_return_report_table_footer').html(
                    __sum_stock($('#product_ecommerce_return_report_table'), 'sell_qty')
                );
                __currency_convert_recursively($('#product_ecommerce_return_report_table'));
            },
        });
        // store to store transfer js code
        product_store_to_store_transfer_report_table = $('table#store_to_store_transfer_report_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[1, 'desc']],
            ajax: {
                url: '/products/store-to-store-history',
                data: function (d) {
                d.id = productId;
                },
            },
            columns: [
                { data: 'product_image', name: 'product_image' },
                { data: 'product_sku', name: 'product_sku' },
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'quantity', name: 'quantity' },
                { data: 'sender', name: 'sender' },
                { data: 'receiver', name: 'receiver' },
            ],
            fnDrawCallback: function(oSettings) {
                $('#store_to_store_transfer_report_table_footer').text(
                    sum_table_col($('#store_to_store_transfer_report_table'), 'row_subtotal')
                );
                $('#store_to_store_transfer_report_table_footer').html(
                    __sum_stock($('#store_to_store_transfer_report_table'), 'sell_qty')
                );
                __currency_convert_recursively($('#store_to_store_transfer_report_table'));
            },
        });

        //store to warehouse transfer
        product_store_to_warehouse_transfer_report_table = $('table#store_to_warehouse_transfer_report_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[1, 'desc']],
            ajax: {
                url: '/products/store-to-warehouse-history',
                data: function (d) {
                d.id = productId;
                },
            },
            columns: [
                { data: 'product_image', name: 'product_image' },
                { data: 'product_sku', name: 'product_sku' },
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'quantity', name: 'quantity' },
                { data: 'sender', name: 'sender' },
                { data: 'receiver', name: 'receiver' },
            ],
            fnDrawCallback: function(oSettings) {
                $('#store_to_warehouse_transfer_report_table_footer').text(
                    sum_table_col($('#store_to_warehouse_transfer_report_table'), 'row_subtotal')
                );
                $('#store_to_warehouse_transfer_report_table_footer').html(
                    __sum_stock($('#store_to_warehouse_transfer_report_table'), 'sell_qty')
                );
                __currency_convert_recursively($('#store_to_warehouse_transfer_report_table'));
            },
        });

        //warehouse to store transfer
        product_warehouse_to_store_transfer_report_table = $('table#warehouse_to_store_transfer_report_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[1, 'desc']],
            ajax: {
                url: '/products/warehouse-to-store-history',
                data: function (d) {
                d.id = productId;
                },
            },
            columns: [
                { data: 'product_image', name: 'product_image' },
                { data: 'product_sku', name: 'product_sku' },
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'quantity', name: 'quantity' },
                { data: 'sender', name: 'sender' },
                { data: 'receiver', name: 'receiver' },
            ],
            fnDrawCallback: function(oSettings) {
                $('#warehouse_to_store_transfer_report_table_footer').text(
                    sum_table_col($('#warehouse_to_store_transfer_report_table'), 'row_subtotal')
                );
                $('#warehouse_to_store_transfer_report_table_footer').html(
                    __sum_stock($('#warehouse_to_store_transfer_report_table'), 'sell_qty')
                );
                __currency_convert_recursively($('#warehouse_to_store_transfer_report_table'));
            },
        });

        //product adjustment js code
        product_adjustment_report_table = $('table#product_adjustment_report_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[1, 'desc']],
            ajax: {
                url: '/products/adjustment-history',
                data: function (d) {
                d.id = productId;
                },
            },
            columns: [
                { data: 'product_image', name: 'product_image' },
                { data: 'product_sku', name: 'product_sku' },
                { data: 'invoice_no', name: 'invoice_no' },
                { data: 'type', name: 'type' },
                { data: 'quantity', name: 'quantity' },
                { data: 'location_name', name: 'location_name' },
                { data: 'transaction_date', name: 'transaction_date' },
            ],
            fnDrawCallback: function(oSettings) {
                $('#product_adjustment_report_table_footer').text(
                    sum_table_col($('#product_adjustment_report_table'), 'row_subtotal')
                );
                $('#product_adjustment_report_table_footer').html(
                    __sum_stock($('#product_adjustment_report_table'), 'sell_qty')
                );
                __currency_convert_recursively($('#product_adjustment_report_table'));
            },
        });
        
    });
    
</script>

@endsection