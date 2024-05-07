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
use Spatie\Permission\Models\Role;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\CashRegisterUtil;
use App\Variation;
use App\ProductVariation;
use App\TransactionSellLine;
use App\TransactionSellLinesPurchaseLines;
use App\VariationLocationDetails;
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
        $this->shipping_status_colors = [
            'ordered' => 'bg-yellow',
            'packed' => 'bg-info',
            'shipped' => 'bg-navy',
            'delivered' => 'bg-green',
            'cancelled' => 'bg-red',
        ];

        $this->dummyPaymentLine = ['method' => 'cash', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
        'is_return' => 0, 'transaction_no' => ''];
    }


    public function index()
    {
        if (!auth()->user()->can('access_sell_return')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);

            $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                    ->leftJoin('transaction_sell_lines as tsl', function($join) {
                        $join->on('transactions.id', '=', 'tsl.transaction_id')
                            ->whereNull('tsl.parent_sell_line_id');
                    })
                    ->join(
                        'business_locations AS bl',
                        'transactions.location_id',
                        '=',
                        'bl.id'
                    )
                    // ->join(
                    //     'transactions as T1',
                    //     'transactions.return_parent_id',
                    //     '=',
                    //     'T1.id'
                    // )
                    ->leftJoin(
                        'transaction_payments AS TP',
                        'transactions.id',
                        '=',
                        'TP.transaction_id'
                    )
                    ->where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'international_return')
                    ->where('transactions.status', 'final')
                    // ->select('transactions.*')
                    ->select(
                        'transactions.id',
                        'transactions.transaction_date',
                        'transactions.invoice_no',
                        'contacts.name',
                        'transactions.final_total',
                        // DB::raw('(SELECT SUM(IF(TP.is_return = 1,-1*TP.amount,TP.amount)) FROM transaction_payments AS TP WHERE
                        // TP.transaction_id=transactions.id) as final_total'),
                        'transactions.payment_status',
                        'bl.name as business_location',
                        // 'T1.invoice_no as parent_sale',
                        // 'T1.id as parent_sale_id',
                        DB::raw('SUM(TP.amount) as amount_paid'),
                        DB::raw("SUM(
                            IF(
                                transactions.type = 'international_return' AND transactions.status = 'final' AND tsl.line_discount_amount > 0,
                                IF(
                                    tsl.line_discount_type = 'percentage',
                                    COALESCE((COALESCE(tsl.unit_price_inc_tax, 0) / (1 - (COALESCE(tsl.line_discount_amount, 0) / 100)) - tsl.unit_price_inc_tax ), 0),
                                    COALESCE(tsl.line_discount_amount, 0)
                                ),
                                0
                            )
                        ) as total_sell_discount"),
                            );
                    // ->get();
                    // dd($sells);

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            if (request()->has('created_by')) {
                $created_by = request()->get('created_by');
                if (!empty($created_by)) {
                    $sells->where('transactions.created_by', $created_by);
                }
            }

            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                        ->whereDate('transactions.transaction_date', '<=', $end);
            }

            $sells->groupBy('transactions.id');
            // dd($sells);

            return Datatables::of($sells)
                ->addColumn(
                    'action',
                    '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                        data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right" role="menu">
                        <li><a href="#" class="print-invoice" data-href="{{action(\'InternationalExchangeController@printInvoice\', [$id])}}"><i class="fa fa-print" aria-hidden="true"></i> @lang("messages.print")</a></li>
                    </ul>
                    </div>'
                )
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final_total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'
                )
                // ->editColumn('parent_sale', function ($row) {
                //     return '<button type="button" class="btn btn-link btn-modal" data-container=".view_modal" data-href="' . action('SellController@show', [$row->parent_sale_id]) . '">' . $row->parent_sale . '</button>';
                // })
                ->editColumn(
                    'discount_amount',
                    function ($row) {
                        $discount = !empty($row->total_sell_discount) ? $row->total_sell_discount : 0;
                        return '<span class="total-discount" data-orig-value="' . $discount . '">' . $this->transactionUtil->num_f($discount, true) . '</span>';
                    }
                )
                ->editColumn(
                    'original_amount',
                    function ($row) {
                        $original_amount = !empty($row->final_total) ? $row->final_total : 0;
                        return '<span class="total-original-amount" data-orig-value="' . $original_amount . '">' . $this->transactionUtil->num_f($original_amount, true) . '</span>';
                    }
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                // ->editColumn(
                //     'payment_status',
                //     '<a href="{{ action("TransactionPaymentController@show", [$id])}}" class="view_payment_modal payment-status payment-status-label" data-orig-value="{{$payment_status}}" data-status-name="{{__(\'lang_v1.\' . $payment_status)}}"><span class="label @payment_status($payment_status)">{{__(\'lang_v1.\' . $payment_status)}}</span></a>'
                // )
                ->editColumn(
                    'payment_status',
                    '<a href="{{ action("TransactionPaymentController@show", [$id])}}" class="view_payment_modal payment-status payment-status-label" data-orig-value="{{$payment_status}}" data-status-name="{{__(\'lang_v1.\' . $payment_status)}}"><span class="label @payment_status($payment_status)">{{__(\'lang_v1.\' . $payment_status)}}</span></a>'
                )
                ->addColumn('payment_due', function ($row) {
                    $due = $row->final_total - $row->amount_paid;
                    return '<span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="' . $due . '">' . $due . '</sapn>';
                })
                ->addColumn('payment_methods', function ($row) use ($payment_types) {
                    $methods = array_unique($row->payment_lines->pluck('method')->toArray());
                    $count = count($methods);
                    $payment_method = '';
                    if ($count == 1) {
                        $payment_method = $payment_types[$methods[0]];
                    } elseif ($count > 1) {
                        $payment_method = __('lang_v1.checkout_multi_pay');
                    }

                    $html = !empty($payment_method) ? '<span class="payment-method" data-orig-value="' . $payment_method . '" data-status-name="' . $payment_method . '">' . $payment_method . '</span>' : '';
                    
                    return $html;
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("sell.view")) {
                            return  action('InternationalExchangeController@showGiftReceipt', [$row->id]) ;
                        } else {
                            return '';
                        }
                    }])
                ->rawColumns(['final_total', 'payment_status', 'payment_due','discount_amount','original_amount','payment_methods','action'])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);
      
        $sales_representative = User::forDropdown($business_id, false, false, true);

        return view('international_exchange.index')->with(compact('business_locations', 'customers', 'sales_representative'));
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

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id, false);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id, false);
        }

        $roles = Role::where('name', 'like', '%employee%')->get();

        $usersCollection = collect();

        foreach ($roles as $role) {
            $usersWithRole = $role->users;

            foreach ($usersWithRole as $user) {
                $usersCollection[$user->id] = $user->first_name . ' ' . $user->last_name;
            }
        }

        return view('international_exchange.create')
        ->with(compact('usersCollection','commission_agent','taxes', 'orderStatuses', 'business_locations', 'currency_details', 'default_purchase_status', 'customer_groups', 'types', 'shortcuts', 'payment_line', 'payment_types', 'accounts', 'bl_attributes','business_details','pos_settings','payment_lines','change_return','default_location'));

    }

    public function store(Request $request) 
    {

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
            if (!empty($input['purchases'])) {
                $business_id = $request->session()->get('user.business_id');
                //Check if subscribed or not
                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse(action('SellReturnController@index'));
                }
        
                $user_id = $request->session()->get('user.id');

                DB::beginTransaction();
                
                $sell_return =  $this->transactionUtil->addSellReturnInternational($input, $business_id, $user_id);
                DB::commit();

                $output = ['success' => 1,
                            'msg' => __('lang_v1.success'),
                            // 'receipt' => $receipt
                        ];
            }

            foreach ($input['exchange_products'] as $key => $product) {

                $variationId = $product['variation_id'];
                
                // Fetch default_sell_price from the database based on $variationId
                $defaultSellPrice = Variation::where('id', $variationId)->value('default_sell_price');
                // dd($defaultSellPrice);
                
                // Fetch tax_id from the products table based on $product['product_id']
                $taxId = Product::where('id', $product['product_id'])->value('tax');
            
                // Calculate item_tax
                $sellPriceIncTax = (float) str_replace(',', '', $product['unit_price_inc_tax']); // Remove commas and convert to float
                $itemTax = $sellPriceIncTax - $defaultSellPrice;
                
                // Add the fetched default_sell_price, tax_id, and calculated item_tax to the sub-array
                $input['exchange_products'][$key]['default_sell_price'] = $defaultSellPrice;
                $input['exchange_products'][$key]['item_tax'] = $itemTax;
                $input['exchange_products'][$key]['tax_id'] = $taxId;
                $input['exchange_products'][$key]['sell_line_note'] = null;

            }
            
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

                //Set commission agent
                $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');
                if ($commsn_agnt_setting == 'logged_in_user') {
                    $input['commission_agent'] = $user_id;
                }

                if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                    $input['exchange_rate'] = 1;
                }

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


                foreach ($input['purchases'] as $key => $product) {
                    $taxId = Product::where('id', $product['product_id'])->value('tax');
                    $input['purchases'][$key]['tax_id'] = $taxId;
                    $input['purchases'][$key]['sell_line_note'] = null;    
                }
                $fbr_lines =   $this->transactionUtil->createOrUpdateSellLinesReturnNEW($sell_return, $input['exchange_products'],$input['purchases'] ,$sell_return->location_id);
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

                    //Add payments to Cash Register
                    if (!$sell_return->is_suspend && !empty($input['payment']) && !$is_credit_sale) {
                        $this->cashRegisterUtil->addSellPayments($sell_return, $input['payment']);
                    }
                    
                    //Update payment status
                    // $payment_status = $this->transactionUtil->updatePaymentStatus($sell_return->id, $sell_return->final_total);
                    $sell_return->payment_status = "paid";

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

                }
                // dd($fbr_lines);
                $selected_arrays = array_filter($fbr_lines, function ($line) {
                    return isset($line->sell_line_note) && $line->sell_line_note === "international_return";
                });
                // dd($selected_arrays);

                foreach ($selected_arrays as $array) {
                    $sell_line_id_of_returning_product = $array->id;
                    $product_id_of_returning_product = $array->product_id;
                    $variation_id = $array->variation_id;
                    $quantity_returned = $array->quantity_returned;
                    $quantity = $array->quantity_returned;
                    // Extract other attributes as needed
                }
                // dd($product_id_of_returning_product);
                $purchase_line_id = PurchaseLine::where('product_id', $product_id_of_returning_product)
                ->orderBy('id', 'desc')
                ->value('id');

                $purchase_line_qty_sold = PurchaseLine::where('product_id', $product_id_of_returning_product)
                    ->orderBy('id', 'desc')
                    ->select('quantity_sold', 'quantity')
                    ->first();

                if ($purchase_line_qty_sold && $purchase_line_qty_sold->quantity_sold > 0) {
                    $purchase_line_qty_sold->decrement('quantity_sold', $quantity_returned);
                }
                if ($purchase_line_qty_sold && $purchase_line_qty_sold->quantity_sold = 0) {
                    $purchase_line_qty_sold->increment('quantity', $quantity_returned);

                }
                // $purchase_line_qty_sold = PurchaseLine::where('product_id', $product_id_of_returning_product)
                // ->orderBy('id', 'desc')
                // ->select('quantity_sold')->first();
                // dd($purchase_line_qty_sold->quantity_sold > 0);

                // if($purchase_line_qty_sold->quantity_sold > 0){

                //     $purchase_line_qty_sold->quantity_sold->decrement('quantity_sold', $quantity_returned);

                // }
                // dd($purchase_line_id);

                $final_array = [];
                $final_array['sell_line_id'] = $sell_line_id_of_returning_product;
                $final_array['purchase_line_id'] = $purchase_line_id;
                $final_array['quantity'] = $quantity;
                $final_array['quantity_returned'] = $quantity_returned;

                $transactionSellLinesPurchaseLines = new TransactionSellLinesPurchaseLines();

                $transactionSellLinesPurchaseLines->sell_line_id = $final_array['sell_line_id'];
                $transactionSellLinesPurchaseLines->purchase_line_id = $final_array['purchase_line_id'];
                $transactionSellLinesPurchaseLines->quantity = $final_array['quantity'];
                $transactionSellLinesPurchaseLines->qty_returned = $final_array['quantity_returned'];
                
                $transactionSellLinesPurchaseLines->save();

                $variation_id_of_returning_product = Variation::where('product_id', $product_id_of_returning_product)
                ->orderBy('id', 'desc')
                ->value('id');
                // dd($variation_id_of_returning_product);

                $product_variation_id_of_returning_product = ProductVariation::where('product_id', $product_id_of_returning_product)
                ->orderBy('id', 'desc')
                ->value('id');

                $VLD_of_returning_product_id = VariationLocationDetails::
                where('product_id', $product_id_of_returning_product)
                ->where('location_id', $sell_return->location_id)
                ->where('product_variation_id', $product_variation_id_of_returning_product)
                ->orderBy('id', 'desc')
                ->value('id');
                $VLD_of_returning_product = VariationLocationDetails::find($VLD_of_returning_product_id);

                // dd($VLD_of_returning_product);
                $purchase_line_of_returning_product = PurchaseLine::find($product_id_of_returning_product);
                // dd($purchase_line_of_returning_product);


                $VLD_of_returning_product->increment('qty_available', $quantity);

                // dd($VLD_of_returning_product);
                // dd($product_variation_id_of_returning_product,$variation_id_of_returning_product);

                $this->transactionUtil->activityLog($sell_return, 'added');

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

                if (!auth()->user()->can("print_invoice")) {
                    $print_invoice = true;
                }
                
                // if ($print_invoice) {
                //     $receipt = $this->receiptContent($business_id, $input['location_id'], $sell_return->id, null, false, true, $invoice_layout_id);
                // }
        return redirect()
        ->action('InternationalExchangeController@index')
        ->with('status', $output);
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


    public function showGiftReceipt($id)
    {
        $business_id = request()->session()->get('user.business_id');
            $taxes = TaxRate::where('business_id', $business_id)
                                ->pluck('name', 'id');
            $query = Transaction::where('business_id', $business_id)
                        ->where('id', $id)
                        ->with(['contact', 'sell_lines' => function ($q) {
                            $q->whereNull('parent_sell_line_id');
                        },'sell_lines.product', 'sell_lines.product.unit', 'sell_lines.variations', 'sell_lines.variations.product_variation', 'payment_lines', 'sell_lines.modifiers', 'sell_lines.lot_details', 'tax', 'sell_lines.sub_unit', 'table', 'service_staff', 'sell_lines.service_staff', 'types_of_service', 'sell_lines.warranties', 'media']);
    
            if (!auth()->user()->can('sell.view') && !auth()->user()->can('direct_sell.access') && auth()->user()->can('view_own_sell_only')) {
                $query->where('transactions.created_by', request()->session()->get('user.id'));
            }
    
            $sellOrg = $query->firstOrFail();
    
            $activities = Activity::forSubject($sellOrg)
                ->with(['causer', 'subject'])
                ->latest()
                ->get();
    
            foreach ($sellOrg->sell_lines as $key => $value) {
                if (!empty($value->sub_unit_id)) {
                    $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $value);
                    $sellOrg->sell_lines[$key] = $formated_sell_line;
                }
            }
    
            $payment_types = $this->transactionUtil->payment_types($sellOrg->location_id, true);
            $order_taxes = [];
            if (!empty($sellOrg->tax)) {
                if ($sellOrg->tax->is_tax_group) {
                    $order_taxes = $this->transactionUtil->sumGroupTaxDetails($this->transactionUtil->groupTaxDetails($sellOrg->tax, $sellOrg->tax_amount));
                } else {
                    $order_taxes[$sellOrg->tax->name] = $sellOrg->tax_amount;
                }
            }
    
            $business_details = $this->businessUtil->getDetails($business_id);
            $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
            $shipping_statuses = $this->transactionUtil->shipping_statuses();
            $shipping_status_colors = $this->shipping_status_colors;
            $common_settings = session()->get('business.common_settings');
            $is_warranty_enabled = !empty($common_settings['enable_product_warranty']) ? true : false;
    
            $statuses = Transaction::getSellStatuses();


        if (!auth()->user()->can('access_sell_return')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $sell = Transaction::where('business_id', $business_id)
                                ->where('id', $id)
                                ->with(
                                    'contact',
                                    'return_parent',
                                    'tax',
                                    'sell_lines',
                                    'sell_lines.product',
                                    'sell_lines.variations',
                                    'sell_lines.sub_unit',
                                    'sell_lines.product',
                                    'sell_lines.product.unit',
                                    'location'
                                )
                                ->first();
                                // dd($sell->sell_lines);

        foreach ($sell->sell_lines as $key => $value) {
            if (!empty($value->sub_unit_id)) {
                $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $value);
                $sell->sell_lines[$key] = $formated_sell_line;
            }
        }

        $sell_taxes = [];
        if (!empty($sell->return_parent->tax)) {
            if ($sell->return_parent->tax->is_tax_group) {
                $sell_taxes = $this->transactionUtil->sumGroupTaxDetails($this->transactionUtil->groupTaxDetails($sell->return_parent->tax, $sell->return_parent->tax_amount));
            } else {
                $sell_taxes[$sell->return_parent->tax->name] = $sell->return_parent->tax_amount;
            }
        }

        $total_discount = 0;
        // if ($sell->return_parent->discount_type == 'fixed') {
        //     $total_discount = $sell->return_parent->discount_amount;
        // } 
        // elseif ($sell->return_parent->discount_type == 'percentage') {
        //     $discount_percent = $sell->return_parent->discount_amount;
        //     if ($discount_percent == 100) {
        //         $total_discount = $sell->return_parent->total_before_tax;
        //     } else {
        //         $total_after_discount = $sell->return_parent->final_total - $sell->return_parent->tax_amount;
        //         $total_before_discount = $total_after_discount * 100 / (100 - $discount_percent);
        //         $total_discount = $total_before_discount - $total_after_discount;
        //     }
        // }

        // $activities = Activity::forSubject($sell->return_parent)
        //     ->with(['causer', 'subject'])
        //     ->latest()
        //     ->get();

        // $sellId = $sell->id;

        // $returnTransaction = Transaction::where('return_parent_id', $sellId)->first();
        // if ($returnTransaction) {
        //     $returnTransactionId = $returnTransaction->id;

        //     $transactionSellLines = TransactionSellLine::where('transaction_id', $returnTransactionId)->first();

        // }


        return view('international_exchange.show')->with(compact('sellOrg', 'sell', 'sell_taxes', 'total_discount', 'activities'));
    }

    public function printInvoice(Request $request, $transaction_id)
    {
        // dd($transaction_id);
        if (request()->ajax()) {
            // dd($transaction_id);

            try {
                $output = ['success' => 0,
                        'msg' => trans("messages.something_went_wrong")
                        ];

                $business_id = $request->session()->get('user.business_id');
                // dd($business_id);
            
                $transaction = Transaction::where('business_id', $business_id)
                                ->where('id', $transaction_id)
                                ->first();
                                // dd($transaction);

                if (empty($transaction)) {
                    return $output;
                }
                // dd($output);

                $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction_id, 'browser');

                if (!empty($receipt)) {
                    $output = ['success' => 1, 'receipt' => $receipt];
                }
            } catch (\Exception $e) {
                $output = ['success' => 0,
                        'msg' => trans("messages.something_went_wrong")
                        ];
            }

            return $output;
        }
    }

}
