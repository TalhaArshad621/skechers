<?php

namespace App\Http\Controllers;

use App\Brands;
use App\BusinessLocation;
use App\CashRegister;
use App\Category;
use App\Charts\CommonChart;
use App\Contact;
use App\CustomerGroup;
use App\ExpenseCategory;
use App\Product;
use App\PurchaseLine;
use App\EcommercePayment;
use App\EcommerceSellLine;
use App\EcommerceTransaction;
use App\Restaurant\ResTable;
use App\SellingPriceGroup;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\TransactionSellLinesPurchaseLines;
use App\Unit;
use App\User;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\VariationLocationDetails;
use Datatables;
use DB;
use Illuminate\Http\Request;
use App\TaxRate;
use Svg\Tag\Rect;

class ReportController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $transactionUtil;
    protected $productUtil;
    protected $moduleUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Shows profit\loss of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getProfitLoss(Request $request)
    {
        if (!auth()->user()->can('profit_loss_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $location_id = $request->get('location_id');

            $data = $this->transactionUtil->getProfitLossDetails($business_id, $location_id, $start_date, $end_date);

            // $data['closing_stock'] = $data['closing_stock'] - $data['total_sell_return'];

            return view('report.partials.profit_loss_details', compact('data'))->render();
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);
        return view('report.profit_loss', compact('business_locations'));
    }

    /**
     * Shows product report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getPurchaseSell(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            // dd($start_date,$end_date);

            $location_id = $request->get('location_id');

            $purchase_details = $this->transactionUtil->getPurchaseTotals($business_id, $start_date, $end_date, $location_id);

            $sell_details = $this->transactionUtil->getSellTotals(
                $business_id,
                $start_date,
                $end_date,
                $location_id
            );

            $transaction_types = [
                'purchase_return', 'sell_return'
            ];

            $transaction_totals = $this->transactionUtil->getTransactionTotals(
                $business_id,
                $transaction_types,
                $start_date,
                $end_date,
                $location_id
            );

            $total_purchase_return_inc_tax = $transaction_totals['total_purchase_return_inc_tax'];
            $total_sell_return_inc_tax = $transaction_totals['total_sell_return_inc_tax'];

            $difference = [
                'total' => $sell_details['total_sell_inc_tax'] + $total_sell_return_inc_tax - $purchase_details['total_purchase_inc_tax'] - $total_purchase_return_inc_tax,
                'due' => $sell_details['invoice_due'] - $purchase_details['purchase_due']
            ];

            return ['purchase' => $purchase_details,
                    'sell' => $sell_details,
                    'total_purchase_return' => $total_purchase_return_inc_tax,
                    'total_sell_return' => $total_sell_return_inc_tax,
                    'difference' => $difference
                ];
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.purchase_sell')
                    ->with(compact('business_locations'));
    }

    /**
     * Shows report for Supplier
     *
     * @return \Illuminate\Http\Response
     */
    public function getCustomerSuppliers(Request $request)
    {
        if (!auth()->user()->can('contacts_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $contacts = Contact::where('contacts.business_id', $business_id)
                ->join('transactions AS t', 'contacts.id', '=', 't.contact_id')
                ->active()
                ->groupBy('contacts.id')
                ->select(
                    DB::raw("SUM(IF(t.type = 'purchase', final_total, 0)) as total_purchase"),
                    DB::raw("SUM(IF(t.type = 'purchase_return', final_total, 0)) as total_purchase_return"),
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                    DB::raw("SUM(IF(t.type = 'purchase', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as purchase_paid"),
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_received"),
                    DB::raw("SUM(IF(t.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as sell_return_paid"),
                    DB::raw("SUM(IF(t.type = 'purchase_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as purchase_return_received"),
                    DB::raw("SUM(IF(t.type = 'sell_return', final_total, 0)) as total_sell_return"),
                    DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                    DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),
                    'contacts.supplier_business_name',
                    'contacts.name',
                    'contacts.id',
                    'contacts.type as contact_type'
                );
            $permitted_locations = auth()->user()->permitted_locations();
            
            if ($permitted_locations != 'all') {
                $contacts->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($request->input('customer_group_id'))) {
                $contacts->where('contacts.customer_group_id', $request->input('customer_group_id'));
            }

            if (!empty($request->input('contact_type'))) {
                $contacts->whereIn('contacts.type', [$request->input('contact_type'), 'both']);
            }

            return Datatables::of($contacts)
                ->editColumn('name', function ($row) {
                    $name = $row->name;
                    if (!empty($row->supplier_business_name)) {
                        $name .= ', ' . $row->supplier_business_name;
                    }
                    return '<a href="' . action('ContactController@show', [$row->id]) . '" target="_blank" class="no-print">' .
                            $name .
                        '</a><span class="print_section">' . $name . '</span>';
                })
                ->editColumn('total_purchase', function ($row) {
                    return '<span class="display_currency total_purchase" data-orig-value="' . $row->total_purchase . '" data-currency_symbol = true>' . $row->total_purchase . '</span>';
                })
                ->editColumn('total_purchase_return', function ($row) {
                    return '<span class="display_currency total_purchase_return" data-orig-value="' . $row->total_purchase_return . '" data-currency_symbol = true>' . $row->total_purchase_return . '</span>';
                })
                ->editColumn('total_sell_return', function ($row) {
                    return '<span class="display_currency total_sell_return" data-orig-value="' . $row->total_sell_return . '" data-currency_symbol = true>' . $row->total_sell_return . '</span>';
                })
                ->editColumn('total_invoice', function ($row) {
                    return '<span class="display_currency total_invoice" data-orig-value="' . $row->total_invoice . '" data-currency_symbol = true>' . $row->total_invoice . '</span>';
                })
                ->addColumn('due', function ($row) {
                    $due = ($row->total_invoice - $row->invoice_received - $row->total_sell_return + $row->sell_return_paid) - ($row->total_purchase - $row->total_purchase_return + $row->purchase_return_received - $row->purchase_paid);

                    if ($row->contact_type == 'supplier') {
                        $due -= $row->opening_balance - $row->opening_balance_paid;
                    } else {
                        $due += $row->opening_balance - $row->opening_balance_paid;
                    }

                    return '<span class="display_currency total_due" data-orig-value="' . $due . '" data-currency_symbol=true data-highlight=true>' . $due .'</span>';
                })
                ->addColumn(
                    'opening_balance_due',
                    '<span class="display_currency opening_balance_due" data-currency_symbol=true data-orig-value="{{$opening_balance - $opening_balance_paid}}">{{$opening_balance - $opening_balance_paid}}</span>'
                )
                ->removeColumn('supplier_business_name')
                ->removeColumn('invoice_received')
                ->removeColumn('purchase_paid')
                ->removeColumn('id')
                ->rawColumns(['total_purchase', 'total_invoice', 'due', 'name', 'total_purchase_return', 'total_sell_return', 'opening_balance_due'])
                ->make(true);
        }

        $customer_group = CustomerGroup::forDropdown($business_id, false, true);
        $types = [
            '' => __('lang_v1.all'),
            'customer' => __('report.customer'),
            'supplier' => __('report.supplier')
        ];

        return view('report.contact')
        ->with(compact('customer_group', 'types'));
    }

    /**
     * Shows product stock report
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockReport(Request $request)
    {
        if (!(auth()->user()->can('stock_report.view') || auth()->user()->can('product.view'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
                                                ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }
        if ($this->moduleUtil->isModuleInstalled('Manufacturing') && (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module'))) {
            $show_manufacturing_data = 1;
        } else {
            $show_manufacturing_data = 0;
        }
        if ($request->ajax()) {

            $filters = request()->only(['location_id', 'category_id', 'sub_category_id', 'brand_id', 'unit_id', 'tax_id', 'type', 
                'only_mfg_products', 'active_state',  'not_for_selling', 'repair_model_id', 'product_id', 'active_state']);

            $filters['not_for_selling'] = isset($filters['not_for_selling']) && $filters['not_for_selling'] == 'true' ? 1 : 0;

            $filters['show_manufacturing_data'] = $show_manufacturing_data;

            //Return the details in ajax call
            $for = request()->input('for') == 'view_product' ? 'view_product' :'datatables';

            $products = $this->productUtil->getProductStockDetails($business_id, $filters, $for);
            //To show stock details on view product modal
            if ($for == 'view_product' && !empty(request()->input('product_id'))) {
                $product_stock_details = $products;

                return view('product.partials.product_stock_details')->with(compact('product_stock_details'));
            }

            $datatable =  Datatables::of($products)
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0 ;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float)$stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float)$stock . '</span>' . ' ' . $row->unit ;
                    } else {
                        return '--';
                    }
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                ->editColumn('total_sold', function ($row) {
                    $total_sold = 0;
                    if ($row->total_sold) {
                        $total_sold =  (float)$row->total_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                })
                ->editColumn('total_transfered', function ($row) {
                    $total_transfered = 0;
                    if ($row->total_transfered) {
                        $total_transfered =  (float)$row->total_transfered;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '" data-unit="' . $row->unit . '" >' . $total_transfered . '</span> ' . $row->unit;
                })
                
                ->editColumn('total_adjusted', function ($row) {
                    $total_adjusted = 0;
                    if ($row->total_adjusted) {
                        $total_adjusted =  (float)$row->total_adjusted;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false  data-orig-value="' . $total_adjusted . '" data-unit="' . $row->unit . '" >' . $total_adjusted . '</span> ' . $row->unit;
                })
                ->editColumn('unit_price', function ($row) use ($allowed_selling_price_group) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                        . $row->unit_price . '</span>';
                    }

                    if ($allowed_selling_price_group) {
                        $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) .'">' . __('lang_v1.view_group_prices') . '</button>';
                    }

                    return $html;
                })
                ->editColumn('stock_price', function ($row) {
                    $html = '<span class="display_currency total_stock_price" data-currency_symbol=true data-orig-value="'
                        . $row->stock_price . '">'
                        . $row->stock_price . '</span>';

                    return $html;
                })
                ->editColumn('stock_value_by_sale_price', function ($row) {
                    $stock = $row->stock ? $row->stock : 0 ;
                    $unit_selling_price = (float)$row->group_price > 0 ? $row->group_price : $row->unit_price;
                    $stock_price = $stock * $unit_selling_price;
                    return  '<span class="stock_value_by_sale_price display_currency" data-orig-value="' . (float)$stock_price . '" data-currency_symbol=true > ' . (float)$stock_price . '</span>';
                })
                ->editColumn('sold_stock_value_by_purchase_price', function ($row) {
                    $stock_price = $row->total_sold * $row->default_purchase_price;
                    return  '<span class="sold_stock_value_by_purchase_price display_currency" data-orig-value="' . (float)$stock_price . '" data-currency_symbol=true > ' . (float)$stock_price . '</span>';
                })
                ->editColumn('sold_stock_value_by_sale_price', function ($row) {
                    $stock_price = $row->total_sold * $row->unit_price;
                    return  '<span class="sold_stock_value_by_sale_price display_currency" data-orig-value="' . (float)$stock_price . '" data-currency_symbol=true > ' . (float)$stock_price . '</span>';
                })
                ->addColumn('potential_profit', function ($row) {
                    $stock = $row->stock ? $row->stock : 0 ;
                    $unit_selling_price = (float)$row->group_price > 0 ? $row->group_price : $row->unit_price;
                    $stock_price_by_sp = $stock * $unit_selling_price;
                    $potential_profit = $stock_price_by_sp - $row->stock_price;

                    return  '<span class="potential_profit display_currency" data-orig-value="' . (float)$potential_profit . '" data-currency_symbol=true > ' . (float)$potential_profit . '</span>';
                })
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                ->removeColumn('id');

            $raw_columns  = ['unit_price', 'total_transfered', 'total_sold',
                    'total_adjusted', 'stock', 'stock_price', 'stock_value_by_sale_price', 'potential_profit',
                'sold_stock_value_by_purchase_price','sold_stock_value_by_sale_price'];

            if ($show_manufacturing_data) {
                $datatable->editColumn('total_mfg_stock', function ($row) {
                    $total_mfg_stock = 0;
                    if ($row->total_mfg_stock) {
                        $total_mfg_stock =  (float)$row->total_mfg_stock;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_mfg_stock" data-currency_symbol=false  data-orig-value="' . $total_mfg_stock . '" data-unit="' . $row->unit . '" >' . $total_mfg_stock . '</span> ' . $row->unit;
                });
                $raw_columns[] = 'total_mfg_stock';
            }

            return $datatable->rawColumns($raw_columns)->make(true);
        }

        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::forDropdown($business_id);
        $units = Unit::where('business_id', $business_id)
                            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.stock_report')
            ->with(compact('categories', 'brands', 'units', 'business_locations', 'show_manufacturing_data'));
    }

    public function getSellReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
                                                ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }
        if ($this->moduleUtil->isModuleInstalled('Manufacturing') && (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module'))) {
            $show_manufacturing_data = 1;
        } else {
            $show_manufacturing_data = 0;
        }
        if ($request->ajax()) {

            $filters = request()->only(['location_id', 'category_id', 'sub_category_id', 'brand_id', 'unit_id', 'tax_id', 'type', 
                'only_mfg_products', 'active_state',  'not_for_selling', 'repair_model_id', 'product_id', 'active_state']);

            $filters['not_for_selling'] = isset($filters['not_for_selling']) && $filters['not_for_selling'] == 'true' ? 1 : 0;

            $filters['show_manufacturing_data'] = $show_manufacturing_data;

            //Return the details in ajax call
            $for = request()->input('for') == 'view_product' ? 'view_product' :'datatables';

            $products = $this->productUtil->getProductSellStockDetails($business_id, $filters, $for);
            //To show stock details on view product modal
            if ($for == 'view_product' && !empty(request()->input('product_id'))) {
                $product_stock_details = $products;

                return view('product.partials.product_stock_details')->with(compact('product_stock_details'));
            }

            $datatable =  Datatables::of($products)
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0 ;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float)$stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float)$stock . '</span>' . ' ' . $row->unit ;
                    } else {
                        return '--';
                    }
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at;
                })
                ->editColumn('total_sold', function ($row) {
                    $total_sold = 0;
                    if ($row->total_sold) {
                        $total_sold =  (float)$row->total_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                })
                ->editColumn('total_transfered', function ($row) {
                    $total_transfered = 0;
                    if ($row->total_transfered) {
                        $total_transfered =  (float)$row->total_transfered;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '" data-unit="' . $row->unit . '" >' . $total_transfered . '</span> ' . $row->unit;
                })
                
                ->editColumn('total_adjusted', function ($row) {
                    $total_adjusted = 0;
                    if ($row->total_adjusted) {
                        $total_adjusted =  (float)$row->total_adjusted;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false  data-orig-value="' . $total_adjusted . '" data-unit="' . $row->unit . '" >' . $total_adjusted . '</span> ' . $row->unit;
                })
                ->editColumn('unit_price', function ($row) use ($allowed_selling_price_group) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                        . $row->unit_price . '</span>';
                    }

                    if ($allowed_selling_price_group) {
                        $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) .'">' . __('lang_v1.view_group_prices') . '</button>';
                    }

                    return $html;
                })
                ->editColumn('stock_price', function ($row) {
                    $html = '<span class="display_currency total_stock_price" data-currency_symbol=true data-orig-value="'
                        . $row->stock_price . '">'
                        . $row->stock_price . '</span>';

                    return $html;
                })
                ->editColumn('total_sell_discount', function ($row) {
                    $html = '<span class="display_currency total_sell_discount" data-currency_symbol=true data-orig-value="'
                        . $row->total_sell_discount . '">'
                        . +$row->total_sell_discount . '</span>';

                    return $html;
                })
                ->editColumn('stock_value_by_sale_price', function ($row) {
                    $stock = $row->stock_quantity ? $row->stock_quantity : 0 ;
                    $unit_selling_price = (float)$row->group_price > 0 ? $row->group_price : $row->unit_price;
                    $stock_price = $stock * $unit_selling_price;
                    return  '<span class="stock_value_by_sale_price display_currency" data-orig-value="' . (float)$stock_price . '" data-currency_symbol=true > ' . (float)$stock_price . '</span>';
                })
                ->addColumn('potential_profit', function ($row) {
                    // $stock = $row->stock_quantity ? $row->stock_quantity : 0 ;
                    // $unit_selling_price = (float)$row->group_price > 0 ? $row->group_price : $row->unit_price;
                    // $stock_price_by_sp = $stock * $unit_selling_price;
                    $potential_profit = $row->unit_price;

                    return  '<span class="potential_profit display_currency" data-orig-value="' . (float)$potential_profit . '" data-currency_symbol=true > ' . (float)$potential_profit . '</span>';
                })
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                ->removeColumn('id');

            $raw_columns  = ['unit_price', 'total_transfered', 'total_sold',
                    'total_adjusted', 'stock', 'stock_price', 'stock_value_by_sale_price', 'potential_profit','total_sell_discount'];

            if ($show_manufacturing_data) {
                $datatable->editColumn('total_mfg_stock', function ($row) {
                    $total_mfg_stock = 0;
                    if ($row->total_mfg_stock) {
                        $total_mfg_stock =  (float)$row->total_mfg_stock;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_mfg_stock" data-currency_symbol=false  data-orig-value="' . $total_mfg_stock . '" data-unit="' . $row->unit . '" >' . $total_mfg_stock . '</span> ' . $row->unit;
                });
                $raw_columns[] = 'total_mfg_stock';
            }

            return $datatable->rawColumns($raw_columns)->make(true);
        }

        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::forDropdown($business_id);
        $units = Unit::where('business_id', $business_id)
                            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.sell_report')
            ->with(compact('categories', 'brands', 'units', 'business_locations', 'show_manufacturing_data'));
    }

    /**
     * Shows product stock details
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockDetails(Request $request)
    {
        //Return the details in ajax call
        if ($request->ajax()) {
            $business_id = $request->session()->get('user.business_id');
            $product_id = $request->input('product_id');
            $query = Product::leftjoin('units as u', 'products.unit_id', '=', 'u.id')
                ->join('variations as v', 'products.id', '=', 'v.product_id')
                ->join('product_variations as pv', 'pv.id', '=', 'v.product_variation_id')
                ->leftjoin('variation_location_details as vld', 'v.id', '=', 'vld.variation_id')
                ->where('products.business_id', $business_id)
                ->where('products.id', $product_id)
                ->whereNull('v.deleted_at');

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';
            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);
                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
            }

            $product_details =  $query->select(
                'products.name as product',
                'u.short_name as unit',
                'pv.name as product_variation',
                'v.name as variation',
                'v.sub_sku as sub_sku',
                'v.sell_price_inc_tax',
                DB::raw("SUM(vld.qty_available) as stock"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell', TSL.quantity - TSL.quantity_returned, -1* TPL.quantity) ) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id

                        LEFT JOIN purchase_lines AS TPL ON transactions.id=TPL.transaction_id

                        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                        AND (TSL.variation_id=v.id OR TPL.variation_id=v.id)) as total_sold"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter 
                        AND (TSL.variation_id=v.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                        LEFT JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter 
                        AND (SAL.variation_id=v.id)) as total_adjusted")
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.variation_id=v.id) as total_sold")
            )
                        ->groupBy('v.id')
                        ->get();

            return view('report.stock_details')
                        ->with(compact('product_details'));
        }
    }

    /**
     * Shows tax report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getTaxDetails(Request $request)
    {
        if (!auth()->user()->can('tax_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->ajax()) {

            $business_id = $request->session()->get('user.business_id');
            $taxes = TaxRate::forBusiness($business_id);
            $type = $request->input('type');

            $sells = Transaction::leftJoin('tax_rates as tr', 'transactions.tax_id', '=', 'tr.id')
                            ->leftJoin('contacts as c', 'transactions.contact_id', '=', 'c.id')
                ->where('transactions.business_id', $business_id)
                ->select('c.name as contact_name', 
                        'c.supplier_business_name',
                        'c.tax_number',
                        'transactions.ref_no',
                        'transactions.invoice_no',
                        'transactions.transaction_date',
                        'transactions.total_before_tax',
                        'transactions.tax_id',
                        'transactions.tax_amount',
                        'transactions.id',
                        'transactions.type',
                        'transactions.discount_type',
                        'transactions.discount_amount'
                    );
                if ($type == 'sell') {
                    $sells->where('transactions.type', 'sell')
                    ->where('transactions.status', 'final')
                    ->where( function($query){
                        $query->whereHas('sell_lines',function($q){
                            $q->whereNotNull('transaction_sell_lines.tax_id');
                        })->orWhereNotNull('transactions.tax_id');
                    })
                    ->with(['sell_lines' => function($q){
                        $q->whereNotNull('transaction_sell_lines.tax_id');
                    }, 'sell_lines.line_tax']);
                }
                if ($type == 'purchase') {
                    $sells->where('transactions.type', 'purchase')
                    ->where('transactions.status', 'received')
                    ->where( function($query){
                        $query->whereHas('purchase_lines', function($q){
                            $q->whereNotNull('purchase_lines.tax_id');
                        })->orWhereNotNull('transactions.tax_id');
                    })
                    ->with(['purchase_lines' => function($q){
                        $q->whereNotNull('purchase_lines.tax_id');
                    }, 'purchase_lines.line_tax']);
                }

                if ($type == 'expense') {
                    $sells->where('transactions.type', 'expense')
                        ->whereNotNull('transactions.tax_id');
                }

                if (request()->has('location_id')) {
                    $location_id = request()->get('location_id');
                    if (!empty($location_id)) {
                        $sells->where('transactions.location_id', $location_id);
                    }
                }
                if (!empty(request()->start_date) && !empty(request()->end_date)) {
                    $start = request()->start_date;
                    $end =  request()->end_date;
                    $sells->whereDate('transactions.transaction_date', '>=', $start)
                                ->whereDate('transactions.transaction_date', '<=', $end);
                }
                $datatable = Datatables::of($sells);
                $raw_cols = ['total_before_tax', 'discount_amount', 'contact_name'];
                $group_taxes_array = TaxRate::groupTaxes($business_id);
                $group_taxes = [];
                foreach ($group_taxes_array as $group_tax) {
                   foreach ($group_tax['sub_taxes'] as $sub_tax) {
                       $group_taxes[$group_tax->id]['sub_taxes'][$sub_tax->id] = $sub_tax;
                   }
                }
                foreach ($taxes as $tax) {
                    $col = 'tax_' . $tax['id'];
                    $raw_cols[] = $col;
                    $datatable->addColumn($col, function($row) use($tax, $type, $col, $group_taxes) {
                        $tax_amount = 0;
                        if ($type == 'sell') {
                            foreach ($row->sell_lines as $sell_line) {
                                if ($sell_line->tax_id == $tax['id']) {
                                    $tax_amount += ($sell_line->item_tax * ($sell_line->quantity - $sell_line->quantity_returned) );
                                }

                                //break group tax
                                if ($sell_line->line_tax->is_tax_group == 1 && array_key_exists($tax['id'], $group_taxes[$sell_line->tax_id]['sub_taxes'])) {

                                    $group_tax_details = $this->transactionUtil->groupTaxDetails($sell_line->line_tax, $sell_line->item_tax);
                                    
                                    $sub_tax_share = 0;
                                    foreach ($group_tax_details as $sub_tax_details) {
                                        if ($sub_tax_details['id'] == $tax['id']) {
                                            $sub_tax_share = $sub_tax_details['calculated_tax'];
                                        }
                                    }

                                    $tax_amount += ($sub_tax_share * ($sell_line->quantity - $sell_line->quantity_returned) );
                                }
                            }
                        } elseif ($type == 'purchase') {
                            foreach ($row->purchase_lines as $purchase_line) {
                                if ($purchase_line->tax_id == $tax['id']) {
                                    $tax_amount += ($purchase_line->item_tax * ($purchase_line->quantity - $purchase_line->quantity_returned));
                                }

                                //break group tax
                                if ($purchase_line->line_tax->is_tax_group == 1 && array_key_exists($tax['id'], $group_taxes[$purchase_line->tax_id]['sub_taxes'])) {

                                    $group_tax_details = $this->transactionUtil->groupTaxDetails($purchase_line->line_tax, $purchase_line->item_tax);
                                    
                                    $sub_tax_share = 0;
                                    foreach ($group_tax_details as $sub_tax_details) {
                                        if ($sub_tax_details['id'] == $tax['id']) {
                                            $sub_tax_share = $sub_tax_details['calculated_tax'];
                                        }
                                    }

                                    $tax_amount += ($sub_tax_share * ($purchase_line->quantity - $purchase_line->quantity_returned) );
                                }
                            }
                        }
                        if ($row->tax_id == $tax['id']) {
                            $tax_amount += $row->tax_amount;
                        }

                        //break group tax
                        if (!empty($group_taxes[$row->tax_id]) && array_key_exists($tax['id'], $group_taxes[$row->tax_id]['sub_taxes'])) {

                            $group_tax_details = $this->transactionUtil->groupTaxDetails($row->tax_id, $row->tax_amount);
                                    
                            $sub_tax_share = 0;
                            foreach ($group_tax_details as $sub_tax_details) {
                                if ($sub_tax_details['id'] == $tax['id']) {
                                    $sub_tax_share = $sub_tax_details['calculated_tax'];
                                }
                            }

                            $tax_amount += $sub_tax_share;
                        }

                        if ($tax_amount > 0) {
                            return '<span class="display_currency ' . $col . '" data-currency_symbol="true" data-orig-value="' . $tax_amount . '">' . $tax_amount . '</span>';
                        } else {
                            return '';
                        }
                    });
                }

                $datatable->editColumn(
                    'total_before_tax',
                    '<span class="display_currency total_before_tax" data-currency_symbol="true" data-orig-value="{{$total_before_tax}}">{{$total_before_tax}}</span>'
                )->editColumn('discount_amount', '@if($discount_amount != 0)<span class="display_currency" data-currency_symbol="true">{{$discount_amount}}</span>@if($discount_type == "percentage")% @endif @endif')
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('contact_name', '@if(!empty($supplier_business_name)) {{$supplier_business_name}},<br>@endif {{$contact_name}}');

                return $datatable->rawColumns($raw_cols)
                            ->make(true);
        }
    }

    /**
     * Shows tax report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getTaxReport(Request $request)
    {
        if (!auth()->user()->can('tax_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $location_id = $request->get('location_id');

            $input_tax_details = $this->transactionUtil->getInputTax($business_id, $start_date, $end_date, $location_id);

            $output_tax_details = $this->transactionUtil->getOutputTax($business_id, $start_date, $end_date, $location_id);

            $expense_tax_details = $this->transactionUtil->getExpenseTax($business_id, $start_date, $end_date, $location_id);

            $module_output_taxes = $this->moduleUtil->getModuleData('getModuleOutputTax', ['start_date' => $start_date, 'end_date' => $end_date]);

            $total_module_output_tax = 0;
            foreach ($module_output_taxes as $key => $module_output_tax) {
                $total_module_output_tax += $module_output_tax;
            }

            $total_output_tax = $output_tax_details['total_tax'] + $total_module_output_tax;
            
            $tax_diff = $total_output_tax - $input_tax_details['total_tax'] - $expense_tax_details['total_tax'];

            return [
                    'tax_diff' => $tax_diff
                ];
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        $taxes = TaxRate::forBusiness($business_id);

        $tax_report_tabs = $this->moduleUtil->getModuleData('getTaxReportViewTabs');

        return view('report.tax_report')
            ->with(compact('business_locations', 'taxes', 'tax_report_tabs'));
    }

    /**
     * Shows trending products
     *
     * @return \Illuminate\Http\Response
     */
    public function getTrendingProducts(Request $request)
    {
        if (!auth()->user()->can('trending_product_report.view')) {
            abort(403, 'Unauthorized action.');
        }
        
        $business_id = $request->session()->get('user.business_id');

        $filters = request()->only(['category', 'sub_category', 'brand', 'unit', 'limit', 'location_id', 'product_type']);

        $date_range = request()->input('date_range');
        
        if (!empty($date_range)) {
            $date_range_array = explode('~', $date_range);
            $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
            $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));
        }

        $products = $this->productUtil->getTrendingProducts($business_id, $filters);

        $values = [];
        $labels = [];
        foreach ($products as $product) {
            $values[] = (float) $product->total_unit_sold;
            $labels[] = $product->product . ' (' . $product->unit . ')';
        }

        $chart = new CommonChart;
        $chart->labels($labels)
            ->dataset(__('report.total_unit_sold'), 'column', $values);

        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::forDropdown($business_id);
        $units = Unit::where('business_id', $business_id)
                            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.trending_products')
                    ->with(compact('chart', 'categories', 'brands', 'units', 'business_locations'));
    }

    public function getTrendingProductsAjax()
    {
        $business_id = request()->session()->get('user.business_id');
    }
    /**
     * Shows expense report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getExpenseReport(Request $request)
    {
        if (!auth()->user()->can('expense_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $filters = $request->only(['category', 'location_id']);

        $date_range = $request->input('date_range');
        
        if (!empty($date_range)) {
            $date_range_array = explode('~', $date_range);
            $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
            $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));
        } else {
            $filters['start_date'] = \Carbon::now()->startOfMonth()->format('Y-m-d');
            $filters['end_date'] = \Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        $expenses = $this->transactionUtil->getExpenseReport($business_id, $filters);

        $values = [];
        $labels = [];
        foreach ($expenses as $expense) {
            $values[] = (float) $expense->total_expense;
            $labels[] = !empty($expense->category) ? $expense->category : __('report.others');
        }

        $chart = new CommonChart;
        $chart->labels($labels)
            ->title(__('report.expense_report'))
            ->dataset(__('report.total_expense'), 'column', $values);

        $categories = ExpenseCategory::where('business_id', $business_id)
                            ->pluck('name', 'id');
        
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.expense_report')
                    ->with(compact('chart', 'categories', 'business_locations', 'expenses'));
    }

    /**
     * Shows stock adjustment report
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockAdjustmentReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $query =  Transaction::where('business_id', $business_id)
                            ->where('type', 'stock_adjustment');

            //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('location_id', $permitted_locations);
            }

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }
            $location_id = $request->get('location_id');
            if (!empty($location_id)) {
                $query->where('location_id', $location_id);
            }

            $stock_adjustment_details = $query->select(
                DB::raw("SUM(final_total) as total_amount"),
                DB::raw("SUM(total_amount_recovered) as total_recovered"),
                DB::raw("SUM(IF(adjustment_type = 'normal', final_total, 0)) as total_normal"),
                DB::raw("SUM(IF(adjustment_type = 'abnormal', final_total, 0)) as total_abnormal")
            )->first();
            return $stock_adjustment_details;
        }
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.stock_adjustment_report')
                    ->with(compact('business_locations'));
    }

    /**
     * Shows register report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getRegisterReport(Request $request)
    {
        if (!auth()->user()->can('register_report.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $registers = CashRegister::join(
                'users as u',
                'u.id',
                '=',
                'cash_registers.user_id'
                )
                ->leftJoin(
                    'business_locations as bl',
                    'bl.id',
                    '=',
                    'cash_registers.location_id'
                )
                ->leftJoin('cash_register_transactions', 'cash_register_transactions.cash_register_id', '=', 'cash_registers.id')

                ->where('cash_registers.business_id', $business_id)
                ->where('cash_register_transactions.transaction_type', 'sell')
                ->select(
                    'cash_registers.*',
                    DB::raw(
                        "CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, ''), '<br>', COALESCE(u.email, '')) as user_name"
                    ),
                    'bl.name as location_name',
                    // DB::raw('SUM(cash_register_transactions.amount) as card_amount'),
                    DB::raw("SUM(IF(cash_register_transactions.pay_method='card', IF(transaction_type='sell', amount, 0), 0)) as card_amount"),
                    DB::raw("SUM(IF(pay_method='cash', IF(transaction_type='sell', amount, 0), 0)) as cash_amount"),

                )
                ->groupBy('cash_registers.id');
                
                if ($request->input('user_id')){
                    // dd($request->input('user_id'));
                    $registers->where('cash_registers.user_id', $request->input('user_id'));
                }
                if (!empty($request->input('status'))) {
                    $registers->where('cash_registers.status', $request->input('status'));
                }
                $start_date = $request->get('start_date');
                $end_date = $request->get('end_date');

                if (!empty($start_date) && !empty($end_date)) {
                    $registers->whereDate('cash_registers.created_at', '>=', $start_date)
                    ->whereDate('cash_registers.created_at', '<=', $end_date);
                }
                // dd($registers,$request->input('user_id'));
            return Datatables::of($registers)
                // ->editColumn('total_card_slips', function ($row) {
                //     if ($row->status == 'close') {
                //         return $row->total_card_slips;
                //     } else {
                //         return '';
                //     }
                // })
                ->editColumn('card_amount', function ($row) {
                    if ($row->status == 'close') {
                        return '<span class="display_currency sell_qty" data-currency_symbol = true data-orig-value="' . $row->card_amount . '">' . $row->card_amount . '</span>';

                    } else {
                        return '<span class="display_currency sell_qty" data-currency_symbol = true data-orig-value="' . $row->card_amount . '">' . $row->card_amount . '</span>';
                    }
                })
                ->editColumn('closed_at', function ($row) {
                    if ($row->status == 'close') {
                        return $this->productUtil->format_date($row->closed_at, true);
                    } else {
                        return '';
                    }
                })
                ->editColumn('created_at', function ($row) {
                    return $this->productUtil->format_date($row->created_at, true);
                })
                ->editColumn('cash_amount', function ($row) {
                    if ($row->status == 'close') {
                        return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->cash_amount . '">' . $row->cash_amount . '</span>';

                        // return '<span class="display_currency row_subtotal" data-currency_symbol="true">' .
                        // $row->cash_amount . '</span>';
                    } else {
                        return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->cash_amount . '">' . $row->cash_amount . '</span>';

                        // return '<span class="display_currency row_subtotal" data-currency_symbol="true">' .
                        // $row->cash_amount . '</span>';                    
                    }
                })
                ->editColumn('total_kamai', function ($row) {
                    $total_kamai = $row->cash_amount + $row->card_amount;
                    return '<span class="display_currency subtotal" data-currency_symbol = true data-orig-value="' . $total_kamai . '">' . $total_kamai . '</span>';

                    return $row->cash_amount + $row->card_amount;
                })
                ->addColumn('action', '<button type="button" data-href="{{action(\'CashRegisterController@show\', [$id])}}" class="btn btn-xs btn-info btn-modal" 
                    data-container=".view_register"><i class="fas fa-eye" aria-hidden="true"></i> @lang("messages.view")</button> @if($status != "close" && auth()->user()->can("close_cash_register"))<button type="button" data-href="{{action(\'CashRegisterController@getCloseRegister\', [$id])}}" class="btn btn-xs btn-danger btn-modal" 
                        data-container=".view_register"><i class="fas fa-window-close"></i> @lang("messages.close")</button> @endif')
                ->filterColumn('user_name', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, ''), '<br>', COALESCE(u.email, '')) like ?", ["%{$keyword}%"]);
                })
                ->rawColumns(['action', 'user_name', 'closing_amount','cash_amount','card_amount','total_kamai'])
                ->make(true);
        }

        $users = User::forDropdown($business_id, false);

        return view('report.register_report')
                    ->with(compact('users'));
    }

    /**
     * Shows sales representative report
     *
     * @return \Illuminate\Http\Response
     */
    public function getSalesRepresentativeReport(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $commission_agent = User::forDropdown($business_id, false);

        $users = User::allUsersEmployeeDropdown($business_id, false);
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.sales_representative')
                ->with(compact('users', 'business_locations'));
    }

    /**
     * Shows sales representative total expense
     *
     * @return json
     */
    public function getSalesRepresentativeTotalExpense(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->ajax()) {
            $business_id = $request->session()->get('user.business_id');

            $filters = $request->only(['expense_for', 'location_id', 'start_date', 'end_date']);

            $total_expense = $this->transactionUtil->getExpenseReport($business_id, $filters, 'total');

            return $total_expense;
        }
    }

    /**
     * Shows sales representative total sales
     *
     * @return json
     */
    public function getSalesRepresentativeTotalSell(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            $location_id = $request->get('location_id');
            $created_by = $request->get('created_by');

            $sell_details = $this->transactionUtil->getSellTotals($business_id, $start_date, $end_date, $location_id, $created_by);

            //Get Sell Return details
            $transaction_types = [
                'sell_return'
            ];
            $sell_return_details = $this->transactionUtil->getTransactionTotals(
                $business_id,
                $transaction_types,
                $start_date,
                $end_date,
                $location_id,
                $created_by
            );

            $total_sell_return = !empty($sell_return_details['total_sell_return_exc_tax']) ? $sell_return_details['total_sell_return_exc_tax'] : 0;
            $total_sell = $sell_details['total_sell_exc_tax'] - $total_sell_return;

            return [
                'total_sell_exc_tax' => $sell_details['total_sell_exc_tax'],
                'total_sell_return_exc_tax' => $total_sell_return,
                'total_sell' => $total_sell
            ];
        }
    }

    /**
     * Shows sales representative total commission
     *
     * @return json
     */
    public function getSalesRepresentativeTotalCommission(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            $location_id = $request->get('location_id');
            $commission_agent = $request->get('commission_agent');

            $sell_details = $this->transactionUtil->getTotalSellCommission($business_id, $start_date, $end_date, $location_id, $commission_agent);

            //Get Commision
            $commission_percentage = User::find($commission_agent)->cmmsn_percent;
            $total_commission = $commission_percentage * $sell_details['total_sales_with_commission'] / 100;

            return ['total_sales_with_commission' =>
                        $sell_details['total_sales_with_commission'],
                    'total_commission' => $total_commission,
                    'commission_percentage' => $commission_percentage
                ];
        }
    }

    /**
     * Shows product stock expiry report
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockExpiryReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        
        //TODO:: Need to display reference number and edit expiry date button

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = PurchaseLine::leftjoin(
                'transactions as t',
                'purchase_lines.transaction_id',
                '=',
                't.id'
            )
                            ->leftjoin(
                                'products as p',
                                'purchase_lines.product_id',
                                '=',
                                'p.id'
                            )
                            ->leftjoin(
                                'variations as v',
                                'purchase_lines.variation_id',
                                '=',
                                'v.id'
                            )
                            ->leftjoin(
                                'product_variations as pv',
                                'v.product_variation_id',
                                '=',
                                'pv.id'
                            )
                            ->leftjoin('business_locations as l', 't.location_id', '=', 'l.id')
                            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                            ->where('t.business_id', $business_id)
                            //->whereNotNull('p.expiry_period')
                            //->whereNotNull('p.expiry_period_type')
                            //->whereNotNull('exp_date')
                            ->where('p.enable_stock', 1);
            // ->whereRaw('purchase_lines.quantity > purchase_lines.quantity_sold + quantity_adjusted + quantity_returned');
                            
            $permitted_locations = auth()->user()->permitted_locations();

            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');
                $query->where('t.location_id', $location_id)
                        //If filter by location then hide products not available in that location
                        ->join('product_locations as pl', 'pl.product_id', '=', 'p.id')
                        ->where(function ($q) use ($location_id) {
                            $q->where('pl.location_id', $location_id);
                        });
            }

            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }
            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }
            if (!empty($request->input('exp_date_filter'))) {
                $query->whereDate('exp_date', '<=', $request->input('exp_date_filter'));
            }

            $only_mfg_products = request()->get('only_mfg_products', 0);
            if (!empty($only_mfg_products)) {
                $query->where('t.type', 'production_purchase');
            }

            $report = $query->select(
                'p.name as product',
                'p.sku',
                'p.type as product_type',
                'v.name as variation',
                'pv.name as product_variation',
                'l.name as location',
                'mfg_date',
                'exp_date',
                'u.short_name as unit',
                DB::raw("SUM(COALESCE(quantity, 0) - COALESCE(quantity_sold, 0) - COALESCE(quantity_adjusted, 0) - COALESCE(quantity_returned, 0)) as stock_left"),
                't.ref_no',
                't.id as transaction_id',
                'purchase_lines.id as purchase_line_id',
                'purchase_lines.lot_number'
            )
            ->having('stock_left', '>', 0)
            ->groupBy('purchase_lines.exp_date')
            ->groupBy('purchase_lines.lot_number');

            return Datatables::of($report)
                ->editColumn('name', function ($row) {
                    if ($row->product_type == 'variable') {
                        return $row->product . ' - ' .
                        $row->product_variation . ' - ' . $row->variation;
                    } else {
                        return $row->product;
                    }
                })
                ->editColumn('mfg_date', function ($row) {
                    if (!empty($row->mfg_date)) {
                        return $this->productUtil->format_date($row->mfg_date);
                    } else {
                        return '--';
                    }
                })
                // ->editColumn('exp_date', function ($row) {
                //     if (!empty($row->exp_date)) {
                //         $carbon_exp = \Carbon::createFromFormat('Y-m-d', $row->exp_date);
                //         $carbon_now = \Carbon::now();
                //         if ($carbon_now->diffInDays($carbon_exp, false) >= 0) {
                //             return $this->productUtil->format_date($row->exp_date) . '<br><small>( <span class="time-to-now">' . $row->exp_date . '</span> )</small>';
                //         } else {
                //             return $this->productUtil->format_date($row->exp_date) . ' &nbsp; <span class="label label-danger no-print">' . __('report.expired') . '</span><span class="print_section">' . __('report.expired') . '</span><br><small>( <span class="time-from-now">' . $row->exp_date . '</span> )</small>';
                //         }
                //     } else {
                //         return '--';
                //     }
                // })
                ->editColumn('ref_no', function ($row) {
                    return '<button type="button" data-href="' . action('PurchaseController@show', [$row->transaction_id])
                            . '" class="btn btn-link btn-modal" data-container=".view_modal"  >' . $row->ref_no . '</button>';
                })
                ->editColumn('stock_left', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency stock_left" data-currency_symbol=false data-orig-value="' . $row->stock_left . '" data-unit="' . $row->unit . '" >' . $row->stock_left . '</span> ' . $row->unit;
                })
                ->addColumn('edit', function ($row) {
                    $html =  '<button type="button" class="btn btn-primary btn-xs stock_expiry_edit_btn" data-transaction_id="' . $row->transaction_id . '" data-purchase_line_id="' . $row->purchase_line_id . '"> <i class="fa fa-edit"></i> ' . __("messages.edit") .
                    '</button>';

                    if (!empty($row->exp_date)) {
                        $carbon_exp = \Carbon::createFromFormat('Y-m-d', $row->exp_date);
                        $carbon_now = \Carbon::now();
                        if ($carbon_now->diffInDays($carbon_exp, false) < 0) {
                            $html .=  ' <button type="button" class="btn btn-warning btn-xs remove_from_stock_btn" data-href="' . action('StockAdjustmentController@removeExpiredStock', [$row->purchase_line_id]) . '"> <i class="fa fa-trash"></i> ' . __("lang_v1.remove_from_stock") .
                            '</button>';
                        }
                    }

                    return $html;
                })
                ->rawColumns(['exp_date', 'ref_no', 'edit', 'stock_left'])
                ->make(true);
        }

        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::forDropdown($business_id);
        $units = Unit::where('business_id', $business_id)
                            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $view_stock_filter = [
            \Carbon::now()->subDay()->format('Y-m-d') => __('report.expired'),
            \Carbon::now()->addWeek()->format('Y-m-d') => __('report.expiring_in_1_week'),
            \Carbon::now()->addDays(15)->format('Y-m-d') => __('report.expiring_in_15_days'),
            \Carbon::now()->addMonth()->format('Y-m-d') => __('report.expiring_in_1_month'),
            \Carbon::now()->addMonths(3)->format('Y-m-d') => __('report.expiring_in_3_months'),
            \Carbon::now()->addMonths(6)->format('Y-m-d') => __('report.expiring_in_6_months'),
            \Carbon::now()->addYear()->format('Y-m-d') => __('report.expiring_in_1_year')
        ];

        return view('report.stock_expiry_report')
                ->with(compact('categories', 'brands', 'units', 'business_locations', 'view_stock_filter'));
    }

    /**
     * Shows product stock expiry report
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockExpiryReportEditModal(Request $request, $purchase_line_id)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $purchase_line = PurchaseLine::join(
                'transactions as t',
                'purchase_lines.transaction_id',
                '=',
                't.id'
            )
                                ->join(
                                    'products as p',
                                    'purchase_lines.product_id',
                                    '=',
                                    'p.id'
                                )
                                ->where('purchase_lines.id', $purchase_line_id)
                                ->where('t.business_id', $business_id)
                                ->select(['purchase_lines.*', 'p.name', 't.ref_no'])
                                ->first();

            if (!empty($purchase_line)) {
                if (!empty($purchase_line->exp_date)) {
                    $purchase_line->exp_date = date('m/d/Y', strtotime($purchase_line->exp_date));
                }
            }

            return view('report.partials.stock_expiry_edit_modal')
                ->with(compact('purchase_line'));
        }
    }

    /**
     * Update product stock expiry report
     *
     * @return \Illuminate\Http\Response
     */
    public function updateStockExpiryReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            //Return the details in ajax call
            if ($request->ajax()) {
                DB::beginTransaction();

                $input = $request->only(['purchase_line_id', 'exp_date']);

                $purchase_line = PurchaseLine::join(
                    'transactions as t',
                    'purchase_lines.transaction_id',
                    '=',
                    't.id'
                )
                                    ->join(
                                        'products as p',
                                        'purchase_lines.product_id',
                                        '=',
                                        'p.id'
                                    )
                                    ->where('purchase_lines.id', $input['purchase_line_id'])
                                    ->where('t.business_id', $business_id)
                                    ->select(['purchase_lines.*', 'p.name', 't.ref_no'])
                                    ->first();

                if (!empty($purchase_line) && !empty($input['exp_date'])) {
                    $purchase_line->exp_date = $this->productUtil->uf_date($input['exp_date']);
                    $purchase_line->save();
                }

                DB::commit();

                $output = ['success' => 1,
                            'msg' => __('lang_v1.updated_succesfully')
                        ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __('messages.something_went_wrong')
                        ];
        }

        return $output;
    }

    /**
     * Shows product stock expiry report
     *
     * @return \Illuminate\Http\Response
     */
    public function getCustomerGroup(Request $request)
    {
        if (!auth()->user()->can('contacts_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $query = Transaction::leftjoin('customer_groups AS CG', 'transactions.customer_group_id', '=', 'CG.id')
                        ->where('transactions.business_id', $business_id)
                        ->where('transactions.type', 'sell')
                        ->where('transactions.status', 'final')
                        ->groupBy('transactions.customer_group_id')
                        ->select(DB::raw("SUM(final_total) as total_sell"), 'CG.name');

            $group_id = $request->get('customer_group_id', null);
            if (!empty($group_id)) {
                $query->where('transactions.customer_group_id', $group_id);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('transactions.location_id', $location_id);
            }

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }
            

            return Datatables::of($query)
                ->editColumn('total_sell', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->total_sell . '</span>';
                })
                ->rawColumns(['total_sell'])
                ->make(true);
        }

        $customer_group = CustomerGroup::forDropdown($business_id, false, true);
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.customer_group')
            ->with(compact('customer_group', 'business_locations'));
    }

    /**
     * Shows product purchase report
     *
     * @return \Illuminate\Http\Response
     */
    public function getproductPurchaseReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
            $query = PurchaseLine::join(
                'transactions as t',
                'purchase_lines.transaction_id',
                '=',
                't.id'
                    )
                    ->join(
                        'variations as v',
                        'purchase_lines.variation_id',
                        '=',
                        'v.id'
                    )
                    ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                    ->join('contacts as c', 't.contact_id', '=', 'c.id')
                    ->join('products as p', 'pv.product_id', '=', 'p.id')
                    ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                    ->where('t.business_id', $business_id)
                    ->where('t.type', 'purchase')
                    ->select(
                        'p.name as product_name',
                        'p.type as product_type',
                        'pv.name as product_variation',
                        'v.name as variation_name',
                        'v.sub_sku',
                        'c.name as supplier',
                        'c.supplier_business_name',
                        't.id as transaction_id',
                        't.ref_no',
                        't.transaction_date as transaction_date',
                        'purchase_lines.purchase_price_inc_tax as unit_purchase_price',
                        DB::raw('(purchase_lines.quantity - purchase_lines.quantity_returned) as purchase_qty'),
                        'purchase_lines.quantity_adjusted',
                        'u.short_name as unit',
                        DB::raw('((purchase_lines.quantity - purchase_lines.quantity_returned - purchase_lines.quantity_adjusted) * purchase_lines.purchase_price_inc_tax) as subtotal')
                    )
                    ->groupBy('purchase_lines.id');
            if (!empty($variation_id)) {
                $query->where('purchase_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $supplier_id = $request->get('supplier_id', null);
            if (!empty($supplier_id)) {
                $query->where('t.contact_id', $supplier_id);
            }

            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                 ->editColumn('ref_no', function ($row) {
                     return '<a data-href="' . action('PurchaseController@show', [$row->transaction_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->ref_no . '</a>';
                 })
                 ->editColumn('purchase_qty', function ($row) {
                     return '<span data-is_quantity="true" class="display_currency purchase_qty" data-currency_symbol=false data-orig-value="' . (float)$row->purchase_qty . '" data-unit="' . $row->unit . '" >' . (float) $row->purchase_qty . '</span> ' . $row->unit;
                 })
                //  ->editColumn('quantity_adjusted', function ($row) {
                //      return '<span data-is_quantity="true" class="display_currency quantity_adjusted" data-currency_symbol=false data-orig-value="' . (float)$row->quantity_adjusted . '" data-unit="' . $row->unit . '" >' . (float) $row->quantity_adjusted . '</span> ' . $row->unit;
                //  })
                 ->editColumn('subtotal', function ($row) {
                     return '<span class="display_currency row_subtotal" data-currency_symbol=true data-orig-value="' . $row->subtotal . '">' . $row->subtotal . '</span>';
                 })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('unit_purchase_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_purchase_price . '</span>';
                })
                ->editColumn('supplier', '@if(!empty($supplier_business_name)) {{$supplier_business_name}},<br>@endif {{$supplier}}')
                ->rawColumns(['ref_no', 'unit_purchase_price', 'subtotal', 'purchase_qty', 'quantity_adjusted', 'supplier'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $suppliers = Contact::suppliersDropdown($business_id);

        return view('report.product_purchase_report')
            ->with(compact('business_locations', 'suppliers'));
    }

    /**
     * Shows product purchase report
     *
     * @return \Illuminate\Http\Response
     */
    public function getproductSellReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
            $query = TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
            )
                ->join(
                    'variations as v',
                    'transaction_sell_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('contacts as c', 't.contact_id', '=', 'c.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                // ->where('t.type', 'sell')
                ->whereIN('t.type', ['sell','sell_return'])
                ->where('t.status', 'final')
                ->select(
                    'p.name as product_name',
                    'p.image as product_image',
                    'p.type as product_type',
                    'pv.name as product_variation',
                    'v.name as variation_name',
                    'v.sub_sku',
                    'c.name as customer',
                    'c.supplier_business_name',
                    'c.contact_id',
                    't.id as transaction_id',
                    't.invoice_no',
                    't.transaction_date as transaction_date',
                    'v.sell_price_inc_tax as unit_price',
                    // 'transaction_sell_lines.unit_price_before_discount as unit_price',
                    'transaction_sell_lines.unit_price_inc_tax as unit_sale_price',
                    // DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as sell_qty'),
                    DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as sell_qty'),

                    'transaction_sell_lines.line_discount_type as discount_type',
                    // 'transaction_sell_lines.line_discount_amount as discount_amount',
                    'transaction_sell_lines.item_tax',
                    'tax_rates.name as tax',
                    'u.short_name as unit',
                    DB::raw('((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal'),
                    DB::raw('CASE WHEN transaction_sell_lines.line_discount_type = "percentage" THEN (v.sell_price_inc_tax * transaction_sell_lines.line_discount_amount / 100) ELSE transaction_sell_lines.line_discount_amount END AS discount_amount')
                )
                // ->get();
                // dd($query);
                ->groupBy('transaction_sell_lines.id');

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->where('t.transaction_date', '>=', $start_date)
                    ->where('t.transaction_date', '<=', $end_date);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            return Datatables::of($query)
                ->editColumn('product_image', function ($row) {
                    $basePath = config('app.url'); // Use your base URL, e.g., http://127.0.0.1:8000
                    
                    if (!empty($row->product_image)) {
                        $imagePath = asset('uploads/img/' . $row->product_image);
                    } else {
                        $imagePath = asset('img/default.png');
                    }
                
                    return '<div style="display: flex; justify-content: center; align-items: center;"><img src="' . $imagePath . '" alt="Product image" class="product-thumbnail-small"></div>';
                })
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                 ->editColumn('invoice_no', function ($row) {
                     return '<a data-href="' . action('SellController@show', [$row->transaction_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->invoice_no . '</a>';
                 })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('unit_sale_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_sale_price . '</span>';
                })
                ->editColumn('sell_qty', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->sell_qty . '" data-unit="' . $row->unit . '" >' . (float) $row->sell_qty . '</span> ' .$row->unit;
                })
                 ->editColumn('subtotal', function ($row) {
                     return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->subtotal . '">' . $row->subtotal . '</span>';
                 })
                ->editColumn('unit_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_price . '</span>';
                })
                ->editColumn('discount_amount', '
                    @if($discount_type == "percentage")
                        {{@number_format($discount_amount)}}
                    @elseif($discount_type == "fixed")
                        {{@number_format($discount_amount)}}
                    @endif
                    ')
                ->editColumn('tax', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>'.
                            $row->item_tax.
                       '</span>'.'<br>'.'<span class="tax" data-orig-value="'.(float)$row->item_tax.'" data-unit="'.$row->tax.'"><small>('.$row->tax.')</small></span>';
                })
                ->editColumn('customer', '@if(!empty($supplier_business_name)) {{$supplier_business_name}},<br>@endif {{$customer}}')
                ->rawColumns(['product_image','invoice_no', 'unit_sale_price', 'subtotal', 'sell_qty', 'discount_amount', 'unit_price', 'tax', 'customer'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id);

        return view('report.product_sell_report')
            ->with(compact('business_locations', 'customers'));
    }

    /**
     * Shows product purchase report with purchase details
     *
     * @return \Illuminate\Http\Response
     */
    public function getproductSellReportWithPurchase(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
            $query = TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
            )
                ->join(
                    'transaction_sell_lines_purchase_lines as tspl',
                    'transaction_sell_lines.id',
                    '=',
                    'tspl.sell_line_id'
                )
                ->join(
                    'purchase_lines as pl',
                    'tspl.purchase_line_id',
                    '=',
                    'pl.id'
                )
                ->join(
                    'transactions as purchase',
                    'pl.transaction_id',
                    '=',
                    'purchase.id'
                )
                ->leftjoin('contacts as supplier', 'purchase.contact_id', '=', 'supplier.id')
                ->join(
                    'variations as v',
                    'transaction_sell_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->leftjoin('contacts as c', 't.contact_id', '=', 'c.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                // ->where('t.type', 'sell')
                ->whereIN('t.type', ['sell','sell_return'])

                ->where('t.status', 'final')
                ->select(
                    'p.name as product_name',
                    'p.type as product_type',
                    'pv.name as product_variation',
                    'v.name as variation_name',
                    'v.sub_sku',
                    'c.name as customer',
                    'c.supplier_business_name',
                    't.id as transaction_id',
                    't.invoice_no',
                    't.transaction_date as transaction_date',
                    // 'tspl.quantity as purchase_quantity',
                    DB::raw('tspl.quantity - tspl.qty_returned as purchase_quantity'),

                    'u.short_name as unit',
                    'supplier.name as supplier_name',
                    'purchase.ref_no as ref_no',
                    'purchase.type as purchase_type',
                    'pl.lot_number'
                );

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->where('t.transaction_date', '>=', $start_date)
                    ->where('t.transaction_date', '<=', $end_date);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                 ->editColumn('invoice_no', function ($row) {
                     return '<a data-href="' . action('SellController@show', [$row->transaction_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->invoice_no . '</a>';
                 })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('unit_sale_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_sale_price . '</span>';
                })
                ->editColumn('purchase_quantity', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency purchase_quantity" data-currency_symbol=false data-orig-value="' . (float)$row->purchase_quantity . '" data-unit="' . $row->unit . '" >' . (float) $row->purchase_quantity . '</span> ' .$row->unit;
                })
                ->editColumn('ref_no', '
                    @if($purchase_type == "opening_stock")
                        <i><small class="help-block">(@lang("lang_v1.opening_stock"))</small></i>
                    @else
                        {{$ref_no}}
                    @endif
                    ')
                ->editColumn('customer', '@if(!empty($supplier_business_name)) {{$supplier_business_name}},<br>@endif {{$customer}}')
                ->rawColumns(['invoice_no', 'purchase_quantity', 'ref_no', 'customer'])
                ->make(true);
        }
    }

    /**
     * Shows product lot report
     *
     * @return \Illuminate\Http\Response
     */
    public function getLotReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = Product::where('products.business_id', $business_id)
                    ->leftjoin('units', 'products.unit_id', '=', 'units.id')
                    ->join('variations as v', 'products.id', '=', 'v.product_id')
                    ->join('purchase_lines as pl', 'v.id', '=', 'pl.variation_id')
                    ->leftjoin(
                        'transaction_sell_lines_purchase_lines as tspl',
                        'pl.id',
                        '=',
                        'tspl.purchase_line_id'
                    )
                    ->join('transactions as t', 'pl.transaction_id', '=', 't.id');

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = 'WHERE ';

            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter = " LEFT JOIN transactions as t2 on pls.transaction_id=t2.id WHERE t2.location_id IN ($locations_imploded) AND ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');
                $query->where('t.location_id', $location_id)
                    //If filter by location then hide products not available in that location
                    ->ForLocation($location_id);

                $location_filter = "LEFT JOIN transactions as t2 on pls.transaction_id=t2.id WHERE t2.location_id=$location_id AND ";
            }

            if (!empty($request->input('category_id'))) {
                $query->where('products.category_id', $request->input('category_id'));
            }

            if (!empty($request->input('sub_category_id'))) {
                $query->where('products.sub_category_id', $request->input('sub_category_id'));
            }

            if (!empty($request->input('brand_id'))) {
                $query->where('products.brand_id', $request->input('brand_id'));
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('products.unit_id', $request->input('unit_id'));
            }

            $only_mfg_products = request()->get('only_mfg_products', 0);
            if (!empty($only_mfg_products)) {
                $query->where('t.type', 'production_purchase');
            }

            $products = $query->select(
                'products.name as product',
                'v.name as variation_name',
                'sub_sku',
                'pl.lot_number',
                'pl.exp_date as exp_date',
                DB::raw("( COALESCE((SELECT SUM(quantity - quantity_returned) from purchase_lines as pls $location_filter variation_id = v.id AND lot_number = pl.lot_number), 0) - 
                    SUM(COALESCE((tspl.quantity - tspl.qty_returned), 0))) as stock"),
                // DB::raw("(SELECT SUM(IF(transactions.type='sell', TSL.quantity, -1* TPL.quantity) ) FROM transactions
                //         LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id

                //         LEFT JOIN purchase_lines AS TPL ON transactions.id=TPL.transaction_id

                //         WHERE transactions.status='final' AND transactions.type IN ('sell', 'sell_return') $location_filter
                //         AND (TSL.product_id=products.id OR TPL.product_id=products.id)) as total_sold"),

                DB::raw("COALESCE(SUM(IF(tspl.sell_line_id IS NULL, 0, (tspl.quantity - tspl.qty_returned)) ), 0) as total_sold"),
                DB::raw("COALESCE(SUM(IF(tspl.stock_adjustment_line_id IS NULL, 0, tspl.quantity ) ), 0) as total_adjusted"),
                'products.type',
                'units.short_name as unit'
            )
            ->whereNotNull('pl.lot_number')
            ->groupBy('v.id')
            ->groupBy('pl.lot_number');

            return Datatables::of($products)
                ->editColumn('stock', function ($row) {
                    $stock = $row->stock ? $row->stock : 0 ;
                    return '<span data-is_quantity="true" class="display_currency total_stock" data-currency_symbol=false data-orig-value="' . (float)$stock . '" data-unit="' . $row->unit . '" >' . (float)$stock . '</span> ' . $row->unit;
                })
                ->editColumn('product', function ($row) {
                    if ($row->variation_name != 'DUMMY') {
                        return $row->product . ' (' . $row->variation_name . ')';
                    } else {
                        return $row->product;
                    }
                })
                ->editColumn('total_sold', function ($row) {
                    if ($row->total_sold) {
                        return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . (float)$row->total_sold . '" data-unit="' . $row->unit . '" >' . (float)$row->total_sold . '</span> ' . $row->unit;
                    } else {
                        return '0' . ' ' . $row->unit;
                    }
                })
                ->editColumn('total_adjusted', function ($row) {
                    if ($row->total_adjusted) {
                        return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false data-orig-value="' . (float)$row->total_adjusted . '" data-unit="' . $row->unit . '" >' . (float)$row->total_adjusted . '</span> ' . $row->unit;
                    } else {
                        return '0' . ' ' . $row->unit;
                    }
                })
                ->editColumn('exp_date', function ($row) {
                    if (!empty($row->exp_date)) {
                        $carbon_exp = \Carbon::createFromFormat('Y-m-d', $row->exp_date);
                        $carbon_now = \Carbon::now();
                        if ($carbon_now->diffInDays($carbon_exp, false) >= 0) {
                            return $this->productUtil->format_date($row->exp_date) . '<br><small>( <span class="time-to-now">' . $row->exp_date . '</span> )</small>';
                        } else {
                            return $this->productUtil->format_date($row->exp_date) . ' &nbsp; <span class="label label-danger no-print">' . __('report.expired') . '</span><span class="print_section">' . __('report.expired') . '</span><br><small>( <span class="time-from-now">' . $row->exp_date . '</span> )</small>';
                        }
                    } else {
                        return '--';
                    }
                })
                ->removeColumn('unit')
                ->removeColumn('id')
                ->removeColumn('variation_name')
                ->rawColumns(['exp_date', 'stock', 'total_sold', 'total_adjusted'])
                ->make(true);
        }

        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::forDropdown($business_id);
        $units = Unit::where('business_id', $business_id)
                            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.lot_report')
            ->with(compact('categories', 'brands', 'units', 'business_locations'));
    }

    /**
     * Shows purchase payment report
     *
     * @return \Illuminate\Http\Response
     */
    public function purchasePaymentReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $supplier_id = $request->get('supplier_id', null);
            $contact_filter1 = !empty($supplier_id) ? "AND t.contact_id=$supplier_id" : '';
            $contact_filter2 = !empty($supplier_id) ? "AND transactions.contact_id=$supplier_id" : '';

            $location_id = $request->get('location_id', null);

            $parent_payment_query_part = empty($location_id) ? "AND transaction_payments.parent_id IS NULL" : "";

            $query = TransactionPayment::leftjoin('transactions as t', function ($join) use ($business_id) {
                $join->on('transaction_payments.transaction_id', '=', 't.id')
                    ->where('t.business_id', $business_id)
                    ->whereIn('t.type', ['purchase', 'opening_balance']);
            })
                ->where('transaction_payments.business_id', $business_id)
                ->where(function ($q) use ($business_id, $contact_filter1, $contact_filter2, $parent_payment_query_part) {
                    $q->whereRaw("(transaction_payments.transaction_id IS NOT NULL AND t.type IN ('purchase', 'opening_balance')  $parent_payment_query_part $contact_filter1)")
                        ->orWhereRaw("EXISTS(SELECT * FROM transaction_payments as tp JOIN transactions ON tp.transaction_id = transactions.id WHERE transactions.type IN ('purchase', 'opening_balance') AND transactions.business_id = $business_id AND tp.parent_id=transaction_payments.id $contact_filter2)");
                })
                              
                ->select(
                    DB::raw("IF(transaction_payments.transaction_id IS NULL, 
                                (SELECT c.name FROM transactions as ts
                                JOIN contacts as c ON ts.contact_id=c.id 
                                WHERE ts.id=(
                                        SELECT tps.transaction_id FROM transaction_payments as tps
                                        WHERE tps.parent_id=transaction_payments.id LIMIT 1
                                    )
                                ),
                                (SELECT CONCAT(COALESCE(c.supplier_business_name, ''), '<br>', c.name) FROM transactions as ts JOIN
                                    contacts as c ON ts.contact_id=c.id
                                    WHERE ts.id=t.id 
                                )
                            ) as supplier"),
                    'transaction_payments.amount',
                    'method',
                    'paid_on',
                    'transaction_payments.payment_ref_no',
                    'transaction_payments.document',
                    't.ref_no',
                    't.id as transaction_id',
                    'cheque_number',
                    'card_transaction_number',
                    'bank_account_number',
                    'transaction_no',
                    'transaction_payments.id as DT_RowId'
                )
                ->groupBy('transaction_payments.id');

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(paid_on)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);
            
            return Datatables::of($query)
                 ->editColumn('ref_no', function ($row) {
                     if (!empty($row->ref_no)) {
                         return '<a data-href="' . action('PurchaseController@show', [$row->transaction_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->ref_no . '</a>';
                     } else {
                         return '';
                     }
                 })
                ->editColumn('paid_on', '{{@format_datetime($paid_on)}}')
                ->editColumn('method', function ($row) use ($payment_types) {
                    $method = !empty($payment_types[$row->method]) ? $payment_types[$row->method] : '';
                    if ($row->method == 'cheque') {
                        $method .= '<br>(' . __('lang_v1.cheque_no') . ': ' . $row->cheque_number . ')';
                    } elseif ($row->method == 'card') {
                        $method .= '<br>(' . __('lang_v1.card_transaction_no') . ': ' . $row->card_transaction_number . ')';
                    } elseif ($row->method == 'bank_transfer') {
                        $method .= '<br>(' . __('lang_v1.bank_account_no') . ': ' . $row->bank_account_number . ')';
                    } elseif ($row->method == 'custom_pay_1') {
                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    } elseif ($row->method == 'custom_pay_2') {
                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    } elseif ($row->method == 'custom_pay_3') {
                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    }
                    return $method;
                })
                ->editColumn('amount', function ($row) {
                    return '<span class="display_currency paid-amount" data-currency_symbol = true data-orig-value="' . $row->amount . '">' . $row->amount . '</span>';
                })
                ->addColumn('action', '<button type="button" class="btn btn-primary btn-xs view_payment" data-href="{{ action("TransactionPaymentController@viewPayment", [$DT_RowId]) }}">@lang("messages.view")
                    </button> @if(!empty($document))<a href="{{asset("/uploads/documents/" . $document)}}" class="btn btn-success btn-xs" download=""><i class="fa fa-download"></i> @lang("purchase.download_document")</a>@endif')
                ->rawColumns(['ref_no', 'amount', 'method', 'action', 'supplier'])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id);
        $suppliers = Contact::suppliersDropdown($business_id, false);

        return view('report.purchase_payment_report')
            ->with(compact('business_locations', 'suppliers'));
    }

    /**
     * Shows sell payment report
     *
     * @return \Illuminate\Http\Response
     */
    public function sellPaymentReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);
        if ($request->ajax()) {
            $customer_id = $request->get('supplier_id', null);
            $contact_filter1 = !empty($customer_id) ? "AND t.contact_id=$customer_id" : '';
            $contact_filter2 = !empty($customer_id) ? "AND transactions.contact_id=$customer_id" : '';

            $location_id = $request->get('location_id', null);
            $parent_payment_query_part = empty($location_id) ? "AND transaction_payments.parent_id IS NULL" : "";

            $query = TransactionPayment::leftjoin('transactions as t', function ($join) use ($business_id) {
                $join->on('transaction_payments.transaction_id', '=', 't.id')
                    ->where('t.business_id', $business_id)
                    ->whereIn('t.type', ['sell', 'opening_balance']);
            })
                ->leftjoin('contacts as c', 't.contact_id', '=', 'c.id')
                ->leftjoin('customer_groups AS CG', 'c.customer_group_id', '=', 'CG.id')
                ->where('transaction_payments.business_id', $business_id)
                ->where(function ($q) use ($business_id, $contact_filter1, $contact_filter2, $parent_payment_query_part) {
                    $q->whereRaw("(transaction_payments.transaction_id IS NOT NULL AND t.type IN ('sell', 'opening_balance') $parent_payment_query_part $contact_filter1)")
                        ->orWhereRaw("EXISTS(SELECT * FROM transaction_payments as tp JOIN transactions ON tp.transaction_id = transactions.id WHERE transactions.type IN ('sell', 'opening_balance') AND transactions.business_id = $business_id AND tp.parent_id=transaction_payments.id $contact_filter2)");
                })
                ->select(
                    DB::raw("IF(transaction_payments.transaction_id IS NULL, 
                                (SELECT c.name FROM transactions as ts
                                JOIN contacts as c ON ts.contact_id=c.id 
                                WHERE ts.id=(
                                        SELECT tps.transaction_id FROM transaction_payments as tps
                                        WHERE tps.parent_id=transaction_payments.id LIMIT 1
                                    )
                                ),
                                (SELECT CONCAT(COALESCE(c.supplier_business_name, ''), '<br>', c.name) FROM transactions as ts JOIN
                                    contacts as c ON ts.contact_id=c.id
                                    WHERE ts.id=t.id 
                                )
                            ) as customer"),
                    'transaction_payments.amount',
                    'transaction_payments.is_return',
                    'method',
                    'paid_on',
                    'transaction_payments.payment_ref_no',
                    'transaction_payments.document',
                    'transaction_payments.transaction_no',
                    't.invoice_no',
                    't.id as transaction_id',
                    'cheque_number',
                    'card_transaction_number',
                    'bank_account_number',
                    'transaction_payments.id as DT_RowId',
                    'CG.name as customer_group'
                )
                ->groupBy('transaction_payments.id');

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            // dd($start_date,$end_date);
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(paid_on)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }
            
            if (!empty($request->get('customer_group_id'))) {
                $query->where('CG.id', $request->get('customer_group_id'));
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            if (!empty($request->get('payment_types'))) {
                $query->where('transaction_payments.method', $request->get('payment_types'));
            }

            return Datatables::of($query)
                 ->editColumn('invoice_no', function ($row) {
                     if (!empty($row->transaction_id)) {
                         return '<a data-href="' . action('SellController@show', [$row->transaction_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->invoice_no . '</a>';
                     } else {
                         return '';
                     }
                 })
                ->editColumn('paid_on', '{{@format_datetime($paid_on)}}')
                ->editColumn('method', function ($row) use ($payment_types) {
                    $method = !empty($payment_types[$row->method]) ? $payment_types[$row->method] : '';
                    if ($row->method == 'cheque') {
                        $method .= '<br>(' . __('lang_v1.cheque_no') . ': ' . $row->cheque_number . ')';
                    } elseif ($row->method == 'card') {
                        $method .= '<br>(' . __('lang_v1.card_transaction_no') . ': ' . $row->card_transaction_number . ')';
                    } elseif ($row->method == 'bank_transfer') {
                        $method .= '<br>(' . __('lang_v1.bank_account_no') . ': ' . $row->bank_account_number . ')';
                    } elseif ($row->method == 'custom_pay_1') {
                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    } elseif ($row->method == 'custom_pay_2') {
                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    } elseif ($row->method == 'custom_pay_3') {
                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    }
                    if ($row->is_return == 1) {
                        $method .= '<br><small>(' . __('lang_v1.change_return') . ')</small>';
                    }
                    return $method;
                })
                ->editColumn('amount', function ($row) {
                    $amount = $row->is_return == 1 ? -1 * $row->amount : $row->amount;
                    return '<span class="display_currency paid-amount" data-orig-value="' . $amount . '" data-currency_symbol = true>' . $amount . '</span>';
                })
                ->addColumn('action', '<button type="button" class="btn btn-primary btn-xs view_payment" data-href="{{ action("TransactionPaymentController@viewPayment", [$DT_RowId]) }}">@lang("messages.view")
                    </button> @if(!empty($document))<a href="{{asset("/uploads/documents/" . $document)}}" class="btn btn-success btn-xs" download=""><i class="fa fa-download"></i> @lang("purchase.download_document")</a>@endif')
                ->rawColumns(['invoice_no', 'amount', 'method', 'action', 'customer'])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id, false);
        $customer_groups = CustomerGroup::forDropdown($business_id, false, true);

        return view('report.sell_payment_report')
            ->with(compact('business_locations', 'customers', 'payment_types', 'customer_groups'));
    }


    /**
     * Shows tables report
     *
     * @return \Illuminate\Http\Response
     */
    public function getTableReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $query = ResTable::leftjoin('transactions AS T', 'T.res_table_id', '=', 'res_tables.id')
                        ->where('T.business_id', $business_id)
                        ->where('T.type', 'sell')
                        ->where('T.status', 'final')
                        ->groupBy('res_tables.id')
                        ->select(DB::raw("SUM(final_total) as total_sell"), 'res_tables.name as table');

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('T.location_id', $location_id);
            }

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }

            return Datatables::of($query)
                ->editColumn('total_sell', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $row->total_sell . '</span>';
                })
                ->rawColumns(['total_sell'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.table_report')
            ->with(compact('business_locations'));
    }

    /**
     * Shows service staff report
     *
     * @return \Illuminate\Http\Response
     */
    public function getServiceStaffReport(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        $waiters = $this->transactionUtil->serviceStaffDropdown($business_id);

        return view('report.service_staff_report')
            ->with(compact('business_locations', 'waiters'));
    }

    /**
     * Shows product sell report grouped by date
     *
     * @return \Illuminate\Http\Response
     */
    public function getproductSellGroupedReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id', null);

        $vld_str = '';
        if (!empty($location_id)) {
            $vld_str = "AND vld.location_id=$location_id";
        }
        // dd($vld_str, $request);

        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
            $query = TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
            )
                ->join(
                    'variations as v',
                    'transaction_sell_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->join('categories','p.category_id','categories.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->select(
                    // 'p.image as product_image'.
                    'p.name as product_name',
                    'p.image',
                    'p.enable_stock',
                    'p.type as product_type',
                    'categories.name as category_name',
                    'pv.name as product_variation',
                    'v.name as variation_name',
                    'v.dpp_inc_tax as purchase_price',
                    'v.updated_at as buying_date',
                    'v.sub_sku',
                    't.id as transaction_id',
                    't.transaction_date as transaction_date',
                    DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),
                    DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=v.id $vld_str) as current_stock"),
                    DB::raw('SUM(transaction_sell_lines.quantity) as total_qty_sold'),
                    DB::raw('SUM(transaction_sell_lines.quantity_returned) as total_qty_returned'),
                    'u.short_name as unit',
                    DB::raw('SUM((transaction_sell_lines.quantity) * transaction_sell_lines.unit_price_inc_tax) as subtotal'),
                    DB::raw("SUM(
                        IF(
                            t.type = 'sell' AND t.status = 'final' AND transaction_sell_lines.line_discount_amount > 0,
                            IF(
                                transaction_sell_lines.line_discount_type = 'percentage',
                                COALESCE((COALESCE(transaction_sell_lines.unit_price_inc_tax, 0) / (1 - (COALESCE(transaction_sell_lines.line_discount_amount, 0) / 100)) - transaction_sell_lines.unit_price_inc_tax ) * transaction_sell_lines.quantity, 0),
                                COALESCE(transaction_sell_lines.line_discount_amount * transaction_sell_lines.quantity, 0)
                            ),
                            0
                        )
                    ) as total_sell_discount")
                )
                ->groupBy('v.id')
                ->groupBy('formated_date');

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            // dd($start_date,$end_date);
            if (!empty($start_date) && !empty($end_date)) {
                $query->where('t.transaction_date', '>=', $start_date)
                    ->where('t.transaction_date', '<=', $end_date);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            return Datatables::of($query)
                ->editColumn('product_image', function ($row) {
                    $basePath = config('app.url'); // Use your base URL, e.g., http://127.0.0.1:8000
                    
                    $imageDirectory = public_path('uploads/img/');
                    $imagePath = $imageDirectory . $row->image;
                
                    if (!empty($row->image) && file_exists($imagePath)) {
                        $imagePath = asset('uploads/img/' . $row->image);
                    } else {
                        $imagePath = asset('img/default.png');
                    }                
                    return '<div style="display: flex; justify-content: center; align-items: center;"><img src="' . $imagePath . '" alt="" class="product-thumbnail-small"></div>';
                })
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    
                    $first_hyphen_position = strpos($product_name, '-');
                
                    $second_hyphen_position = strpos($product_name, '-', $first_hyphen_position + 1);
                
                    if ($second_hyphen_position !== false) {
                        $product_color = substr($product_name, $first_hyphen_position + 1, $second_hyphen_position - $first_hyphen_position - 1);
                    } else {
                        $product_color = substr($product_name, $first_hyphen_position + 1);
                    }
                
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }
                
                    return $product_name;
                })
                
                ->addColumn('stock', function ($row) use ($vld_str, $business_id) {
                    $product_name = $row->product_name;
                    
                    $second_hyphen_position = strpos($product_name, '-', strpos($product_name, '-') + 1);
                
                    $product_name_without_size = $second_hyphen_position !== false ? substr($product_name, 0, $second_hyphen_position) : $product_name;
                
                    if ($row->product_type == 'variable') {
                        $product_name_without_size .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }
                
                    $stock = $this->productUtil->calculateStock($product_name_without_size, $row->variation_id, $vld_str, $business_id);
                
                    $stocks = $stock->pluck('stock');

                    if ($stocks->isNotEmpty()) {
                        // return $stocks->sum();
                        return '<span data-is_quantity="true" class="display_currency stock_by_color" data-currency_symbol=false data-orig-value="' . (float)$stocks->sum() . '" data-unit="' . $row->unit . '" >' . (float) $stocks->sum() . '</span> ' .$row->unit;

                    } else {
                        return 0;
                    }
                })
                                
                
                
                // ->editColumn('product_name', function ($row) {
                //     $product_name = $row->product_name;
                //     if ($row->product_type == 'variable') {
                //         $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                //     }

                //     return $product_name;
                // })
                ->editColumn('transaction_date', '{{@format_date($formated_date)}}')
                ->editColumn('buying_date', '{{@format_date($formated_date)}}')
                ->editColumn('total_qty_sold', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->total_qty_sold . '" data-unit="' . $row->unit . '" >' . (float) $row->total_qty_sold . '</span> ' .$row->unit;
                })
                ->editColumn('total_qty_returned', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency ret_qty" data-currency_symbol=false data-orig-value="' . (float)$row->total_qty_returned . '" data-unit="' . $row->unit . '" >' . (float) $row->total_qty_returned . '</span> ' .$row->unit;
                })
                ->editColumn('current_stock', function ($row) {
                    if ($row->enable_stock) {
                        return '<span data-is_quantity="true" class="display_currency current_stock" data-currency_symbol=false data-orig-value="' . (float)$row->current_stock . '" data-unit="' . $row->unit . '" >' . (float) $row->current_stock . '</span> ' .$row->unit;
                    } else {
                        return '';
                    }
                })
                ->editColumn('discount_amount', function ($row) {
                    return '<span class="display_currency discount_amount" data-currency_symbol = true data-orig-value="' . $row->total_sell_discount . '">' . $row->total_sell_discount . '</span>';
                })
                 ->editColumn('subtotal', function ($row) {
                     return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->subtotal . '">' . $row->subtotal . '</span>';
                 })
                 ->editColumn('buy_price', function ($row) {
                    $buy_price = $row->purchase_price *  $row->total_qty_sold;
                     return '<span class="display_currency buy_price" data-currency_symbol = true data-orig-value="' . $buy_price . '">' . $buy_price . '</span>';
                 })
                 ->editColumn('profit', function ($row) {
                    $buy_price = $row->purchase_price *  $row->total_qty_sold;
                    $sell_price = $row->subtotal;
                    $profit = $sell_price - $buy_price;
                     return '<span class="display_currency profit" data-currency_symbol = true data-orig-value="' . $profit . '">' . $profit . '</span>';
                 })
                 ->editColumn('image', function ($row) {
                    return '<div style="display: flex;"><img src="' . $row->image_url . '" alt="Product image" class="product-thumbnail-small"></div>';
                })
                
                ->rawColumns(['current_stock', 'subtotal', 'total_qty_sold','discount_amount','buy_price','total_qty_returned','profit','product_image','stock'])
                ->make(true);
        }
    }

    /**
     * Shows product stock details and allows to adjust mismatch
     *
     * @return \Illuminate\Http\Response
     */
    public function productStockDetails()
    {
        if (!auth()->user()->can('report.stock_details')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $stock_details = [];
        $location = null;
        $total_stock_calculated = 0;
        if (!empty(request()->input('location_id'))) {
            $variation_id = request()->get('variation_id', null);
            $location_id = request()->input('location_id');

            $location = BusinessLocation::where('business_id', $business_id)
                                        ->where('id', $location_id)
                                        ->first();

            $query = Variation::leftjoin('products as p', 'p.id', '=', 'variations.product_id')
                    ->leftjoin('units', 'p.unit_id', '=', 'units.id')
                    ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                    ->leftjoin('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                    ->where('p.business_id', $business_id)
                    ->where('vld.location_id', $location_id);
            if (!is_null($variation_id)) {
                $query->where('variations.id', $variation_id);
            }

            $stock_details = $query->select(
                DB::raw("(SELECT SUM(COALESCE(TSL.quantity, 0)) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' AND transactions.location_id=$location_id 
                        AND TSL.variation_id=variations.id) as total_sold"),
                DB::raw("(SELECT SUM(COALESCE(TSL.quantity_returned, 0)) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' AND transactions.location_id=$location_id 
                        AND TSL.variation_id=variations.id) as total_sell_return"),
                DB::raw("(SELECT SUM(COALESCE(TSL.quantity,0)) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' AND transactions.location_id=$location_id 
                        AND TSL.variation_id=variations.id) as total_sell_transfered"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity,0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='purchase_transfer' AND transactions.location_id=$location_id 
                        AND PL.variation_id=variations.id) as total_purchase_transfered"),
                DB::raw("(SELECT SUM(COALESCE(SAL.quantity, 0)) FROM transactions 
                        LEFT JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.type='stock_adjustment' AND transactions.location_id=$location_id 
                        AND SAL.variation_id=variations.id) as total_adjusted"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity, 0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='purchase' AND transactions.location_id=$location_id
                        AND PL.variation_id=variations.id) as total_purchased"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity_returned, 0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='purchase' AND transactions.location_id=$location_id
                        AND PL.variation_id=variations.id) as total_purchase_return"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity, 0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.type='opening_stock' AND transactions.status='received' AND transactions.location_id=$location_id
                        AND PL.variation_id=variations.id) as total_opening_stock"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity, 0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='production_purchase' AND transactions.location_id=$location_id
                        AND PL.variation_id=variations.id) as total_manufactured"),
                DB::raw("(SELECT SUM(COALESCE(TSL.quantity, 0)) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='production_sell' AND transactions.location_id=$location_id 
                        AND TSL.variation_id=variations.id) as total_ingredients_used"),
                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sub_sku',
                'p.name as product',
                'p.id as product_id',
                'p.type',
                'p.sku as sku',
                'units.short_name as unit',
                'p.enable_stock as enable_stock',
                'variations.sell_price_inc_tax as unit_price',
                'pv.name as product_variation',
                'variations.name as variation_name',
                'variations.id as variation_id'
            )
            ->groupBy('variations.id')
            ->get();

            foreach ($stock_details as $index => $row) {
                $total_sold = $row->total_sold ?: 0;
                $total_sell_return = $row->total_sell_return ?: 0;
                $total_sell_transfered = $row->total_sell_transfered ?: 0;

                $total_purchase_transfered = $row->total_purchase_transfered ?: 0;
                $total_adjusted = $row->total_adjusted ?: 0;
                $total_purchased = $row->total_purchased ?: 0;
                $total_purchase_return = $row->total_purchase_return ?: 0;
                $total_opening_stock = $row->total_opening_stock ?: 0;
                $total_manufactured = $row->total_manufactured ?: 0;
                $total_ingredients_used = $row->total_ingredients_used ?: 0;

                $total_stock_calculated = $total_opening_stock + $total_purchased + $total_purchase_transfered + $total_sell_return + $total_manufactured
                - ($total_sold + $total_sell_transfered + $total_adjusted + $total_purchase_return + $total_ingredients_used);

                $stock_details[$index]->total_stock_calculated = $total_stock_calculated;
            }
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        return view('report.product_stock_details')
            ->with(compact('stock_details', 'business_locations', 'location'));
    }

    /**
     * Adjusts stock availability mismatch if found
     *
     * @return \Illuminate\Http\Response
     */
    public function adjustProductStock()
    {
        if (!auth()->user()->can('report.stock_details')) {
            abort(403, 'Unauthorized action.');
        }

        if (!empty(request()->input('variation_id'))
            && !empty(request()->input('location_id'))
            && request()->has('stock')) {
            $business_id = request()->session()->get('user.business_id');

            $vld = VariationLocationDetails::leftjoin(
                'business_locations as bl',
                'bl.id',
                '=',
                'variation_location_details.location_id'
            )
                    ->where('variation_location_details.location_id', request()->input('location_id'))
                        ->where('variation_id', request()->input('variation_id'))
                        ->where('bl.business_id', $business_id)
                        ->select('variation_location_details.*')
                        ->first();

            if (!empty($vld)) {
                $vld->qty_available = request()->input('stock');
                $vld->save();
            }
        }

        return redirect()->back()->with(['status' => [
                'success' => 1,
                'msg' => __('lang_v1.updated_succesfully')
            ]]);
    }

    /**
     * Retrieves line orders/sales
     *
     * @return obj
     */
    public function serviceStaffLineOrders()
    {
        $business_id = request()->session()->get('user.business_id');

        $query = TransactionSellLine::leftJoin('transactions as t', 't.id', '=', 'transaction_sell_lines.transaction_id')
                ->leftJoin('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
                ->leftJoin('products as p', 'v.product_id', '=', 'p.id')
                ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
                ->leftJoin('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->leftJoin('users as ss', 'ss.id', '=', 'transaction_sell_lines.res_service_staff_id')
                ->leftjoin(
                    'business_locations AS bl',
                    't.location_id',
                    '=',
                    'bl.id'
                )
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->whereNotNull('transaction_sell_lines.res_service_staff_id');


        if (!empty(request()->service_staff_id)) {
            $query->where('transaction_sell_lines.res_service_staff_id', request()->service_staff_id);
        }

        if (request()->has('location_id')) {
            $location_id = request()->get('location_id');
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }
        }

        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $start = request()->start_date;
            $end =  request()->end_date;
            $query->whereDate('t.transaction_date', '>=', $start)
                        ->whereDate('t.transaction_date', '<=', $end);
        }
                
        $query->select(
            'p.name as product_name',
            'p.type as product_type',
            'v.name as variation_name',
            'pv.name as product_variation_name',
            'u.short_name as unit',
            't.id as transaction_id',
            'bl.name as business_location',
            't.transaction_date',
            't.invoice_no',
            'transaction_sell_lines.quantity',
            'transaction_sell_lines.unit_price_before_discount',
            'transaction_sell_lines.line_discount_type',
            'transaction_sell_lines.line_discount_amount',
            'transaction_sell_lines.item_tax',
            'transaction_sell_lines.unit_price_inc_tax',
            DB::raw('CONCAT(COALESCE(ss.first_name, ""), COALESCE(ss.last_name, "")) as service_staff')
        );

        $datatable = Datatables::of($query)
            ->editColumn('product_name', function ($row) {
                $name = $row->product_name;
                if ($row->product_type == 'variable') {
                    $name .= ' - ' . $row->product_variation_name . ' - ' . $row->variation_name;
                }
                return $name;
            })
            ->editColumn(
                'unit_price_inc_tax',
                '<span class="display_currency unit_price_inc_tax" data-currency_symbol="true" data-orig-value="{{$unit_price_inc_tax}}">{{$unit_price_inc_tax}}</span>'
            )
            ->editColumn(
                'item_tax',
                '<span class="display_currency item_tax" data-currency_symbol="true" data-orig-value="{{$item_tax}}">{{$item_tax}}</span>'
            )
            ->editColumn(
                'quantity',
                '<span class="display_currency quantity" data-unit="{{$unit}}" data-currency_symbol="false" data-orig-value="{{$quantity}}">{{$quantity}}</span> {{$unit}}'
            )
            ->editColumn(
                'unit_price_before_discount',
                '<span class="display_currency unit_price_before_discount" data-currency_symbol="true" data-orig-value="{{$unit_price_before_discount}}">{{$unit_price_before_discount}}</span>'
            )
            ->addColumn(
                'total',
                '<span class="display_currency total" data-currency_symbol="true" data-orig-value="{{$unit_price_inc_tax * $quantity}}">{{$unit_price_inc_tax * $quantity}}</span>'
            )
            ->editColumn(
                'line_discount_amount',
                function ($row) {
                    $discount = !empty($row->line_discount_amount) ? $row->line_discount_amount : 0;

                    if (!empty($discount) && $row->line_discount_type == 'percentage') {
                        $discount = $row->unit_price_before_discount * ($discount / 100);
                    }

                    return '<span class="display_currency total-discount" data-currency_symbol="true" data-orig-value="' . $discount . '">' . $discount . '</span>';
                }
            )
            ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')

            ->rawColumns(['line_discount_amount', 'unit_price_before_discount', 'item_tax', 'unit_price_inc_tax', 'item_tax', 'quantity', 'total'])
                  ->make(true);
                
        return $datatable;
    }

    /**
     * Lists profit by product, category, brand, location, invoice and date
     *
     * @return string $by = null
     */
    public function getProfit($by = null)
    {
        $business_id = request()->session()->get('user.business_id');

        $query = TransactionSellLine
            ::join('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
            ->leftjoin('transaction_sell_lines_purchase_lines as TSPL', 'transaction_sell_lines.id', '=', 'TSPL.sell_line_id')
            ->leftjoin(
                'purchase_lines as PL',
                'TSPL.purchase_line_id',
                '=',
                'PL.id'
            )
            ->whereIn('sale.type', ['sell','sell_return'])
            ->where('sale.status', 'final')
            ->join('products as P', 'transaction_sell_lines.product_id', '=', 'P.id')
            ->where('sale.business_id', $business_id)
            ->where('sale.location_id', '<>', 9)
            ->where('transaction_sell_lines.children_type', '!=', 'combo');
        //If type combo: find childrens, sale price parent - get PP of childrens
        $query->select(DB::raw('SUM(IF (TSPL.id IS NULL AND P.type="combo", ( 
            SELECT Sum((tspl2.quantity - tspl2.qty_returned) * (tsl.unit_price_inc_tax - pl2.purchase_price_inc_tax)) AS total
                FROM transaction_sell_lines AS tsl
                    JOIN transaction_sell_lines_purchase_lines AS tspl2
                ON tsl.id=tspl2.sell_line_id 
                JOIN purchase_lines AS pl2 
                ON tspl2.purchase_line_id = pl2.id 
                WHERE tsl.parent_sell_line_id = transaction_sell_lines.id), IF(P.enable_stock=0,(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax,   
                (TSPL.quantity - TSPL.qty_returned) * (transaction_sell_lines.unit_price_inc_tax - PL.purchase_price_inc_tax)) )) AS gross_profit')
            );

        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $start = request()->start_date;
            $end =  request()->end_date;
            $query->whereDate('sale.transaction_date', '>=', $start)
                        ->whereDate('sale.transaction_date', '<=', $end);
        }

        if ($by == 'product') {
            $query->join('variations as V', 'transaction_sell_lines.variation_id', '=', 'V.id')
                ->leftJoin('product_variations as PV', 'PV.id', '=', 'V.product_variation_id')
                ->addSelect(DB::raw("IF(P.type='variable', CONCAT(P.name, ' - ', PV.name, ' - ', V.name, ' (', V.sub_sku, ')'), CONCAT(P.name, ' (', P.sku, ')')) as product"))
                ->groupBy('V.id');
        }

        if ($by == 'category') {
            $query->join('variations as V', 'transaction_sell_lines.variation_id', '=', 'V.id')
                ->leftJoin('categories as C', 'C.id', '=', 'P.category_id')
                ->addSelect("C.name as category")
                ->groupBy('C.id');
        }

        if ($by == 'brand') {
            $query->join('variations as V', 'transaction_sell_lines.variation_id', '=', 'V.id')
                ->leftJoin('brands as B', 'B.id', '=', 'P.brand_id')
                ->addSelect("B.name as brand")
                ->groupBy('B.id');
        }

        if ($by == 'location') {
            $query->join('business_locations as L', 'sale.location_id', '=', 'L.id')
                ->addSelect("L.name as location")
                ->groupBy('L.id');
        }

        if ($by == 'invoice') {
            $query->addSelect(
                'sale.invoice_no', 
                'sale.id as transaction_id',
                'sale.discount_type',
                'sale.discount_amount',
                'sale.total_before_tax'
            )
                ->groupBy('sale.invoice_no');
        }

        if ($by == 'date') {
            $query->addSelect("sale.transaction_date")
                ->groupBy(DB::raw('DATE(sale.transaction_date)'));
        }

        if ($by == 'day') {
            $results = $query->addSelect(DB::raw("DAYNAME(sale.transaction_date) as day"))
                ->groupBy(DB::raw('DAYOFWEEK(sale.transaction_date)'))
                ->get();

            $profits = [];
            foreach ($results as $result) {
                $profits[strtolower($result->day)] = $result->gross_profit;
            }
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

            return view('report.partials.profit_by_day')->with(compact('profits', 'days'));
        }

        if ($by == 'customer') {
            $query->join('contacts as CU', 'sale.contact_id', '=', 'CU.id')
            ->addSelect("CU.name as customer" , "CU.supplier_business_name")
                ->groupBy('sale.contact_id');
        }

        $datatable = Datatables::of($query);

        if (in_array($by, ['invoice'])) {
            $datatable->editColumn( 'gross_profit', function($row) {
                $discount = $row->discount_amount;
                if ($row->discount_type == 'percentage') {
                   $discount = ($row->discount_amount * $row->total_before_tax) / 100;
                }

                $profit = $row->gross_profit - $discount;
                $html = '<span class="display_currency gross-profit" data-currency_symbol="true" data-orig-value="' . $profit . '">' . $profit . '</span>';
                return $html;
            });
        } else {
            $datatable->editColumn(
                'gross_profit',
                '<span class="display_currency gross-profit" data-currency_symbol="true" data-orig-value="{{$gross_profit}}">{{$gross_profit}}</span>'
            );
        }

        if ($by == 'category') {
            $datatable->editColumn(
                'category',
                '{{$category ?? __("lang_v1.uncategorized")}}'
            );
        }
        if ($by == 'brand') {
            $datatable->editColumn(
                'brand',
                '{{$brand ?? __("report.others")}}'
            );
        }

        if ($by == 'date') {
            $datatable->editColumn('transaction_date', '{{@format_date($transaction_date)}}');
        }
        $raw_columns = ['gross_profit'];

        if ($by == 'customer') {
            $datatable->editColumn('customer', '@if(!empty($supplier_business_name)) {{$supplier_business_name}}, <br> @endif {{$customer}}');
            $raw_columns[] = 'customer';
        }
        
        if ($by == 'invoice') {
            $datatable->editColumn('invoice_no', function ($row) {
                return '<a data-href="' . action('SellController@show', [$row->transaction_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->invoice_no . '</a>';
            });
            $raw_columns[] = 'invoice_no';
        }
        return $datatable->rawColumns($raw_columns)
                  ->make(true);
    }

    /**
     * Shows items report from sell purchase mapping table
     *
     * @return \Illuminate\Http\Response
     */
    public function itemsReport()
    {
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $query = TransactionSellLinesPurchaseLines::leftJoin('transaction_sell_lines 
                    as SL', 'SL.id', '=', 'transaction_sell_lines_purchase_lines.sell_line_id')
                ->leftJoin('stock_adjustment_lines 
                    as SAL', 'SAL.id', '=', 'transaction_sell_lines_purchase_lines.stock_adjustment_line_id')
                ->leftJoin('transactions as sale', 'SL.transaction_id', '=', 'sale.id')
                ->leftJoin('transactions as stock_adjustment', 'SAL.transaction_id', '=', 'stock_adjustment.id')
                ->leftJoin('transaction_sell_lines as TSL','TSL.transaction_id','sale.id')
                ->join('purchase_lines as PL', 'PL.id', '=', 'transaction_sell_lines_purchase_lines.purchase_line_id')
                ->join('transactions as purchase', 'PL.transaction_id', '=', 'purchase.id')
                ->join('business_locations as bl', 'purchase.location_id', '=', 'bl.id')
                ->join(
                    'variations as v',
                    'PL.variation_id',
                    '=',
                    'v.id'
                    )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('variation_location_details as vld', 'vld.variation_id', '=', 'v.id')
                ->join('products as p', 'PL.product_id', '=', 'p.id')
                ->join('units as u', 'p.unit_id', '=', 'u.id')
                ->leftJoin('contacts as suppliers', 'purchase.contact_id', '=', 'suppliers.id')
                ->leftJoin('contacts as customers', 'sale.contact_id', '=', 'customers.id')
                ->where('purchase.business_id', $business_id)
                ->whereIn('sale.type',['sell','sell_return'])
                ->select(
                    'v.sub_sku as sku',
                    'p.type as product_type',
                    'p.name as product_name',
                    'p.image as product_image',
                    'v.name as variation_name',
                    'pv.name as product_variation',
                    'u.short_name as unit',
                    'purchase.transaction_date as purchase_date',
                    'purchase.ref_no as purchase_ref_no',
                    'purchase.type as purchase_type',
                    'suppliers.name as supplier',
                    'suppliers.supplier_business_name',
                    'PL.purchase_price_inc_tax as purchase_price',
                    'sale.transaction_date as sell_date',
                    'stock_adjustment.transaction_date as stock_adjustment_date',
                    'sale.invoice_no as sale_invoice_no',
                    'stock_adjustment.ref_no as stock_adjustment_ref_no',
                    'customers.name as customer',
                    'customers.supplier_business_name as customer_business_name',
                    DB::raw('SUM(SL.quantity - SL.quantity_returned) as quantity'),
                    DB::raw("SUM(
                        IF(
                            sale.type = 'sell' AND sale.status = 'final' AND SL.line_discount_amount > 0,
                            IF(
                                SL.line_discount_type = 'percentage',
                                COALESCE((COALESCE(SL.unit_price_inc_tax, 0) / (1 - (COALESCE(SL.line_discount_amount, 0) / 100)) - SL.unit_price_inc_tax ), 0),
                                COALESCE(SL.line_discount_amount, 0)
                            ),
                            0
                        )
                    ) as total_sell_discount"),
                    DB::raw("v.sell_price_inc_tax - v.dpp_inc_tax  as profit"),
                    DB::raw("vld.qty_available as qty_available"),
                    // 'transaction_sell_lines_purchase_lines.quantity as quantity',
                    'SL.unit_price_inc_tax as selling_price',
                    'SAL.unit_price as stock_adjustment_price',
                    'transaction_sell_lines_purchase_lines.stock_adjustment_line_id',
                    'transaction_sell_lines_purchase_lines.sell_line_id',
                    'transaction_sell_lines_purchase_lines.purchase_line_id',
                    // 'transaction_sell_lines_purchase_lines.qty_returned',
                    'SL.quantity_returned as qty_returned ',
                    'bl.name as location'
                )
                ->groupBy('v.id');

            if (!empty(request()->purchase_start) && !empty(request()->purchase_end)) {
                $start = request()->purchase_start;
                $end =  request()->purchase_end;
                $query->whereDate('purchase.transaction_date', '>=', $start)
                            ->whereDate('purchase.transaction_date', '<=', $end);
            }
            if (!empty(request()->sale_start) && !empty(request()->sale_end)) {
                $start = request()->sale_start;
                $end =  request()->sale_end;
                $query->where(function ($q) use ($start, $end) {
                    $q->where(function ($qr) use ($start, $end) {
                        $qr->whereDate('sale.transaction_date', '>=', $start)
                           ->whereDate('sale.transaction_date', '<=', $end);
                    })->orWhere(function ($qr) use ($start, $end) {
                        $qr->whereDate('stock_adjustment.transaction_date', '>=', $start)
                           ->whereDate('stock_adjustment.transaction_date', '<=', $end);
                    });
                });
            }

            $supplier_id = request()->get('supplier_id', null);
            if (!empty($supplier_id)) {
                $query->where('suppliers.id', $supplier_id);
            }

            $customer_id = request()->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('customers.id', $customer_id);
            }

            $location_id = request()->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('purchase.location_id', $location_id);
            }

            $only_mfg_products = request()->get('only_mfg_products', 0);
            if (!empty($only_mfg_products)) {
                $query->where('purchase.type', 'production_purchase');
            }

            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->editColumn('image', function ($row) {
                    return '<div style="display: flex;"><img src="' . $row->image_url . '" alt="Product image" class="product-thumbnail-small"></div>';
                })
                ->editColumn('purchase_date', '{{@format_datetime($purchase_date)}}')
                ->editColumn('purchase_ref_no', function ($row) {
                    $html = $row->purchase_type == 'purchase' ? '<a data-href="' . action('PurchaseController@show', [$row->purchase_line_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->purchase_ref_no . '</a>' : $row->purchase_ref_no;
                    if ($row->purchase_type == 'opening_stock') {
                        $html .= '(' . __('lang_v1.opening_stock') . ')';
                    }
                    return $html;
                })
                ->editColumn('purchase_price', function ($row) {
                    return '<span class="display_currency purchase_price" data-currency_symbol=true data-orig-value="' . $row->purchase_price . '">' . $row->purchase_price . '</span>';
                })
                ->editColumn('sell_date', '@if(!empty($sell_line_id)) {{@format_datetime($sell_date)}} @else {{@format_datetime($stock_adjustment_date)}} @endif')

                ->editColumn('sale_invoice_no', function ($row) {
                    $invoice_no = !empty($row->sell_line_id) ? $row->sale_invoice_no : $row->stock_adjustment_ref_no . '<br><small>(' . __('stock_adjustment.stock_adjustment') . '</small>' ;

                    return $invoice_no;
                })
                ->editColumn('quantity', function ($row) {
                    $html = '<span data-is_quantity="true" class="display_currency quantity" data-currency_symbol=false data-orig-value="' . (float)$row->quantity . '" data-unit="' . $row->unit . '" >' . (float) $row->quantity . '</span> ' . $row->unit;
                    // if ($row->qty_returned > 0) {
                    //     $html .= '<small><i>(<span data-is_quantity="true" class="display_currency" data-currency_symbol=false>' . '</span> ' . $row->unit . ' ' . __('lang_v1.returned') . ')</i></small>';
                    // }

                    return $html;
                })
                ->editColumn('qty_available', function ($row) {
                    $html = '<span data-is_quantity="true" class="display_currency qty_available" data-currency_symbol=false data-orig-value="' . (float)$row->qty_available . '" data-unit="' . $row->unit . '" >' . (float) $row->qty_available . '</span> ' . $row->unit;
                    // if ($row->qty_returned > 0) {
                    //     $html .= '<small><i>(<span data-is_quantity="true" class="display_currency" data-currency_symbol=false>' . '</span> ' . $row->unit . ' ' . __('lang_v1.returned') . ')</i></small>';
                    // }

                    return $html;
                })
                ->editColumn('exchanged', function ($row) {
                    return $row->qty_returned;
                    // if($row->qty_returned > 0) {
                    //     $html = "EX";
                    // } else {
                    //     $html = "--";
                    // }
                    // return $html;
                })
                 ->editColumn('selling_price', function ($row) {
                     $selling_price = !empty($row->sell_line_id) ? $row->selling_price : $row->stock_adjustment_price;

                     return '<span class="display_currency row_selling_price" data-currency_symbol=true data-orig-value="' . $selling_price . '">' . $selling_price . '</span>';
                 })
                 ->editColumn('sell_discount', function ($row) {
                     $selling_price = $row->total_sell_discount;

                     return '<span class="display_currency row_sel_discount" data-currency_symbol=true data-orig-value="' . $selling_price . '">' . $selling_price . '</span>';
                 })
                 ->editColumn('profit', function ($row) {
                     $selling_price = $row->profit;

                     return '<span class="display_currency row_profit" data-currency_symbol=true data-orig-value="' . $selling_price . '">' . $selling_price . '</span>';
                 })

                 ->addColumn('subtotal', function ($row) {
                     $selling_price = !empty($row->sell_line_id) ? $row->selling_price : $row->stock_adjustment_price;
                     $subtotal = $selling_price * $row->quantity;
                     return '<span class="display_currency row_subtotal" data-currency_symbol=true data-orig-value="' . $subtotal . '">' . $subtotal . '</span>';
                 })
                 ->editColumn('supplier', '@if(!empty($supplier_business_name))
                 {{$supplier_business_name}},<br> @endif {{$supplier}}')
                 ->editColumn('customer', '@if(!empty($customer_business_name))
                 {{$customer_business_name}},<br> @endif {{$customer}}')
                ->filterColumn('sale_invoice_no', function ($query, $keyword) {
                    $query->where('sale.invoice_no', 'like', ["%{$keyword}%"])
                          ->orWhere('stock_adjustment.ref_no', 'like', ["%{$keyword}%"]);
                })
                
                ->rawColumns(['subtotal', 'selling_price', 'quantity', 'purchase_price', 'sale_invoice_no', 'purchase_ref_no', 'supplier', 'customer','sell_discount','profit', 'image' ,'qty_available'])
                ->make(true);
        }

        $suppliers = Contact::suppliersDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);
        $business_locations = BusinessLocation::forDropdown($business_id);
        return view('report.items_report')->with(compact('suppliers', 'customers', 'business_locations'));
    }

    /**
     * Shows purchase report
     *
     * @return \Illuminate\Http\Response
     */
    public function purchaseReport()
    {
        if ((!auth()->user()->can('purchase.view') && !auth()->user()->can('purchase.create') && !auth()->user()->can('view_own_purchase')) || empty(config('constants.show_report_606'))) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);
            $purchases = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                    ->join(
                        'business_locations AS BS',
                        'transactions.location_id',
                        '=',
                        'BS.id'
                    )
                    ->leftJoin(
                        'transaction_payments AS TP',
                        'transactions.id',
                        '=',
                        'TP.transaction_id'
                    )
                    ->where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'purchase')
                    ->with(['payment_lines'])
                    ->select(
                        'transactions.id',
                        'transactions.ref_no',
                        'contacts.name',
                        'contacts.contact_id',
                        'final_total',
                        'total_before_tax',
                        'discount_amount',
                        'discount_type',
                        'tax_amount',
                        DB::raw('DATE_FORMAT(transaction_date, "%Y/%m") as purchase_year_month'),
                        DB::raw('DATE_FORMAT(transaction_date, "%d") as purchase_day')
                    )
                    ->groupBy('transactions.id');

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $purchases->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty(request()->supplier_id)) {
                $purchases->where('contacts.id', request()->supplier_id);
            }
            if (!empty(request()->location_id)) {
                $purchases->where('transactions.location_id', request()->location_id);
            }
            if (!empty(request()->input('payment_status')) && request()->input('payment_status') != 'overdue') {
                $purchases->where('transactions.payment_status', request()->input('payment_status'));
            } elseif (request()->input('payment_status') == 'overdue') {
                $purchases->whereIn('transactions.payment_status', ['due', 'partial'])
                    ->whereNotNull('transactions.pay_term_number')
                    ->whereNotNull('transactions.pay_term_type')
                    ->whereRaw("IF(transactions.pay_term_type='days', DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number DAY) < CURDATE(), DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number MONTH) < CURDATE())");
            }

            if (!empty(request()->status)) {
                $purchases->where('transactions.status', request()->status);
            }
            
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $purchases->whereDate('transactions.transaction_date', '>=', $start)
                            ->whereDate('transactions.transaction_date', '<=', $end);
            }

            if (!auth()->user()->can('purchase.view') && auth()->user()->can('view_own_purchase')) {
                $purchases->where('transactions.created_by', request()->session()->get('user.id'));
            }

            return Datatables::of($purchases)
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final_total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'
                )
                ->editColumn(
                    'total_before_tax',
                    '<span class="display_currency total_before_tax" data-currency_symbol="true" data-orig-value="{{$total_before_tax}}">{{$total_before_tax}}</span>'
                )
                ->editColumn(
                    'tax_amount',
                    '<span class="display_currency tax_amount" data-currency_symbol="true" data-orig-value="{{$tax_amount}}">{{$tax_amount}}</span>'
                )
                ->editColumn(
                    'discount_amount',
                    function ($row) {
                        $discount = !empty($row->discount_amount) ? $row->discount_amount : 0;

                        if (!empty($discount) && $row->discount_type == 'percentage') {
                            $discount = $row->total_before_tax * ($discount / 100);
                        }

                        return '<span class="display_currency total-discount" data-currency_symbol="true" data-orig-value="' . $discount . '">' . $discount . '</span>';
                    }
                )
                ->addColumn('payment_year_month', function ($row) {
                    $year_month = '';
                    if (!empty($row->payment_lines->first())) {
                        $year_month = \Carbon::parse($row->payment_lines->first()->paid_on)->format('Y/m');
                    }
                    return $year_month;
                })
                ->addColumn('payment_day', function ($row) {
                    $payment_day = '';
                    if (!empty($row->payment_lines->first())) {
                        $payment_day = \Carbon::parse($row->payment_lines->first()->paid_on)->format('d');
                    }
                    return $payment_day;
                })
                ->addColumn('payment_method', function ($row) use ($payment_types) {
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
                        if (auth()->user()->can("purchase.view")) {
                            return  action('PurchaseController@show', [$row->id]) ;
                        } else {
                            return '';
                        }
                    }])
                ->rawColumns(['final_total', 'total_before_tax', 'tax_amount', 'discount_amount', 'payment_method'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $suppliers = Contact::suppliersDropdown($business_id, false);
        $orderStatuses = $this->productUtil->orderStatuses();

        return view('report.purchase_report')
            ->with(compact('business_locations', 'suppliers', 'orderStatuses'));
    }

    /**
     * Shows sale report
     *
     * @return \Illuminate\Http\Response
     */
    public function saleReport()
    {
        if ((!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access') && !auth()->user()->can('view_own_sell_only')) ||empty(config('constants.show_report_607'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        return view('report.sale_report')
            ->with(compact('business_locations', 'customers'));
    }

    /**
     * Calculates stock values
     *
     * @return array
     */
    public function getStockValue()
    {
        $business_id = request()->session()->get('user.business_id');
        $end_date = \Carbon::now()->format('Y-m-d');
        $location_id = request()->input('location_id');
        $filters = request()->only(['category_id', 'sub_category_id', 'brand_id', 'unit_id']);
        //Get Closing stock
        $closing_stock_by_pp = $this->transactionUtil->getOpeningClosingStock(
            $business_id,
            $end_date,
            $location_id,
            false,
            false,
            $filters
        );
        $closing_stock_by_sp = $this->transactionUtil->getOpeningClosingStock(
            $business_id,
            $end_date,
            $location_id,
            false,
            true,
            $filters
        );
        $potential_profit = $closing_stock_by_sp - $closing_stock_by_pp;
        // $profit_margin = empty($closing_stock_by_sp) ? 0 : ($potential_profit / $closing_stock_by_sp) * 100;
        if ($closing_stock_by_sp == 0) {
            // Division by zero, handle this case
            $profit_margin = 0; // Set to a default value or return null, depending on your requirement
        } else {
            $profit_margin = ($potential_profit / $closing_stock_by_sp) * 100;
        }

        return [
            'closing_stock_by_pp' => $closing_stock_by_pp,
            'closing_stock_by_sp' => $closing_stock_by_sp,
            'potential_profit' => $potential_profit,
            'profit_margin' => $profit_margin
        ];
    }

    public function getproductSellGroupedReportDetailed(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id', null);

        $vld_str = '';
        if (!empty($location_id)) {
            $vld_str = "AND vld.location_id=$location_id";
        }

        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);

            $query = TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
                )
                ->join(
                    'variations as v',
                    'transaction_sell_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->join('categories as cat', 'p.category_id', '=', 'cat.id')
                ->leftJoin('categories as c2', 'p.sub_category_id', '=', 'c2.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                // ->where('t.type', 'sell')
                ->whereIN('t.type', ['sell','sell_return'])
                ->where('t.status', 'final')
                ->select(
                    'p.gender as product_gender',
                    'p.image as product_image',
                    'p.name as product_name',
                    'p.enable_stock',
                    'p.type as product_type',
                    'pv.name as product_variation',
                    'v.name as variation_name',
                    'v.sub_sku',
                    't.id as transaction_id',
                    't.transaction_date as transaction_date',
                    DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),
                    DB::raw('SUM(transaction_sell_lines.quantity) as total_qty_sold'),

                    // DB::raw('SUM(transaction_sell_lines.quantity) as total_qty_sold'),
                    'u.short_name as unit',
                    DB::raw('SUM((transaction_sell_lines.quantity) * transaction_sell_lines.unit_price_inc_tax) as subtotal'),
                    'cat.name as category_name',
                    'c2.name as sub_category'
                )
                ->whereRaw('transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned <> 0')

                ->groupBy('v.id')
                ->groupBy('formated_date');
                // dd($query);

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->where('t.transaction_date', '>=', $start_date)
                    ->where('t.transaction_date', '<=', $end_date);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            return Datatables::of($query)
            ->editColumn('product_image', function ($row) {
                $basePath = config('app.url'); // Use your base URL, e.g., http://127.0.0.1:8000
                
                if (!empty($row->product_image)) {
                    $imagePath = asset('uploads/img/' . $row->product_image);
                } else {
                    $imagePath = asset('img/default.png');
                }
            
                return '<div style="display: flex; justify-content: center; align-items: center;"><img src="' . $imagePath . '" alt="Product image" class="product-thumbnail-small"></div>';
              })
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->addColumn('category_name', function ($row) {
                    return $row->category_name;
                })
                ->editColumn('sub_category', function ($row) {
                    $sub_category = $row->sub_category;
                    if(!empty($sub_category)){
                        return $sub_category;
                    }
                    else
                        return "--";
                }) 
                ->addColumn('product_gender', function ($row) {
                    return ucwords($row->product_gender);
                })
                ->editColumn('transaction_date', '{{@format_date($formated_date)}}')
                ->editColumn('total_qty_sold', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->total_qty_sold . '" data-unit="' . $row->unit . '" >' . (float) $row->total_qty_sold . '</span> ' .$row->unit;
                })
                ->editColumn('subtotal', function ($row) {
                    return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->subtotal . '">' . $row->subtotal . '</span>';
                })
                
                ->rawColumns(['product_image','subtotal', 'total_qty_sold'])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id);       

        return view('report.product_sell_report_detailed')
            ->with(compact('business_locations', 'customers'));
    }

    public function getproductSellGroupedReportDetailedCategory(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id', null);
        // dd($location_id);

        $vld_str = '';
        if (!empty($location_id)) {
            $vld_str = "AND vld.location_id=$location_id";
        }

        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);

            $query = TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
                )
                ->join(
                    'variations as v',
                    'transaction_sell_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->join('categories as cat', 'p.category_id', '=', 'cat.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                // ->where('t.type', 'sell')
                ->whereIN('t.type', ['sell','sell_return'])

                ->where('t.status', 'final')
                ->select(
                    'p.image as product_image',
                    DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),
                    // DB::raw('SUM(transaction_sell_lines.quantity) as total_qty_sold'),
                    DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_qty_sold'),

                    // DB::raw('SUM(transaction_sell_lines.unit_price_inc_tax) as total_sale'),
                    DB::raw('SUM((transaction_sell_lines.quantity) * transaction_sell_lines.unit_price_inc_tax) as total_sale'),

                    'cat.name as category_name',
                    'cat.id as category_id'
                )
                ->whereRaw('transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned <> 0');
                // dd($query);

                if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            if (!empty($start_date) && !empty($end_date)) {
                $query->where('t.transaction_date', '>=', $start_date)
                    ->where('t.transaction_date', '<=', $end_date);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            $query =   $query->groupBy('category_name')
            ->get();

            return Datatables::of($query)
            // ->editColumn('product_image', function ($row) {
            //     $basePath = config('app.url'); // Use your base URL, e.g., http://127.0.0.1:8000
                
            //     if (!empty($row->product_image)) {
            //         $imagePath = asset('uploads/img/' . $row->product_image);
            //     } else {
            //         $imagePath = asset('img/default.png');
            //     }
            
            //     return '<div style="display: flex; justify-content: center; align-items: center;"><img src="' . $imagePath . '" alt="Product image" class="product-thumbnail-small"></div>';
            //   })
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->addColumn('category_name', function ($row) {
                    return $row->category_name;
                })
                ->editColumn('transaction_date', '{{@format_date($formated_date)}}')
                ->editColumn('total_qty_sold', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->total_qty_sold . '" data-unit="' . $row->unit . '" >' . (float) $row->total_qty_sold . '</span> ' .$row->unit;
                })
                ->editColumn('subtotal', function ($row) {
                    return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->total_sale . '">' . $row->total_sale . '</span>';
                })
                
                ->rawColumns(['product_image','subtotal', 'total_qty_sold'])
                ->make(true);
        }
    }


    public function getproductSellGroupedReportDetailedReturns(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id', null);

        $vld_str = '';
        if (!empty($location_id)) {
            $vld_str = "AND vld.location_id=$location_id";
        }

        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
            $query = TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
                )
                ->join(
                    'variations as v',
                    'transaction_sell_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->join('categories as cat', 'p.category_id', '=', 'cat.id')
                ->leftJoin('categories as c2', 'p.sub_category_id', '=', 'c2.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->where('transaction_sell_lines.quantity_returned', '>', 0)
                ->select(
                    'p.gender as product_gender',
                    'p.image as product_image',
                    'p.name as product_name',
                    'p.enable_stock',
                    'p.type as product_type',
                    'pv.name as product_variation',
                    'v.name as variation_name',
                    'v.sub_sku',
                    't.id as transaction_id',
                    't.transaction_date as transaction_date',
                    DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),
                    DB::raw('SUM(transaction_sell_lines.quantity_returned) as total_qty_sold'),
                    'u.short_name as unit',
                    DB::raw('SUM(transaction_sell_lines.quantity_returned * transaction_sell_lines.unit_price_inc_tax) as subtotal'),
                    // DB::raw('transaction_sell_lines.quantity_returned * transaction_sell_lines.unit_price_inc_tax as subtotal'),
                    'cat.name as category_name',
                    'c2.name as sub_category'
                )
                ->groupBy('v.id')
                ->groupBy('formated_date');

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->where('t.transaction_date', '>=', $start_date)
                    ->where('t.transaction_date', '<=', $end_date);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            return Datatables::of($query)
            ->editColumn('product_image', function ($row) {
                $basePath = config('app.url'); // Use your base URL, e.g., http://127.0.0.1:8000
                
                if (!empty($row->product_image)) {
                    $imagePath = asset('uploads/img/' . $row->product_image);
                } else {
                    $imagePath = asset('img/default.png');
                }
            
                return '<div style="display: flex; justify-content: center; align-items: center;"><img src="' . $imagePath . '" alt="Product image" class="product-thumbnail-small"></div>';
              })
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->addColumn('category_name', function ($row) {
                    return $row->category_name;
                })
                ->editColumn('sub_category', function ($row) {
                    $sub_category = $row->sub_category;
                    if(!empty($sub_category)){
                        return $sub_category;
                    }
                    else
                        return "--";
                })
                ->addColumn('product_gender', function ($row) {
                    return ucwords($row->product_gender);
                })
                ->editColumn('transaction_date', '{{@format_date($formated_date)}}')
                ->editColumn('total_qty_sold', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->total_qty_sold . '" data-unit="' . $row->unit . '" >' . (float) $row->total_qty_sold . '</span> ' .$row->unit;
                })
                ->editColumn('subtotal', function ($row) {
                    return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->subtotal . '">' . $row->subtotal . '</span>';
                })
                
                ->rawColumns(['product_image','subtotal', 'total_qty_sold'])
                ->make(true);
        }
    }


    public function getproductSellGroupedReportDetailedReturnsCategory(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id', null);

        $vld_str = '';
        if (!empty($location_id)) {
            $vld_str = "AND vld.location_id=$location_id";
        }

        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
            $query = TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
                )
                ->join(
                    'variations as v',
                    'transaction_sell_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->join('categories as cat', 'p.category_id', '=', 'cat.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->where('transaction_sell_lines.quantity_returned', '>', 0)
                ->select(
                    'p.image as product_image',
                    DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),
                    DB::raw('SUM(transaction_sell_lines.quantity_returned) as total_qty_sold'),
                    // DB::raw('SUM(transaction_sell_lines.unit_price_inc_tax) as subtotal'),
                    DB::raw('SUM(transaction_sell_lines.quantity_returned * transaction_sell_lines.unit_price_inc_tax) as subtotal'),
                    'cat.name as category_name'
                )
                // ->get();
                // dd($query);
                ->groupBy('category_name');

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->where('t.transaction_date', '>=', $start_date)
                    ->where('t.transaction_date', '<=', $end_date);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            return Datatables::of($query)
            // ->editColumn('product_image', function ($row) {
            //     $basePath = config('app.url'); // Use your base URL, e.g., http://127.0.0.1:8000
                
            //     if (!empty($row->product_image)) {
            //         $imagePath = asset('uploads/img/' . $row->product_image);
            //     } else {
            //         $imagePath = asset('img/default.png');
            //     }
            
            //     return '<div style="display: flex; justify-content: center; align-items: center;"><img src="' . $imagePath . '" alt="Product image" class="product-thumbnail-small"></div>';
            //   })
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->addColumn('category_name', function ($row) {
                    return $row->category_name;
                })                ->editColumn('transaction_date', '{{@format_date($formated_date)}}')
                ->editColumn('total_qty_sold', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->total_qty_sold . '" data-unit="' . $row->unit . '" >' . (float) $row->total_qty_sold . '</span> ' .$row->unit;
                })
                ->editColumn('subtotal', function ($row) {
                    return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->subtotal . '">' . $row->subtotal . '</span>';
                })
                
                ->rawColumns(['product_image','subtotal', 'total_qty_sold'])
                ->make(true);
        }
    }

    public function ecommerceSellReport(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        
        if (request()->ajax()) {
            $query = EcommerceTransaction::leftJoin('ecommerce_payments as EP', 'EP.ecommerce_transaction_id', '=', 'ecommerce_transactions.id')
            ->leftJoin('ecommerce_sell_lines as ESL', 'ESL.ecommerce_transaction_id','=','ecommerce_transactions.id')
            ->leftJoin('variations', 'variations.id', '=' , 'ESL.variation_id')
            ->leftJoin('products', 'products.id', '=' , 'ESL.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('variation_location_details as VLD', 'VLD.id', '=', 'ESL.location_id')
            ->whereNull('ecommerce_transactions.return_parent_id')
            ->select(
                'ecommerce_transactions.transaction_date AS transaction_date',
                'products.name AS product_name',
                'categories.name AS category_name',
                'ESL.quantity as quantity',
                'ESL.unit_price_inc_tax AS sell_price',
                'VLD.qty_available',
                'ESL.quantity_returned AS quantity_returned'
                );

            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }
                    return $product_name;
                })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->addColumn('quantity_returned', function ($row){
                    $quantity_returned = $row->quantity_returned;
                    return $quantity_returned;
                })
                ->addColumn('category_name', function ($row){
                    $category_name = $row->category_name;
                    return $category_name;
                })
                ->addColumn('quantity', function ($row){
                    $quantity = $row->quantity;
                    return $quantity;
                })
                ->addColumn('sell_price', function ($row){
                    $sell_price = $row->sell_price;
                    return $sell_price;
                })
                ->addColumn('qty_available', function ($row){
                    $qty_available = $row->qty_available;
                    return $qty_available;
                })
                ->rawColumns(['product_name','transaction_date','quantity_returned'])
                ->make(true);
        }
        return view('report.ecommerce_sell_report');
    }

    public function overviewReport(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, true);
        return view('report.overview_report', compact('business_locations'));
    }

    public function getSellOverviewReport(Request $request)
    {
        // dd($request);
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $location_id = $request->location_id;
        $business_id = $request->session()->get('user.business_id');

        if($request->ajax()) {

                // Return Data
                $query1 = DB::table('transactions')
                ->where('transactions.type', 'sell_return')
                ->where('transactions.status', 'final')
                ->where('transactions.business_id', $business_id);
                
                if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                    $query1->whereDate('transactions.transaction_date', '>=', $start_date)
                        ->whereDate('transactions.transaction_date', '<=', $end_date);
                }
                if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                    $query1->whereDate('transactions.transaction_date', $end_date);
                }
        
                //Filter by the location
                if (!empty($location_id)) {
                    $query1->where('transactions.location_id', $location_id);
                }  
                $return_data =  $query1->select(
                    DB::raw('COUNT(transactions.id) as return_invoices'),
                    DB::raw('SUM(transactions.final_total) as returned_amount'),
                )
                ->first();


                // Return Items
                $query2 = DB::table('transaction_sell_lines')
                ->leftJoin('transactions as t', 't.return_parent_id', 'transaction_sell_lines.transaction_id')
                ->where('t.type', 'sell_return')
                ->where('t.business_id', $business_id);
                if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                    $query2->whereDate('t.transaction_date', '>=', $start_date)
                        ->whereDate('t.transaction_date', '<=', $end_date);
                }
                if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                    $query2->whereDate('t.transaction_date', $end_date);
                }
        
                //Filter by the location
                if (!empty($location_id)) {
                    $query2->where('t.location_id', $location_id);
                }
                $return_items = $query2->select(  DB::raw('SUM(transaction_sell_lines.quantity_returned) as returned_items'))
                ->first();
            


                // Invoice Data
                $query3 = TransactionSellLine::join(
                    'transactions as t',
                    'transaction_sell_lines.transaction_id',
                    '=',
                    't.id'
                )
                ->where('t.business_id', $business_id)
                // ->where('t.type', 'sell')
                ->whereIN('t.type', ['sell','sell_return'])

                ->where('t.status', 'final');
                if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                    $query3->whereDate('t.transaction_date', '>=', $start_date)
                        ->whereDate('t.transaction_date', '<=', $end_date);
                }
                if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                    $query3->whereDate('t.transaction_date', $end_date);
                }
        
                //Filter by the location
                if (!empty($location_id)) {
                    $query3->where('t.location_id', $location_id);
                }
                $invoice_data =   $query3->select(
                    DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_item_sold'),

                    // DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_item_sold'),
                    DB::raw('SUM((transaction_sell_lines.quantity) * transaction_sell_lines.unit_price_inc_tax) as invoice_amount'),
                    DB::raw("SUM(
                        IF(
                            t.type = 'sell' AND t.status = 'final' AND line_discount_amount > 0,
                            IF(
                                line_discount_type = 'percentage',
                                COALESCE((COALESCE(unit_price_inc_tax, 0) / (1 - (COALESCE(line_discount_amount, 0) / 100)) - unit_price_inc_tax ), 0),
                                COALESCE(line_discount_amount, 0)
                            ),
                            0
                        )
                    ) as total_sell_discount"),
                    DB::raw('COUNT(t.id) as total_invoice_count')
                )
                ->first();
                
                // dd($invoice_data);


                // Cash Payment
                $query4 = DB::table('cash_register_transactions')->leftJoin('transactions', 'cash_register_transactions.transaction_id', 'transactions.id')
                ->where('transactions.business_id', $business_id)
                ->whereIn('transactions.type', ['sell','sell_return'])
                ->where('transactions.status', 'final')
                ->where('cash_register_transactions.pay_method', 'cash')
                ->where('cash_register_transactions.transaction_type','sell');
                if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                    $query4->whereDate('transactions.transaction_date', '>=', $start_date)
                        ->whereDate('transactions.transaction_date', '<=', $end_date);
                }
                if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                    $query4->whereDate('transactions.transaction_date', $end_date);
                }
        
                //Filter by the location
                if (!empty($location_id)) {
                    $query4->where('transactions.location_id', $location_id);
                }
                $cash_payment = $query4->select(DB::raw('SUM(cash_register_transactions.amount) as cash_amount'))
                ->first();


                // Card Payment

                $query5 = DB::table('transactions')->leftJoin('transaction_payments','transactions.id','transaction_payments.transaction_id')
                ->where('transactions.business_id', $business_id)
                ->whereIn('transactions.type', ['sell','sell_return'])
                ->where('transactions.status', 'final')
                ->where('transaction_payments.method', 'card');
                if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                    $query5->whereDate('transactions.transaction_date', '>=', $start_date)
                        ->whereDate('transactions.transaction_date', '<=', $end_date);
                }
                if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                    $query5->whereDate('transactions.transaction_date', $end_date);
                }
        
                //Filter by the location
                if (!empty($location_id)) {
                    $query5->where('transactions.location_id', $location_id);
                }
                $card_payment = $query5->select(DB::raw('SUM(transaction_payments.amount) as card_amount'))
                ->first();


            // GROSS PROFIT

            $gross_profit = TransactionSellLine::join('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
            ->leftjoin('transaction_sell_lines_purchase_lines as TSPL', 'transaction_sell_lines.id', '=', 'TSPL.sell_line_id')
            ->leftjoin(
                'purchase_lines as PL',
                'TSPL.purchase_line_id',
                '=',
                'PL.id'
            )
            ->join('business_locations as L', 'sale.location_id', '=', 'L.id')
            ->whereIn('sale.type', ['sell'])
            ->where('sale.status', 'final')
            ->join('products as P', 'transaction_sell_lines.product_id', '=', 'P.id')
            ->where('sale.business_id', $business_id)
            ->where('sale.location_id', '<>', 9)
            ->where('transaction_sell_lines.children_type', '!=', 'combo');
              //Filter by the location
              if (!empty($location_id)) {
                $gross_profit->where('L.id', $location_id);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $gross_profit->whereDate('sale.transaction_date', '>=', $start)
                            ->whereDate('sale.transaction_date', '<=', $end);
            }
            //If type combo: find childrens, sale price parent - get PP of childrens
            $gross_profit->select(DB::raw('SUM(IF (TSPL.id IS NULL AND P.type="combo", ( 
                SELECT Sum((tspl2.quantity - tspl2.qty_returned) * (tsl.unit_price_inc_tax - pl2.purchase_price_inc_tax)) AS total
                    FROM transaction_sell_lines AS tsl
                        JOIN transaction_sell_lines_purchase_lines AS tspl2
                    ON tsl.id=tspl2.sell_line_id 
                    JOIN purchase_lines AS pl2 
                    ON tspl2.purchase_line_id = pl2.id 
                    WHERE tsl.parent_sell_line_id = transaction_sell_lines.id), IF(P.enable_stock=0,(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax,   
                    (TSPL.quantity - TSPL.qty_returned) * (transaction_sell_lines.unit_price_inc_tax - PL.purchase_price_inc_tax)) )) AS gross_profit')
                )->groupBy('L.id');
                $results = $gross_profit->first();
                $gross_profit = $results ?  $results['gross_profit']: 0;

                $exchange_one_profit =  TransactionSellLine::join('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.return_parent_id')
                ->leftjoin('transaction_sell_lines_purchase_lines as TSPL', 'transaction_sell_lines.id', '=', 'TSPL.sell_line_id')
                ->leftjoin(
                    'purchase_lines as PL',
                    'TSPL.purchase_line_id',
                    '=',
                    'PL.id'
                )
                ->join('business_locations as L', 'sale.location_id', '=', 'L.id')
                ->whereIn('sale.type', ['sell_return'])
                ->where('sale.status', 'final')
                ->join('products as P', 'transaction_sell_lines.product_id', '=', 'P.id')
                ->where('sale.business_id', $business_id)
                ->where('sale.location_id', '<>', 9)
                ->where('transaction_sell_lines.children_type', '!=', 'combo');
                  //Filter by the location
                if (!empty($location_id)) {
                    $exchange_one_profit->where('L.id', $location_id);
                }
                if (!empty(request()->start_date) && !empty(request()->end_date)) {
                    $start = request()->start_date;
                    $end =  request()->end_date;
                    $exchange_one_profit->whereDate('sale.transaction_date', '>=', $start)->whereDate('sale.transaction_date', '<=', $end);
                }

                 //If type combo: find childrens, sale price parent - get PP of childrens
                $exchange_one_profit->select(DB::raw('SUM(IF (TSPL.id IS NULL AND P.type="combo", ( 
                SELECT Sum((tspl2.quantity) * (tsl.unit_price_inc_tax - pl2.purchase_price_inc_tax)) AS total
                FROM transaction_sell_lines AS tsl
                JOIN transaction_sell_lines_purchase_lines AS tspl2
                ON tsl.id=tspl2.sell_line_id 
                JOIN purchase_lines AS pl2 
                ON tspl2.purchase_line_id = pl2.id 
                WHERE tsl.parent_sell_line_id = transaction_sell_lines.id), IF(P.enable_stock=0,(transaction_sell_lines.quantity) * transaction_sell_lines.unit_price_inc_tax,   
                (TSPL.quantity) * (transaction_sell_lines.unit_price_inc_tax - PL.purchase_price_inc_tax)) )) AS gross_profit')
                )->groupBy('L.id');
                $results_new = $exchange_one_profit->first();

                $result_new_profit = $results_new ?  $results_new['gross_profit']: 0;

                $exchange_two_profit =  TransactionSellLine::join('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
                ->leftjoin('transaction_sell_lines_purchase_lines as TSPL', 'transaction_sell_lines.id', '=', 'TSPL.sell_line_id')
                ->leftjoin(
                    'purchase_lines as PL',
                    'TSPL.purchase_line_id',
                    '=',
                    'PL.id'
                )
                ->join('business_locations as L', 'sale.location_id', '=', 'L.id')
                ->whereIn('sale.type', ['sell_return'])
                ->where('sale.status', 'final')
                ->join('products as P', 'transaction_sell_lines.product_id', '=', 'P.id')
                ->where('sale.business_id', $business_id)
                ->where('sale.location_id', '<>', 9)
                ->where('transaction_sell_lines.children_type', '!=', 'combo');
                  //Filter by the location
                if (!empty($location_id)) {
                    $exchange_two_profit->where('L.id', $location_id);
                }
                if (!empty(request()->start_date) && !empty(request()->end_date)) {
                    $start = request()->start_date;
                    $end =  request()->end_date;
                    $exchange_two_profit->whereDate('sale.transaction_date', '>=', $start)->whereDate('sale.transaction_date', '<=', $end);
                }

                 //If type combo: find childrens, sale price parent - get PP of childrens
                $exchange_two_profit->select(DB::raw('SUM(IF (TSPL.id IS NULL AND P.type="combo", ( 
                SELECT Sum((tspl2.quantity) * (tsl.unit_price_inc_tax - pl2.purchase_price_inc_tax)) AS total
                FROM transaction_sell_lines AS tsl
                JOIN transaction_sell_lines_purchase_lines AS tspl2
                ON tsl.id=tspl2.sell_line_id 
                JOIN purchase_lines AS pl2 
                ON tspl2.purchase_line_id = pl2.id 
                WHERE tsl.parent_sell_line_id = transaction_sell_lines.id), IF(P.enable_stock=0,(transaction_sell_lines.quantity) * transaction_sell_lines.unit_price_inc_tax,   
                (TSPL.quantity) * (transaction_sell_lines.unit_price_inc_tax - PL.purchase_price_inc_tax)) )) AS gross_profit')
                )->groupBy('L.id');
                $results_two = $exchange_two_profit->first();
                $result_two_pofit = $results_two ?  $results_two['gross_profit']: 0;

                $exchanged_profit =  $result_two_pofit - $result_new_profit ;

                // Gift Amount
                $query6 = DB::table('transactions')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'gift')
                ->where('transactions.status', 'final');
                if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                    $query6->whereDate('transactions.transaction_date', '>=', $start_date)
                        ->whereDate('transactions.transaction_date', '<=', $end_date);
                }
                if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                    $query6->whereDate('transactions.transaction_date', $end_date);
                }
        
                //Filter by the location
                if (!empty($location_id)) {
                    $query6->where('transactions.location_id', $location_id);
                }
                $gift_amount = $query6->select(DB::raw('SUM(transactions.final_total) as amount'))
                ->first();

                //Gift Return Amount
                $query16 = DB::table('transactions')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'gift_return')
                ->where('transactions.status', 'final');
                if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                    $query16->whereDate('transactions.transaction_date', '>=', $start_date)
                        ->whereDate('transactions.transaction_date', '<=', $end_date);
                }
                if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                    $query16->whereDate('transactions.transaction_date', $end_date);
                }
        
                //Filter by the location
                if (!empty($location_id)) {
                    $query16->where('transactions.location_id', $location_id);
                }
                $gift_return_amount = $query16->select(DB::raw('SUM(transactions.final_total) as amount'))
                ->first();

                //Gift SubTotal
                // dd($gift_amount->amount, $gift_return_amount->amount);
                $amount = $gift_amount->amount - $gift_return_amount->amount;
                // dd($amount);

                // Gift Items
                $query7 = TransactionSellLine::join('transactions as t', 't.id', 'transaction_sell_lines.transaction_id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'gift')
                ->where('t.status', 'final');
                if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                    $query7->whereDate('t.transaction_date', '>=', $start_date)
                        ->whereDate('t.transaction_date', '<=', $end_date);
                }
                if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                    $query7->whereDate('t.transaction_date', $end_date);
                }
        
                //Filter by the location
                if (!empty($location_id)) {
                    $query7->where('t.location_id', $location_id);
                }
                $gift_items = $query7->select(  DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as gift_items'))
                ->first();


                // GST tax
                $query8 = TransactionSellLine::join('transactions as t', 't.id', 'transaction_sell_lines.transaction_id')
                ->where('t.business_id', $business_id)
                ->whereIN('t.type', ['sell','sell_return'])
                // ->where('t.type', 'sell')
                ->where('transaction_sell_lines.quantity_returned' , 0)
                ->where('t.status', 'final');
                if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                    $query8->whereDate('t.transaction_date', '>=', $start_date)
                        ->whereDate('t.transaction_date', '<=', $end_date);
                }
                if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                    $query8->whereDate('t.transaction_date', $end_date);
                }
        
                //Filter by the location
                if (!empty($location_id)) {
                    $query8->where('t.location_id', $location_id);
                }
                $gst_tax = $query8->select(DB::raw('SUM(item_tax) as tax'))
                ->first();

                $query9 = TransactionSellLine::join('transactions as sale', 'sale.id','transaction_sell_lines.transaction_id')
                ->leftjoin('transaction_sell_lines_purchase_lines as TSPL', 'transaction_sell_lines.id', '=', 'TSPL.sell_line_id')
                ->leftjoin(
                    'purchase_lines as PL',
                    'TSPL.purchase_line_id',
                    '=',
                    'PL.id'
                )
                ->join('business_locations as L', 'sale.location_id', '=', 'L.id')
                ->whereIn('sale.type', ['sell','sell_return'])
                ->where('sale.status', 'final')
                ->join('products as P', 'transaction_sell_lines.product_id', '=', 'P.id')
                ->where('sale.business_id', $business_id)
                ->where('sale.location_id', '<>', 9)
                ->where('transaction_sell_lines.children_type', '!=', 'combo');
                  //Filter by the location
                if (!empty($location_id)) {
                    $query9->where('L.id', $location_id);
                }
                if (!empty(request()->start_date) && !empty(request()->end_date)) {
                    $start = request()->start_date;
                    $end =  request()->end_date;
                    $query9->whereDate('sale.transaction_date', '>=', $start)->whereDate('sale.transaction_date', '<=', $end);
                }

                  //If type combo: find childrens, sale price parent - get PP of childrens
                  $query9->select(DB::raw('SUM(PL.purchase_price_inc_tax * PL.quantity_sold) AS buy_price')
                    )->groupBy('L.id');
                    $buy_of_sell = $query9->first();
                // dd($buy_of_sell);
            // dd($return_data, $return_items, $invoice_data, $cash_payment, $card_payment, $gross_profit, $gift_amount, $gift_items, $gst_tax);
            return response()->json([
                'return_invoices' => $return_data->return_invoices,
                'return_amount' => $return_data->returned_amount ? $return_data->returned_amount : 0.000 ,
                'return_items' => $return_items->returned_items ? $return_items->returned_items : 0.000,
                'total_item_sold' => $invoice_data['total_item_sold'],
                'total_invoice_count' => $invoice_data['total_invoice_count'] - $return_data->return_invoices, 
                'buy_price'  => $buy_of_sell ? $buy_of_sell['buy_price'] : 0,
                'invoice_amount' => $invoice_data['invoice_amount'],
                // 'invoice_amount' => ($cash_payment->cash_amount) + ($card_payment->card_amount) - ($return_data->returned_amount ? $return_data->returned_amount : 0.000),
                'total_sell_discount' => $invoice_data['total_sell_discount'],
                'cash_amount' => $cash_payment->cash_amount,
                'card_amount' => $card_payment->card_amount,
                'total_received' => ($cash_payment->cash_amount) + ($card_payment->card_amount),
                // 'total_received' => $invoice_data['invoice_amount'],
                'profit_loss' => $gross_profit + $exchanged_profit,
                'total_gift_amount' => $gift_amount->amount,
                'total_gift_items' => $gift_items['gift_items'],
                'gst_tax' => $gst_tax['tax'],
                'amount' => $amount
            ]);

        }

    }

    public function getBuyOverviewReport(Request $request) 
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $location_id = $request->location_id;
        $business_id = $request->session()->get('user.business_id');

        if($request->ajax()) {
            $purchase_details = $this->transactionUtil->getPurchaseTotals($business_id, $start_date, $end_date, $location_id);

            $query1 = DB::table('purchase_lines')
            ->join('transactions','transactions.id','purchase_lines.transaction_id')
            ->where('transactions.type', 'purchase')
            ->where('transactions.status', 'received')
            ->where('transactions.business_id', $business_id);
            
            if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                $query1->whereDate('transactions.transaction_date', '>=', $start_date)
                    ->whereDate('transactions.transaction_date', '<=', $end_date);
            }
            if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                $query1->whereDate('transactions.transaction_date', $end_date);
            }
    
            //Filter by the location
            if (!empty($location_id)) {
                $query1->where('transactions.location_id', $location_id);
            }  
            $purchased_items =  $query1->select(
                DB::raw('SUM(purchase_lines.quantity) as quantity'),
            )
            ->first();

            return response()->json([
                'total_items' => $purchased_items->quantity ? $purchased_items->quantity : 0 ,
                'total_buy_amount' => $purchase_details['total_purchase_inc_tax'],
                'total_cost_amount' => $purchase_details['total_purchase_inc_tax'],
                'total_purchase_amount' => $purchase_details['total_purchase_inc_tax']
            ]);
        }
    }

    public function getEcommerceOverviewReport(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $business_id = $request->session()->get('user.business_id');

        if($request->ajax()) {
            $query1 = DB::table('ecommerce_transactions')
            ->where('ecommerce_transactions.type', 'sell')
            ->where('ecommerce_transactions.status', 'final')
            ->where('ecommerce_transactions.business_id', $business_id);
            
            if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                $query1->whereDate('ecommerce_transactions.transaction_date', '>=', $start_date)
                    ->whereDate('ecommerce_transactions.transaction_date', '<=', $end_date);
            }
            if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                $query1->whereDate('ecommerce_transactions.transaction_date', $end_date);
            }
    

            $total_orders =  $query1->select(
                DB::raw('Count(ecommerce_transactions.id) as total_orders'),
                DB::raw('SUM(ecommerce_transactions.final_total) as total_order_amount')
            )
            ->first();
            
            
            // Received Amount
            $query2 = DB::table('ecommerce_transactions')
            ->where('ecommerce_transactions.type', 'sell')
            ->where('ecommerce_transactions.status', 'final')
            ->where('ecommerce_transactions.payment_status','paid')
            ->where('ecommerce_transactions.business_id', $business_id);
            
            if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                $query2->whereDate('ecommerce_transactions.transaction_date', '>=', $start_date)
                    ->whereDate('ecommerce_transactions.transaction_date', '<=', $end_date);
            }
            if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                $query2->whereDate('ecommerce_transactions.transaction_date', $end_date);
            }
    

            $received_mount =  $query2->select(
                DB::raw('SUM(ecommerce_transactions.final_total) as total_received_amount')
            )
            ->first();
            
            // Total Items
            $query3 = DB::table('ecommerce_sell_lines')
            ->join('ecommerce_transactions', 'ecommerce_transactions.id','ecommerce_sell_lines.ecommerce_transaction_id')
            ->where('ecommerce_transactions.type', 'sell')
            ->where('ecommerce_transactions.status', 'final')
            ->where('ecommerce_transactions.business_id', $business_id);
            
            if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                $query3->whereDate('ecommerce_transactions.transaction_date', '>=', $start_date)
                    ->whereDate('ecommerce_transactions.transaction_date', '<=', $end_date);
            }
            if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                $query3->whereDate('ecommerce_transactions.transaction_date', $end_date);
            }
    

            $total_items =  $query3->select(
                DB::raw('SUM(ecommerce_sell_lines.quantity) as total_items'),
            )
            ->first();


            //  New Orders
            $query4 = DB::table('ecommerce_transactions')
            ->where('ecommerce_transactions.type', 'sell')
            ->where('ecommerce_transactions.status', 'final')
            ->where('shipping_status', 'ordered')
            ->where('ecommerce_transactions.business_id', $business_id);
            
            if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                $query4->whereDate('ecommerce_transactions.transaction_date', '>=', $start_date)
                    ->whereDate('ecommerce_transactions.transaction_date', '<=', $end_date);
            }
            if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                $query4->whereDate('ecommerce_transactions.transaction_date', $end_date);
            }
    

            $new_orders =  $query4->select(
                DB::raw('Count(ecommerce_transactions.id) as new_orders'),
            )
            ->first();

            // $profit_loss =  EcommerceSellLine::join('purchase_lines','purchase_lines.product_id',)
            $gross_profit = $this->transactionUtil->getEcommerceGrossProfit($business_id,$start_date, $end_date);


            // Completed Orders
             $query5 = DB::table('ecommerce_transactions')
             ->where('ecommerce_transactions.type', 'sell')
             ->where('ecommerce_transactions.status', 'final')
             ->where('shipping_status', 'delivered')
             ->where('ecommerce_transactions.business_id', $business_id);
             
             if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                 $query5->whereDate('ecommerce_transactions.transaction_date', '>=', $start_date)
                     ->whereDate('ecommerce_transactions.transaction_date', '<=', $end_date);
             }
             if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                 $query5->whereDate('ecommerce_transactions.transaction_date', $end_date);
             }
     
 
             $completed_orders =  $query5->select(
                 DB::raw('Count(ecommerce_transactions.id) as completed'),
             )
             ->first();

            // Dispatched Orders
             $query6 = DB::table('ecommerce_transactions')
             ->where('ecommerce_transactions.type', 'sell')
             ->where('ecommerce_transactions.status', 'final')
             ->where('shipping_status', 'shipped')
             ->where('ecommerce_transactions.business_id', $business_id);
             
             if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                 $query6->whereDate('ecommerce_transactions.transaction_date', '>=', $start_date)
                     ->whereDate('ecommerce_transactions.transaction_date', '<=', $end_date);
             }
             if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                 $query6->whereDate('ecommerce_transactions.transaction_date', $end_date);
             }
     
 
             $dispatched_orders =  $query6->select(
                 DB::raw('Count(ecommerce_transactions.id) as dispached'),
             )
             ->first();
            
            // Exchanged Orders
             $query7 = DB::table('ecommerce_transactions')
             ->where('ecommerce_transactions.type', 'sell_return')
             ->where('ecommerce_transactions.status', 'final')
             ->where('ecommerce_transactions.business_id', $business_id);
             
             if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                 $query7->whereDate('ecommerce_transactions.transaction_date', '>=', $start_date)
                     ->whereDate('ecommerce_transactions.transaction_date', '<=', $end_date);
             }
             if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                 $query7->whereDate('ecommerce_transactions.transaction_date', $end_date);
             }
     
 
             $exchanged_orders =  $query7->select(
                 DB::raw('Count(ecommerce_transactions.id) as exchanged_orders'),
             )
             ->first();
            
            
            //  Cancelled Items
            $query8 = DB::table('ecommerce_sell_lines')
            ->join('ecommerce_transactions','ecommerce_transactions.id','ecommerce_sell_lines.ecommerce_transaction_id')
            ->where('ecommerce_transactions.type', 'sell')
            ->where('ecommerce_transactions.status', 'final')
            ->where('ecommerce_transactions.shipping_status', 'cancelled')
            ->where('ecommerce_transactions.business_id', $business_id);
            
            if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                $query8->whereDate('ecommerce_transactions.transaction_date', '>=', $start_date)
                    ->whereDate('ecommerce_transactions.transaction_date', '<=', $end_date);
            }
            if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                $query8->whereDate('ecommerce_transactions.transaction_date', $end_date);
            }
    

            $cancelled_items =  $query8->select(
                DB::raw('SUM(ecommerce_sell_lines.quantity) as cancelled_quantity'),
            )
            ->first();

            // Discount Amount
            $query9 = DB::table('ecommerce_sell_lines')
            ->join('ecommerce_transactions','ecommerce_transactions.id','ecommerce_sell_lines.ecommerce_transaction_id')
            ->where('ecommerce_transactions.type', 'sell')
            ->where('ecommerce_transactions.status', 'final')
            ->where('ecommerce_transactions.business_id', $business_id);
            
            if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                $query9->whereDate('ecommerce_transactions.transaction_date', '>=', $start_date)
                    ->whereDate('ecommerce_transactions.transaction_date', '<=', $end_date);
            }
            if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                $query9->whereDate('ecommerce_transactions.transaction_date', $end_date);
            }
    

            $discount_amount =  $query9->select(
                DB::raw('SUM(ecommerce_sell_lines.line_discount_amount) as discount_amount'),
            )
            ->first();


             
            // Cancelled Orders
            $query10 = DB::table('ecommerce_transactions')
            ->where('ecommerce_transactions.type', 'sell')
            ->where('ecommerce_transactions.status', 'final')
            ->where('ecommerce_transactions.shipping_status', 'cancelled')
            ->where('ecommerce_transactions.business_id', $business_id);
            
            if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                $query10->whereDate('ecommerce_transactions.transaction_date', '>=', $start_date)
                    ->whereDate('ecommerce_transactions.transaction_date', '<=', $end_date);
            }
            if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                $query10->whereDate('ecommerce_transactions.transaction_date', $end_date);
            }
    

            $cancelled_orders =  $query10->select(
                DB::raw('Count(ecommerce_transactions.id) as cancelled_orders'),
            )
            ->first();
             
            // Cancelled Orders Amount
            $query11 = DB::table('ecommerce_transactions')
            ->where('ecommerce_transactions.type', 'sell')
            ->where('ecommerce_transactions.status', 'final')
            ->where('ecommerce_transactions.shipping_status', 'cancelled')
            ->where('ecommerce_transactions.business_id', $business_id);
            
            if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                $query11->whereDate('ecommerce_transactions.transaction_date', '>=', $start_date)
                    ->whereDate('ecommerce_transactions.transaction_date', '<=', $end_date);
            }
            if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                $query11->whereDate('ecommerce_transactions.transaction_date', $end_date);
            }
    

            $cancelled_order_amount =  $query11->select(
                DB::raw('SUM(ecommerce_transactions.final_total) as cancelled_order_amount'),
            )
            ->first();
             
            // Excahnged Orders Amount
            $query11 = DB::table('ecommerce_transactions')
            ->where('ecommerce_transactions.type', 'sell_return')
            ->where('ecommerce_transactions.status', 'final')
            ->where('ecommerce_transactions.business_id', $business_id);
            
            if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                $query11->whereDate('ecommerce_transactions.transaction_date', '>=', $start_date)
                    ->whereDate('ecommerce_transactions.transaction_date', '<=', $end_date);
            }
            if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                $query11->whereDate('ecommerce_transactions.transaction_date', $end_date);
            }
    

            $exchanged_order_amount =  $query11->select(
                DB::raw('SUM(ecommerce_transactions.final_total) as exchanged_order_amount'),
            )
            ->first();
             
            // Completed Orders Amount
            $query11 = DB::table('ecommerce_transactions')
            ->where('ecommerce_transactions.type', 'sell')
            ->where('ecommerce_transactions.status', 'final')
            ->where('ecommerce_transactions.shipping_status', 'delivered')
            ->where('ecommerce_transactions.business_id', $business_id);
            
            if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
                $query11->whereDate('ecommerce_transactions.transaction_date', '>=', $start_date)
                    ->whereDate('ecommerce_transactions.transaction_date', '<=', $end_date);
            }
            if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
                $query11->whereDate('ecommerce_transactions.transaction_date', $end_date);
            }
    

            $completed_order_amount =  $query11->select(
                DB::raw('SUM(ecommerce_transactions.final_total) as completed_order_amount'),
            )
            ->first();

            return response()->json([
                'total_orders' => $total_orders->total_orders,
                'total_items' => $total_items->total_items,
                'new_orders' => $new_orders->new_orders,
                'total_received_amount' => $received_mount->total_received_amount,
                'total_order_amount' => $total_orders->total_order_amount,
                'profit_loss' => $gross_profit,
                'completed_orders' => $completed_orders->completed,
                'dispatched_order' => $dispatched_orders->dispached,
                'exchanged_orders' => $exchanged_orders->exchanged_orders,
                'cancelled_items' => $cancelled_items->cancelled_quantity,
                'discount_amount' => $discount_amount->discount_amount,
                'cancelled_orders' => $cancelled_orders->cancelled_orders,
                'cancelled_order_amount' => $cancelled_order_amount->cancelled_order_amount,
                'exchanged_order_amount' => $exchanged_order_amount->exchanged_order_amount,
                'completed_order_amount' => $completed_order_amount->completed_order_amount
            ]);
        }
    }

    public function getbrandfolioReport(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
            $query = TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
            )
                ->join(
                    'variations as v',
                    'transaction_sell_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('contacts as c', 't.contact_id', '=', 'c.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->join('business_locations as bl','bl.id','t.location_id')
                ->join('categories','categories.id','p.category_id')
                ->leftjoin('variation_location_details as vld', 'vld.product_id','transaction_sell_lines.product_id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->select(
                    'v.sub_sku',
                    'c.name as customer',
                    'c.supplier_business_name',
                    'c.contact_id',
                    't.id as transaction_id',
                    't.transaction_date as transaction_date',
                    'v.sell_price_inc_tax as unit_price',
                    'transaction_sell_lines.unit_price_inc_tax as unit_sale_price',
                    DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as sell_qty'),
                    DB::raw('((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal'),
                    'bl.name as location_name',
                    'categories.name as category_name',
                )
                // ->get();
                // dd($query);
                ->groupBy('transaction_sell_lines.id');

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->where('t.transaction_date', '>=', $start_date)
                    ->where('t.transaction_date', '<=', $end_date);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            return Datatables::of($query)
                 ->editColumn('distributor', function ($row) {
                     return 'BrandFolio';
                 })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('unit_sale_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_sale_price . '</span>';
                })
                ->editColumn('sell_qty', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->sell_qty . '" data-unit="' . $row->unit . '" >' . (float) $row->sell_qty . '</span> ' .$row->unit;
                })
                ->editColumn('closing_stock', function ($row) {
                    return 0;
                })
                 ->editColumn('subtotal', function ($row) {
                     return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->subtotal . '">' . $row->subtotal . '</span>';
                 })
                ->editColumn('country', function ($row) {
                    return 'Pakistan';
                })
                ->editColumn('store', function ($row) {
                    return $row->location_name;
                })
                ->editColumn('category', function ($row) {
                    return $row->category_name;
                })
                ->rawColumns(['subtotal', 'sell_qty'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id);

        return view('report.brandfolio_report');
    }

    public function getDetailedProductCategory(Request $request)
    {

        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id', null);
        
        $vld_str = '';
        if (!empty($location_id)) {
            $vld_str = "AND vld.location_id=$location_id";
        }

        if ($request->ajax() || true) {
            $variation_id = $request->get('variation_id', null);

            $query = TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
                )
                ->leftJoin(
                    'variations as v',
                    'transaction_sell_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->join('categories as cat', 'p.category_id', '=', 'cat.id')
                ->leftJoin('categories as c2', 'p.sub_category_id', '=', 'c2.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->whereIN('t.type', ['sell','sell_return'])
                ->where('t.status', 'final')
                ->select(
                    'p.image as product_image',
                    DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),
                    // DB::raw('SUM(transaction_sell_lines.quantity) as total_qty_sold'),
                    DB::raw('SUM(transaction_sell_lines.quantity) as total_qty_sold'),
                    DB::raw('SUM(transaction_sell_lines.quantity_returned) as total_returned_quantity'),
                    DB::raw('SUM(transaction_sell_lines.quantity) - SUM(transaction_sell_lines.quantity_returned) as total_net_unit'),
                    DB::raw('SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) as sale_value'),
                    // DB::raw('SUM(t.final_total) as sale_value'),
                    DB::raw('SUM(transaction_sell_lines.quantity_returned * transaction_sell_lines.unit_price_inc_tax) as return_value'),

                    // DB::raw('IF(t.type="sell_return",SUM(transaction_sell_lines.unit_price_inc_tax), 0) as return_value'),
                    'cat.name as category_name',
                    'c2.name as sub_category',
                    'cat.id as category_id'
                );
                // dd($query);
            
            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->where('t.transaction_date', '>=', $start_date)
                    ->where('t.transaction_date', '<=', $end_date);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            $query = $query->groupBy('cat.id')->get();

            return Datatables::of($query)
            ->editColumn('product_image', function ($row) {
                $basePath = config('app.url'); // Use your base URL, e.g., http://127.0.0.1:8000
                
                if (!empty($row->product_image)) {
                    $imagePath = asset('uploads/img/' . $row->product_image);
                } else {
                    $imagePath = asset('img/default.png');
                }
            
                return '<div style="display: flex; justify-content: center; align-items: center;"><img src="' . $imagePath . '" alt="Product image" class="product-thumbnail-small"></div>';
              })
                ->addColumn('category_name', function ($row) {
                    return $row->category_name;
                })
                // ->editColumn('sub_category', function ($row) {
                //     $sub_category = $row->sub_category;
                //     if(!empty($sub_category)){
                //         return $sub_category;
                //     }
                //     else
                //         return "--";
                // }) 
                ->editColumn('total_qty_sold', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency total_qty_sold" data-currency_symbol=false data-orig-value="' . (float)$row->total_qty_sold . '" data-unit="' . $row->unit . '" >' . (float) $row->total_qty_sold . '</span> ' .$row->unit;
                })
                ->editColumn('total_qty_returned', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency total_qty_returned" data-currency_symbol=false data-orig-value="' . (float)$row->total_returned_quantity . '" data-unit="' . $row->unit . '" >' . (float) $row->total_returned_quantity . '</span> ' .$row->unit;
                })
                ->editColumn('total_net_qty', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency total_net_qty" data-currency_symbol=false data-orig-value="' . (float)$row->total_net_unit . '" data-unit="' . $row->unit . '" >' . (float) $row->total_net_unit . '</span> ' .$row->unit;
                })
                ->editColumn('sale_value', function ($row) {
                    $sale = $row->sale_value;
                    return '<span class="display_currency sale_value" data-currency_symbol = true data-orig-value="' . $sale . '">' . $sale . '</span>';
                })
                ->editColumn('return_value', function ($row) {
                    return '<span class="display_currency return_value" data-currency_symbol = true data-orig-value="' . $row->return_value . '">' . $row->return_value . '</span>';
                })
                ->editColumn('subtotal', function ($row) {
                    $net_total = $row->sale_value - $row->return_value;
                    return '<span class="display_currency subtotal" data-currency_symbol = true data-orig-value="' . $net_total . '" data-unit="' . $row->unit . '">' . $net_total . '</span>' . $row->unit;
                })
                
                ->rawColumns(['product_image','subtotal', 'total_qty_sold','total_qty_returned','total_net_qty','sale_value','return_value'])
                ->make(true);
        }
    }

    public function employeeReport(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id', null);
        $start_date  = $request->get('start_date', null);
        $end_date    = $request->get('end_date', null);
        $commission_agent = $request->get('commission_agent', null);

        // Modify start date to include time
        if ($start_date !== null) {
            $start_date .= ' 00:00:00';
        }

        // Modify end date to include time
        if ($end_date !== null) {
            $end_date .= ' 23:59:59';
        }
        // dd($start_date, $end_date);
        if($request->ajax() || true){
            $query = Transaction::leftjoin('transaction_sell_lines as tsl', 'tsl.transaction_id','transactions.id')
            ->join('users','users.id','transactions.commission_agent')
            ->whereIn('transactions.type',['sell'])            
            ->select(
                'users.first_name', 'users.last_name',
                // DB::raw('CONCAT(users.first_name, " " , users.last_name) as user_name'),
                DB::raw('COUNT(transactions.id) as total_invoices'),
                DB::raw('SUM(tsl.quantity) as total_items'),
                DB::raw('SUM(tsl.unit_price_inc_tax * (tsl.quantity)) as total_sales')
            );
            if (!empty($start_date) && !empty($end_date)) {
                $query->where('transactions.transaction_date', '>=', $start_date)
                    ->where('transactions.transaction_date', '<=', $end_date);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('transactions.location_id', $location_id);
            }

            if (!empty($commission_agent)) {
                $query->where('transactions.commission_agent', $commission_agent);
            }

            $result = $query->groupBy('transactions.commission_agent')
            ->get();

            return Datatables::of($result)
                ->addColumn('employee_name', function ($row) {
                    return $row->first_name . ' ' . $row->last_name;
                })
                
                ->editColumn('total_invoices', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency total_invoices" data-currency_symbol=false data-orig-value="' . (float)$row->total_invoices . '" >' . (float) $row->total_invoices . '</span> ';
                })
                ->editColumn('total_items', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency total_items" data-currency_symbol=false data-orig-value="' . (float)$row->total_items . '" >' . (float) $row->total_items . '</span> ';
                })
                // ->editColumn('total_sales', function ($row) {
                    ->editColumn('total_sales', function ($row) {
                        $total = $row->total_sales ;
                        return '<span class="display_currency total_sales" data-currency_symbol = true data-orig-value="' . $total . '">' . $total . '</span>';
                    })                
                ->rawColumns([ 'total_invoices','total_items','total_sales'])
                ->make(true);
        }
    }


    public function getMonthlyReport(Request $request)
    {
        if (!auth()->user()->can('register_report.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = $request->session()->get('user.business_id');

        // dd($request->ajax());
        // //Return the details in ajax call
        // if ($request->ajax()) {
        //     $registers = CashRegister::join(
        //         'users as u',
        //         'u.id',
        //         '=',
        //         'cash_registers.user_id'
        //         )
        //         ->leftJoin(
        //             'business_locations as bl',
        //             'bl.id',
        //             '=',
        //             'cash_registers.location_id'
        //         )
        //         ->leftJoin('cash_register_transactions', 'cash_register_transactions.cash_register_id', '=', 'cash_registers.id')

        //         ->where('cash_registers.business_id', $business_id)
        //         ->where('cash_register_transactions.transaction_type', 'sell')
        //         ->select(
        //             'cash_registers.*',
        //             DB::raw(
        //                 "CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, ''), '<br>', COALESCE(u.email, '')) as user_name"
        //             ),
        //             'bl.name as location_name',
        //             // DB::raw('SUM(cash_register_transactions.amount) as card_amount'),
        //             DB::raw("SUM(IF(cash_register_transactions.pay_method='card', IF(transaction_type='sell', amount, 0), 0)) as card_amount"),
        //             DB::raw("SUM(IF(pay_method='cash', IF(transaction_type='sell', amount, 0), 0)) as cash_amount"),

        //         )
        //         ->groupBy('cash_registers.id');
                
        //         if ($request->input('user_id')){
        //             // dd($request->input('user_id'));
        //             $registers->where('cash_registers.user_id', $request->input('user_id'));
        //         }
        //         if (!empty($request->input('status'))) {
        //             $registers->where('cash_registers.status', $request->input('status'));
        //         }
        //         $start_date = $request->get('start_date');
        //         $end_date = $request->get('end_date');

        //         if (!empty($start_date) && !empty($end_date)) {
        //             $registers->whereDate('cash_registers.created_at', '>=', $start_date)
        //             ->whereDate('cash_registers.created_at', '<=', $end_date);
        //         }
        //         // dd($registers,$request->input('user_id'));
        //     return Datatables::of($registers)
        //         // ->editColumn('total_card_slips', function ($row) {
        //         //     if ($row->status == 'close') {
        //         //         return $row->total_card_slips;
        //         //     } else {
        //         //         return '';
        //         //     }
        //         // })
        //         ->editColumn('card_amount', function ($row) {
        //             if ($row->status == 'close') {
        //                 return '<span class="display_currency sell_qty" data-currency_symbol = true data-orig-value="' . $row->card_amount . '">' . $row->card_amount . '</span>';

        //             } else {
        //                 return '<span class="display_currency sell_qty" data-currency_symbol = true data-orig-value="' . $row->card_amount . '">' . $row->card_amount . '</span>';
        //             }
        //         })
        //         ->editColumn('closed_at', function ($row) {
        //             if ($row->status == 'close') {
        //                 return $this->productUtil->format_date($row->closed_at, true);
        //             } else {
        //                 return '';
        //             }
        //         })
        //         ->editColumn('created_at', function ($row) {
        //             return $this->productUtil->format_date($row->created_at, true);
        //         })
        //         ->editColumn('cash_amount', function ($row) {
        //             if ($row->status == 'close') {
        //                 return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->cash_amount . '">' . $row->cash_amount . '</span>';

        //                 // return '<span class="display_currency row_subtotal" data-currency_symbol="true">' .
        //                 // $row->cash_amount . '</span>';
        //             } else {
        //                 return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->cash_amount . '">' . $row->cash_amount . '</span>';

        //                 // return '<span class="display_currency row_subtotal" data-currency_symbol="true">' .
        //                 // $row->cash_amount . '</span>';                    
        //             }
        //         })
        //         ->editColumn('total_kamai', function ($row) {
        //             $total_kamai = $row->cash_amount + $row->card_amount;
        //             return '<span class="display_currency subtotal" data-currency_symbol = true data-orig-value="' . $total_kamai . '">' . $total_kamai . '</span>';

        //             return $row->cash_amount + $row->card_amount;
        //         })
        //         ->addColumn('action', '<button type="button" data-href="{{action(\'CashRegisterController@show\', [$id])}}" class="btn btn-xs btn-info btn-modal" 
        //             data-container=".view_register"><i class="fas fa-eye" aria-hidden="true"></i> @lang("messages.view")</button> @if($status != "close" && auth()->user()->can("close_cash_register"))<button type="button" data-href="{{action(\'CashRegisterController@getCloseRegister\', [$id])}}" class="btn btn-xs btn-danger btn-modal" 
        //                 data-container=".view_register"><i class="fas fa-window-close"></i> @lang("messages.close")</button> @endif')
        //         ->filterColumn('user_name', function ($query, $keyword) {
        //             $query->whereRaw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, ''), '<br>', COALESCE(u.email, '')) like ?", ["%{$keyword}%"]);
        //         })
        //         ->rawColumns(['action', 'user_name', 'closing_amount','cash_amount','card_amount','total_kamai'])
        //         ->make(true);
        // }

        $users = User::forDropdown($business_id, false);

        return view('report.monthly_report')
                    ->with(compact('users'));
    }

    public function getMenthlyReportData(Request $request)
    {
        if (!auth()->user()->can('register_report.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $registers = CashRegister::join(
                'users as u',
                'u.id',
                '=',
                'cash_registers.user_id'
                )
                ->leftJoin(
                    'business_locations as bl',
                    'bl.id',
                    '=',
                    'cash_registers.location_id'
                )
                ->leftJoin('cash_register_transactions', 'cash_register_transactions.cash_register_id', '=', 'cash_registers.id')

                ->where('cash_registers.business_id', $business_id)
                ->where('cash_register_transactions.transaction_type', 'sell')
                ->select(
                    'cash_registers.*',
                    DB::raw(
                        "CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, ''), '<br>', COALESCE(u.email, '')) as user_name"
                    ),
                    'bl.name as location_name',
                    // DB::raw('SUM(cash_register_transactions.amount) as card_amount'),
                    DB::raw("SUM(IF(cash_register_transactions.pay_method='card', IF(transaction_type='sell', amount, 0), 0)) as card_amount"),
                    DB::raw("SUM(IF(pay_method='cash', IF(transaction_type='sell', amount, 0), 0)) as cash_amount"),

                )
                ->groupBy('cash_registers.id');
                
                if ($request->input('user_id')){
                    // dd($request->input('user_id'));
                    $registers->where('cash_registers.user_id', $request->input('user_id'));
                }
                if (!empty($request->input('status'))) {
                    $registers->where('cash_registers.status', $request->input('status'));
                }
                $start_date = $request->get('start_date');
                $end_date = $request->get('end_date');

                if (!empty($start_date) && !empty($end_date)) {
                    $registers->whereDate('cash_registers.created_at', '>=', $start_date)
                    ->whereDate('cash_registers.created_at', '<=', $end_date);
                }
                // dd($registers,$request->input('user_id'));
            return Datatables::of($registers)
                // ->editColumn('total_card_slips', function ($row) {
                //     if ($row->status == 'close') {
                //         return $row->total_card_slips;
                //     } else {
                //         return '';
                //     }
                // })
                ->editColumn('card_amount', function ($row) {
                    if ($row->status == 'close') {
                        return '<span class="display_currency sell_qty" data-currency_symbol = true data-orig-value="' . $row->card_amount . '">' . $row->card_amount . '</span>';

                    } else {
                        return '<span class="display_currency sell_qty" data-currency_symbol = true data-orig-value="' . $row->card_amount . '">' . $row->card_amount . '</span>';
                    }
                })
                ->editColumn('closed_at', function ($row) {
                    if ($row->status == 'close') {
                        return $this->productUtil->format_date($row->closed_at, true);
                    } else {
                        return '';
                    }
                })
                ->editColumn('location_name', function ($row) {
                    return $row->location_name;
                })
                ->editColumn('created_at', function ($row) {
                    return $this->productUtil->format_date($row->created_at, true);
                })
                ->editColumn('cash_amount', function ($row) {
                    if ($row->status == 'close') {
                        return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->cash_amount . '">' . $row->cash_amount . '</span>';

                        // return '<span class="display_currency row_subtotal" data-currency_symbol="true">' .
                        // $row->cash_amount . '</span>';
                    } else {
                        return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->cash_amount . '">' . $row->cash_amount . '</span>';

                        // return '<span class="display_currency row_subtotal" data-currency_symbol="true">' .
                        // $row->cash_amount . '</span>';                    
                    }
                })
                ->editColumn('total_kamai', function ($row) {
                    $total_kamai = $row->cash_amount + $row->card_amount;
                    return '<span class="display_currency subtotal" data-currency_symbol = true data-orig-value="' . $total_kamai . '">' . $total_kamai . '</span>';

                    return $row->cash_amount + $row->card_amount;
                })
                ->editColumn('merchant_tax', function ($row) {
                    $total_kamai =  $row->card_amount * 0.016;
                    return '<span class="display_currency subtotal" data-currency_symbol = true data-orig-value="' . $total_kamai . '">' . $total_kamai . '</span>';
                })
                ->editColumn('card_amount_after_tax', function ($row) {
                    $merchant_tax =  $row->card_amount * 0.016;
                    $card_amount = $row->card_amount - $merchant_tax;
                    return '<span class="display_currency subtotal" data-currency_symbol = true data-orig-value="' . $card_amount . '">' . $card_amount . '</span>';
                })
                ->editColumn('bank_transfer', function ($row) {
                    $cash_amount =  $row->cash_amount;

                    return '<span class="display_currency subtotal" data-currency_symbol = true data-orig-value="' . $cash_amount . '">' . $cash_amount . '</span>';
                })
                ->editColumn('total_net_amount', function ($row) {
                    $cash_amount =  $row->cash_amount;
                    $merchant_tax =  $row->card_amount * 0.016;
                    $card_amount = $row->card_amount - $merchant_tax;
                    $total_net_amount = $cash_amount + $card_amount;
                    return '<span class="display_currency subtotal" data-currency_symbol = true data-orig-value="' . $total_net_amount . '">' . $total_net_amount . '</span>';
                })
                ->rawColumns(['action', 'user_name', 'closing_amount','cash_amount','card_amount','total_kamai','merchant_tax','total_net_amount','bank_transfer','card_amount_after_tax'])
                ->make(true);
        }
    }
}
