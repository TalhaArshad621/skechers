<?php

namespace App\Utils;

use App\CashRegister;
use App\CashRegisterTransaction;
use App\Transaction;

use DB;

class CashRegisterUtil extends Util
{
    /**
     * Returns number of opened Cash Registers for the
     * current logged in user
     *
     * @return int
     */
    public function countOpenedRegister()
    {
        $user_id = auth()->user()->id;
        $count =  CashRegister::where('user_id', $user_id)
                                ->where('status', 'open')
                                ->count();
        return $count;
    }

        /**
     * Adds sell payments to currently opened cash register
     *
     * @param object/int $transaction
     * @param array $payments
     *
     * @return boolean
     */
    public function addSellEcommercePayments($transaction, $shopifyOrder, $user_id)
    {
        
        $register =  CashRegister::where('user_id', $user_id)
                                ->where('status', 'open')
                                ->first();
        $payments_formatted = [];
            
        $payments_formatted[] = new CashRegisterTransaction([
                'amount' => $this->num_uf($shopifyOrder['total_price']),
                'pay_method' => str_contains($shopifyOrder['payment_gateway_names'][0],  "(COD)") ? "cash" : "card",
                'type' => 'credit',
                'transaction_type' => 'sell',
                'transaction_id' => $transaction->id
            ]);

        if (!empty($payments_formatted)) {
            $register->cash_register_transactions()->saveMany($payments_formatted);
        }

        return true;
    }

    /**
     * Adds sell payments to currently opened cash register
     *
     * @param object/int $transaction
     * @param array $payments
     *
     * @return boolean
     */
    public function addSellPayments($transaction, $payments)
    {
        // dd($transaction, $payments);
        $user_id = auth()->user()->id;
        $register =  CashRegister::where('user_id', $user_id)
                                ->where('status', 'open')
                                ->first();
        $payments_formatted = [];
        foreach ($payments as $payment) {
            $payments_formatted[] = new CashRegisterTransaction([
                    'amount' => (isset($payment['is_return']) && $payment['is_return'] == 1) ? (-1*$this->num_uf($payment['amount'])) : $this->num_uf($payment['amount']),
                    'pay_method' => $payment['method'],
                    'type' => 'credit',
                    'transaction_type' => 'sell',
                    'transaction_id' => $transaction->id
                ]);
        }

        if (!empty($payments_formatted)) {
            $register->cash_register_transactions()->saveMany($payments_formatted);
        }

        return true;
    }

