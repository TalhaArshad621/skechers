@extends('layouts.app')
@section('title', __('Import Purchase'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('Import Purchase')
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
            {{-- @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                    <div class="@if(!empty($default_purchase_status)) col-sm-4 @else col-sm-3 @endif">
                        <div class="form-group">
                            {!! Form::label('supplier_id', __('purchase.supplier') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-user"></i>
                                </span>
                                <select name="suppliers" class="form-control select2" required>
                                    <option value="">Select Supplier</option>
                                    @foreach ($suppliers as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div id="supplier_address_div"></div>
                    </div>
                            
                    @if(count($business_locations) == 1)
                        @php 
                            $default_location = current(array_keys($business_locations->toArray()));
                            $search_disable = false; 
                        @endphp
                    @else
                        @php $default_location = null;
                        $search_disable = true;
                        @endphp
                    @endif
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('location_id', __('purchase.business_location').':*') !!}
                            @show_tooltip(__('tooltip.purchase_location'))
                            {!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required'], $bl_attributes); !!}
                        </div>
                    </div>
                </div>
            @endcomponent --}}
            @component('components.widget', ['class' => 'box-primary'])
                {!! Form::open(['url' => action('ImportPurchaseController@store'), 'method' => 'post', 'enctype' => 'multipart/form-data' ]) !!}
                    <div class="row">
                        <div class="@if(!empty($default_purchase_status)) col-sm-4 @else col-sm-3 @endif">
                            <div class="form-group">
                                {!! Form::label('supplier_id', __('purchase.supplier') . ':*') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-user"></i>
                                    </span>
                                    <select name="supplier_id" class="form-control select2" required>
                                        <option value="">Select Supplier</option>
                                        @foreach ($suppliers as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div id="supplier_address_div"></div>
                        </div>
                                
                        @if(count($business_locations) == 1)
                            @php 
                                $default_location = current(array_keys($business_locations->toArray()));
                                $search_disable = false; 
                            @endphp
                        @else
                            @php $default_location = null;
                            $search_disable = true;
                            @endphp
                        @endif
                        <div class="col-sm-3">
                            <div class="form-group">
                                {!! Form::label('location_id', __('purchase.business_location').':*') !!}
                                @show_tooltip(__('tooltip.purchase_location'))
                                {!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required'], $bl_attributes); !!}
                            </div>
                        </div>
                        <div class="col-sm-6">
                        <div class="col-sm-8">
                            <div class="form-group">
                                {!! Form::label('name', __( 'File To Import' ) . ':') !!}
                                {!! Form::file('purchase_csv', ['accept'=> '.xls, .xlsx, .csv', 'required' => 'required']); !!}
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
                        <a href="{{ asset('files/import_purchase_csv_template.xls') }}" class="btn btn-success" download><i class="fa fa-download"></i> @lang('lang_v1.download_template_file')</a>
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
                        <td>SKU Number <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>Unique Product ID</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Purchase Quantity <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>Quantity Of The Product To Be Purchased</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>UNIT COST <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>Cost Per Piece</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>UNIT SELLING PRICE (Inc. tax) <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>Cost Per Piece Including Tax </td>
                    </tr>
                    {{-- <tr>
                        <td>5</td>
                        <td>DISCOUNT PERCENT <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.sub_category_ins') <br><small class="text-muted">({!! __('lang_v1.sub_category_ins2') !!})</small></td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td>UNIT COST (Before TAX) <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.sku_ins')</td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td>LINE TOTAL <small class="text-muted">(@lang('lang_v1.optional')</small></td>
                        <td>@lang('lang_v1.barcode_type_ins') <br>
                            <strong>@lang('lang_v1.barcode_type_ins2'): C128, C39, EAN-13, EAN-8, UPC-A, UPC-E, ITF-14</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td>PROFIT MARGIN %  <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>@lang('lang_v1.manage_stock_ins')<br>
                            <strong>1 = @lang('messages.yes')<br>
                            0 = @lang('messages.no')</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td>UNIT SELLING PRICE (Inc. tax)  <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('product.alert_quantity')</td>
                    </tr>
                    <tr>
                        <td>10</td>
                        <td>@lang('product.expires_in') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.expires_in_ins')</td>
                    </tr>
                    <tr>
                        <td>11</td>
                        <td>@lang('lang_v1.expire_period_unit') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.expire_period_unit_ins')<br>
                            <strong>@lang('lang_v1.available_options'): days, months</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>12</td>
                        <td>@lang('product.applicable_tax') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.applicable_tax_ins') {!! __('lang_v1.applicable_tax_help') !!}</td>
                    </tr>
                    <tr>
                        <td>13</td>
                        <td>@lang('product.selling_price_tax_type') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>@lang('product.selling_price_tax_type') <br>
                            <strong>@lang('lang_v1.available_options'): inclusive, exclusive</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>14</td>
                        <td>@lang('product.product_type') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>@lang('product.product_type') <br>
                            <strong>@lang('lang_v1.available_options'): single, variable</strong></td>
                    </tr>
                    <tr>
                        <td>15</td>
                        <td>@lang('product.variation_name') <small class="text-muted">(@lang('lang_v1.variation_name_ins'))</small></td>
                        <td>@lang('lang_v1.variation_name_ins2')</td>
                    </tr>
                    <tr>
                        <td>16</td>
                        <td>@lang('product.variation_values') <small class="text-muted">(@lang('lang_v1.variation_values_ins'))</small></td>
                        <td>{!! __('lang_v1.variation_values_ins2') !!}</td>
                    </tr>
                    <tr>
                        <td>17</td>
                        <td> @lang('lang_v1.purchase_price_inc_tax')<br><small class="text-muted">(@lang('lang_v1.purchase_price_inc_tax_ins1'))</small></td>
                        <td>{!! __('lang_v1.purchase_price_inc_tax_ins2') !!}</td>
                    </tr>
                    <tr>
                        <td>18</td>
                        <td>@lang('lang_v1.purchase_price_exc_tax')  <br><small class="text-muted">(@lang('lang_v1.purchase_price_exc_tax_ins1'))</small></td>
                        <td>{!! __('lang_v1.purchase_price_exc_tax_ins2') !!}</td>
                    </tr>
                    <tr>
                        <td>19</td>
                        <td>@lang('lang_v1.profit_margin') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.profit_margin_ins')<br>
                            <small class="text-muted">{!! __('lang_v1.profit_margin_ins1') !!}</small></td>
                    </tr>
                    <tr>
                        <td>20</td>
                        <td>@lang('lang_v1.selling_price') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.selling_price_ins')<br>
                         <small class="text-muted">{!! __('lang_v1.selling_price_ins1') !!}</small></td>
                    </tr>
                    <tr>
                        <td>21</td>
                        <td>@lang('lang_v1.opening_stock') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.opening_stock_ins') {!! __('lang_v1.opening_stock_help_text') !!}<br>
                        </td>
                    </tr>
                    <tr>
                        <td>22</td>
                        <td>@lang('lang_v1.opening_stock_location') <small class="text-muted">(@lang('lang_v1.optional')) <br>@lang('lang_v1.location_ins')</small></td>
                        <td>@lang('lang_v1.location_ins1')<br>
                        </td>
                    </tr>
                    <tr>
                        <td>23</td>
                        <td>@lang('lang_v1.expiry_date') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.expiry_date_ins') !!}<br>
                        </td>
                    </tr>
                    <tr>
                        <td>24</td>
                        <td>@lang('lang_v1.enable_imei_or_sr_no') <small class="text-muted">(@lang('lang_v1.optional'), @lang('lang_v1.default'): 0)</small></td>
                        <td><strong>1 = @lang('messages.yes')<br>
                            0 = @lang('messages.no')</strong><br>
                        </td>
                    </tr>
                    <tr>
                        <td>25</td>
                        <td>@lang('lang_v1.weight') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.optional')<br>
                        </td>
                    </tr>
                    <tr>
                        <td>26</td>
                        <td>@lang('lang_v1.rack') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.rack_help_text') !!}</td>
                    </tr>
                    <tr>
                        <td>27</td>
                        <td>@lang('lang_v1.row') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.row_help_text') !!}</td>
                    </tr>
                    <tr>
                        <td>28</td>
                        <td>@lang('lang_v1.position') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.position_help_text') !!}</td>
                    </tr>
                    <tr>
                        <td>29</td>
                        <td>@lang('lang_v1.image') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.image_help_text', ['path' => 'public/uploads/'.config('constants.product_img_path')]) !!}</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>30</td>
                        <td>@lang('lang_v1.product_description') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>31</td>
                        <td>@lang('lang_v1.product_custom_field1') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>32</td>
                        <td>@lang('lang_v1.product_custom_field2') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                    </tr>
                    <tr>
                        <td>33</td>
                        <td>@lang('lang_v1.product_custom_field3') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>34</td>
                        <td>@lang('lang_v1.product_custom_field4') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                    </tr>
                    <tr>
                        <td>35</td>
                        <td>@lang('lang_v1.not_for_selling') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td><strong>1 = @lang('messages.yes')<br>
                            0 = @lang('messages.no')</strong><br>
                        </td>
                    </tr>
                    <tr>
                        <td>36</td>
                        <td>@lang('lang_v1.product_locations') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.product_locations_ins')
                        </td>
                    </tr> --}}

                </table>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->

@endsection