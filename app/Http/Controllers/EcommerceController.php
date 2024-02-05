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
        NotificationUtil $notificationUtil
    )
    {
        $this->contactUtil = $contactUtil;
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
        $this->notificationUtil = $notificationUtil;
        $this->business_id = 4;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('ecommerce.index');
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

            $response = $client->request('GET', $url, [
                'headers' => [
                    'X-Shopify-Access-Token' => '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',
                    'Content-Type' => 'application/json',
                ],
            ]);
            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody(),true);
            $shopifyOrders = $body['orders'];

            $input = [];
            $input['is_quotation'] = 0;
            $is_direct_sale = false;

            foreach($shopifyOrders as $shopifyOrder) {
                if($shopifyOrder['name'] == "#SS6034"){                    
                    
                    // Order number for the shopify order in DB it will be invoice number
                    $orderId = trim($shopifyOrder['name'], "#");
                    $input['invoice_no'] = $orderId;
                    // Check if the order already exist
                    $existingTransactions = DB::table('transactions')->where('type','sell')->where('sub_type','ecommerce')->where('invoice_no',$orderId)->first();
                    if(!$existingTransactions) {
                        $business_id = $this->business_id;
                        $input['status'] = "final";
                        $input['discount_type'] = "fixed";
                        $input['discount_amount'] = $shopifyOrder['total_discounts'];
                        $input['is_credit_sale'] = 0;
                      
                        // total_price = original_price - discount_amount;
                        $invoice_total = $shopifyOrder['total_price'];
                        $user_id = 1;
                        
                        $input['total_before_tax'] = $invoice_total;
                        $input['tax'] = $shopifyOrder['total_tax'];
                        $discount = [
                            'discount_type' => $input['discount_type'],
                            'discount_amount' => $input['discount_amount']
                        ];
    
                        DB::beginTransaction();
                        $input['transaction_date'] = Carbon::now();
                        $input['commission_agent'] = null;

                        // dd($shopifyOrder);
                        // Customer Details
                        $contact_id = $this->contactUtil->getEcommerceCustomer($shopifyOrder['customer']['email']);
                        if(!$contact_id) {
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
                        $input['shipping_status'] = "pending";
                        $input['delivered_to'] = $shopifyOrder['shipping_address']['first_name'];
                        $input['order_addresses'] = $shopifyOrder['shipping_address']['address1'];
                        $input['is_created_from_api'] = 1;
                        $input['products'] = $shopifyOrder['line_items'];

                        // if($input['payment_gateway_names'][0] == "")
                        
                        
                        $transaction = $this->transactionUtil->createEcommerceTransaction($business_id, $input, $invoice_total, $user_id);

                        $this->transactionUtil->createEcommerceSellLines($transaction, $input['products']);
    
                    }
                }
            }

        } catch(Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            return response()->json([
                'error' => true,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
}