    /**
     * Adds sell payments to currently opened cash register
     *
     * @param object/int $transaction
     * @param array $payments
     *
     * @return boolean
     */
    public function updateSellPayments($status_before, $transaction, $payments)
    {
        $user_id = auth()->user()->id;
        $register =  CashRegister::where('user_id', $user_id)
                                ->where('status', 'open')
                                ->first();
        //If draft -> final then add all
        //If final -> draft then refund all
        //If final -> final then update payments
        if ($status_before == 'draft' && $transaction->status == 'final') {
            $this->addSellPayments($transaction, $payments);
        } elseif ($status_before == 'final' && $transaction->status == 'draft') {
            $this->refundSell($transaction);
        } elseif ($status_before == 'final' && $transaction->status == 'final') {
            $prev_payments = CashRegisterTransaction::where('transaction_id', $transaction->id)
                            ->select(
                                DB::raw("SUM(IF(pay_method='cash', IF(type='credit', amount, -1 * amount), 0)) as total_cash"),
                                DB::raw("SUM(IF(pay_method='card', IF(type='credit', amount, -1 * amount), 0)) as total_card"),
                                DB::raw("SUM(IF(pay_method='cheque', IF(type='credit', amount, -1 * amount), 0)) as total_cheque"),
                                DB::raw("SUM(IF(pay_method='bank_transfer', IF(type='credit', amount, -1 * amount), 0)) as total_bank_transfer"),
                                DB::raw("SUM(IF(pay_method='other', IF(type='credit', amount, -1 * amount), 0)) as total_other"),
                                DB::raw("SUM(IF(pay_method='custom_pay_1', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_1"),
                                DB::raw("SUM(IF(pay_method='custom_pay_2', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_2"),
                                DB::raw("SUM(IF(pay_method='custom_pay_3', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_3"),
                                DB::raw("SUM(IF(pay_method='custom_pay_4', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_4"),
                                DB::raw("SUM(IF(pay_method='custom_pay_5', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_5"),
                                DB::raw("SUM(IF(pay_method='custom_pay_6', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_6"),
                                DB::raw("SUM(IF(pay_method='custom_pay_7', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_7"),
                                DB::raw("SUM(IF(pay_method='advance', IF(type='credit', amount, -1 * amount), 0)) as total_advance")
                            )->first();
            if (!empty($prev_payments)) {
                $payment_diffs = [
                    'cash' => $prev_payments->total_cash,
                    'card' => $prev_payments->total_card,
                    'cheque' => $prev_payments->total_cheque,
                    'bank_transfer' => $prev_payments->total_bank_transfer,
                    'other' => $prev_payments->total_other,
                    'custom_pay_1' => $prev_payments->total_custom_pay_1,
                    'custom_pay_2' => $prev_payments->total_custom_pay_2,
                    'custom_pay_3' => $prev_payments->total_custom_pay_3,
                    'custom_pay_4' => $prev_payments->total_custom_pay_4,
                    'custom_pay_5' => $prev_payments->total_custom_pay_5,
                    'custom_pay_6' => $prev_payments->total_custom_pay_6,
                    'custom_pay_7' => $prev_payments->total_custom_pay_7,
                    'advance' => $prev_payments->total_advance 
                ];

                foreach ($payments as $payment) {
                    if (isset($payment['is_return']) && $payment['is_return'] == 1) {
                        $payment_diffs[$payment['method']] += $this->num_uf($payment['amount']);
                    } else {
                        $payment_diffs[$payment['method']] -= $this->num_uf($payment['amount']);
                    }
                }
                $payments_formatted = [];
                foreach ($payment_diffs as $key => $value) {
                    if ($value > 0) {
                        $payments_formatted[] = new CashRegisterTransaction([
                            'amount' => $value,
                            'pay_method' => $key,
                            'type' => 'debit',
                            'transaction_type' => 'refund',
                            'transaction_id' => $transaction->id
                        ]);
                    } elseif ($value < 0) {
                        $payments_formatted[] = new CashRegisterTransaction([
                            'amount' => -1 * $value,
                            'pay_method' => $key,
                            'type' => 'credit',
                            'transaction_type' => 'sell',
                            'transaction_id' => $transaction->id
                        ]);
                    }
                }
                if (!empty($payments_formatted)) {
                    $register->cash_register_transactions()->saveMany($payments_formatted);
                }
            }
        }

        return true;
    }

    /**
     * Refunds all payments of a sell
     *
     * @param object/int $transaction
     *
     * @return boolean
     */
    public function refundSell($transaction)
    {
        $user_id = auth()->user()->id;
        $register =  CashRegister::where('user_id', $user_id)
                                ->where('status', 'open')
                                ->first();

        $total_payment = CashRegisterTransaction::where('transaction_id', $transaction->id)
                            ->select(
                                DB::raw("SUM(IF(pay_method='cash', IF(type='credit', amount, -1 * amount), 0)) as total_cash"),
                                DB::raw("SUM(IF(pay_method='card', IF(type='credit', amount, -1 * amount), 0)) as total_card"),
                                DB::raw("SUM(IF(pay_method='cheque', IF(type='credit', amount, -1 * amount), 0)) as total_cheque"),
                                DB::raw("SUM(IF(pay_method='bank_transfer', IF(type='credit', amount, -1 * amount), 0)) as total_bank_transfer"),
                                DB::raw("SUM(IF(pay_method='other', IF(type='credit', amount, -1 * amount), 0)) as total_other"),
                                DB::raw("SUM(IF(pay_method='custom_pay_1', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_1"),
                                DB::raw("SUM(IF(pay_method='custom_pay_2', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_2"),
                                DB::raw("SUM(IF(pay_method='custom_pay_3', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_3"),
                                DB::raw("SUM(IF(pay_method='custom_pay_4', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_4"),
                                DB::raw("SUM(IF(pay_method='custom_pay_5', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_5"),
                                DB::raw("SUM(IF(pay_method='custom_pay_6', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_6"),
                                DB::raw("SUM(IF(pay_method='custom_pay_7', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_7")
                            )->first();
        $refunds = [
                    'cash' => $total_payment->total_cash,
                    'card' => $total_payment->total_card,
                    'cheque' => $total_payment->total_cheque,
                    'bank_transfer' => $total_payment->total_bank_transfer,
                    'other' => $total_payment->total_other,
                    'custom_pay_1' => $total_payment->total_custom_pay_1,
                    'custom_pay_2' => $total_payment->total_custom_pay_2,
                    'custom_pay_3' => $total_payment->total_custom_pay_3,
                    'custom_pay_4' => $total_payment->total_custom_pay_4,
                    'custom_pay_5' => $total_payment->total_custom_pay_5,
                    'custom_pay_6' => $total_payment->total_custom_pay_6,
                    'custom_pay_7' => $total_payment->total_custom_pay_7
                ];
        $refund_formatted = [];
        foreach ($refunds as $key => $val) {
            if ($val > 0) {
                $refund_formatted[] = new CashRegisterTransaction([
                    'amount' => $val,
                    'pay_method' => $key,
                    'type' => 'debit',
                    'transaction_type' => 'refund',
                    'transaction_id' => $transaction->id
                ]);
            }
        }

        if (!empty($refund_formatted)) {
            $register->cash_register_transactions()->saveMany($refund_formatted);
        }
        return true;
    }

