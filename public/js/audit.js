"use strict";

var productArray = [];

var physicalProductArray = [];

var ScannedProduct = [];


$.ajax({
    type: 'GET',
    url: '/all-products-list',
    success: function(response) {
        if (response && response.data) {
            response.data.forEach(function(product) {
                var data = {
                    id: product.id,
                    categoryName: product.category,
                    sku: product.sku,
                    quantity_in_stock: product.available_qty,
                    quantity: 0
                };
                productArray.push(data);
                physicalProductArray.push(data);

                // console.log(productArray,physicalProductArray);
            });
        } else {
            console.error("No data received or data format incorrect.");
        }
    },
    error: function(xhr, status, error) {
        console.error(error);
    }
});
// var debounceTimeout;

// function debouncedGetProductDetails() {
//     clearTimeout(debounceTimeout);
//     debounceTimeout = setTimeout(getProductDetails, 1); // Adjust debounce time as needed
// }

// function getProductDetails() {
//     var barcodeNumber = $('#code').val();
//     var locationId = $('input#location_id').val();

//     $.ajax({
//         type: 'GET',
//         url: '/products-list',
//         data: { barcode: barcodeNumber, location_id: locationId },
//         success: function (response) {
//             if (!response.data.id) {
//                 toastr.error("Product Not Found");
//                 return;
//             }

//             var productData = response.data;
//             var scan = {
//                 id: productData.id,
//                 categoryName: productData.category,
//                 sku: productData.sku,
//                 img: productData.image_url,
//                 product: productData.product
//             };

//             ScannedProduct.unshift(scan);
//             $('#rowCount').text(ScannedProduct.length);

//             var existingProduct = physicalProductArray.find(item => item.id === productData.id);

//             if (existingProduct) {
//                 existingProduct.quantity++;
//             } else {
//                 physicalProductArray.push({
//                     id: productData.id,
//                     categoryName: productData.category,
//                     sku: productData.sku,
//                     quantity_in_stock: productData.available_qty,
//                     quantity: 1
//                 });
//             }

//             $('#code').val('');
//         },
//         error: function (error) {
//             console.error('Error retrieving product details:', error);
//         }
//     });
// }

// var debounceTimeout;
// var entryCount = 0;
// var totalEntries = 1900;
// var entryValue = '95010816';

// function debouncedGetProductDetails() {
//     clearTimeout(debounceTimeout);
//     debounceTimeout = setTimeout(getProductDetails, 300); // Adjust debounce time as needed
// }

// function getProductDetails() {
//     var barcodeInput = $('#code');
//     var barcodeNumber = barcodeInput.val().trim();
//     var locationId = $('input#location_id').val();

//     if (!barcodeNumber) {
//         toastr.error("Please enter a barcode number");
//         return;
//     }

//     // Disable the input field to restrict adding another barcode
//     barcodeInput.prop('disabled', true);

//     $.ajax({
//         type: 'GET',
//         url: '/products-list',
//         data: { barcode: barcodeNumber, location_id: locationId },
//         success: function (response) {
//             if (!response.data || !response.data.id) {
//                 toastr.error("Product Not Found");
//             } else {
//                 var productData = response.data;
//                 var scan = {
//                     id: productData.id,
//                     categoryName: productData.category,
//                     sku: productData.sku,
//                     img: productData.image_url,
//                     product: productData.product
//                 };

//                 ScannedProduct.unshift(scan);
//                 $('#rowCount').text(ScannedProduct.length);

//                 var existingProduct = physicalProductArray.find(item => item.id === productData.id);

//                 if (existingProduct) {
//                     existingProduct.quantity++;
//                 } else {
//                     physicalProductArray.push({
//                         id: productData.id,
//                         categoryName: productData.category,
//                         sku: productData.sku,
//                         quantity_in_stock: productData.available_qty,
//                         quantity: 1
//                     });
//                 }
//             }
//             // Re-enable the input field after the request completes
//             barcodeInput.prop('disabled', false).val('').focus();

