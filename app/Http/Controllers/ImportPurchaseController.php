<?php

namespace App\Http\Controllers;

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

use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;

use App\Variation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Activitylog\Models\Activity;

use App\Brands;
use App\Category;
use App\Unit;
use App\VariationValueTemplate;
use Excel;
use Carbon\Carbon;


class ImportPurchaseController extends Controller
{

    protected $productUtil;
    protected $transactionUtil;
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;

        $this->dummyPaymentLine = ['method' => 'cash', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
        'is_return' => 0, 'transaction_no' => ''];
    }


    public function index()
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        $zip_loaded = extension_loaded('zip') ? true : false;

        //Check if zip extension it loaded or not.
        if ($zip_loaded === false) {
            $output = ['success' => 0,
                            'msg' => 'Please install/enable PHP Zip archive for import'
                        ];

        $business_locations = BusinessLocation::fortransferDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];
            return view('import_purchase.index')
                ->with(compact('taxes', 'orderStatuses', 'business_locations', 'currency_details', 'default_purchase_status', 'customer_groups', 'types', 'shortcuts', 'payment_line', 'payment_types', 'accounts', 'bl_attributes'),'notification', $output);
        } else {

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
            $business_locations = BusinessLocation::fortransferDropdown($business_id, false, true);
            $bl_attributes = $business_locations['attributes'];
            $business_locations = $business_locations['locations'];
    
            $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);
    
            $default_purchase_status = null;

    
            $business_details = $this->businessUtil->getDetails($business_id);
            $shortcuts = json_decode($business_details->keyboard_shortcuts, true);
    
            $payment_line = $this->dummyPaymentLine;
            $payment_types = $this->productUtil->payment_types(null, true);
    
            //Accounts
            $accounts = $this->moduleUtil->accountsDropdown($business_id, true);

            $suppliers = DB::table('contacts')->select('*')->where('type', 'supplier')->get();

                return view('import_purchase.index')
            ->with(compact('suppliers','taxes', 'orderStatuses', 'business_locations', 'currency_details', 'default_purchase_status', 'shortcuts', 'payment_line', 'payment_types', 'accounts', 'bl_attributes'),'notification');
        }
    }


    public function store(Request $request)
    {
        // dd($request);
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }
        
        try {
            $notAllowed = $this->productUtil->notAllowedInDemo();
            if (!empty($notAllowed)) {
                return $notAllowed;
            }
            
            //Set maximum php execution time
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            
            if ($request->hasFile('purchase_csv')) {
                $file = $request->file('purchase_csv');
                
                $parsed_array = Excel::toArray([], $file);
                //Remove header row
                $imported_data = array_splice($parsed_array[0], 1);
                $business_id = $request->session()->get('user.business_id');
                $user_id = $request->session()->get('user.id');
                $default_profit_percent = $request->session()->get('business.default_profit_percent');

                $formated_data = [];

                $is_valid = true;
                $error_msg = '';

                $total_rows = count($imported_data);

                //Check if subscribed or not, then check for products quota
                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse();
                } elseif (!$this->moduleUtil->isQuotaAvailable('products', $business_id, $total_rows)) {
                    return $this->moduleUtil->quotaExpiredResponse('products', $business_id, action('ImportProductsController@index'));
                }
                
                
                $business_locations = BusinessLocation::where('business_id', $business_id)->get();
                DB::beginTransaction();

                $total_before_tax_sum = 0;
                $final_total_sum = 0;

                $transaction = new Transaction();

                    $transaction->business_id  = $business_id;
                    $transaction->location_id = $request->location_id;
                    $transaction->type = !empty($type) ? $type : null ;
                    $transaction->status = !empty($status) ? $status : 0;
                    $transaction->payment_status = !empty($payment_status) ? $payment_status : null;
                    $transaction->contact_id = $request->supplier_id;
                    $transaction->transaction_date = !empty($transaction_date) ? $transaction_date : 0;
                    $transaction->total_before_tax = !empty($total_before_tax_sum) ? $total_before_tax_sum : 0;
                    $transaction->tax_amount = !empty($tax_amount) ? $tax_amount : 0;
                    $transaction->discount_type = !empty($discount_type) ? $discount_type : null;
                    $transaction->final_total  = !empty($final_total_sum) ? $final_total_sum : 0;
                    $transaction->created_by  = !empty($user_id) ? $user_id : null;
                    $transaction->ref_no  = !empty($ref_no) ? $ref_no : null;
                    $transaction->exchange_rate = 1;

                    $transaction->save();

                    $product_array = [];

                    foreach ($imported_data as $key => $value) {
                        //Check if any column is missing
                        if (count($value) < 4) {
                            $is_valid =  false;
                            $error_msg = "Some of the columns are missing. Please, use latest CSV file template.";
                            break;
                        }
                        
                        $row_no = $key + 1;
                        $product_array['business_id'] = $business_id;
                        $product_array['created_by'] = $user_id;
                        
                        //Add sku
                        $product_sku = trim($value[0]);
                        if (!empty($product_sku)) {
                            $product_array['sku'] = $product_sku;
                        } else {
                            $is_valid =  false;
                            $error_msg = "Product name is required in row no. $row_no";
                            break;
                        }

                        $products = DB::table('products')
                        ->leftJoin('variations','products.id', '=', 'variations.product_id')
                        ->select('products.id AS product_id','products.name AS product_sku', 'products.unit_id AS product_unit_id','products.sub_unit_ids AS sub_unit_id','variations.product_variation_id AS variation_id','variations.default_purchase_price AS pp_without_discount','variations.profit_percent AS profit_percent','variations.default_sell_price','variations.dpp_inc_tax AS dpp_inc_tax','products.tax AS tax_id')
                        ->where('products.sku', $product_sku)
                        ->first();
                        // dd($products);

                        if (!$products) {
                            $is_valid =  false;
                            $error_msg = "Product $product_sku not found in the system in row no. $row_no";
                            break;
                        }
                        
                        $tax_percentage = DB::table('tax_rates')
                        ->where('id', $products->tax_id)
                        ->select('amount')
                        ->first();

                        $tax =  ($tax_percentage->amount/100) + 1;

                        $purchase_price_from_excel_inc_tax = trim($value[2]);

                        $purchase_price_without_tax = $purchase_price_from_excel_inc_tax/$tax;
                        $amount_of_tax = $purchase_price_from_excel_inc_tax - $purchase_price_without_tax;

                        $selling_price_from_excel_inc_tax = trim($value[3]);

                        $selling_price_without_tax = $selling_price_from_excel_inc_tax/$tax;
                        /*
                        profit margin percent formula:

                            [(sell_price - purchase_price)/purchase_price]*100 

                        */
                        $profit_margin_percent = ($selling_price_from_excel_inc_tax - $purchase_price_from_excel_inc_tax)/$purchase_price_from_excel_inc_tax * 100;

                        
                        if (!empty($products)) {
                            $product_array['product_id'] = $products->product_id;
                        } else {
                            $is_valid =  false;
                            $error_msg = "No matching product is found. $row_no";
                            break;
                        }
                        if (!empty($products)) {
                            $product_array['variation_id'] = $products->variation_id;
                            $product_array['product_unit_id'] = $products->product_unit_id;
                            $product_array['sub_unit_id'] = $products->product_unit_id;
                            $product_array['pp_without_discount'] = $purchase_price_without_tax;
                            $product_array['discount_percent'] = 0;
                            $product_array['purchase_price'] = $purchase_price_without_tax;
                            $product_array['purchase_line_tax_id'] = $products->tax_id;
                            $product_array['item_tax'] = $amount_of_tax;
                            $product_array['purchase_price_inc_tax'] = $purchase_price_from_excel_inc_tax;
                            $product_array['profit_percent'] = $profit_margin_percent;
                            $product_array['default_sell_price'] = $selling_price_without_tax;
                            $product_array['sell_price_inc_tax'] = $selling_price_from_excel_inc_tax;
                            $product_array['rows_count'] = $total_rows;

                        } else {
                            $is_valid =  false;
                            $error_msg = "No matching product is found. $row_no";
                            // dd("first");
                            break;
                        }
                        $purchase_quantity = trim($value[1]);
                        
                        if (!empty($purchase_quantity)) {
                            $product_array['quantity'] = $purchase_quantity;
                        } else {
                            $is_valid =  false;
                            $error_msg = "No matching product is found. $row_no";
                            break;
                        }
                        $all_product_data[] = $product_array;
                        // dd($all_product_data);
                        
                        $unit_cost = trim($value[2]);
                        $unit_selling_price = trim($value[3]);
                        $exchange_rate = 1;
                        $discount_type = 'percentage';
                        $discount_amount = 0;
                        $tax_amount = 0;
                        $shipping_charges = 0;
                        $final_total = $unit_cost*$purchase_quantity;
                        $type = 'purchase';
                        $payment_status = 'due';
                        $transaction_date = Carbon::now();
                        $ref_no = '';
                        $status = 'received';
                        $total_before_tax_sum += $unit_cost;
                        $final_total_sum += $final_total;
                        
                        $user_id = $request->session()->get('user.id');
                        $enable_product_editing = $request->session()->get('business.enable_editing_product_from_purchase');
                        
                        $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);
                        
                        //unformat input values
                        $unit_cost = $this->productUtil->num_uf($unit_cost, $currency_details)*$exchange_rate;
                    
                    }

                    if (!$is_valid) {
                        throw new \Exception($error_msg);
                    }

                    //Update reference count
                    $ref_count = $this->productUtil->setAndGetReferenceCount($type);
                    //Generate reference number
                    if (empty($ref_no)) {
                        $ref_no = $this->productUtil->generateReferenceNumber($type, $ref_count);
                    }

                    $transaction_id = $transaction->id;
                    $transaction = DB::table('transactions')
                    ->where('id', $transaction->id)
                    ->update(['business_id' => $business_id,
                            'type' => $type,
                            'status' => $status,
                            'payment_status' => $payment_status,
                            'transaction_date' => $transaction_date,
                            'total_before_tax' => $final_total_sum,
                            'tax_amount' => $tax_amount,
                            'discount_type' => $discount_type,
                            'final_total' => $final_total_sum,
                            'created_by' => $user_id,
                            'ref_no' => $ref_no
                    ]);
                    if ($transaction > 0) {
                        // Fetch the updated record
                        $transaction = Transaction::find($transaction_id);  
                    }
                    // dd($transaction, $all_product_data);
                    $this->productUtil->createOrUpdatePurchaseLinesForImport($transaction, $all_product_data, $currency_details, $enable_product_editing, $total_rows);
                    // //Adjust stock over selling if found
                    $this->productUtil->adjustStockOverSelling($transaction);

                    $this->transactionUtil->activityLog($transaction, 'added');
                    
                    DB::commit();
                    
                    $output = ['success' => 1,
                                    'msg' => __('purchase.purchase_add_success')
                                ];
                }
            
            $output = ['success' => 1,
                            'msg' => __('purchase.file_imported_successfully')
                        ];

            DB::commit();
        }
         catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                            'msg' => $e->getMessage()
                        ];
            return redirect('import-purchase')->with('notification', $output);
        }

        return redirect('import-purchase')->with('status', $output);
    }


}
