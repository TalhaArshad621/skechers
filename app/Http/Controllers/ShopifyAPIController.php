<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Variation;
use Illuminate\Support\Facades\DB;

class ShopifyAPIController extends Controller
{
    public function getProductPrice(Request $request)
    {
        $sku = $request->input('sku');
        $variation = Variation::where('sub_sku', $sku)->first();

        if ($variation) {
            return response()->json(['original_price' => $variation->sell_price_inc_tax, 'sku' => $variation->sub_sku]);
        } else {
            return response()->json(['error' => 'Product not found'], 404);
        }
    }

    public function getDiscountPriceBySku(Request $request)
    {
        $sku = $request->input('sku');
        $variation = Variation::where('sub_sku', $sku)->first();

        if (!$variation) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $discount = DB::table('discount_variations')
            ->join('discounts', 'discount_variations.discount_id', '=', 'discounts.id')
            ->join('variations', 'discount_variations.variation_id', '=', 'variations.id')
            ->where('variations.sub_sku', $sku)
            ->select('discounts.discount_amount', 'variations.sub_sku AS sku')
            ->first();

        if (!$discount) {
            return response()->json(['original_price' => $variation->sell_price_inc_tax, 'sku' => $sku]);
        }

        $discountPercentage = $discount->discount_amount;

        $discountedPrice = $variation->sell_price_inc_tax * (1 - ($discountPercentage / 100));

        return response()->json(['discounted_price' => $discountedPrice, 'sku' => $discount->sku]);
    }


    public function getStockQuantityBySku(Request $request)
    {
        $sku = $request->input('sku');
        $variationIds = DB::table('variations')->where('sub_sku', $sku)->pluck('id');

        if ($variationIds->isEmpty()) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $totalQuantity = DB::table('variation_location_details')
            ->whereIn('variation_id', $variationIds)
            ->where('location_id', '<>', 9)
            ->sum('qty_available');

        return response()->json([
            'sku' => $sku,
            'total_quantity_available' => $totalQuantity
        ]);
    }

    public function getAllProducts(Request $request)
    {
        $allProducts = DB::table('variation_location_details')
        ->join('products', 'variation_location_details.product_id', 'products.id')
        ->join('variations', 'variation_location_details.variation_id', 'variations.id')
            ->where('location_id', '<>', 9)
            ->select('products.name',
            'products.sku',
            'variations.sell_price_inc_tax as sell_price',
            'variation_location_details.qty_available as available_quantity')
            ->get();

        return response()->json([
            'products_data' => $allProducts
        ]);
    }
}
