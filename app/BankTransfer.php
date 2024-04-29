<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BankTransfer extends Model
{
    protected $guarded;
    protected $table = "bank_transfer";
    protected $fillable = [
        'bank',
        'amount',
        'transaction_date',
        'added_by'
    ];
}
