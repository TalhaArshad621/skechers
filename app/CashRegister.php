<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the Cash registers transactions.
     */
    public function cash_register_transactions()
    {
        return $this->hasMany(\App\CashRegisterTransaction::class);
    }
    public function transactions()
    {
        return $this->hasMany(CashRegisterTransaction::class);
    }

    // Method to get the sum of amounts for a specific transaction type
    public function sumOfAmountByType($transactionType)
    {
        return $this->transactions()
            ->where('transaction_type', $transactionType)
            ->sum('amount');
    }
}