    /**
     * Retrieves details of given rigister id else currently opened register
     *
     * @param $register_id default null
     *
     * @return object
     */
    public function getRegisterDetails($register_id = null)
    {
        $query = CashRegister::leftjoin(
            'cash_register_transactions as ct',
            'ct.cash_register_id',
            '=',
            'cash_registers.id'
        )
        ->join(
            'users as u',
            'u.id',
            '=',
            'cash_registers.user_id'
        )
        ->leftJoin(
            'business_locations as bl',
            'bl.id',
            '=',
            'cash_registers.location_id'
        );
        if (empty($register_id)) {
            $user_id = auth()->user()->id;
            $query->where('user_id', $user_id)
                ->where('cash_registers.status', 'open');
        } else {
            $query->where('cash_registers.id', $register_id);
        }
                              
        $register_details = $query->select(
            'cash_registers.created_at as open_time',
            'cash_registers.closed_at as closed_at',
            'cash_registers.user_id',
            'cash_registers.closing_note',
            'cash_registers.location_id',
            DB::raw("SUM(IF(transaction_type='initial', amount, 0)) as cash_in_hand"),
            DB::raw("SUM(IF(transaction_type='sell', amount, IF(transaction_type='refund', -1 * amount, 0))) as total_sale"),
            DB::raw("SUM(IF(pay_method='cash', IF(transaction_type='sell', amount, 0), 0)) as total_cash"),
            DB::raw("SUM(IF(pay_method='cheque', IF(transaction_type='sell', amount, 0), 0)) as total_cheque"),
            DB::raw("SUM(IF(pay_method='card', IF(transaction_type='sell', amount, 0), 0)) as total_card"),
            DB::raw("SUM(IF(pay_method='bank_transfer', IF(transaction_type='sell', amount, 0), 0)) as total_bank_transfer"),
            DB::raw("SUM(IF(pay_method='other', IF(transaction_type='sell', amount, 0), 0)) as total_other"),
            DB::raw("SUM(IF(pay_method='advance', IF(transaction_type='sell', amount, 0), 0)) as total_advance"),
            DB::raw("SUM(IF(pay_method='custom_pay_1', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_1"),
            DB::raw("SUM(IF(pay_method='custom_pay_2', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_2"),
            DB::raw("SUM(IF(pay_method='custom_pay_3', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_3"),
            DB::raw("SUM(IF(pay_method='custom_pay_4', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_4"),
            DB::raw("SUM(IF(pay_method='custom_pay_5', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_5"),
            DB::raw("SUM(IF(pay_method='custom_pay_6', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_6"),
            DB::raw("SUM(IF(pay_method='custom_pay_7', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_7"),
            DB::raw("SUM(IF(transaction_type='refund', amount, 0)) as total_refund"),
            // DB::raw("SUM(IF(transaction_type='sell_return', amount, 0)) as total_sale_return"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='cash', amount, 0), 0)) as total_cash_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='cheque', amount, 0), 0)) as total_cheque_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='card', amount, 0), 0)) as total_card_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='bank_transfer', amount, 0), 0)) as total_bank_transfer_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='other', amount, 0), 0)) as total_other_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='advance', amount, 0), 0)) as total_advance_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_1', amount, 0), 0)) as total_custom_pay_1_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_2', amount, 0), 0)) as total_custom_pay_2_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_3', amount, 0), 0)) as total_custom_pay_3_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_4', amount, 0), 0)) as total_custom_pay_4_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_5', amount, 0), 0)) as total_custom_pay_5_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_6', amount, 0), 0)) as total_custom_pay_6_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_7', amount, 0), 0)) as total_custom_pay_7_refund"),
            DB::raw("SUM(IF(pay_method='cheque', 1, 0)) as total_cheques"),
            DB::raw("SUM(IF(pay_method='card', 1, 0)) as total_card_slips"),
            DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as user_name"),
            'u.email',
            'bl.name as location_name'
        )
        // ->where('transactions.payment_status','paid')
        ->first();

       

        // dd($register_details);
        return $register_details;
    }

