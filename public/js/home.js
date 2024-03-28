$(document).ready(function () {
    var start = $('input[name="date-filter"]:checked').data('start');
    var end = $('input[name="date-filter"]:checked').data('end');
    update_statistics(start, end);
    $(document).on('change', 'input[name="date-filter"], #dashboard_location', function () {
        var start = $('input[name="date-filter"]:checked').data('start');
        var end = $('input[name="date-filter"]:checked').data('end');
        update_statistics(start, end);
        if ($('#quotation_table').length && $('#dashboard_location').length) {
            quotation_datatable.ajax.reload();
        }

    });

    //atock alert datatables
    var stock_alert_table = $('#stock_alert_table').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        searching: false,
        scrollY: "75vh",
        scrollX: true,
        scrollCollapse: true,
        fixedHeader: false,
        dom: 'Btirp',
        ajax: '/home/product-stock-alert',
        fnDrawCallback: function (oSettings) {
            __currency_convert_recursively($('#stock_alert_table'));
        },
    });
    //payment dues datatables
    var purchase_payment_dues_table = $('#purchase_payment_dues_table').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        searching: false,
        scrollY: "75vh",
        scrollX: true,
        scrollCollapse: true,
        fixedHeader: false,
        dom: 'Btirp',
        ajax: '/home/purchase-payment-dues',
        fnDrawCallback: function (oSettings) {
            __currency_convert_recursively($('#purchase_payment_dues_table'));
        },
    });

    //Sales dues datatables
    var sales_payment_dues_table = $('#sales_payment_dues_table').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        searching: false,
        scrollY: "75vh",
        scrollX: true,
        scrollCollapse: true,
        fixedHeader: false,
        dom: 'Btirp',
        ajax: '/home/sales-payment-dues',
        fnDrawCallback: function (oSettings) {
            __currency_convert_recursively($('#sales_payment_dues_table'));
        },
    });

    //Stock expiry report table
    stock_expiry_alert_table = $('#stock_expiry_alert_table').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        scrollY: "75vh",
        scrollX: true,
        scrollCollapse: true,
        fixedHeader: false,
        dom: 'Btirp',
        ajax: {
            url: '/reports/stock-expiry',
            data: function (d) {
                d.exp_date_filter = $('#stock_expiry_alert_days').val();
            },
        },
        order: [[3, 'asc']],
        columns: [
            { data: 'product', name: 'p.name' },
            { data: 'location', name: 'l.name' },
            { data: 'stock_left', name: 'stock_left' },
            { data: 'exp_date', name: 'exp_date' },
        ],
        fnDrawCallback: function (oSettings) {
            __show_date_diff_for_human($('#stock_expiry_alert_table'));
            __currency_convert_recursively($('#stock_expiry_alert_table'));
        },
    });

    if ($('#quotation_table').length) {
        quotation_datatable = $('#quotation_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']],
            "ajax": {
                "url": '/sells/draft-dt?is_quotation=1',
                "data": function (d) {
                    if ($('#dashboard_location').length > 0) {
                        d.location_id = $('#dashboard_location').val();
                    }
                }
            },
            columnDefs: [{
                "targets": 4,
                "orderable": false,
                "searchable": false
            }],
            columns: [
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'invoice_no', name: 'invoice_no' },
                { data: 'name', name: 'contacts.name' },
                { data: 'business_location', name: 'bl.name' },
                { data: 'action', name: 'action' }
            ]
        });
    }

    if ($('#dashboard_stock_report_table').length) {
        quotation_datatable = $('#dashboard_stock_report_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']],
            "ajax": {
                "url": '/reports/dashboard-stock-report',
                "data": function (d) {
                    if ($('#dashboard_location').length > 0) {
                        d.location_id = $('#dashboard_location').val();
                    }
                }
            },
            columns: [
                { data: 'category_name', name: 'category_name' },
                { data: 'stock', name: 'stock' },
                { data: 'total_sold', name: 'total_sold' },
                { data: 'stock_price', name: 'stock_price' },
                { data: 'stock_value_by_sale_price', name: 'stock_value_by_sale_price' },
                { data: 'cost_of_sold', name: 'cost_of_sold' },
                { data: 'sale_price_of_sold', name: 'sale_price_of_sold' },
            ],
            fnDrawCallback: function(oSettings) {
                $('#available_quantity').text(
                    sum_table_col($('#dashboard_stock_report_table'), 'current_stock')
                );
                $('#sold_quantity').text(
                    sum_table_col($('#dashboard_stock_report_table'), 'total_sold')
                );
                $('#cost_of_qty').html(
                    sum_table_col($('#dashboard_stock_report_table'), 'total_stock_price')
                );
                $('#price_of_qty').html(
                    sum_table_col($('#dashboard_stock_report_table'), 'stock_value_by_sale_price')
                );
                $('#cost_of_sold').html(
                    sum_table_col($('#dashboard_stock_report_table'), 'potential_profit')
                );
                $('#price_of_sold').text(
                    sum_table_col($('#dashboard_stock_report_table'), 'potential_profit_2')
                );
    
                __currency_convert_recursively($('#product_and_category_table'));
            },
        });
    }
});

function update_statistics(start, end) {
    var location_id = '';
    if ($('#dashboard_location').length > 0) {
        location_id = $('#dashboard_location').val();
    }
    var data = { start: start, end: end, location_id: location_id };
    //get purchase details
    var loader = '<i class="fas fa-sync fa-spin fa-fw margin-bottom"></i>';
    $('.total_purchase').html(loader);
    $('.purchase_due').html(loader);
    $('.total_sell').html(loader);
    $('.invoice_due').html(loader);
    $('.total_expense').html(loader);
    $('.total_item_sold').html(loader);
    $.ajax({
        method: 'get',
        url: '/home/get-totals',
        dataType: 'json',
        data: data,
        success: function (data) {
            //purchase details
            $('.total_purchase').html(__currency_trans_from_en(data.total_purchase, true));
            $('.purchase_due').html(__currency_trans_from_en(data.purchase_due, true));

            //sell details
            $('.total_sell').html(__currency_trans_from_en(data.total_sell, true));
            $('.invoice_due').html(__currency_trans_from_en(data.invoice_due, true));
            $('.total_item_sold').html(parseInt(data.total_item_sold));
            //expense details
            $('.total_expense').html(__currency_trans_from_en(data.total_expense, true));
        },
    });
}
