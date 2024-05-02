@extends('layouts.app')
@section('title', __('International Exchange'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang('International Exchange')
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_list_filter_location_id',  __('purchase.business_location') . ':') !!}

                {!! Form::select('sell_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
            </div>
        </div>
        <div class="col-md-3" style="display: none;">
            <div class="form-group">
                {!! Form::label('sell_list_filter_customer_id',  __('contact.customer') . ':') !!}
                {!! Form::select('sell_list_filter_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_list_filter_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('sell_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('created_by',  __('report.user') . ':') !!}
                {!! Form::select('created_by', $sales_representative, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
            </div>
        </div>
    @endcomponent
    @component('components.widget', ['class' => 'box-primary', 'title' => __('Product Exchange')])
    <div class="table-responsive">
        <table class="table table-bordered table-striped ajax_view" id="international_return_table">
            <thead>
                <tr>
                    <th>@lang('messages.date')</th>
                    <th>@lang('sale.invoice_no')</th>
                    {{-- <th>Old Invoice No.</th> --}}
                    <th>@lang('sale.customer_name')</th>
                    <th>@lang('sale.location')</th>
                    <th>@lang('purchase.payment_status')</th>
                    <th>Sub Total</th>
                    <th>Discount Amount</th>
                    <th>Invoice Amount</th>    
                    {{-- <th>@lang('purchase.payment_due')</th> --}}
                    <th>@lang('lang_v1.payment_method')</th>
                    {{-- <th>@lang('messages.action')</th> --}}
                </tr>
            </thead>
            <tfoot>
                <tr class="bg-gray font-17 text-center footer-total">
                    <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                    <td id="footer_payment_status_count_sr"></td>
                    <td><span class="display_currency" id="footer_sell_return_total" data-currency_symbol ="true"></span></td>
                    {{-- <td><span class="display_currency" id="footer_total_due_sr" data-currency_symbol ="true"></span></td> --}}
                    <td><span class="display_currency" id="footer_discount_total" data-currency_symbol ="true"></span></td>
                    <td><span class="display_currency" id="footer_invoice_total" data-currency_symbol ="true"></span></td>
                    <td></td>
                    {{-- <td></td> --}}
                </tr>
            </tfoot>
        </table>
    </div>
    @endcomponent
    <div class="modal fade payment_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>
</section>

<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
<script>
    $(document).ready(function(){
        $('#sell_list_filter_date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                international_return_table.ajax.reload();
            }
        );
        $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#sell_list_filter_date_range').val('');
            international_return_table.ajax.reload();
        });

        international_return_table = $('#international_return_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']],
            "ajax": {
                "url": "/international-exchange",
                "data": function ( d ) {
                    if($('#sell_list_filter_date_range').val()) {
                        var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                        d.start_date = start;
                        d.end_date = end;
                    }

                    if($('#sell_list_filter_location_id').length) {
                        d.location_id = $('#sell_list_filter_location_id').val();
                    }
                    d.customer_id = $('#sell_list_filter_customer_id').val();

                    if($('#created_by').length) {
                        d.created_by = $('#created_by').val();
                    }
                }
            },
            columnDefs: [ {
                "targets": [5, 6],
                "orderable": false,
                "searchable": false
            } ],
            columns: [
                { data: 'transaction_date', name: 'transaction_date'  },
                { data: 'invoice_no', name: 'invoice_no'},
                // { data: 'parent_sale', name: 'T1.invoice_no'},
                { data: 'name', name: 'contacts.name'},
                { data: 'business_location', name: 'bl.name'},
                { data: 'payment_status', name: 'payment_status'},
                { data: 'original_amount', name: 'original_amount'},
                { data: 'discount_amount', name: 'discount_amount'},
                { data: 'final_total', name: 'final_total'},
                // { data: 'payment_due', name: 'payment_due'},
                { data: 'payment_methods', orderable: false, "searchable": false},
                // { data: 'action', name: 'action'}
            ],
            "fnDrawCallback": function (oSettings) {
                var total_sell = sum_table_col($('#international_return_table'), 'final_total');
                $('#footer_sell_return_total').text(total_sell);

                var total_sell = sum_table_col($('#international_return_table'), 'total-discount');
                $('#footer_discount_total').text(total_sell);

                var total_sell = sum_table_col($('#international_return_table'), 'total-original-amount');
                $('#footer_invoice_total').text(total_sell);
                
                $('#footer_payment_status_count_sr').html(__sum_status_html($('#international_return_table'), 'payment-status-label'));

                var total_due = sum_table_col($('#international_return_table'), 'payment_due');
                $('#footer_total_due_sr').text(total_due);

                __currency_convert_recursively($('#international_return_table'));
            },
            createdRow: function( row, data, dataIndex ) {
                $( row ).find('td:eq(2)').attr('class', 'clickable_td');
            }
        });
        $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #created_by',  function() {
            international_return_table.ajax.reload();
        });
    })

    $(document).on('click', 'a.delete_sell_return', function(e) {
        e.preventDefault();
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).attr('href');
                var data = $(this).serialize();

                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            international_return_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
</script>
	
@endsection