@extends('layouts.app')
@section('title', __('product.import_products'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('product.import_products')
    </h1>
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
    
    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary'])
                {!! Form::open(['url' => action('ImportProductsController@store'), 'method' => 'post', 'enctype' => 'multipart/form-data' ]) !!}
                    <div class="row">
                        <div class="col-sm-6">
                        <div class="col-sm-8">
                            <div class="form-group">
                                {!! Form::label('name', __( 'product.file_to_import' ) . ':') !!}
                                {!! Form::file('products_csv', ['accept'=> '.xls, .xlsx, .csv', 'required' => 'required']); !!}
                              </div>
                        </div>
                        <div class="col-sm-4">
                        <br>
                            <button type="submit" class="btn btn-primary">@lang('messages.submit')</button>
                        </div>
                        </div>
                    </div>

                {!! Form::close() !!}
                <br><br>
                <div class="row">
                    <div class="col-sm-4">
                        <a href="{{ asset('files/import_products_csv_template.xls') }}" class="btn btn-success" download><i class="fa fa-download"></i> @lang('lang_v1.download_template_file')</a>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.instructions')])
                <strong>@lang('lang_v1.instruction_line1')</strong><br>
                    @lang('lang_v1.instruction_line2')
                    <br><br>
                <table class="table table-striped">
                    <tr>
                        <th>@lang('lang_v1.col_no')</th>
                        <th>@lang('lang_v1.col_name')</th>
                        <th>@lang('lang_v1.instruction')</th>
                    </tr>
                    <tr>
                        <td>1</td>
                        <td>@lang('product.product_name') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>@lang('lang_v1.name_ins')</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Category <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>Category of Products is Required <br><small class="text-muted">For Example:Shoes, Socks, etc</small></td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Sub Category <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>Name of Sub Category Related to Category</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>@lang('product.sku') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.sku_ins')</td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>@lang('product.applicable_tax') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>Name of the Tax Rate</td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td>Purchase Price<small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>Purchase Price (Including Tax) (Only in numbers)</td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td>@lang('lang_v1.selling_price') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>Selling Price (Including Tax) (Only in numbers)</td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td>@lang('lang_v1.product_locations') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>@lang('lang_v1.product_locations_ins')
                        </td>
                    </tr>
                </table>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->

@endsection