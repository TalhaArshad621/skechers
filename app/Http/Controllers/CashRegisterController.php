<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\CashRegister;
use App\Utils\CashRegisterUtil;
use App\Utils\TransactionUtil;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use App\Utils\SmsUtil;
use App\User;
use DB;
use App\Utils\BusinessUtil;
use Illuminate\Support\Facades\Auth;

class CashRegisterController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $cashRegisterUtil;
    protected $moduleUtil;
    protected $transactionUtil;
    protected $smsUtil;
    protected $businessUtil;


    /**
     * Constructor
     *
     * @param CashRegisterUtil $cashRegisterUtil
     * @return void
     */
    public function __construct(CashRegisterUtil $cashRegisterUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil, SmsUtil $smsUtil)
    {
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->smsUtil = $smsUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('cash_register.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //like:repair
        $sub_type = request()->get('sub_type');

        //Check if there is a open register, if yes then redirect to POS screen.
        if ($this->cashRegisterUtil->countOpenedRegister() != 0) {
            return redirect()->action('SellPosController@create', ['sub_type' => $sub_type]);
        }
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);
        $defaultAmount = 12000;


        return view('cash_register.create')->with(compact('business_locations', 'sub_type', 'defaultAmount'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request->input('amount'));
        //like:repair
        $sub_type = request()->get('sub_type');

        try {
            $initial_amount = 0;
            if (!empty($request->input('amount'))) {
                $initial_amount = $this->cashRegisterUtil->num_uf($request->input('amount'));
            }
            $user_id = $request->session()->get('user.id');
            $business_id = $request->session()->get('user.business_id');

            $register = CashRegister::create([
                'business_id' => $business_id,
                'user_id' => $user_id,
                'status' => 'open',
                'location_id' => $request->input('location_id'),
                'created_at' => \Carbon::now()->format('Y-m-d H:i:00')
            ]);
            if (!empty($initial_amount)) {
                $register->cash_register_transactions()->create([
                    'amount' => $initial_amount,
                    'pay_method' => 'cash',
                    'type' => 'credit',
                    'transaction_type' => 'initial'
                ]);
            }
            $input = $request->only(['amount', 'location_id']);
            $user_name = User::where('id', $user_id)
                // ->select(DB::raw("CONCAT(first_name, ' ', last_name) AS full_name"))
                ->select(DB::raw("CONCAT(COALESCE(first_name, ''),' ', COALESCE(last_name,'')) AS full_name"))
                ->first();
            // dd($user_name);
            // dd($user_name);
            // $business_location = BusinessLocation::select('id','location_id')->where('business_id', $business_id)->first();
            // dd($business_location);
            $input['started_at'] = \Carbon::now()->format('Y-m-d H:i:s');
            // dd($input);

            $messageText = "DAY STARTED\n" .
                "DATE: " . $input['started_at'] . "\n" .
                "USERNAME: " . $user_name->full_name . "\n" .
                "STORE: SKX Jhelum\n" .
                "Opening Balance: " .  12000 . " ";

            $phone = "03416881318";

            $this->smsUtil->sendSmsMessage($messageText, preg_replace('/^0/', '92', $phone), 'SKECHERS.', '');
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
        }

        return redirect()->action('SellPosController@create', ['sub_type' => $sub_type]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CashRegister  $cashRegister
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('view_cash_register')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $register_details =  $this->cashRegisterUtil->getRegisterDetails($id);


        $user_id = $register_details->user_id;
        $open_time = $register_details['open_time'];
        $close_time = !empty($register_details['closed_at']) ? $register_details['closed_at'] : \Carbon::now()->toDateTimeString();
        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time);
        $sell_return =  $this->cashRegisterUtil->getSaleReturnDetails($register_details->location_id, $open_time, $close_time);

        $payment_types = $this->cashRegisterUtil->payment_types(null, false, $business_id);
        $start_date = \Carbon\Carbon::parse($open_time)->format('Y-m-d');
        $end_date = \Carbon\Carbon::parse($close_time)->format('Y-m-d');

        $bank_transfer = CashRegister::where('id',$id)->select('bank_transfer')->first();
        // dd($bank_transfer);
        $data = $this->transactionUtil->getProfitLossDetailsForRegister($business_id, $register_details->location_id, $start_date, $end_date);


        return view('cash_register.register_details')
            ->with(compact('register_details', 'details', 'payment_types', 'close_time', 'sell_return', 'data','bank_transfer'));
    }

    /**
     * Shows register details modal.
     *
     * @param  void
     * @return \Illuminate\Http\Response
     */
    public function getRegisterDetails()
    {
        if (!auth()->user()->can('view_cash_register')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $register_details =  $this->cashRegisterUtil->getRegisterDetails();
        $open_time = $register_details['open_time'];
        $close_time = \Carbon::now()->toDateTimeString();
        $sell_return =  $this->cashRegisterUtil->getSaleReturnDetails($register_details->location_id, $open_time, $close_time);

        $user_id = auth()->user()->id;
        $open_time = $register_details['open_time'];
        $close_time = \Carbon::now()->toDateTimeString();

        $is_types_of_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');

        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time, $is_types_of_service_enabled);
        // dd($details);
        $payment_types = $this->cashRegisterUtil->payment_types($register_details->location_id, true, $business_id);

        $start_date = \Carbon\Carbon::parse($open_time)->format('Y-m-d');
        $end_date = \Carbon\Carbon::parse($close_time)->format('Y-m-d');

        // dd($open_time_formatted, $close_time_formatted);
        // dd($open_time, $close_time);
        $data = $this->transactionUtil->getProfitLossDetailsForRegister($business_id, $register_details->location_id, $start_date, $end_date);
        // dd($data);

        return view('cash_register.register_details')
            ->with(compact('register_details', 'details', 'payment_types', 'close_time', 'sell_return', 'data'));
    }

    /**
     * Shows close register form.
     *
     * @param  void
     * @return \Illuminate\Http\Response
     */
    public function getCloseRegister($id = null)
    {
        if (!auth()->user()->can('close_cash_register')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $register_details =  $this->cashRegisterUtil->getRegisterDetails($id);

        $user_id = $register_details->user_id;
        $open_time = $register_details['open_time'];
        $close_time = \Carbon::now()->toDateTimeString();
        $sell_return =  $this->cashRegisterUtil->getSaleReturnDetails($register_details->location_id, $open_time, $close_time);

        $is_types_of_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');

        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time, $is_types_of_service_enabled);

        $payment_types = $this->cashRegisterUtil->payment_types($register_details->location_id, true, $business_id);
        return view('cash_register.close_register_modal')
            ->with(compact('register_details', 'details', 'payment_types', 'sell_return'));
    }

    /**
     * Closes currently opened register.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postCloseRegister(Request $request)
    {
        // dd($request);
        if (!auth()->user()->can('close_cash_register')) {
            abort(403, 'Unauthorized action.');
        }
        // $business_id = request()->session()->get('user.business_id');

        // $business_details = $this->businessUtil->getDetails($business_id);
        // dd($business_id, $business_details);


        try {
            //Disable in demo
            if (config('app.env') == 'demo') {
                $output = [
                    'success' => 0,
                    'msg' => 'Feature disabled in demo!!'
                ];
                return redirect()->action('HomeController@index')->with('status', $output);
            }

            $input = $request->only([
                'closing_amount', 'total_card_slips', 'total_cheques',
                'closing_note','bank_transfer'
            ]);
            $input['closing_amount'] = $this->cashRegisterUtil->num_uf($input['closing_amount']);
            $user_id = $request->input('user_id');
            $user_name = User::where('id', $user_id)
            // (DB::raw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) AS full_name")),

                ->select(DB::raw("CONCAT(COALESCE(first_name, ''),' ', COALESCE(last_name,'')) AS full_name"))
                ->first();
            // dd($user_name);
            // $business_location = BusinessLocation::select('id','location_id')->where('business_id', $business_id)->first();
            // dd($business_location);
            $input['closed_at'] = \Carbon::now()->format('Y-m-d H:i:s');
            $input['status'] = 'close';
            // dd($input);

           $cashRegister =   CashRegister::where('user_id', $user_id)
                ->where('status', 'open')->first();
                $cashRegister->update($input);
            $output = [
                'success' => 1,
                'msg' => __('cash_register.close_success')
            ];

            $business_location = DB::table('business_locations')->select('name')->where('id', $cashRegister->location_id)->first();
            // dd($cashRegister);
            // $messageText = "DAY ENDED\n" .
            // "DATE: " . $input['closed_at'] . "\n" .
            // "USERNAME: " . $user_name->full_name . "\n" .
            // "STORE: SKX Jhelum\n" .
            // "Total Sale: " . (int)$request->input('total_sales') . "\n" .
            // "Card Sale: " . (int)$request->input('card_sale') . "\n" .
            // "Cash In Hand: " . (int)$request->input('cash_in_hand') . "\n" .
            // "Cash Sale: " . (int)$request->input('cash_sale') . " ";

            $messageText = "DAY ENDED\n" .
                "DATE: " . $input['closed_at'] . "\n" .
                "USERNAME: " . $user_name->full_name . "\n" .
                "STORE: ".$business_location->name."\n" .
                "Total Sale: " . number_format((int)$request->input('total_sales')) . "\n" .
                "Card Sale: " . number_format((int)$request->input('card_sale')) . "\n" .
                "Cash Sale: " . number_format((int)$request->input('cash_sale')) . "\n" .
                "Cash In Hand: " . number_format((int)$request->input('cash_in_hand')) . " ";

            // dd($messageText);
            // $messageText = 
            // // "Register Closed";
            // "DAY ENDED
            // DATE: $input['closed_at']
            // USERNAME: $user_name->full_name
            // STORE: SKX Jhelum
            // Total Sale: $request->input('total_sales');
            // Card Sale: $request->input('card_sale');
            // Cash In Hand: $request->input('cash_in_hand');
            // Cash Sale: $request->input('cash_sale');
            // To block promotions from SKECHERS. send UNSUB to 9689128
            // To block all promotions, send REG to 3627";
            $phone = "03416881318";

            // $this->smsUtil->sendSmsMessage($messageText, preg_replace('/^0/', '92', $phone), 'SKECHERS.', '');
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return redirect()->back()->with('status', $output);
    }
}
