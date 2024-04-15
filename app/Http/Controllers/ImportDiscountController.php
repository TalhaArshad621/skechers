<?php

namespace App\Http\Controllers;
use App\BusinessLocation;

use App\PurchaseLine;
use App\Transaction;
use App\TransactionSellLinesPurchaseLines;
use App\Utils\ModuleUtil;

use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Datatables;

use DB;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

use Excel;
use Carbon\Carbon;


class ImportDiscountController extends Controller
{
     /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $transactionUtil;
    protected $moduleUtil;

       /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->status_colors = [
            'in_transit' => 'bg-yellow',
            'completed' => 'bg-green',
            'pending' => 'bg-red',
        ];
    }

    public function create()
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('StockTransferController@index'));
        }

        $locations = BusinessLocation::forDropdown($business_id);

        return view('import_discount.create')
                ->with(compact('locations'));
    }


    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request);
        if (!auth()->user()->can('discount.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            $business_id = $request->session()->get('user.business_id');

            if ($request->hasFile('stock_transfer_csv')) {
                $file = $request->file('stock_transfer_csv');
                // dd("yes");
                
                $parsed_array = Excel::toArray([], $file);
                //Remove header row
                $imported_data = array_splice($parsed_array[0], 1);
                $business_id = $request->session()->get('user.business_id');
                $user_id = $request->session()->get('user.id');

                $formated_data = [];
                $is_valid = true;
                $error_msg = '';

                $total_rows = count($imported_data);
                // $business_locations = BusinessLocation::where('business_id', $business_id)->get();

            }
            $product_array = [];

            foreach ($imported_data as $key => $value) {
                                // dd("yes");

                //Check if any column is missing
                if (count($value) < 2) {
                    $is_valid =  false;
                    $error_msg = "Some of the columns are missing. Please, use latest CSV file template.";
                    break;
                }
                
                
                $row_no = $key + 1;
                
                //Add sku
                $product_sku = trim($value[0]);
                //Product Quantity
                $discount_amount = trim($value[1]);

                if($discount_amount <= 0){
                    $is_valid =  false;
                    $error_msg = "Product Discount is required in row no. $row_no";
                    break;
                }

                if (!empty($product_sku)) {
                    $product_array['sku'] = $product_sku;
                } else {
                    $is_valid =  false;
                    $error_msg = "Product name is required in row no. $row_no";
                    break;
                }

                $products = DB::table('products')
                ->leftJoin('variations','products.id', '=', 'variations.product_id')
                ->select('variations.product_variation_id AS variation_id')
                ->where('products.sku', $product_sku)
                ->first();    

            
                if (!empty($products)) {
                    $product_array['variation_id'] = $products->variation_id;
                } else {
                    $is_valid =  false;
                    $error_msg = "No matching product is found. $row_no";
                    break;
                }
                if (!empty($products)) {
                    $product_array['variation_id'] = $products->variation_id;
                    $product_array['enable_stock'] = $products->enable_stock;
                    $product_array['quantity'] = $product_quantity;
                    $product_array['unit_price'] = $products->pp_without_discount;
                    $product_array['price'] = $products->pp_without_discount * $product_quantity;

                } else {
                    $is_valid =  false;
                    $error_msg = "No matching product is found. $row_no";
                    break;
                }

                $all_product_data[] = $product_array;                
                

                $sell_lines = [];
                $purchase_lines = [];

                if (!empty($all_product_data)) {
                    foreach ($all_product_data as $product) {
                        $sell_line_arr = [
                                    'product_id' => $product['product_id'],
                                    'variation_id' => $product['variation_id'],
                                    'quantity' => $this->productUtil->num_uf($product['quantity']),
                                    'item_tax' => 0,
                                    'tax_id' => null];

                        $purchase_line_arr = $sell_line_arr;
                        $sell_line_arr['unit_price'] = $this->productUtil->num_uf($product['unit_price']);
                        $sell_line_arr['unit_price_inc_tax'] = $sell_line_arr['unit_price'];

                        $purchase_line_arr['purchase_price'] = $sell_line_arr['unit_price'];
                        $purchase_line_arr['purchase_price_inc_tax'] = $sell_line_arr['unit_price'];

                        if (!empty($product['lot_no_line_id'])) {
                            //Add lot_no_line_id to sell line
                            $sell_line_arr['lot_no_line_id'] = $product['lot_no_line_id'];

                            //Copy lot number and expiry date to purchase line
                            $lot_details = PurchaseLine::find($product['lot_no_line_id']);
                            $purchase_line_arr['lot_number'] = $lot_details->lot_number;
                            $purchase_line_arr['mfg_date'] = $lot_details->mfg_date;
                            $purchase_line_arr['exp_date'] = $lot_details->exp_date;
                        }

                        $sell_lines[] = $sell_line_arr;
                        $purchase_lines[] = $purchase_line_arr;
                    }
                }
            }
            if (!$is_valid) {
                throw new \Exception($error_msg);
            }

            $totalPrice = 0;

            foreach ($all_product_data as $product) {
                $totalPrice += $product['price'];
            }

            $input_data = $request->only([ 'location_id', 'ref_no', 'transaction_date', 'additional_notes', 'shipping_charges', 'final_total']);
                $status = 'completed';
                $user_id = $request->session()->get('user.id');

                $input_data['final_total'] = $this->productUtil->num_uf($totalPrice);
                $input_data['total_before_tax'] = $totalPrice;

                $input_data['type'] = 'sell_transfer';
                $input_data['business_id'] = $business_id;
                $input_data['created_by'] = $user_id;
                $input_data['transaction_date'] = Carbon::now();
                $input_data['shipping_charges'] = 0;
                $input_data['payment_status'] = 'paid';
                $input_data['status'] = $status;
                $input_data['additional_notes'] = '';


                //Update reference count
                $ref_count = $this->productUtil->setAndGetReferenceCount('stock_transfer');
                //Generate reference number
                if (empty($input_data['ref_no'])) {
                    $input_data['ref_no'] = $this->productUtil->generateReferenceNumber('stock_transfer', $ref_count);
                }
       

            DB::beginTransaction();

            $sell_transfer = Transaction::create($input_data);

            //Create Purchase Transfer at transfer location
            $input_data['type'] = 'purchase_transfer';
            $input_data['location_id'] = $request->input('transfer_location_id');
            $input_data['transfer_parent_id'] = $sell_transfer->id;
            $input_data['status'] = 'completed';
            $purchase_transfer = Transaction::create($input_data);

            

            //Sell Product from first location
            if (!empty($sell_lines)) {
                $this->transactionUtil->createOrUpdateSellLines($sell_transfer, $sell_lines, $input_data['location_id']);
            }

            //Purchase product in second location
            if (!empty($purchase_lines)) {
                $purchase_transfer->purchase_lines()->createMany($purchase_lines);
            }

            //Decrease product stock from sell location
            //And increase product stock at purchase location
            if ($status == 'completed') {
                foreach ($all_product_data as $product) {
                    if ($product['enable_stock']) {
                        $this->productUtil->decreaseProductQuantity(
                            $product['product_id'],
                            $product['variation_id'],
                            $sell_transfer->location_id,
                            $this->productUtil->num_uf($product['quantity'])
                        );

                        $this->productUtil->updateProductQuantity(
                            $purchase_transfer->location_id,
                            $product['product_id'],
                            $product['variation_id'],
                            $product['quantity']
                        );
                    }
                }

                //Adjust stock over selling if found
                $this->productUtil->adjustStockOverSelling($purchase_transfer);

                //Map sell lines with purchase lines
                $business = ['id' => $business_id,
                            'accounting_method' => $request->session()->get('business.accounting_method'),
                            'location_id' => $sell_transfer->location_id
                        ];
                $this->transactionUtil->mapPurchaseSell($business, $sell_transfer->sell_lines, 'purchase');
            }

            $this->transactionUtil->activityLog($sell_transfer, 'added');

            $output = ['success' => 1,
                            'msg' => __('lang_v1.stock_transfer_added_successfully')
                        ];

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => $e->getMessage()
                        ];
        }

        return redirect('stock-transfers')->with('status', $output);
    }
}
