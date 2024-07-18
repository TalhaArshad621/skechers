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

        $discount = DB::table('web_discount_variations')
            ->join('web_discounts', 'web_discount_variations.discount_id', '=', 'web_discounts.id')
            ->join('variations', 'web_discount_variations.variation_id', '=', 'variations.id')
            ->where('variations.sub_sku', $sku)
            ->select( 
                DB::raw('CASE 
                WHEN web_discounts.discount_amount IS NULL OR web_discounts.discount_amount = 0 THEN variations.sell_price_inc_tax 
                ELSE CAST(variations.sell_price_inc_tax * (web_discounts.discount_amount / 100) AS DECIMAL(10,2)) 
                END as discount_price'),
                'variations.sub_sku AS sku')
            ->first();

        // if (!$discount) {
        //     return response()->json(['original_price' => $variation->sell_price_inc_tax, 'sku' => $sku]);
        // }

        // $discountPercentage = $discount->discount_amount;

        // $discountedPrice = $variation->sell_price_inc_tax * (1 - ($discountPercentage / 100));

        return response()->json(['discounted_price' => $discount->discount_price, 'sku' => $discount->sku]);
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


    public function getProducts()
    {
        $products = DB::table('variations')
        ->leftJoin('variation_location_details', 'variation_location_details.variation_id', '=', 'variations.product_variation_id')
        ->leftJoin('web_discount_variations', 'web_discount_variations.variation_id', '=', 'variations.id')
        ->leftJoin('web_discounts', 'web_discount_variations.discount_id', '=', 'web_discounts.id')
        ->select(
            'variations.sub_sku as sku',
            DB::raw('COALESCE(variations.sell_price_inc_tax, 0) as sell_price'),
            DB::raw('COALESCE(SUM(variation_location_details.qty_available), 0) as qty_available'),
            DB::raw('CASE 
                        WHEN web_discounts.discount_amount IS NULL OR web_discounts.discount_amount = 0 THEN variations.sell_price_inc_tax 
                        ELSE CAST(variations.sell_price_inc_tax * (web_discounts.discount_amount / 100) AS DECIMAL(10,2)) 
                     END as discount_price')
        )
        ->where('variation_location_details.qty_available','>',0)
        ->groupBy('variations.id', 'variations.sub_sku', 'variations.sell_price_inc_tax')
        ->get();
    
        return response()->json([
            "result" => $products
        ]);
    }
}
