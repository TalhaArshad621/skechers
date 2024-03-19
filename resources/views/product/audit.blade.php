@extends('layouts.app')
@section('title', __('Stock Audit'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('Stock Audit')}}</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
            <div class="row">
                <div class="col-sm-4">
                  <div class="form-group">
                    <label>Barcode No.</label>
                    <input type="text" name="code" id="code" class="form-control" placeholder="Enter Barcode Number" onblur="getProductDetails()">
                </div>
                </div>
              </div>
              <div class="row">
                <div class="col-sm-12 text-right">
                  <div class="form-group">
                    <button type="button" id="submit-audit" class="btn btn-primary btn-flat">Submit</button>
                  </div>
                </div>
              </div>
            @endcomponent
            @component('components.widget', ['class' => 'box-primary'])
                <div class="table-responsive">
                    <h3>Scanned Items</h3>
                    <table class="table table-bordered table-striped" id="audit_table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Product SKU</th>
                                <th>Category</th>
                            </tr>
                        </thead>
                        <tbody id="productDetails">
                        </tbody>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->
<div class="modal fade view_register" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

@endsection

@section('javascript')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.3/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/alasql/0.3/alasql.min.js"></script>
<script src="{{asset('js/audit.js?v=' . $asset_v)}}"></script>

@endsection