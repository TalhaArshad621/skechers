<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EcommerceSellLine extends Model
{
    //
    protected $guarded = ['id'];

    
    public function ecommerce_transaction()
    {
        return $this->belongsTo(\App\EcommerceTransaction::class, 'ecommerce_transaction_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }
    
    public function variations()
    {
        return $this->belongsTo(\App\Variation::class, 'variation_id');
    }

    public function sell_line_purchase_lines()
    {
        return $this->hasMany(\App\TransactionSellLinesPurchaseLines::class, 'sell_line_id');
    }

    
    public function modifiers()
    {
        return $this->hasMany(\App\TransactionSellLine::class, 'parent_sell_line_id')
            ->where('children_type', 'modifier');
    }

    
    /**
     * Get the quantity column.
     *
     * @param  string  $value
     * @return float $value
     */
    public function getQuantityAttribute($value)
    {
        return (float)$value;
    }

    public function lot_details()
    {
        return $this->belongsTo(\App\PurchaseLine::class, 'lot_no_line_id');
    }

    /**
     * Get the unit associated with the purchase line.
     */
    public function sub_unit()
    {
        return $this->belongsTo(\App\Unit::class, 'sub_unit_id');
    }

    public function order_statuses()
    {
        $statuses = [
            'received',
            'cooked',
            'served'
        ];
    }

    public function service_staff()
    {
        return $this->belongsTo(\App\User::class, 'res_service_staff_id');
    }

    /**
     * The warranties that belong to the sell lines.
     */
    public function warranties()
    {
        return $this->belongsToMany('App\Warranty', 'sell_line_warranties', 'sell_line_id', 'warranty_id');
    }

    public function line_tax()
    {
        return $this->belongsTo(\App\TaxRate::class, 'tax_id');
    }

    public function get_discount_amount()
    {
        $discount_amount = 0;
        if (!empty($this->line_discount_type) && !empty($this->line_discount_amount)) {
            if ($this->line_discount_type == 'fixed') {
                $discount_amount = $this->line_discount_amount;
            } elseif ($this->line_discount_type == 'percentage') {
                $discount_amount = ($this->unit_price_before_discount * $this->line_discount_amount) / 100;
            }
        }
        return $discount_amount;
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }
}
