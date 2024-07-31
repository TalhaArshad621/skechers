<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Utils\ContactUtil;
use App\Utils\ProductUtil;
use App\Utils\BusinessUtil;
use App\Utils\TransactionUtil;
use App\Utils\CashRegisterUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Account;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\CustomerGroup;
use App\EcommercePayment;
use App\EcommerceSellLine;
use App\EcommerceTransaction;
use App\Events\EcommercePaymentAdded;
use App\InvoiceScheme;
use App\SellingPriceGroup;
use App\TaxRate;
use App\Transaction;
use App\TransactionSellLine;
use App\TypesOfService;
use App\User;
use App\Utils\SmsUtil;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Activitylog\Models\Activity;

class EcommerceController extends Controller
{

    /**
     * All Utils instance.
     *
     */
    protected $contactUtil;
    protected $productUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $cashRegisterUtil;
    protected $moduleUtil;
    protected $notificationUtil;
    protected $business_id;
    protected $smsUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(
        ContactUtil $contactUtil,
        ProductUtil $productUtil,
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        CashRegisterUtil $cashRegisterUtil,
        ModuleUtil $moduleUtil,
        NotificationUtil $notificationUtil,
        SmsUtil $smsUtil
    ) {
        $this->contactUtil = $contactUtil;
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
        $this->notificationUtil = $notificationUtil;
        $this->smsUtil = $smsUtil;
        $this->business_id = 4;
        $this->shipping_status_colors = [
            'ordered' => 'bg-yellow',
            'packed' => 'bg-info',
            'shipped' => 'bg-navy',
            'delivered' => 'bg-green',
            'cancelled' => 'bg-red',
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $is_admin = $this->businessUtil->is_admin(auth()->user());

        if (!$is_admin && !auth()->user()->hasAnyPermission(['view_ecommerce', 'edit_ecommerce'])) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $is_woocommerce = $this->moduleUtil->isModuleInstalled('Woocommerce');
        $is_tables_enabled = $this->transactionUtil->isModuleEnabled('tables');
        $is_service_staff_enabled = $this->transactionUtil->isModuleEnabled('service_staff');
        $is_types_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');
        $status = ["cancelled", "delivered", "ordered"];
        if (request()->ajax()) {

            $shipping_status = request()->get("shipping_status");

            $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);
            $with = [];
            $shipping_statuses = $this->transactionUtil->shipping_statuses();
            $sells = $this->transactionUtil->getEcommerceListSells($business_id);

            // $permitted_locations = auth()->user()->permitted_locations();
            // if ($permitted_locations != 'all') {
            //     $sells->whereIn('transactions.location_id', $permitted_locations);
            // }

            //Add condition for created_by,used in sales representative sales report
            if (request()->has('created_by')) {
                $created_by = request()->get('created_by');
                if (!empty($created_by)) {
                    $sells->where('ecommerce_transactions.created_by', $created_by);
                }
            }

            $partial_permissions = ['view_own_sell_only', 'view_commission_agent_sell', 'access_own_shipping', 'access_commission_agent_shipping'];
            if (!auth()->user()->can('direct_sell.access')) {
                $sells->where(function ($q) {
                    if (auth()->user()->hasAnyPermission(['view_own_sell_only', 'access_own_shipping'])) {
                        $q->where('ecommerce_transactions.created_by', request()->session()->get('user.id'));
                    }

                    //if user is commission agent display only assigned sells
                    if (auth()->user()->hasAnyPermission(['view_commission_agent_sell', 'access_commission_agent_shipping'])) {
                        $q->orWhere('ecommerce_transactions.commission_agent', request()->session()->get('user.id'));
                    }
                });
            }

            if (!empty($shipping_status) && $shipping_status) {
                $sells->where('ecommerce_transactions.shipping_status', $shipping_status);
            }

            if (!empty(request()->input('payment_status')) && request()->input('payment_status') != 'overdue') {
                $sells->where('ecommerce_transactions.payment_status', request()->input('payment_status'));
            } elseif (request()->input('payment_status') == 'overdue') {
                $sells->whereIn('ecommerce_transactions.payment_status', ['due', 'partial'])
                    ->whereNotNull('ecommerce_transactions.pay_term_number')
                    ->whereNotNull('ecommerce_transactions.pay_term_type')
                    ->whereRaw("IF(ecommerce_transactions.pay_term_type='days', DATE_ADD(ecommerce_transactions.transaction_date, INTERVAL ecommerce_transactions.pay_term_number DAY) < CURDATE(), DATE_ADD(ecommerce_transactions.transaction_date, INTERVAL ecommerce_transactions.pay_term_number MONTH) < CURDATE())");
            }

            //Add condition for location,used in sales representative expense report
            // if (request()->has('location_id')) {
            //     $location_id = request()->get('location_id');
            //     if (!empty($location_id)) {
            //         $sells->where('ecommerce_transactions.location_id', $location_id);
            //     }
            // }

            if (!empty(request()->input('rewards_only')) && request()->input('rewards_only') == true) {
                $sells->where(function ($q) {
                    $q->whereNotNull('ecommerce_transactions.rp_earned')
                        ->orWhere('ecommerce_transactions.rp_redeemed', '>', 0);
                });
            }

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $sells->whereDate('ecommerce_transactions.transaction_date', '>=', $start)
                    ->whereDate('ecommerce_transactions.transaction_date', '<=', $end);
            }

            //Check is_direct sell
            if (request()->has('is_direct_sale')) {
                $is_direct_sale = request()->is_direct_sale;
                if ($is_direct_sale == 0) {
                    $sells->where('ecommerce_transactions.is_direct_sale', 0);
                    $sells->whereNull('ecommerce_transactions.sub_type');
                }
            }

            //Add condition for commission_agent,used in sales representative sales with commission report
            if (request()->has('commission_agent')) {
                $commission_agent = request()->get('commission_agent');
                if (!empty($commission_agent)) {
                    $sells->where('ecommerce_transactions.commission_agent', $commission_agent);
                }
            }

            if ($is_woocommerce) {
                $sells->addSelect('ecommerce_transactions.woocommerce_order_id');
                if (request()->only_woocommerce_sells) {
                    $sells->whereNotNull('ecommerce_transactions.woocommerce_order_id');
                }
            }

            if (request()->only_subscriptions) {
                $sells->where(function ($q) {
                    $q->whereNotNull('ecommerce_transactions.recur_parent_id')
                        ->orWhere('ecommerce_transactions.is_recurring', 1);
                });
            }

            if (!empty(request()->list_for) && request()->list_for == 'service_staff_report') {
                $sells->whereNotNull('ecommerce_transactions.res_waiter_id');
            }

            if (!empty(request()->res_waiter_id)) {
                $sells->where('ecommerce_transactions.res_waiter_id', request()->res_waiter_id);
            }

            if (!empty(request()->input('sub_type'))) {
                $sells->where('ecommerce_transactions.sub_type', request()->input('sub_type'));
            }

            if (!empty(request()->input('created_by'))) {
                $sells->where('ecommerce_transactions.created_by', request()->input('created_by'));
            }

            if (!empty(request()->input('sales_cmsn_agnt'))) {
                $sells->where('ecommerce_transactions.commission_agent', request()->input('sales_cmsn_agnt'));
            }

            if (!empty(request()->input('service_staffs'))) {
                $sells->where('ecommerce_transactions.res_waiter_id', request()->input('service_staffs'));
            }
            $only_shipments = request()->only_shipments == 'true' ? true : false;
            if ($only_shipments) {
                $sells->whereNotNull('ecommerce_transactions.shipping_status');
            }

            if (!empty(request()->input('shipping_status'))) {
                $sells->where('ecommerce_transactions.shipping_status', request()->input('shipping_status'));
            }

            if (!empty(request()->input('shipping_status'))) {
                $sells->where('ecommerce_transactions.shipping_status', request()->input('shipping_status'));
            }

            $sells->where('ecommerce_transactions.sub_type', 'ecommerce');

            $sells->groupBy('ecommerce_transactions.id');

            if (!empty(request()->suspended)) {
                $transaction_sub_type = request()->get('transaction_sub_type');
                if (!empty($transaction_sub_type)) {
                    $sells->where('ecommerce_transactions.sub_type', $transaction_sub_type);
                } else {
                    $sells->where('ecommerce_transactions.sub_type', null);
                }

                $with = ['sell_lines'];

                if ($is_tables_enabled) {
                    $with[] = 'table';
                }

                if ($is_service_staff_enabled) {
                    $with[] = 'service_staff';
                }

                $sales = $sells->where('ecommerce_transactions.is_suspend', 1)
                    ->with($with)
                    ->addSelect('ecommerce_transactions.is_suspend', 'ecommerce_transactions.additional_notes')
                    ->get();

                return view('sale_pos.partials.suspended_sales_modal')->with(compact('sales', 'is_tables_enabled', 'is_service_staff_enabled', 'transaction_sub_type'));
            }
            $with[] = 'payment_lines';
            if (!empty($with)) {
                $sells->with($with);
            }

            //$business_details = $this->businessUtil->getDetails($business_id);
            if ($this->businessUtil->isModuleEnabled('subscription')) {
                $sells->addSelect('ecommerce_transactions.is_recurring', 'ecommerce_transactions.recur_parent_id');
            }
            $datatable = Datatables::of($sells)
                ->addColumn(
                    'action',
                    function ($row) use ($only_shipments, $is_admin) {
                        $html = '<div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                        data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                        if (auth()->user()->can("view_ecommerce")) {
                            $html .= '<li><a href="#" data-href="' . action("EcommerceController@show", [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-eye" aria-hidden="true"></i> ' . __("messages.view") . '</a></li>';
                        }
                        // if (!$only_shipments) {
                        //     if ($row->is_direct_sale == 0) {
                        //         if (auth()->user()->can("sell.update")) {
                        //             $html .= '<li><a target="_blank" href="' . action('SellPosController@edit', [$row->id]) . '"><i class="fas fa-edit"></i> ' . __("messages.edit") . '</a></li>';
                        //         }
                        //     } else {
                        //         if (auth()->user()->can("direct_sell.access")) {
                        //             $html .= '<li><a target="_blank" href="' . action('SellController@edit', [$row->id]) . '"><i class="fas fa-edit"></i> ' . __("messages.edit") . '</a></li>';
                        //         }
                        //     }

                        //     if (auth()->user()->can("direct_sell.delete") || auth()->user()->can("sell.delete")) {
                        //         $html .= '<li><a href="' . action('SellPosController@destroy', [$row->id]) . '" class="delete-sale"><i class="fas fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        //     }
                        // }
                        // if (auth()->user()->can("print_invoice")) {
                        //     $html .= '<li><a href="#" class="print-invoice" data-href="' . route('sell.printInvoice', [$row->id]) . '"><i class="fas fa-print" aria-hidden="true"></i> ' . __("lang_v1.print_invoice") . '</a></li>
                        //         <li><a href="#" class="print-invoice" data-href="' . route('sell.printInvoice', [$row->id]) . '?package_slip=true"><i class="fas fa-file-alt" aria-hidden="true"></i> ' . __("lang_v1.packing_slip") . '</a></li>';
                        // }
                        // if ($is_admin || auth()->user()->hasAnyPermission(['access_shipping', 'access_own_shipping', 'access_commission_agent_shipping']) ) {
                        //     $html .= '<li><a href="#" data-href="' . action('SellController@editShipping', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-truck" aria-hidden="true"></i>' . __("lang_v1.edit_shipping") . '</a></li>';
                        // }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="final-total" data-orig-value="{{$final_total}}">@format_currency($final_total)</span>'
                )
                ->addColumn('city', function ($row) {
                    return $row->contact->city;
                })

                ->addColumn('order_status', function ($row) {
                    return $row->contact->city;
                })
                ->editColumn(
                    'tax_amount',
                    '<span class="total-tax" data-orig-value="{{$tax_amount}}">@format_currency($tax_amount)</span>'
                )
                ->editColumn(
                    'total_paid',
                    '<span class="total-paid" data-orig-value="{{$total_paid}}">@format_currency($total_paid)</span>'
                )
                ->editColumn(
                    'total_before_tax',
                    '<span class="total_before_tax" data-orig-value="{{$total_before_tax}}">@format_currency($total_before_tax)</span>'
                )
                ->editColumn(
                    'discount_amount',
                    function ($row) {
                        $discount = !empty($row->discount_amount) ? $row->discount_amount : 0;

                        if (!empty($discount) && $row->discount_type == 'percentage') {
                            $discount = $row->total_before_tax * ($discount / 100);
                        }

                        return '<span class="total-discount" data-orig-value="' . $discount . '">' . $this->transactionUtil->num_f($discount, true) . '</span>';
                    }
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn(
                    'payment_status',
                    function ($row) {
                        $payment_status = Transaction::getPaymentStatus($row);
                        return (string) view('ecommerce.partials.payment_status', ['payment_status' => $payment_status, 'id' => $row->id]);
                    }
                )
                ->editColumn(
                    'types_of_service_name',
                    '<span class="service-type-label" data-orig-value="" data-status-name=""></span>'
                )
                ->addColumn('total_remaining', function ($row) {
                    $total_remaining =  $row->final_total - $row->total_paid;
                    $total_remaining_html = '<span class="payment_due" data-orig-value="' . $total_remaining . '">' . $this->transactionUtil->num_f($total_remaining, true) . '</span>';


                    return $total_remaining_html;
                })
                ->addColumn('return_due', function ($row) {
                    $return_due_html = '';
                    if (!empty($row->return_exists)) {
                        $return_due = $row->amount_return - $row->return_paid;
                        $return_due_html .= '<a href="' . action("TransactionPaymentController@show", [$row->return_transaction_id]) . '" class="view_purchase_return_payment_modal"><span class="sell_return_due" data-orig-value="' . $return_due . '">' . $this->transactionUtil->num_f($return_due, true) . '</span></a>';
                    }

                    return $return_due_html;
                })
                ->editColumn('invoice_no', function ($row) {
                    $invoice_no = $row->invoice_no;
                    if (!empty($row->woocommerce_order_id)) {
                        $invoice_no .= ' <i class="fab fa-wordpress text-primary no-print" title="' . __('lang_v1.synced_from_woocommerce') . '"></i>';
                    }
                    if (!empty($row->return_exists)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.some_qty_returned_from_sell') . '"><i class="fas fa-undo"></i></small>';
                    }
                    if (!empty($row->is_recurring)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.subscribed_invoice') . '"><i class="fas fa-recycle"></i></small>';
                    }

                    if (!empty($row->recur_parent_id)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-info label-round no-print" title="' . __('lang_v1.subscription_invoice') . '"><i class="fas fa-recycle"></i></small>';
                    }

                    return $invoice_no;
                })
                ->editColumn('shipping_status', function ($row) use ($shipping_statuses) {
                    $status_color = !empty($this->shipping_status_colors[$row->shipping_status]) ? $this->shipping_status_colors[$row->shipping_status] : 'bg-gray';
                    $status = !empty($row->shipping_status) && $row->shipping_status != "delivered"  ? '<a href="#" class="btn-modal" data-href="' . action('EcommerceController@editShipping', [$row->id]) . '" data-container=".view_modal"><span class="label ' . $status_color . '">' . $shipping_statuses[$row->shipping_status] . '</span></a>' : '<a href="#" class="btn-modal" ><span class="label ' . $status_color . '">' . $shipping_statuses[$row->shipping_status] . '</span></a>';

                    return $status;
                })
                ->addColumn('conatct_name', '@if(!empty($supplier_business_name)) {{$supplier_business_name}}, <br> @endif {{$name}}')
                ->editColumn('total_items', '{{@format_quantity($total_items)}}')
                ->filterColumn('conatct_name', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('contacts.name', 'like', "%{$keyword}%")
                            ->orWhere('contacts.supplier_business_name', 'like', "%{$keyword}%");
                    });
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
                        if (auth()->user()->can("view_ecommerce")) {
                            return  action('EcommerceController@show', [$row->id]);
                        } else {
                            return '';
                        }
                    }
                ]);

