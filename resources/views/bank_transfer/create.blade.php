@extends('layouts.app')
@section('title', __('Add Bank Transfer'))

@section('content')

<section class="content-header">
<br>
    <h1>@lang('Add Bank Transfer')</h1>
</section>

<!-- Main content -->
<section class="content no-print">
        {!! Form::open(['url' => action('BankTransferController@store'), 'method' => 'post', 'id' => 'stock_adjustment_form', 'enctype' => 'multipart/form-data']) !!}

        <div class="box box-solid">
		<div class="box-body">
			<div class="row">
				<div class="col-sm-3">
					<div class="form-group">
                        <label for="bank" class="form-label">Bank: *</label>
                        <select name="bank" id="bank" class="form-control select2" required>
                            <option value="" selected disabled>Please Select</option>
                            <option value="united_bank">United Bank</option>
                            <option value="habib_bank">Habib Bank</option>
                            <option value="al_habib_bank">Al-Habib Bank</option>
                            <option value="mcb_bank">MCB Bank</option>
                            <option value="alfalah_bank">Alfalah Bank</option>
                            <option value="meezan_bank">Meezan Bank</option>
                            <option value="ceo">CEO</option>
                            <option value="mcb_8141">MCB 8141</option>
                            <option value="albaraka_bank">Albaraka Bank</option>
                            <option value="js_bank">JS Bank</option>
                        </select>					
                    </div>
				</div>
				<div class="col-sm-3">
					<div class="form-group">
						{!! Form::label('amount', __('Amount').':*') !!}
						{!! Form::text('amount', null, ['class' => 'form-control', 'required']); !!}
					</div>
				</div>				
                <div class="col-sm-3">
					<div class="form-group">
						{!! Form::label('transaction_date', __('messages.date') . ':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
							{!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control transaction_date', 'readonly', 'required']); !!}
						</div>
					</div>
				</div>
                <div class="col-sm-3">
					<button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
				</div>

			</div>
		</div>
	</div> <!--box end-->
{!! Form::close() !!}

</section>
@stop
@section('javascript')
	<script type="text/javascript">
		$(document).ready(function() {
			$('#transaction_date').datetimepicker({
				format: moment_date_format + ' ' + moment_time_format,
				ignoreReadonly: true,
			});

			$('.transaction_date').val(moment().format(moment_date_format + ' ' + moment_time_format));
		});
		__page_leave_confirmation('#stock_adjustment_form');
	</script>
@endsection