//             // Continue with the next entry if not finished
//             if (entryCount < totalEntries) {
//                 entryCount++;
//                 setTimeout(enterBarcode, 300); // Adjust delay as needed
//             }
//         },
//         error: function (error) {
//             console.error('Error retrieving product details:', error);
//             toastr.error('Error retrieving product details');
//             // Re-enable the input field in case of error
//             barcodeInput.prop('disabled', false);
//         }
//     });
// }

function enterBarcode() {
    $('#code').val(entryValue);
    getProductDetails();
}

// Start the process
enterBarcode();

var debounceTimeout;

function debouncedGetProductDetails() {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(getProductDetails, 300); // Adjust debounce time as needed
}

function getProductDetails() {
    var barcodeInput = $('#code');
    var barcodeNumber = barcodeInput.val().trim();
    var locationId = $('input#location_id').val();

    if (!barcodeNumber) {
        toastr.error("Please enter a barcode number");
        return;
    }

    // Disable the input field to restrict adding another barcode
    barcodeInput.prop('disabled', true);

    $.ajax({
        type: 'GET',
        url: '/products-list',
        data: { barcode: barcodeNumber, location_id: locationId },
        success: function (response) {
            if (!response.data || !response.data.id) {
                toastr.error("Product Not Found");
            } else {
                var productData = response.data;
                var scan = {
                    id: productData.id,
                    categoryName: productData.category,
                    sku: productData.sku,
                    img: productData.image_url,
                    product: productData.product
                };

                ScannedProduct.unshift(scan);
                $('#rowCount').text(ScannedProduct.length);

                var existingProduct = physicalProductArray.find(item => item.id === productData.id);

                if (existingProduct) {
                    existingProduct.quantity++;
                } else {
                    physicalProductArray.push({
                        id: productData.id,
                        categoryName: productData.category,
                        sku: productData.sku,
                        quantity_in_stock: productData.available_qty,
                        quantity: 1
                    });
                }
            }
            // Re-enable the input field after the request completes
            barcodeInput.prop('disabled', false).val('').focus();
        },
        error: function (error) {
            console.error('Error retrieving product details:', error);
            toastr.error('Error retrieving product details');
            // Re-enable the input field in case of error
            barcodeInput.prop('disabled', false);
        }
    });
}


// function getProductDetails() {
//     // Get the entered barcode number
//     var barcodeNumber = $('#code').val();
    
//     // console.log(barcodeNumber);

//     // Make an AJAX request to retrieve product details
//     $.ajax({
//         type: 'GET',
//         url: '/products-list', // Replace with your actual API endpoint
//         data: {
//             barcode: barcodeNumber,
//             location_id: $('input#location_id').val(),
//         },
//         success: function (response) {
//             console.log(response);
//             if (response.data.id) {

//                 var scan = {
//                     id: response.data.id,
//                     categoryName: response.data.category,
//                     sku: response.data.sku,
//                     img: response.data.image_url,
//                     product: response.data.product
//                 }
//                 ScannedProduct.unshift(scan);

//                 console.log(ScannedProduct);
//                 $('#rowCount').text(ScannedProduct.length);

//                 // Populate the table with the retrieved product details
//                 // populateTable(response.data);