    public function getSaleReturnDetails($location_id,$open_time, $close_time,$register_id = null){

        $sell_return_seperate = CashRegister::leftjoin('cash_register_transactions','cash_register_transactions.cash_register_id','=','cash_registers.id')
        ->leftjoin('transactions', 'transactions.id', '=', 'cash_register_transactions.transaction_id')
        ->where('transactions.type','sell_return')
        ->where('cash_register_transactions.transaction_type', 'sell')
        ->where('transactions.location_id', $location_id)
        ->where('transactions.payment_status','paid')
        ->whereBetween('transactions.transaction_date', [$open_time, $close_time])
        ->select(
            // 'cash_registers.created_at as open_time',
            // 'cash_registers.closed_at as closed_at',
            // '*',
            DB::raw("SUM(cash_register_transactions.amount) as total_sale_return"),
            // DB::raw("SUM(final_total) as total_sale_return"),

            )
        ->first();
        // dd($sell_return);


        $international_return = CashRegister::leftjoin('cash_register_transactions','cash_register_transactions.cash_register_id','=','cash_registers.id')
        ->leftjoin('transactions', 'transactions.id', '=', 'cash_register_transactions.transaction_id')
        ->where('transactions.type','international_return')
        ->where('transactions.location_id', $location_id)
        ->where('transactions.payment_status','paid')
        ->whereBetween('transactions.transaction_date', [$open_time, $close_time])
        ->select(
            DB::raw("SUM(cash_register_transactions.amount) as total_sale_return"),

            )
        ->first();
        $sell_return = 0;

        if ($sell_return_seperate) {
            $sell_return += floatval($sell_return_seperate->total_sale_return ?? 0);
        }

        if ($international_return) {
            $sell_return += floatval($international_return->total_sale_return ?? 0);
        }
        return  $sell_return;
    }

