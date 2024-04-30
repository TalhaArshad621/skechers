<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\CustomerGroup;
use App\Product;
use App\PurchaseLine;
use App\TaxRate;
use App\Transaction;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\CashRegisterUtil;
use App\Variation;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Activitylog\Models\Activity;

class InternationalExchangeController extends Controller
{

    protected $productUtil;
    protected $transactionUtil;
    protected $moduleUtil;
    protected $contactUtil;
    protected $cashRegisterUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil,ContactUtil $contactUtil, CashRegisterUtil $cashRegisterUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;
        $this->contactUtil = $contactUtil;
        $this->moduleUtil = $moduleUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;

        $this->dummyPaymentLine = ['method' => 'cash', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
        'is_return' => 0, 'transaction_no' => ''];
    }


    public function index()
    {
    }

    public function create()
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }

        $taxes = TaxRate::where('business_id', $business_id)
                        ->ExcludeForTaxGroup()
                        ->get();
        $orderStatuses = $this->productUtil->orderStatuses();
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        // dd($business_locations);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];
        // dd($business_locations);

        $default_location = null;
        foreach ($business_locations as $id => $name) {
            $default_location = BusinessLocation::findOrFail($id);
            break;
        }
        // dd($business_locations,$business_id,$permitted_locations);

        $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);

        $default_purchase_status = null;
        if (request()->session()->get('business.enable_purchase_status') != 1) {
            $default_purchase_status = 'received';
        }

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = CustomerGroup::forDropdown($business_id);

        $business_details = $this->businessUtil->getDetails($business_id);
        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);

        $payment_line = $this->dummyPaymentLine;
        $payment_types = $this->productUtil->payment_types(null, true);

        //Accounts
        $accounts = $this->moduleUtil->accountsDropdown($business_id, true);

        $business_details = $this->businessUtil->getDetails($business_id);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);


        $payment_lines[] = $this->dummyPaymentLine;

        $payment_types = $this->productUtil->payment_types(null, true, $business_id);
        $change_return = $this->dummyPaymentLine;

        return view('international_exchange.create')
        ->with(compact('taxes', 'orderStatuses', 'business_locations', 'currency_details', 'default_purchase_status', 'customer_groups', 'types', 'shortcuts', 'payment_line', 'payment_types', 'accounts', 'bl_attributes','business_details','pos_settings','payment_lines','change_return','default_location'));

    }

    public function store(Request $request) 
    {
        // dd($request);

        if (!auth()->user()->can('access_sell_return')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->except('_token');
            // dd($input);
            if (!empty($input['products'])) {
                $input['products'] = array_filter($input['products'], function ($product) {
                    return isset($product['quantity']) && isset($product['unit_price_inc_tax']) && isset($product['sell_line_id']);
                });
            }
            // dd($input['purchases'],$input['products']);
            // dd($input['products']);
            // if (empty($input['products'])) {
            //     // Handle case where there are no valid products
            //     return response()->json(['error' => 'No valid products found.'], 400);
            // }
            if (!empty($input['purchases'])) {
                $business_id = $request->session()->get('user.business_id');
                //Check if subscribed or not
                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse(action('SellReturnController@index'));
                }
        
                $user_id = $request->session()->get('user.id');

                DB::beginTransaction();
                // dd($input);
                
                // dd($input, $business_id, $user_id);
                $sell_return =  $this->transactionUtil->addSellReturnInternational($input, $business_id, $user_id);
                // dd($sell_return,$business_id);
                // $receipt = $this->receiptContent($business_id, $sell_return->location_id, $sell_return->id);
                // dd($receipt);
                DB::commit();

                $output = ['success' => 1,
                            'msg' => __('lang_v1.success'),
                            // 'receipt' => $receipt
                        ];
            }

            // dd($input['exchange_products']);
            foreach ($input['exchange_products'] as $key => $product) {

                $variationId = $product['variation_id'];
                
                // Fetch default_sell_price from the database based on $variationId
                $defaultSellPrice = Variation::where('id', $variationId)->value('default_sell_price');
                // dd($defaultSellPrice);
                
                // Fetch tax_id from the products table based on $product['product_id']
                $taxId = Product::where('id', $product['product_id'])->value('tax');
                // dd($taxId);
            
                // Calculate item_tax
                $sellPriceIncTax = (float) str_replace(',', '', $product['unit_price_inc_tax']); // Remove commas and convert to float
                $itemTax = $sellPriceIncTax - $defaultSellPrice;
                
                // Add the fetched default_sell_price, tax_id, and calculated item_tax to the sub-array
                $input['exchange_products'][$key]['default_sell_price'] = $defaultSellPrice;
                $input['exchange_products'][$key]['item_tax'] = $itemTax;
                $input['exchange_products'][$key]['tax_id'] = $taxId;
                // $input['exchange_products'][$key]['line_discount_type'] = 'fixed';
                // $input['exchange_products'][$key]['line_discount_amount'] = 0;
                $input['exchange_products'][$key]['sell_line_note'] = null;

            }
            
            // foreach ($input['exchange_products'] as $key => $product) {
            //     // Add the new key-value pair to each sub-array
            //     $input['exchange_products'][$key]['line_discount_type'] = 'fixed';
            // }
            // dd($input['exchange_products']);

            if (!empty($input['exchange_products'])) {
                $business_id = $request->session()->get('user.business_id');

                //Check if subscribed or not, then check for users quota
                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse();
                } elseif (!$this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
                    return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action('SellPosController@index'));
                }
        
                $user_id = $request->session()->get('user.id');

                $discount = ['discount_type' => $input['discount_type'],
                                'discount_amount' => $input['discount_amount']
                            ];
                // $input['exchange_products'];
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['exchange_products'], $input['tax_rate_id'], $discount);

                DB::beginTransaction();
                
                $is_direct_sale = false;
                if (!empty($request->input('is_direct_sale'))) {
                    $is_direct_sale = true;
                }
                
                if (empty($request->input('transaction_date'))) {
                    $input['transaction_date'] =  \Carbon::now();
                } else {
                    $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                }
                if ($is_direct_sale) {
                    $input['is_direct_sale'] = 1;
                }
                // dd("here");

                //Set commission agent
                $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');
                if ($commsn_agnt_setting == 'logged_in_user') {
                    $input['commission_agent'] = $user_id;
                }

                if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                    $input['exchange_rate'] = 1;
                }

                // dd("heree");
                //Customer group details
                $contact_id = $request->get('contact_id', null);
                $cg = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
                $input['customer_group_id'] = (empty($cg) || empty($cg->id)) ? null : $cg->id;

                //set selling price group id
                $price_group_id = $request->has('price_group') ? $request->input('price_group') : null;

                //If default price group for the location exists
                $price_group_id = $price_group_id == 0 && $request->has('default_price_group') ? $request->input('default_price_group') : $price_group_id;

                $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend']  ? 1 : 0;
                if ($input['is_suspend']) {
                    $input['sale_note'] = !empty($input['additional_notes']) ? $input['additional_notes'] : null;
                }
                // dd("hit");

                //Generate reference number
                if (!empty($input['is_recurring'])) {
                    //Update reference count
                    $ref_count = $this->transactionUtil->setAndGetReferenceCount('subscription');
                    $input['subscription_no'] = $this->transactionUtil->generateReferenceNumber('subscription', $ref_count);
                }

                if (!empty($request->input('invoice_scheme_id'))) {
                    $input['invoice_scheme_id'] = $request->input('invoice_scheme_id');
                }

                //Types of service
                if ($this->moduleUtil->isModuleEnabled('types_of_service')) {
                    $input['types_of_service_id'] = $request->input('types_of_service_id');
                    $price_group_id = !empty($request->input('types_of_service_price_group')) ? $request->input('types_of_service_price_group') : $price_group_id;
                    $input['packing_charge'] = !empty($request->input('packing_charge')) ?
                    $this->transactionUtil->num_uf($request->input('packing_charge')) : 0;
                    $input['packing_charge_type'] = $request->input('packing_charge_type');
                    $input['service_custom_field_1'] = !empty($request->input('service_custom_field_1')) ?
                    $request->input('service_custom_field_1') : null;
                    $input['service_custom_field_2'] = !empty($request->input('service_custom_field_2')) ?
                    $request->input('service_custom_field_2') : null;
                    $input['service_custom_field_3'] = !empty($request->input('service_custom_field_3')) ?
                    $request->input('service_custom_field_3') : null;
                    $input['service_custom_field_4'] = !empty($request->input('service_custom_field_4')) ?
                    $request->input('service_custom_field_4') : null;
                }

                $input['selling_price_group_id'] = $price_group_id;

                if ($this->transactionUtil->isModuleEnabled('tables')) {
                    $input['res_table_id'] = request()->get('res_table_id');
                }
                if ($this->transactionUtil->isModuleEnabled('service_staff')) {
                    $input['res_waiter_id'] = request()->get('res_waiter_id');
                }

                //upload document
                $input['document'] = $this->transactionUtil->uploadFile($request, 'sell_document', 'documents');

                // $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id);

                //Upload Shipping documents
                // Media::uploadMedia($business_id, $transaction, $request, 'shipping_documents', false, 'shipping_document');
                
                // Getting FBR LINES DATA FROM SELL LINES FUCNCTION4
                // dd($sell_return);
                // dd($input['exchange_products']);

                $fbr_lines =   $this->transactionUtil->createOrUpdateSellLinesReturn($sell_return, $input['exchange_products'], $sell_return->location_id);
                // dd($fbr_lines);
                if (!$is_direct_sale) {
                    //Add change return
                    $change_return = $this->dummyPaymentLine;
                    $change_return['amount'] = $input['change_return'];
                    $change_return['is_return'] = 1;
                    $input['payment'][] = $change_return;
                }

                $is_credit_sale = isset($input['is_credit_sale']) && $input['is_credit_sale'] == 1 ? true : false;

                if (!$sell_return->is_suspend && !empty($input['payment']) && !$is_credit_sale) {
                    $this->transactionUtil->createOrUpdatePaymentLines($sell_return, $input['payment']);
                }
                $input['status'] = 'final';
                $input['location_id'] = $sell_return->location_id;

                // dd($input);

                //Check for final and do some processing.
                if ($input['status'] == 'final') {
                    //update product stock
                    foreach ($input['exchange_products'] as $product) {
                        $decrease_qty = $this->productUtil
                                    ->num_uf($product['quantity']);
                        if (!empty($product['base_unit_multiplier'])) {
                            $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                        }

                        if ($product['enable_stock']) {
                            $this->productUtil->decreaseProductQuantity(
                                $product['product_id'],
                                $product['variation_id'],
                                $input['location_id'],
                                $decrease_qty
                            );
                        }

                        if ($product['product_type'] == 'combo') {
                            //Decrease quantity of combo as well.
                            $this->productUtil
                                ->decreaseProductQuantityCombo(
                                    $product['combo'],
                                    $input['location_id']
                                );
                        }
                    }

                    // dd($item_array,$input);

                    // dd($input);

                    //Add payments to Cash Register
                    if (!$sell_return->is_suspend && !empty($input['payment']) && !$is_credit_sale) {
                        $this->cashRegisterUtil->addSellPayments($sell_return, $input['payment']);
                    }
                    // dd("hit");
                    
                    //Update payment status
                    // $payment_status = $this->transactionUtil->updatePaymentStatus($sell_return->id, $sell_return->final_total);
                    // dd($payment_status);
                    $sell_return->payment_status = "paid";
                    // dd($sell_return);

                    if ($request->session()->get('business.enable_rp') == 1) {
                        $redeemed = !empty($input['rp_redeemed']) ? $input['rp_redeemed'] : 0;
                        $this->transactionUtil->updateCustomerRewardPoints($contact_id, $sell_return->rp_earned, 0, $redeemed);
                    }

                    //Allocate the quantity from purchase and add mapping of
                    //purchase & sell lines in
                    //transaction_sell_lines_purchase_lines table
                    $business_details = $this->businessUtil->getDetails($business_id);
                    $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

                    $business = ['id' => $business_id,
                                    'accounting_method' => $request->session()->get('business.accounting_method'),
                                    'location_id' => $input['location_id'],
                                    'pos_settings' => $pos_settings
                                ];
                    $this->transactionUtil->mapPurchaseSell($business, $sell_return->sell_lines, 'purchase');
                    // dd("done");

                    //Auto send notification
                    // $whatsapp_link = $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);
                }

                //Set Module fields
                // if (!empty($input['has_module_data'])) {
                //     $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);
                // }

                
                // Media::uploadMedia($business_id, $transaction, $request, 'documents');

                $this->transactionUtil->activityLog($sell_return, 'added');
                // dd("done");

                DB::commit();

                if ($request->input('is_save_and_print') == 1) {
                    $url = $this->transactionUtil->getInvoiceUrl($sell_return->id, $business_id);
                    return redirect()->to($url . '?print_on_load=true');
                }

                $msg = trans("sale.pos_sale_added");
                $receipt = '';
                $invoice_layout_id = $request->input('invoice_layout_id');
                $print_invoice = true;
                // if (!$is_direct_sale) {
                //     if ($input['status'] == 'draft') {
                //         $msg = trans("sale.draft_added");

                //         if ($input['is_quotation'] == 1) {
                //             $msg = trans("lang_v1.quotation_added");
                //             $print_invoice = true;
                //         }
                //     } elseif ($input['status'] == 'final') {
                //         $print_invoice = true;
                //     }
                // }

                if ($sell_return->is_suspend == 1 && empty($pos_settings['print_on_suspend'])) {
                    $print_invoice = false;
                }
                // dd("hit");

                if (!auth()->user()->can("print_invoice")) {
                    $print_invoice = true;
                }
                
                // if ($print_invoice) {
                //     $receipt = $this->receiptContent($business_id, $input['location_id'], $sell_return->id, null, false, true, $invoice_layout_id);
                // }
                // dd("hehhe");
        return redirect()
        ->action('SellReturnController@index');
                // $output = ['success' => 1, 'msg' => $msg, 'receipt' => $receipt ];

                if (!empty($whatsapp_link)) {
                    $output['whatsapp_link'] = $whatsapp_link;
                }


            } else {
                $output = ['success' => 0,
                            'msg' => trans("messages.something_went_wrong")
                        ];
            }
        } catch (\Exception $e) {
            DB::rollBack();

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            } else {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                $msg = __('messages.something_went_wrong');
            }

            $output = ['success' => 0,
                            'msg' => $msg
                        ];
        }
        // dd("done");
        // return redirect()
        // ->action('SellReturnController@index')
        // ->with('status', $output);

        return $output;
    }



    private function receiptContent(
        $business_id,
        $location_id,
        $transaction_id,
        $printer_type = null
    ) {
        $output = ['is_enabled' => false,
                    'print_type' => 'browser',
                    'html_content' => null,
                    'printer_config' => [],
                    'data' => []
                ];

        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::find($location_id);

        //Check if printing of invoice is enabled or not.
        // dd($location_details);
        if ($location_details->print_receipt_on_invoice == 1) {
            //If enabled, get print type.
            $output['is_enabled'] = true;

            $invoice_layout = $this->businessUtil->invoiceLayout($business_id, $location_id, $location_details->invoice_layout_id);
// dd($invoice_layout);
            //Check if printer setting is provided.
            $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;
// dd("ok");
            // $receipt_details = $this->transactionUtil->getReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type);
            // dd($receipt_details);
            // dd("ok");
            //If print type browser - return the content, printer - return printer config data, and invoice format config
            if ($receipt_printer_type == 'printer') {
                $output['print_type'] = 'printer';
                $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
                // $output['data'] = $receipt_details;
            } else {
                $output['html_content'] = view('sell_return.receipt', compact('receipt_details'))->render();
            }
        }
        return $output;
    }

}
