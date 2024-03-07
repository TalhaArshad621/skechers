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
<script>
    function getProductDetails() {
      // Get the entered barcode number
      var barcodeNumber = $('#code').val();
      console.log(barcodeNumber);
    
      // Make an AJAX request to retrieve product details
      $.ajax({
        type: 'GET',
        url: '/products-list', // Replace with your actual API endpoint
        data: { barcode: barcodeNumber },
        success: function(response) {
          // Populate the table with the retrieved product details
          populateTable(response);
        },
        error: function(error) {
          console.error('Error retrieving product details:', error);
        }
      });
    }
    
    function populateTable(productDetails) {
      // Clear existing table rows
      console.log(productDetails);
    //   $('#productDetails').empty();
    
      // Check if productDetails is not empty
      if (productDetails) {
        // Add a new row with product details
        var newRow = '<tr>';
        var imagePath = productDetails.image ? ('uploads/img/' + productDetails.image) : 'img/default.png';
        newRow += '<td><div style="display: flex; justify-content: center; align-items: center;"><img src="' + imagePath + '" alt="Product image" class="product-thumbnail-small"></div></td>';
        newRow += '<td>' + productDetails.id + '</td>';
        newRow += '<td>' + productDetails.product + '</td>';
        newRow += '<td>' + productDetails.sku + '</td>';
        newRow += '<td>' + productDetails.category + '</td>';
        newRow += '</tr>';
    
        // Append the new row to the table
        $('#productDetails').append(newRow);
      }
    }
    </script>
@endsection