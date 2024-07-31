<?php

namespace App\Http\Controllers;

use App\Account;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\CustomerGroup;
use App\InvoiceScheme;
use App\SellingPriceGroup;
use App\TaxRate;
use App\Transaction;
use App\TransactionSellLine;
use App\TypesOfService;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Warranty;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Product;
use App\Media;
use Spatie\Activitylog\Models\Activity;


class GiftController extends Controller
{

    protected $contactUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $productUtil;


    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ContactUtil $contactUtil, BusinessUtil $businessUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil)
    {
        $this->contactUtil = $contactUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;

        $this->dummyPaymentLine = [
            'method' => '', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
            'is_return' => 0, 'transaction_no' => ''
        ];

        $this->shipping_status_colors = [
            'ordered' => 'bg-yellow',
            'packed' => 'bg-info',
            'shipped' => 'bg-navy',
            'delivered' => 'bg-green',
            'cancelled' => 'bg-red',
        ];
    }

    public function index(Request $request)
    {
        // dd($request->input('end_date'));
        if (!auth()->user()->can('view_gifts')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            // dd($request->input('location_id'));
            // dd($request->input('start_date'), $request->input('end_date'));
            $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')

                ->join(
                    'business_locations AS bl',
                    'transactions.location_id',
                    '=',
                    'bl.id'
                )
                ->leftJoin('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
                ->leftJoin(
                    'transactions AS SR',
                    'transactions.id',
                    '=',
                    'SR.return_parent_id'
                )
                // ->join(
                //     'transactions as T1',
                //     'transactions.return_parent_id',
                //     '=',
                //     'T1.id'
                // )
                // ->leftJoin(
                //     'transaction_payments AS TP',
                //     'transactions.id',
                //     '=',
                //     'TP.transaction_id'
                // )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'gift')
                ->where('transactions.status', 'final')
                ->select(
                    'transactions.id',
                    'transactions.transaction_date',
                    'transactions.invoice_no',
                    'contacts.name',
                    'transactions.final_total',
                    'transactions.payment_status',
                    'bl.name as business_location',
                    'transaction_sell_lines.quantity',
                    DB::raw('COUNT(SR.id) as return_exists')
                    // 'T1.invoice_no as parent_sale',
                    // 'T1.id as parent_sale_id',
                    // DB::raw('SUM(TP.amount) as amount_paid')
                )
                ->groupBy('transactions.id');


            $customer_id = $request->input('customer_id');

            if (!empty($customer_id)) {
                // dd("hehe");
                $sells->where('contacts.id', $customer_id);
            }

            $created_by = $request->input('created_by');

            if ($created_by) {
                $sells->where('transactions.created_by', $created_by);
            }

            if (!empty($location_id)) {
                $sells->where('transactions.location_id', $location_id);
            }
            if (!empty($request->input('start_date')) && !empty($request->input('end_date')) && $request->input('start_date') != $request->input('end_date')) {
                // dd("hehe");
                $sells->whereDate('transactions.transaction_date', '>=', $request->input('start_date'))
                    ->whereDate('transactions.transaction_date', '<=', $request->input('end_date'));
            }
            if (!empty($request->input('start_date')) && !empty($request->input('end_date')) && $request->input('start_date') == $request->input('end_date')) {
                $sells->whereDate('transactions.transaction_date', $request->input('end_date'));
            }

            // $permitted_locations = auth()->user()->permitted_locations();
            // if ($permitted_locations != 'all') {
            //     $sells->whereIn('transactions.location_id', $permitted_locations);
            // }

            // //Add condition for created_by,used in sales representative sales report
            // if (request()->has('created_by')) {
            //     $created_by = request()->get('created_by');
            //     if (!empty($created_by)) {
            //         $sells->where('transactions.created_by', $created_by);
            //     }
            // }

            // //Add condition for location,used in sales representative expense report
            // if (request()->has('location_id')) {
            //     $location_id = request()->get('location_id');
            //     if (!empty($location_id)) {
            //         $sells->where('transactions.location_id', $location_id);
            //     }
            // }

            // if (!empty(request()->customer_id)) {
            //     $customer_id = request()->customer_id;
            //     $sells->where('contacts.id', $customer_id);
            // }
            // if (!empty(request()->start_date) && !empty(request()->end_date)) {
            //     $start = request()->start_date;
            //     $end =  request()->end_date;
            //     // dd($start,$end);
            //     $sells->whereDate('transactions.transaction_date', '>=', $start)
            //             ->whereDate('transactions.transaction_date', '<=', $end);
            // }

            // // dd($sells);
            // $sells->groupBy('transactions.id');

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
                        <li><a href="#" class="btn-modal" data-container=".view_modal" data-href="{{action(\'SellReturnController@showGiftReceipt\', [$id])}}"><i class="fas fa-eye" aria-hidden="true"></i> @lang("messages.view")</a></li>
                        <li><a href="#" class="print-invoice" data-href="{{action(\'GiftController@printInvoice\', [$id])}}"><i class="fa fa-print" aria-hidden="true"></i> @lang("messages.print")</a></li>

                    @if($payment_status != "paid")
                        <li><a href="{{action(\'TransactionPaymentController@addPayment\', [$id])}}" class="add_payment_modal"><i class="fas fa-money-bill-alt"></i> @lang("purchase.add_payment")</a></li>
                    @endif

                    </ul>
                    </div>'
                )
                // <li><a href="{{action(\'TransactionPaymentController@show\', [$id])}}" class="view_payment_modal"><i class="fas fa-money-bill-alt"></i> @lang("purchase.view_payments")</a></li>

                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final_total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'
                )
                ->editColumn('quantity', function ($row) {
                    if (!empty($row->return_exists)) {
                        $row->quantity .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.some_qty_returned_from_sell') . '"><i class="fas fa-undo"></i></small>';
                    }
                    return '<button type="button" class="btn btn-link btn-modal" data-container=".view_modal" data-href="' . '">' . intval($row->quantity) . '</button>';
                })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn(
                    'payment_status',
                    '<a href="{{ action("TransactionPaymentController@show", [$id])}}" class="view_payment_modal payment-status payment-status-label" data-orig-value="{{$payment_status}}" data-status-name="{{__(\'lang_v1.\' . $payment_status)}}"><span class="label @payment_status($payment_status)">{{__(\'lang_v1.\' . $payment_status)}}</span></a>'
                )
                // ->addColumn('payment_due', function ($row) {
                //     $due = $row->final_total - $row->amount_paid;
                //     return '<span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="' . $due . '">' . $due . '</sapn>';
                // })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("view_gifts")) {
                            // dd($row);
                            return  action('SellReturnController@showGiftReceipt', [$row->id]);
                        } else {
                            return '';
                        }
                    }
                ])
                ->rawColumns(['final_total', 'action', 'quantity', 'payment_status'])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        $sales_representative = User::forDropdown($business_id, false, false, true);

        return view('sell.gift_index')->with(compact('business_locations', 'customers', 'sales_representative'));
    }

    public function create()
    {
        if (!auth()->user()->can('add_gifts')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not, then check for users quota
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        } elseif (!$this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action('SellController@index'));
        }

        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);

        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        $default_location = null;
        foreach ($business_locations as $id => $name) {
            $default_location = BusinessLocation::findOrFail($id);
            break;
        }

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdownActive($business_id);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdownActive($business_id);
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

        $payment_line = $this->dummyPaymentLine;
        $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);

        //Selling Price Group Dropdown
        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $default_datetime = $this->businessUtil->format_date('now', true);

        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $invoice_schemes = InvoiceScheme::forDropdown($business_id);
        $default_invoice_schemes = InvoiceScheme::getDefault($business_id);
        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        //Types of service
        $types_of_service = [];
        if ($this->moduleUtil->isModuleEnabled('types_of_service')) {
            $types_of_service = TypesOfService::forDropdown($business_id);
        }

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }

        $status = request()->get('status', '');

        return view('sell.gift_create')
            ->with(compact(
                'business_details',
                'taxes',
                'walk_in_customer',
                'business_locations',
                'bl_attributes',
                'default_location',
                'commission_agent',
                'types',
                'customer_groups',
                'payment_line',
                'payment_types',
                'price_groups',
                'default_datetime',
                'pos_settings',
                'invoice_schemes',
                'default_invoice_schemes',
                'types_of_service',
                'accounts',
                'shipping_statuses',
                'status'
            ));
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

                $transaction = Transaction::where('business_id', $business_id)
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
                $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction_id, $printer_type, $is_package_slip, false, $invoice_layout_id);

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
        if (!auth()->user()->can("print_invoice")) {
            abort(403, 'Unauthorized action.');
        }

        $output = [
            'is_enabled' => false,
            'print_type' => 'browser',
            'html_content' => null,
            'printer_config' => [],
            'data' => []
        ];


        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::find($location_id);

        if ($from_pos_screen && $location_details->print_receipt_on_invoice != 1) {
            return $output;
        }
        //Check if printing of invoice is enabled or not.
        //If enabled, get print type.
        $output['is_enabled'] = true;

        $invoice_layout_id = !empty($invoice_layout_id) ? $invoice_layout_id : $location_details->invoice_layout_id;
        $invoice_layout = $this->businessUtil->invoiceLayout($business_id, $location_id, $invoice_layout_id);
        // dd($invoice_layout);

        //Check if printer setting is provided.
        $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;

        $receipt_details = $this->transactionUtil->getReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type);
        // dd($receipt_details);
        $currency_details = [
            'symbol' => $business_details->currency_symbol,
            'thousand_separator' => $business_details->thousand_separator,
            'decimal_separator' => $business_details->decimal_separator,
        ];
        $receipt_details->currency = $currency_details;

        if ($is_package_slip) {
            $output['html_content'] = view('sale_pos.receipts.packing_slip', compact('receipt_details'))->render();
            return $output;
        }
        //If print type browser - return the content, printer - return printer config data, and invoice format config
        if ($receipt_printer_type == 'printer') {
            $output['print_type'] = 'printer';
            $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
            $output['data'] = $receipt_details;
        } else {
            $layout = !empty($receipt_details->design) ? 'sale_pos.receipts.' . $receipt_details->design : 'sale_pos.receipts.classic';

            $output['html_content'] = view($layout, compact('receipt_details'))->render();
        }

        return $output;
    }
}