//                 var find = $.grep(physicalProductArray, function (item) {
//                     return item.id == response.data.id;
//                 });
//                 if (find.length > 0) {
//                     $.each(physicalProductArray, function (index, item) {
//                         if (response.data.id == item.id) {
//                             item.quantity++;
//                         }
//                     });
//                 } else {
//                     var data = {
//                         id: response.data.id,
//                         categoryName: response.data.category,
//                         sku: response.data.sku,
//                         quantity_in_stock: response.data.available_qty,
//                         quantity: 1
//                     };
//                     physicalProductArray.push(data);
//                 }
//                 // console.log(physicalProductArray, ScannedProduct);
//                 $('#code').val('');
//             } else {
//                 toastr.error("Product Not Found");
//             }
//         },
//         error: function (error) {
//             console.error('Error retrieving product details:', error);
//         }
//     });
// }

    // Event handler for the "Check Audit" button
    $('#check-audit').click(function() {
        // Clear existing table rows
        $('#audit_table tbody').empty();
        
        // Get the last 20 entries from the ScannedProduct array
        var lastEntries = ScannedProduct.slice(0, 20);
        
        // Iterate over the last entries and append them to the table
        $.each(lastEntries, function(index, item) {
            // console.log(item);
            var newRow = '<tr>';
            var imagePath = item.img ? ('uploads/img/' + item.img) : 'img/default.png';
            newRow += '<td><div style="display: flex; justify-content: center; align-items: center;"><img src="' + item.img + '" alt="Product image" class="product-thumbnail-small"></div></td>';
            newRow += '<td>' + item.id + '</td>';
            newRow += '<td>' + item.product + '</td>';
            newRow += '<td>' + item.sku + '</td>';
            newRow += '<td>' + item.categoryName + '</td>';
            newRow += '</tr>';
    
            // var newRow = '<tr>';
            // newRow += '<td>' + item.id + '</td>';
            // newRow += '<td>' + item.categoryName + '</td>';
            // newRow += '<td>' + item.sku + '</td>';
            // newRow += '</tr>';
            $('#audit_table tbody').append(newRow);
        });
    });


// Array to store all products
// var allAppendedProducts = [];
// Counter to keep track of the total number of products
var productCount = 0;

var maxRows = 10;

function populateTable(productDetails) {
    // Check if productDetails is not empty
    if (productDetails) {
        // Add productDetails to the allProducts array
        // allProducts.push(productDetails);

        // Increment the product count
        productCount++;

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
        $('#productDetails').prepend(newRow);

        // Update the count of all rows
        $('#rowCount').text(productCount);

        // If the number of rows exceeds the maximum, remove the last row
        if ($('#productDetails tr').length > maxRows) {
            $('#productDetails tr:last').remove();
        }
    }
}
// function populateTable(productDetails) {
//     // Check if productDetails is not empty
//     if (productDetails) {
//         // Add productDetails to the allProducts array
//         // allAppendedProducts.push(productDetails);

//         // Increment the product count
//         productCount++;

//         // Clear existing table rows if there are more than 10 rows
//         if ($('#productDetails tr').length >= 19) {
//             $('#productDetails tr').slice(19).remove();
//         }

//         // Add a new row with product details
//         var newRow = '<tr>';
//         var imagePath = productDetails.image ? ('uploads/img/' + productDetails.image) : 'img/default.png';
//         newRow += '<td><div style="display: flex; justify-content: center; align-items: center;"><img src="' + imagePath + '" alt="Product image" class="product-thumbnail-small"></div></td>';
//         newRow += '<td>' + productDetails.id + '</td>';
//         newRow += '<td>' + productDetails.product + '</td>';
//         newRow += '<td>' + productDetails.sku + '</td>';
//         newRow += '<td>' + productDetails.category + '</td>';
//         newRow += '</tr>';

//         // Append the new row to the table
//         $('#productDetails').prepend(newRow);

//         // Update the count of all rows
//         $('#rowCount').text(productCount);
//     }
// }



// function populateTable(productDetails) {
//     // Clear existing table rows
//     //   $('#productDetails').empty();

//     // Check if productDetails is not empty
//     if (productDetails) {
//         // Add a new row with product details
//         var newRow = '<tr>';
//         var imagePath = productDetails.image ? ('uploads/img/' + productDetails.image) : 'img/default.png';
//         newRow += '<td><div style="display: flex; justify-content: center; align-items: center;"><img src="' + imagePath + '" alt="Product image" class="product-thumbnail-small"></div></td>';
//         newRow += '<td>' + productDetails.id + '</td>';
//         newRow += '<td>' + productDetails.product + '</td>';
//         newRow += '<td>' + productDetails.sku + '</td>';
//         newRow += '<td>' + productDetails.category + '</td>';
//         newRow += '</tr>';

//         // Append the new row to the table
//         $('#productDetails').append(newRow);
//     }
// }


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
        // setTimeout(function () {
        //     // Reload the current page
        //     window.location.reload();
        // }, 5000); // 5000 milliseconds = 5 seconds
    } else {
        toastr.error("No product selected");
    }
})