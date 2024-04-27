@extends('layouts.app')
@section('title', __('Bank Transfer'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('Bank Transfer')
        <small></small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('bank_transfer_filter_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('bank_transfer_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
            </div>
        </div>
    @endcomponent
    @component('components.widget', ['class' => 'box-primary', 'title' => __('All Bank Transfer')])
        @slot('tool')
            {{-- <div class="box-tools">
                <a class="btn btn-block btn-primary" href="{{action('StockAdjustmentController@create')}}">
                <i class="fa fa-plus"></i> @lang('messages.add')</a>
            </div> --}}
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped ajax_view" id="bank_transfer_table">
                <thead>
                    <tr>
                        <th>Sr. No.</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Bank</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

</section>
<!-- /.content -->
@stop
@section('javascript')
<script>
        $('#bank_transfer_filter_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#bank_transfer_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            bank_transfer_table.ajax.reload();
        }
        );
        $('#bank_transfer_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#bank_transfer_filter_date_range').val('');
            bank_transfer_table.ajax.reload();
        });

    bank_transfer_table = $('#bank_transfer_table').DataTable({
        processing: true,
        serverSide: true,
        "ajax": {
                "url": "/bank-transfers",
                "data": function ( d ) {
                    if($('#bank_transfer_filter_date_range').val()) {
                        var start = $('#bank_transfer_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        var end = $('#bank_transfer_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                        d.start_date = start;
                        d.end_date = end;
                    }
                }
            },
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                searchable: false,
            },
        ],
        aaSorting: [[1, 'desc']],
        columns: [
            { data: null, name: 'serial_number', searchable: false, orderable: false, render: function (data, type, row, meta) {
                return meta.row + 1;
            }},
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'amount', name: 'amount' },
            { data: 'bank', name: 'bank' }
        ],
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#bank_transfer_table'));
        },
        createdRow: function(row, data, dataIndex) {
            // Add a class to the row for styling purposes if needed
            $(row).addClass('bank-transfer-row');
        }
    });

</script>
@endsection