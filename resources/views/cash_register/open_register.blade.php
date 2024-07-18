@extends('layouts.app')
@section('title',  __('cash_register.open_cash_register'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('cash_register.open_cash_register')</h1>
</section>

<!-- Main content -->
<section class="content">
  <div class="box box-solid">
    <div class="box-body text-center">
    <br><br><br>
        <h2>Need To open Register First!</h2>
      <br><br><br>
    </div>
  </div>
</section>
<!-- /.content -->
@endsection