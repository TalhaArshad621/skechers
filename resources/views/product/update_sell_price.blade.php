@extends('layouts.app')
@section('title', __('Update Selling Price'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'Update Selling Price' )
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    @if (session('notification') || !empty($notification))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    @if(!empty($notification['msg']))
                        {{$notification['msg']}}
                    @elseif(session('notification.msg'))
                        {{ session('notification.msg') }}
                    @endif
                </div>
            </div>  
        </div>     
    @endif
    @component('components.widget', ['class' => 'box-primary', 'title' => __('Update Sell Price Manually')])
    {!! Form::open(['url' => action('ProductController@storeProductPriceManually'), 'method' => 'post', 
    'id' => 'product_add_form','class' => 'product_form', 'files' => true ]) !!}
            <div class="row">
                    <div class="col-sm-4">
                      <div class="form-group">
                        {!! Form::label('name', __('product.product_name') . ':*') !!}
                          <input type="text" class="form-control" name="p_name">
                      </div>
                    </div>
            
                    <div class="col-sm-4">
                      <div class="form-group">
                        {!! Form::label('sell_price', __('Sell Price') . ':') !!}
                        {!! Form::text('sell_price', null, ['class' => 'form-control',
                          'placeholder' => __('Sell Price')]); !!}
                      </div>
                    </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <button type="submit" value="submit" class="btn btn-primary submit_product_form">@lang('messages.save')</button>
                  </div>
                  
                  </div>
                </div>
              </div>
    {!! Form::close() !!}
    @endcomponent

</section>
<!-- /.content -->
@stop
@section('javascript')

@endsection