            $rawColumns = ['final_total', 'discount', 'action', 'total_paid', 'total_remaining', 'payment_status', 'invoice_no', 'discount_amount', 'tax_amount', 'total_before_tax', 'shipping_status', 'types_of_service_name', 'payment_methods', 'return_due', 'conatct_name'];

            return $datatable->rawColumns($rawColumns)
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);
        $sales_representative = User::forDropdown($business_id, false, false, true);

        //Commission agent filter
        $is_cmsn_agent_enabled = request()->session()->get('business.sales_cmsn_agnt');
        $commission_agents = [];
        if (!empty($is_cmsn_agent_enabled)) {
            $commission_agents = User::forDropdown($business_id, false, true, true);
        }

        //Service staff filter
        $service_staffs = null;
        if ($this->productUtil->isModuleEnabled('service_staff')) {
            $service_staffs = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $shipping_statuses = $this->transactionUtil->shipping_statuses();


        return view('ecommerce.index')->with(compact('status', 'business_locations', 'customers', 'is_woocommerce', 'sales_representative', 'is_cmsn_agent_enabled', 'commission_agents', 'service_staffs', 'is_tables_enabled', 'is_service_staff_enabled', 'is_types_service_enabled', 'shipping_statuses'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $url = "https://skechers-footwear.myshopify.com/admin/api/2022-04/orders.json";

            $client = new Client();
            $_token = env('SHOPIFY_ACCESS_KEY');
            $response = $client->request('GET', $url, [
                'headers' => [
                    'X-Shopify-Access-Token' => $_token,
                    'Content-Type' => 'application/json',
                ],
            ]);
            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody(), true);
            $shopifyOrders = $body['orders'];

            $input = [];
            $input['is_quotation'] = 0;
            $is_direct_sale = false;

            foreach ($shopifyOrders as $key => $shopifyOrder) {
                // Order number for the shopify order in DB it will be invoice number
                $orderId = trim($shopifyOrder['name'], "#");
                $input['invoice_no'] = $orderId;
                // Current date and time
                // Create Carbon instances
                $dateToCheck = '2024-06-28T02:50:53+05:00';
                $carbonDateToCheck = Carbon::parse($dateToCheck);

                $orderDate = Carbon::parse($shopifyOrder['created_at']);
                // dd($orderDate->greaterThan($carbonDateToCheck));
                // Check if the order already exist
                $existingTransactions = DB::table('ecommerce_transactions')->where('type', 'sell')->where('sub_type', 'ecommerce')->where('invoice_no', $orderId)->first();
                if (!$existingTransactions && $orderDate->greaterThan($carbonDateToCheck)) {
                    $business_id = $this->business_id;
                    $input['status'] = "final";
                    $input['discount_type'] = "fixed";
                    $input['discount_amount'] = $shopifyOrder['total_discounts'];
                    $input['is_credit_sale'] = 0;

                    // total_price = original_price - discount_amount;
                    $invoice_total = $shopifyOrder['total_price'];
                    $user_id = 4;

                    $input['total_before_tax'] = $shopifyOrder['total_line_items_price'];
                    $input['tax'] = $shopifyOrder['total_tax'];
                    $discount = [
                        'discount_type' => $input['discount_type'],
                        'discount_amount' => $input['discount_amount']
                    ];

                    DB::beginTransaction();
                    $input['transaction_date'] = Carbon::now();
                    $input['commission_agent'] = null;

                    // Customer Details
                    $contact_id = $this->contactUtil->getEcommerceCustomer($shopifyOrder['customer']['email']);
                    if (!$contact_id) {
                        $contact_id = $this->contactUtil->createNewEcommerceCustomer($shopifyOrder['customer'], $business_id);
                    }

                    $input['contact_id'] = $contact_id;
                    $input['cutomer_group_id'] = null;
                    $price_group_id = null;
                    $input['is_suspend'] = 0;
                    $input['sale_note'] = $shopifyOrder['note'];
                    $input['is_recurring'] = 0;
                    $input['shipping_details'] = $shopifyOrder['shipping_address']['address1'];
                    $input['shipping_address'] = $shopifyOrder['shipping_address']['address1'];
                    $input['shipping_status'] = "ordered";
                    $input['delivered_to'] = $shopifyOrder['shipping_address']['first_name'];
                    $input['order_addresses'] = $shopifyOrder['shipping_address']['address1'];
                    $input['is_created_from_api'] = 1;
                    $input['products'] = $shopifyOrder['line_items'];

                    $transaction = $this->transactionUtil->createEcommerceTransaction($business_id, $input, $invoice_total, $user_id);

                    $transactionSellLines = $this->transactionUtil->createEcommerceSellLines($transaction, $input['products'], $business_id);

                    if (!$transactionSellLines) {
                        DB::rollBack();
                        continue;
                    }

                    if (empty($shopifyOrder['payment_gateway_names'])) {
                        DB::rollBack();
                        continue;
                    }
                    // dd($transaction);
                    $is_credit_sale = false;
                    if (str_contains($shopifyOrder['payment_gateway_names'][0],  "(COD)")) {
                        $is_credit_sale = true;
                    }

                    if (!$is_credit_sale) {
                        $this->transactionUtil->createEcommercePaymentLine($transaction, $shopifyOrder, $user_id, $business_id);
                    }

                    //Add payments to Cash Register
                    // if(!$is_credit_sale) {
                    //     $this->cashRegisterUtil->addSellEcommercePayments($transaction, $shopifyOrder, $user_id);
                    // }

                    //Update payment status
                    $payment_status = $this->transactionUtil->updateEcommercePaymentStatus($transaction->id, $invoice_total);
                    $transaction->payment_status = $payment_status;


                    //Allocate the quantity from purchase and add mapping of
                    //purchase & sell lines in
                    //transaction_ecommerce_sell_lines_purchase_lines table
                    $business_details = $this->businessUtil->getDetails($business_id);
                    $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

                    $business = [
                        'id' => $business_id,
                        'accounting_method' => $request->session()->get('business.accounting_method'),
                        'location_id' => 8,
                        'pos_settings' => $pos_settings
                    ];

                    $messageText = "Order placed Successfully with Order ID: $transaction->invoice_no and Total Amount : $transaction->final_total \n 
                        on shoestreet.pk. Thankyou for shopping! \n";

                    // $phone = "03200412197";

                    // $this->smsUtil->sendSmsMessage($messageText, preg_replace('/^0/', '92', $transaction->contact->mobile), 'SKECHERS.', '');
                    // $this->smsUtil->sendSmsMessage($messageText, preg_replace('/^0/', '92', $phone), 'SKECHERS.', '');

                    //  dd($transaction->ecommerce_sell_lines);
                    $this->transactionUtil->mapPurchaseEcommerceSell($business, $transaction->ecommerce_sell_lines, 'purchase');
                    $this->transactionUtil->activityLog($transaction, 'added');
                }


                $msg = trans("sale.ecommerce_sale");

                $output = ['success' => 1, 'msg' => $msg];

                DB::commit();
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => $e->getMessage() . ' ' . $e->getLine()
            ];
        }

        return $output;
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // if (!auth()->user()->can('sell.view') && !auth()->user()->can('direct_sell.access') && !auth()->user()->can('view_own_sell_only')) {
        //     abort(403, 'Unauthorized action.');
        // }

        $business_id = request()->session()->get('user.business_id');
        $taxes = TaxRate::where('business_id', $business_id)
            ->pluck('name', 'id');
        $query = EcommerceTransaction::where('business_id', $business_id)
            ->where('id', $id)
            ->with(['contact', 'ecommerce_sell_lines' => function ($q) {
                $q->whereNull('parent_sell_line_id');
            }, 'ecommerce_sell_lines.product', 'ecommerce_sell_lines.product.unit', 'ecommerce_sell_lines.variations', 'ecommerce_sell_lines.variations.product_variation', 'payment_lines', 'ecommerce_sell_lines.modifiers', 'ecommerce_sell_lines.lot_details', 'tax', 'ecommerce_sell_lines.sub_unit', 'table', 'service_staff', 'ecommerce_sell_lines.service_staff', 'types_of_service', 'ecommerce_sell_lines.warranties', 'media', 'ecommerce_sell_lines.location']);

        if (!auth()->user()->can('sell.view') && !auth()->user()->can('direct_sell.access') && auth()->user()->can('view_own_sell_only')) {
            $query->where('ecommerce_transactions.created_by', request()->session()->get('user.id'));
        }

        $sell = $query->firstOrFail();

        $activities = Activity::forSubject($sell)
            ->with(['causer', 'subject'])
            ->latest()
            ->get();

        foreach ($sell->ecommerce_sell_lines as $key => $value) {
            if (!empty($value->sub_unit_id)) {
                $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $value);
                $sell->sell_lines[$key] = $formated_sell_line;
            }
        }

        $payment_types = $this->transactionUtil->payment_types($sell->location_id, true);
        $order_taxes = [];
        if (!empty($sell->tax)) {
            if ($sell->tax->is_tax_group) {
                $order_taxes = $this->transactionUtil->sumGroupTaxDetails($this->transactionUtil->groupTaxDetails($sell->tax, $sell->tax_amount));
            } else {
                $order_taxes[$sell->tax->name] = $sell->tax_amount;
            }
        }

        $business_details = $this->businessUtil->getDetails($business_id);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
        $shipping_statuses = $this->transactionUtil->shipping_statuses();
        $shipping_status_colors = $this->shipping_status_colors;
        $common_settings = session()->get('business.common_settings');
        $is_warranty_enabled = !empty($common_settings['enable_product_warranty']) ? true : false;

        $statuses = Transaction::getSellStatuses();
        return view('ecommerce.show')
            ->with(compact(
                'taxes',
                'sell',
                'payment_types',
                'order_taxes',
                'pos_settings',
                'shipping_statuses',
                'shipping_status_colors',
                'is_warranty_enabled',
                'activities',
                'statuses'
            ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    /**
     * Shows modal to edit shipping details.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editShipping($id)
    {
        $is_admin = $this->businessUtil->is_admin(auth()->user());

        if (!$is_admin && !auth()->user()->hasAnyPermission(['access_shipping', 'access_own_shipping', 'access_commission_agent_shipping'])) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $transaction = EcommerceTransaction::where('business_id', $business_id)
            ->with(['media', 'media.uploaded_by_user'])
            ->findorfail($id);
        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        return view('ecommerce.partials.edit_shipping')
            ->with(compact('transaction', 'shipping_statuses'));
    }

    /**
     * Update shipping.
     *
     * @param  Request $request, int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateShipping(Request $request, $id)
    {
        $is_admin = $this->businessUtil->is_admin(auth()->user());

        if (!$is_admin && !auth()->user()->hasAnyPermission(['access_shipping', 'access_own_shipping', 'access_commission_agent_shipping'])) {
            abort(403, 'Unauthorized action.');
        }

        try {
            db::beginTransaction();
            $input = $request->only([
                'shipping_details', 'shipping_address',
                'shipping_status', 'delivered_to', 'shipping_custom_field_1', 'shipping_custom_field_2', 'shipping_custom_field_3', 'shipping_custom_field_4', 'shipping_custom_field_5'
            ]);
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');


            $transaction = EcommerceTransaction::where('business_id', $business_id)
                ->findOrFail($id);

            $transaction_before = $transaction->replicate();

            $transaction->update($input);

            if ($request->shipping_status == "cancelled") {
                $ecommerce_return = $this->transactionUtil->addEcommerceCancelOrder($transaction, $business_id, $user_id);
            }

            if ($request->shipping_status == "delivered") {
                $dispatchResult =   $this->transactionUtil->dispatchEcommerceOrderTCS($transaction, $business_id, $user_id);
                if (!$dispatchResult) {
                    $output = [
                        'success' => 0,
                        'msg' => trans("messages.something_went_wrong")
                    ];
                    DB::rollBack();
                    return $output;
                }
            }

            $this->transactionUtil->activityLog($transaction, 'shipping_edited', $transaction_before);

            $output = [
                'success' => 1,
                'msg' => trans("lang_v1.updated_success")
            ];
            db::commit();
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            db::rollBack();
            $output = [
                'success' => 0,
                'msg' => trans("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function returnItem($id)
    {
        //  dd($id);
        if (!auth()->user()->can('access_sell_return')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = EcommerceSellLine::findorFail($id);
            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            DB::beginTransaction();
            // dd($input); 
            $sell_return =  $this->transactionUtil->addEcommerceSellReturn($input, $business_id, $user_id);
            // $receipt = $this->receiptContent($business_id, $input->location_id, $sell_return->id);
            $payment_details['total_price'] =  $sell_return->final_total;
            $payment_details['payment_gateway_names'] = [
                '0' => 'COD'
            ];
            // $this->transactionUtil->createEcommercePaymentLine($sell_return, $payment_details, $user_id, $business_id);
            if ($sell_return) {

                $inputs['paid_on'] = Carbon::now();
                $inputs['ecommerce_transaction_id'] = $sell_return->id;
                $inputs['amount'] = $this->transactionUtil->num_uf($sell_return->final_total);
                $inputs['created_by'] = auth()->user()->id;
                $inputs['payment_for'] = $sell_return->contact_id;
                $inputs['method'] = "cash";

                $prefix_type = 'purchase_payment';

                $prefix_type = 'sell_payment';



                $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                //Generate reference number
                $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

                $inputs['business_id'] = $business_id;

                //Pay from advance balance
                $payment_amount = $inputs['amount'];
                if (!empty($inputs['amount'])) {
                    $tp = EcommercePayment::create($inputs);

                    $inputs['transaction_type'] = $sell_return->type;
                    event(new EcommercePaymentAdded($tp, $inputs));
                }

                EcommerceTransaction::where('id', $sell_return->id)
                    ->update(['payment_status' => 'paid']);

                // dd($tp);

                // $this->transactionUtil->activityLog($sell_return, 'payment_edited', $transaction_before);

            }


            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.success'),
                'receipt' => $receipt
            ];
            // dd($output); 
        } catch (Exception $e) {
            DB::rollBack();

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            } else {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
                $msg = __('messages.something_went_wrong');
            }

            $output = [
                'success' => 0,
                'msg' => $msg
            ];
        }

        return Redirect::to('/ecommerce')->with('output');
    }


    public function printInvoice(Request $request, $transaction_id)
    {
        if (request()->ajax()) {
            try {
                $output = [
                    'success' => 0,
                    'msg' => trans("messages.something_went_wrong")
                ];

                $business_id = $request->session()->get('user.business_id');

                $transaction = EcommerceTransaction::where('business_id', $business_id)
                    ->where('id', $transaction_id)
                    ->with(['location'])
                    ->first();

                if (empty($transaction)) {
                    return $output;
                }

                $printer_type = 'browser';
                if (!empty(request()->input('check_location')) && request()->input('check_location') == true) {
                    $printer_type = $transaction->location->receipt_printer_type;
                }

                $is_package_slip = !empty($request->input('package_slip')) ? true : false;

                $invoice_layout_id = $transaction->is_direct_sale ? $transaction->location->sale_invoice_layout_id : null;
                $receipt = $this->receiptContent($business_id, 11, $transaction_id, $printer_type, $is_package_slip, false, $invoice_layout_id);

                if (!empty($receipt)) {
                    $output = ['success' => 1, 'receipt' => $receipt];
                }
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [
                    'success' => 0,
                    'msg' => trans("messages.something_went_wrong")
                ];
            }

            return $output;
        }
    }

    private function receiptContent(
        $business_id,
        $location_id,
        $transaction_id,
        $printer_type = null,
        $is_package_slip = false,
        $from_pos_screen = true,
        $invoice_layout_id = null
    ) {
        $output = [
            'is_enabled' => false,
            'print_type' => 'browser',
            'html_content' => null,
            'printer_config' => [],
            'data' => []
        ];

        $business_details = $this->businessUtil->getDetails($business_id);

        $location_details = BusinessLocation::find($location_id);
        //Check if printing of invoice is enabled or not.
        if ($location_details->print_receipt_on_invoice == 1) {
            //If enabled, get print type.
            $output['is_enabled'] = true;

            $invoice_layout = $this->businessUtil->invoiceLayout($business_id, $location_id, $location_details->invoice_layout_id);

            //Check if printer setting is provided.
            $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;

            $receipt_details = $this->transactionUtil->getEcommerceReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type);
            //If print type browser - return the content, printer - return printer config data, and invoice format config
            if ($is_package_slip) {
                $output['html_content'] = view('sale_pos.receipts.packing_slip', compact('receipt_details'))->render();
                return $output;
            }

            if ($receipt_printer_type == 'printer') {
                $output['print_type'] = 'printer';
                $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
                $output['data'] = $receipt_details;
            } else {
                $output['html_content'] = view('sale_pos.receipts.slim', compact('receipt_details'))->render();
            }
        }
        return $output;
    }
}
