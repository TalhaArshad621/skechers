<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use App\BankTransfer;
use Exception;
use Datatables;
use DB;
use App\Utils\ProductUtil;


class BankTransferController extends Controller
{
    protected $productUtil;


    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }


    public function index(Request $request)
    {
        if (!auth()->user()->can('view_bank_transfer')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {

            $bank_trasnfer = BankTransfer::
                    select(
                        'bank',
                        'transaction_date',
                        'amount',
                        'added_by'
                    );

            $hide = '';
            $start_date = request()->get('start_date');
            $end_date = request()->get('end_date');
            // dd($start_date,$end_date);
            if (!empty($start_date) && !empty($end_date)) {
                $bank_trasnfer->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
                $hide = 'hide';
            }

            return Datatables::of($bank_trasnfer)
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('amount', function ($row) {
                    return __($row->amount);
                })
                ->editColumn('bank', function ($row) {
                    $formattedBank = strtoupper(str_replace('_', ' ', $row->bank));
                    return $formattedBank;
                })
                ->make(true);
        }

        return view('bank_transfer.index');

    }

    public function create()
    {
        if (!auth()->user()->can('create_bank_transfer')) {
            abort(403, 'Unauthorized action.');
        }

        return view('bank_transfer.create');
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'bank' => 'required',
                'amount' => 'required',
                'transaction_date' => 'required',
            ], [
                'bank.required' => 'The bank field is required.',
                'amount.required' => 'The status field is required.',
                'transaction_date.required' => 'The transaction_date field is required.',
            ]);
    
            if ($validator->fails()) {
                return Redirect::back()
                    ->withInput()
                    ->withErrors($validator);
            }
    
            DB::beginTransaction();
    
            $data = $validator->validated();
            $data['transaction_date'] = $this->productUtil->uf_date($data['transaction_date'], true);
            $data['added_by'] = $request->session()->get('user.id');
    
            $bankTransfer = BankTransfer::create($data);
    
            if ($bankTransfer) {
                DB::commit();
    
                $output = [
                    'success' => 1,
                    'msg' => __('Bank Transfer Added Successfully'),
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
    
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
    
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
    
        return redirect('bank-transfers')->with('status', $output);

    }
    
}
