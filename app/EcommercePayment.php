<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EcommercePayment extends Model
{
        /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

     /**
     * Get the phone record associated with the user.
     */
    public function payment_account()
    {
        return $this->belongsTo(\App\Account::class, 'account_id');
    }

    /**
     * Get the transaction related to this payment.
     */
    public function transaction()
    {
        return $this->belongsTo(\App\EcommerceTransaction::class, 'ecommerce_transaction_id');
    }

    /**
     * Get the user.
     */
    public function created_user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    /**
     * Get child payments
     */
    public function child_payments()
    {
        return $this->hasMany(\App\EcommercePayment::class, 'parent_id');
    }

    /**
     * Retrieves documents path if exists
     */
    public function getDocumentPathAttribute()
    {
        $path = !empty($this->document) ? asset('/uploads/documents/' . $this->document) : null;
        
        return $path;
    }

    /**
     * Removes timestamp from document name
     */
    public function getDocumentNameAttribute()
    {
        $document_name = !empty(explode("_", $this->document, 2)[1]) ? explode("_", $this->document, 2)[1] : $this->document ;
        return $document_name;
    }
}