    /**
     * Get the transaction details for a particular register
     *
     * @param $user_id int
     * @param $open_time datetime
     * @param $close_time datetime
     *
     * @return array
     */
    public function getRegisterTransactionDetails($user_id, $open_time, $close_time, $is_types_of_service_enabled = false)
    {
        $product_details = Transaction::where('transactions.created_by', $user_id)
                ->whereBetween('transaction_date', [$open_time, $close_time])
                ->whereIN('transactions.type', ['sell','sell_return'])
                // ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->where('transactions.is_direct_sale', 0)
                ->join('transaction_sell_lines AS TSL', 'transactions.id', '=', 'TSL.transaction_id')
                ->join('products AS P', 'TSL.product_id', '=', 'P.id')
                ->leftjoin('brands AS B', 'P.brand_id', '=', 'B.id')
                ->groupBy('B.id')
                ->select(
                    'transactions.id',
                    'B.name as brand_name',
                    DB::raw('SUM(TSL.quantity - TSL.quantity_returned) as total_quantity'),
                    // DB::raw('SUM(TSL.quantity - TSL.quantity_returned) as total_qty_sold'),

                    DB::raw('SUM((TSL.quantity) * TSL.unit_price_inc_tax) as total_amount'),

                    // DB::raw('SUM(TSL.unit_price_inc_tax*TSL.quantity) as total_amount')
                )
                // ->whereRaw('TSL.quantity - TSL.quantity_returned <> 0')

                ->orderByRaw('CASE WHEN brand_name IS NULL THEN 2 ELSE 1 END, brand_name')
                ->get();
                // dd($product_details);
                // $transactionIds = $product_details->pluck('id')->toArray();
                // dd($transactionIds);


                $product_details_international = Transaction::where('transactions.created_by', $user_id)
                ->whereBetween('transaction_date', [$open_time, $close_time])
                ->where('transactions.type', 'international_return')
                ->where('transactions.status', 'final')
                ->where('transactions.is_direct_sale', 0)
                ->join('transaction_sell_lines AS TSL', 'transactions.id', '=', 'TSL.transaction_id')
                ->join('products AS P', 'TSL.product_id', '=', 'P.id')
                ->leftjoin('brands AS B', 'P.brand_id', '=', 'B.id')
                ->where('TSL.sell_line_note','<>','international_return')
                ->groupBy('B.id')
                ->select(
                    'transactions.id',
                    'B.name as brand_name',
                    DB::raw('SUM(TSL.quantity - TSL.quantity_returned) as total_quantity'),
                    // DB::raw('SUM(TSL.quantity - TSL.quantity_returned) as total_qty_sold'),

                    DB::raw('SUM((TSL.quantity) * TSL.unit_price_inc_tax) as total_amount'),

                    // DB::raw('SUM(TSL.unit_price_inc_tax*TSL.quantity) as total_amount')
                )
                // ->whereRaw('TSL.quantity - TSL.quantity_returned <> 0')

                ->orderByRaw('CASE WHEN brand_name IS NULL THEN 2 ELSE 1 END, brand_name')
                ->get();
                // dd($product_details_international);
                // $transactionIds = $product_details->pluck('id')->toArray();
                // dd($transactionIds);


                $exchanged_product_details = Transaction::where('transactions.created_by', $user_id)
                ->whereBetween('transaction_date', [$open_time, $close_time])
                ->where('transactions.type','sell_return')
                ->where('transactions.status', 'final')
                ->where('transactions.is_direct_sale', 0)
                // ->join('transaction_sell_lines AS TSL', 'transactions.id', '=', 'TSL.transaction_id')
                // ->join('products AS P', 'TSL.product_id', '=', 'P.id')
                // ->leftjoin('brands AS B', 'P.brand_id', '=', 'B.id')
                // ->groupBy('B.id')
                ->select(
                    // 'transactions.id',
                    // 'B.name as brand_name',
                    // DB::raw('SUM(TSL.quantity) as total_quantity'),
                    // DB::raw('SUM(TSL.quantity - TSL.quantity_returned) as total_qty_sold'),

                    DB::raw('SUM(transactions.final_total) as total_amount'),

                    // DB::raw('SUM(TSL.unit_price_inc_tax*TSL.quantity) as total_amount')
                )
                // ->whereRaw('TSL.quantity - TSL.quantity_returned <> 0')

                // ->orderByRaw('CASE WHEN brand_name IS NULL THEN 2 ELSE 1 END, brand_name')
                ->first();
                // dd($exchanged_product_details);


        $return_product_details_id = Transaction::where('transactions.created_by', $user_id)
                ->whereBetween('transaction_date', [$open_time, $close_time])
                ->whereIn('transactions.type', ['sell_return','international_return'])
                // ->whereIn('transactions.return_parent_id', $transactionIds)
                // ->whereNull('transactions.return_parent_id')
                ->where('transactions.status', 'final')
                ->where('transactions.is_direct_sale', 0)
                // ->join('transaction_sell_lines AS TSL', 'transactions.return_parent_id', '=', 'TSL.transaction_id')
                // ->join('products AS P', 'TSL.product_id', '=', 'P.id')
                // ->leftjoin('brands AS B', 'P.brand_id', '=', 'B.id')
                // ->groupBy('B.id')
                ->select(
                    // '*',
                    'transactions.id',
                    // 'B.name as brand_name',
                    // DB::raw('SUM(TSL.quantity_returned) as returned_quantity'),
                    // DB::raw('SUM(TSL.unit_price_inc_tax*TSL.quantity_returned) as total_amount_returned'),
                    // DB::raw('SUM(TSL.quantity) as total_quantity'),
                    // DB::raw('SUM(TSL.unit_price_inc_tax*TSL.quantity) as total_amount'),
                    // DB::raw('SUM(TSL.unit_price_inc_tax*TSL.quantity - TSL.unit_price_inc_tax*TSL.quantity_returned) as net_total_amount')

                )
                // ->orderByRaw('CASE WHEN brand_name IS NULL THEN 2 ELSE 1 END, brand_name')
                ->get();
                $transactionIds = $return_product_details_id->pluck('id')->toArray();

                // dd($transactionIds);

                $return_product_details = Transaction::where('transactions.created_by', $user_id)
                ->whereBetween('transaction_date', [$open_time, $close_time])
                ->where('transactions.type', 'sell_return')
                ->whereIn('transactions.id', $transactionIds)
                // ->whereNull('transactions.return_parent_id')
                ->where('transactions.status', 'final')
                ->where('transactions.is_direct_sale', 0)
                ->join('transaction_sell_lines AS TSL', 'transactions.return_parent_id', '=', 'TSL.transaction_id')
                ->join('products AS P', 'TSL.product_id', '=', 'P.id')
                ->leftjoin('brands AS B', 'P.brand_id', '=', 'B.id')
                // ->groupBy('B.id')
                ->select(
                    'TSL.quantity_returned AS returned_quantity', 'TSL.quantity AS total_quantity','TSL.unit_price_inc_tax',
                    DB::raw('(TSL.unit_price_inc_tax*TSL.quantity_returned) as total_amount_returned'),
                    DB::raw('(TSL.unit_price_inc_tax*TSL.quantity - TSL.unit_price_inc_tax*TSL.quantity_returned) as net_total_amount'),

                    // 'transactions.id',
                    'B.name as brand_name',
                    // DB::raw('SUM(TSL.quantity_returned) as returned_quantity'),
                    // DB::raw('SUM(TSL.unit_price_inc_tax*TSL.quantity_returned) as total_amount_returned'),
                    // DB::raw('SUM(TSL.quantity) as total_quantity'),
                    // DB::raw('SUM(TSL.unit_price_inc_tax*TSL.quantity) as total_amount'),
                    // DB::raw('SUM(TSL.unit_price_inc_tax*TSL.quantity - TSL.unit_price_inc_tax*TSL.quantity_returned) as net_total_amount')

                )
                // ->orderByRaw('CASE WHEN brand_name IS NULL THEN 2 ELSE 1 END, brand_name')
                ->get();
                // dd($return_product_details);


                $return_product_details_international = Transaction::where('transactions.created_by', $user_id)
                ->whereBetween('transaction_date', [$open_time, $close_time])
                ->where('transactions.type', 'international_return')
                ->whereIn('transactions.id', $transactionIds)
                ->where('transactions.status', 'final')
                ->where('transactions.is_direct_sale', 0)
                ->join('transaction_sell_lines AS TSL', 'transactions.id', '=', 'TSL.transaction_id')
                ->join('products AS P', 'TSL.product_id', '=', 'P.id')
                ->leftjoin('brands AS B', 'P.brand_id', '=', 'B.id')
                ->where('TSL.sell_line_note','international_return')
                ->select(
                    'TSL.quantity_returned AS returned_quantity', 'TSL.quantity AS total_quantity','TSL.unit_price_inc_tax',
                    DB::raw('(TSL.unit_price_inc_tax*TSL.quantity_returned) as total_amount_returned'),
                    DB::raw('(TSL.unit_price_inc_tax*TSL.quantity - TSL.unit_price_inc_tax*TSL.quantity_returned) as net_total_amount'),
                    'B.name as brand_name',
                )
                ->get();

        //If types of service
        $types_of_service_details = null;
        if ($is_types_of_service_enabled) {
            $types_of_service_details = Transaction::where('transactions.created_by', $user_id)
                ->whereBetween('transaction_date', [$open_time, $close_time])
                ->where('transactions.is_direct_sale', 0)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->leftjoin('types_of_services AS tos', 'tos.id', '=', 'transactions.types_of_service_id')
                ->groupBy('tos.id')
                ->select(
                    'tos.name as types_of_service_name',
                    DB::raw('SUM(final_total) as total_sales')
                )
                ->orderBy('total_sales', 'desc')
                ->get();
        }

        $transaction_details = Transaction::where('transactions.created_by', $user_id)
                ->whereBetween('transaction_date', [$open_time, $close_time])
                ->where('transactions.type', 'sell')
                ->where('transactions.is_direct_sale', 0)
                ->where('transactions.status', 'final')
                ->select(
                    DB::raw('SUM(tax_amount) as total_tax'),
                    DB::raw('SUM(IF(discount_type = "percentage", total_before_tax*discount_amount/100, discount_amount)) as total_discount'),
                    DB::raw('SUM(final_total) as total_sales')
                )
                ->first();

        return ['product_details' => $product_details,
                'product_details_international' => $product_details_international,
                'return_product_details_international' => $return_product_details_international,
                'return_product_details' => $return_product_details,
                'transaction_details' => $transaction_details,
                'types_of_service_details' => $types_of_service_details,
                'exchanged_product_details' => $exchanged_product_details
            ];
    }

    /**
     * Retrieves the currently opened cash register for the user
     *
     * @param $int user_id
     *
     * @return obj
     */
    public function getCurrentCashRegister($user_id)
    {
        $register =  CashRegister::where('user_id', $user_id)
                                ->where('status', 'open')
                                ->first();

        return $register;
    }
}
