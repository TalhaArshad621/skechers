<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CashRegisterTransaction extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }
}
