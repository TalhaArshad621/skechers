"use strict";

var physicalProductArray = [];

var ScannedProduct = [];

function getProductDetails() {
    // Get the entered barcode number
    var barcodeNumber = $('#code').val();
    console.log(barcodeNumber);

    // Make an AJAX request to retrieve product details
    $.ajax({
        type: 'GET',
        url: '/products-list', // Replace with your actual API endpoint
        data: {
            barcode: barcodeNumber,
            location_id: $('input#location_id').val(),
        },
        success: function (response) {
            if (response.data.id) {

                var scan = {
                    id: response.data.id,
                    categoryName: response.data.category,
                    sku: response.data.sku,
                }
                ScannedProduct.unshift(scan);
                // Populate the table with the retrieved product details
                populateTable(response.data);

                var find = $.grep(physicalProductArray, function (item) {
                    return item.id == response.data.id;
                });
                if (find.length > 0) {
                    $.each(physicalProductArray, function (index, item) {
                        if (response.data.id == item.id) {
                            item.quantity++;
                        }
                    });
                } else {
                    var data = {
                        id: response.data.id,
                        categoryName: response.data.category,
                        sku: response.data.sku,
                        quantity_in_stock: response.data.available_qty,
                        quantity: 1
                    };
                    physicalProductArray.push(data);
                }
                // console.log(physicalProductArray, ScannedProduct);
            } else {
                toastr.error("Product Not Found");
            }
        },
        error: function (error) {
            console.error('Error retrieving product details:', error);
        }
    });
}

function populateTable(productDetails) {
    // Clear existing table rows
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


$("#submit-audit").on("click", function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    e.stopPropagation();
    var finalauditArray = [];

    if (physicalProductArray.length > 0) {
        $.each(physicalProductArray, function (index, item) {
            var data = {
                p_id: item.id,
                sku: item.sku,
                categoryName: item.categoryName,
                store_quantity: item.quantity_in_stock,
                physical_quantity: item.quantity,
                variance: item.quantity - item.quantity_in_stock,
            }

            if (item.quantity > item.quantity_in_stock) {
                data.item_audit_result = "Excess";
            } else if (item.quantity < item.quantity_in_stock) {
                data.item_audit_result = "Shortage";
            } else {
                data.item_audit_result = "Balance";
            }
            finalauditArray.push(data);
        });

        var result_array = [];
        var Store_Product = [];
        var Physical_Product = [];

        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
        var yyyy = today.getFullYear();
        today = mm + '/' + dd + '/' + yyyy;


        $.each(finalauditArray, function (index, item) {
            var result = {
                "Product id": item.p_id,
                "Product Sku": item.sku,
                "Product Group": item.categoryName,
                "Product Book Balance": item.store_quantity,
                "Count balance": item.physical_quantity,
                "Variance": item.variance,
                "Remarks": item.item_audit_result
            };
            result_array.push(result);
        });

        $.each(physicalProductArray, function (index, item) {
            var product = {
                "Product ID": item.id,
                "Product Sku": item.sku,
                "Product Group": item.categoryName,
                "Product Stock Quantity": item.quantity_in_stock
            };
            Store_Product.push(product);
        });

        $.each(finalauditArray, function (index, item) {
            var scanned = {
                "Product Id": item.p_id,
                "Product Sku": item.sku,
                "Product Group": item.categoryName,
                "Product Quantity": item.physical_quantity
            };
            Physical_Product.push(scanned);
        });

        console.log(result_array, Store_Product, Physical_Product);

        var opts = [{ sheetid: 'Audit', header: true }, { sheetid: 'Store Product', header: false }, { sheetid: 'Scanned Product', header: false }];
        var result = alasql('SELECT * INTO XLSX("Audit.xlsx",?) FROM ?',
            [opts, [result_array, Store_Product, Physical_Product]]);


        toastr.success("Audit Completed!");
        setTimeout(function () {
            // Reload the current page
            window.location.reload();
        }, 5000); // 5000 milliseconds = 5 seconds
    } else {
        toastr.error("No product selected");
    }
})