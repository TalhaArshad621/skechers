@extends('layouts.app')
@section('title', __('Import Discount'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('Import Discount')</h1>
</section>

<!-- Main content -->
<section class="content no-print">
	{!! Form::open(['url' => action('ImportStockTrasferController@store'), 'method' => 'post', 'id' => 'stock_transfer_form', 'enctype' => 'multipart/form-data' ]) !!}
	<div class="box box-solid">
		<div class="box-body">
			<div class="row">
				<div class="clearfix"></div>
                <div class="col-sm-6">
                    <div class="form-group">
                      {!! Form::label('location_id', __('sale.location') . ':*') !!}
                      {!! Form::select('location_ids[]', $locations, null, ['class' => 'form-control select2', 'multiple', 'required']) !!}
                    </div>
                  </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('name', __( 'File To Import' ) . ':') !!}
                        {!! Form::file('stock_transfer_csv', ['accept'=> '.xls, .xlsx, .csv', 'required' => 'required']); !!}
                      </div>
                </div>
                <div class="col-sm-3">
                    <br>
                        <button type="submit" class="btn btn-primary">@lang('messages.submit')</button>
                </div>
			</div>
            <br><br>
                {{-- <div class="row">
                    <div class="col-sm-4">
                        <a href="{{ asset('files/import_stock_transfer_csv_template.csv') }}" class="btn btn-success" download><i class="fa fa-download"></i> @lang('lang_v1.download_template_file')</a>
                    </div>
                </div> --}}
		</div>
	</div> <!--box end-->
	{!! Form::close() !!}
</section>
@stop
{{-- @section('javascript')
	<script src="{{ asset('js/stock_transfer.js?v=' . $asset_v) }}"></script>
	<script type="text/javascript">
		__page_leave_confirmation('#stock_transfer_form');
	</script>
@endsection --}}
