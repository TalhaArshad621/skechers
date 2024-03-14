@extends('layouts.app')
@section('title', __('BrandFolio Report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>{{ __('BrandFolio Report')}}</h1>
</section>

<!-- Main content -->
<section class="content no-print">
    <div class="row">
        <div class="col-md-12">
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="brandfolio_report_table">
                    <thead>
                        <tr>
                            <th>sku</th>
                            <th>Distributor</th>
                            <th>Country</th>
                            <th>Transaction Date</th>
                            <th>Store</th>
                            <th>Category</th>
                            <th>Sale Unit</th>
                            <th>TotaL Sale Amount</th>
                            <th>Closing Stock</th>
                        </tr>
                    </thead>
                   
                </table>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->


@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
@endsection