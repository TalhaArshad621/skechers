<?php

namespace App\Listeners;

use App\AccountTransaction;

use App\Events\EcommercePaymentAdded;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;

class AddEcommerceAccountTransation
{

    protected $transactionUtil;
    protected $moduleUtil;

    /**
     * Create the event listener.
     *
     * @return void
     */
  public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
    }


    /**
     * Handle the event.
     *
     * @param  EcommercePaymentAdded  $event
     * @return void
     */
    public function handle(EcommercePaymentAdded $event)
    {
          //echo "<pre>";print_r($event->transactionPayment->toArray());exit;
          if ($event->ecommercePayment->method == 'advance') {
            $this->transactionUtil->updateContactBalance($event->ecommercePayment->payment_for, $event->ecommercePayment->amount, 'deduct');
        }

        if (!$this->moduleUtil->isModuleEnabled('account')) {
            return true;
        }

        // //Create new account transaction
        if (!empty($event->formInput['account_id']) && $event->ecommercePayment->method != 'advance') {
            $account_transaction_data = [
                'amount' => $event->formInput['amount'],
                'account_id' => $event->formInput['account_id'],
                'type' => AccountTransaction::getAccountTransactionType($event->formInput['transaction_type']),
                'operation_date' => $event->ecommercePayment->paid_on,
                'created_by' => $event->ecommercePayment->created_by,
                'transaction_id' => $event->ecommercePayment->transaction_id,
                'transaction_payment_id' =>  $event->ecommercePayment->id
            ];

            //If change return then set type as debit
            if ($event->formInput['transaction_type'] == 'sell' && isset($event->formInput['is_return']) && $event->formInput['is_return'] == 1) {
                $account_transaction_data['type'] = 'debit';
            }

            AccountTransaction::createAccountTransaction($account_transaction_data);
        }
    }
}
