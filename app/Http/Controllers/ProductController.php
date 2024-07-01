<?php

namespace App\Http\Controllers;

use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Media;
use App\Product;
use App\ProductVariation;
use App\PurchaseLine;
use App\SellingPriceGroup;
use App\TaxRate;
use App\EcommercePayment;
use App\EcommerceSellLine;
use App\EcommerceTransaction;
use App\StockAdjustmentLine;
use App\Transaction;
use App\Unit;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Variation;
use App\TransactionSellLine;
use App\VariationGroupPrice;
use App\VariationLocationDetails;
use App\VariationTemplate;
use App\Warranty;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $moduleUtil;

    private $barcode_types;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;

        //barcode types
        $this->barcode_types = $this->productUtil->barcode_types();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('product.view') && !auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);

        if (request()->ajax()) {
            $query = Product::with(['media'])
                ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
                ->join('units', 'products.unit_id', '=', 'units.id')
                ->leftJoin('categories as c1', 'products.category_id', '=', 'c1.id')
                ->leftJoin('categories as c2', 'products.sub_category_id', '=', 'c2.id')
                ->leftJoin('tax_rates', 'products.tax', '=', 'tax_rates.id')
                ->join('variations as v', 'v.product_id', '=', 'products.id')
                ->leftJoin('variation_location_details as vld', 'vld.variation_id', '=', 'v.id')
                ->where('products.business_id', $business_id)
                ->where('products.type', '!=', 'modifier');

            //Filter by location
            $location_id = request()->get('location_id', null);
            $permitted_locations = auth()->user()->permitted_locations();

            if (!empty($location_id) && $location_id != 'none') {
                if ($permitted_locations == 'all' || in_array($location_id, $permitted_locations)) {
                    $query->whereHas('product_locations', function ($query) use ($location_id) {
                        $query->where('product_locations.location_id', '=', $location_id);
                    });
                }
            } elseif ($location_id == 'none') {
                $query->doesntHave('product_locations');
            } else {
                if ($permitted_locations != 'all') {
                    $query->whereHas('product_locations', function ($query) use ($permitted_locations) {
                        $query->whereIn('product_locations.location_id', $permitted_locations);
                    });
                } else {
                    $query->with('product_locations');
                }
            }

            $products = $query->select(
                'products.id',
                'products.name as product',
                'products.type',
                'products.barcode',
                'products.gender',
                'c1.name as category',
                'c2.name as sub_category',
                'units.actual_name as unit',
                'brands.name as brand',
                'tax_rates.name as tax',
                'products.sku',
                'products.image',
                'products.enable_stock',
                'products.is_inactive',
                'products.not_for_selling',
                'products.product_custom_field1',
                'products.product_custom_field2',
                'products.product_custom_field3',
                'products.product_custom_field4',
                DB::raw('SUM(vld.qty_available) as current_stock'),
                DB::raw('MAX(v.sell_price_inc_tax) as max_price'),
                DB::raw('MIN(v.sell_price_inc_tax) as min_price'),
                DB::raw('MAX(v.dpp_inc_tax) as max_purchase_price'),
                DB::raw('MIN(v.dpp_inc_tax) as min_purchase_price')

                )->groupBy('products.id');
                // ->get();
                // dd($products);

            $type = request()->get('type', null);
            if (!empty($type)) {
                $products->where('products.type', $type);
            }

            $category_id = request()->get('category_id', null);
            if (!empty($category_id)) {
                $products->where('products.category_id', $category_id);
            }

            $brand_id = request()->get('brand_id', null);
            if (!empty($brand_id)) {
                $products->where('products.brand_id', $brand_id);
            }

            $unit_id = request()->get('unit_id', null);
            if (!empty($unit_id)) {
                $products->where('products.unit_id', $unit_id);
            }

            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $products->where('products.tax', $tax_id);
            }

            $active_state = request()->get('active_state', null);
            if ($active_state == 'active') {
                $products->Active();
            }
            if ($active_state == 'inactive') {
                $products->Inactive();
            }
            $not_for_selling = request()->get('not_for_selling', null);
            if ($not_for_selling == 'true') {
                $products->ProductNotForSales();
            }

            $woocommerce_enabled = request()->get('woocommerce_enabled', 0);
            if ($woocommerce_enabled == 1) {
                $products->where('products.woocommerce_disable_sync', 0);
            }

            if (!empty(request()->get('repair_model_id'))) {
                $products->where('products.repair_model_id', request()->get('repair_model_id'));
            }

            return Datatables::of($products)
                ->addColumn(
                    'product_locations',
                    function ($row) {
                        return $row->product_locations->implode('name', ', ');
                    }
                )
                ->editColumn('category', '{{$category}}')
                ->editColumn('sub_category', function ($row) {
                    $sub_category = $row->sub_category;
                    if(!empty($sub_category)){
                        return $sub_category;
                    }
                    else
                        return "--";
                })
                ->addColumn(
                    'action',
                    function ($row) use ($selling_price_group_count) {
                        $html =
                        '<div class="btn-group"><button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'. __("messages.actions") . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button><ul class="dropdown-menu dropdown-menu-left" role="menu">';
                        // <li><a href="' . action('LabelsController@show') . '?product_id=' . $row->id . '" data-toggle="tooltip" title="' . __('lang_v1.label_help') . '"><i class="fa fa-barcode"></i> ' . __('barcode.labels') . '</a></li>';

                        if (auth()->user()->can('product.view')) {
                            $html .=
                            '<li><a href="' . action('ProductController@view', [$row->id]) . '" class="view-product"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';
                        }

                        if (auth()->user()->can('product.update')) {
                            $html .=
                            '<li><a href="' . action('ProductController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                        }

                        // if (auth()->user()->can('product.delete')) {
                        //     $html .=
                        //     '<li><a href="' . action('ProductController@destroy', [$row->id]) . '" class="delete-product"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        // }

                        if ($row->is_inactive == 1) {
                            $html .=
                            '<li><a href="' . action('ProductController@activate', [$row->id]) . '" class="activate-product"><i class="fas fa-check-circle"></i> ' . __("lang_v1.reactivate") . '</a></li>';
                        }

                        // $html .= '<li class="divider"></li>';

                        // if ($row->enable_stock == 1 && auth()->user()->can('product.opening_stock')) {
                        //     $html .=
                        //     '<li><a href="#" data-href="' . action('OpeningStockController@add', ['product_id' => $row->id]) . '" class="add-opening-stock"><i class="fa fa-database"></i> ' . __("lang_v1.add_edit_opening_stock") . '</a></li>';
                        // }

                        if (auth()->user()->can('product.view')) {
                            $html .=
                            '<li><a href="' . action('ProductController@productStockHistory', [$row->id]) . '"><i class="fas fa-history"></i> ' . __("Product Ledger") . '</a></li>';
                        }
                        if (auth()->user()->can('product.view')) {
                            $html .=
                            '<li><a href="' . action('ProductController@productHistoryAJAX', [$row->id]) . '"><i class="fas fa-history"></i> ' . __("Product History") . '</a></li>';
                        }

                        // if (auth()->user()->can('product.create')) {
            
                        //     if ($selling_price_group_count > 0) {
                        //         $html .=
                        //         '<li><a href="' . action('ProductController@addSellingPrices', [$row->id]) . '"><i class="fas fa-money-bill-alt"></i> ' . __("lang_v1.add_selling_price_group_prices") . '</a></li>';
                        //     }

                        //     $html .=
                        //         '<li><a href="' . action('ProductController@create', ["d" => $row->id]) . '"><i class="fa fa-copy"></i> ' . __("lang_v1.duplicate_product") . '</a></li>';
                        // }

                        // if (!empty($row->media->first())) {

                        //     $html .=
                        //         '<li><a href="' . $row->media->first()->display_url . '" download="'.$row->media->first()->display_name.'"><i class="fas fa-download"></i> ' . __("lang_v1.product_brochure") . '</a></li>';
                        // }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->editColumn('product', function ($row) {
                    $product = $row->is_inactive == 1 ? $row->product . ' <span class="label bg-gray">' . __("lang_v1.inactive") .'</span>' : $row->product;

                    $product = $row->not_for_selling == 1 ? $product . ' <span class="label bg-gray">' . __("lang_v1.not_for_selling") .
                        '</span>' : $product;
                    
                    return $product;
                })
                ->editColumn('image', function ($row) {
                    return '<div style="display: flex;"><img src="' . $row->image_url . '" alt="Product image" class="product-thumbnail-small"></div>';
                })
                // ->editColumn('type', '@lang("lang_v1." . $type)')
                ->addColumn('mass_delete', function ($row) {
                    return  '<input type="checkbox" class="row-select" value="' . $row->id .'">' ;
                })
                ->editColumn('current_stock', '@if($enable_stock == 1) {{@number_format($current_stock)}} @else -- @endif')
                ->addColumn(
                    'purchase_price',
                    '<div style="white-space: nowrap;" class="unit_price">@format_currency($min_purchase_price) @if($max_purchase_price != $min_purchase_price && $type == "variable") -  @format_currency($max_purchase_price)@endif </div>'
                )
                ->addColumn(
                    'selling_price',
                    '<div style="white-space: nowrap;" class="selling_price">@format_currency($min_price) @if($max_price != $min_price && $type == "variable") -  @format_currency($max_price)@endif </div>'
                )
                ->filterColumn('products.sku', function ($query, $keyword) {
                    $query->whereHas('variations', function($q) use($keyword){
                            $q->where('sub_sku', 'like', "%{$keyword}%");
                        })
                    ->orWhere('products.sku', 'like', "%{$keyword}%");
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("product.view")) {
                            return  action('ProductController@view', [$row->id]) ;
                        } else {
                            return '';
                        }
                    }])
                ->rawColumns(['action', 'image', 'mass_delete', 'product', 'selling_price', 'purchase_price', 'category'])
                ->make(true);
        }

        $rack_enabled = (request()->session()->get('business.enable_racks') || request()->session()->get('business.enable_row') || request()->session()->get('business.enable_position'));

        $categories = Category::forDropdown($business_id, 'product');

        $brands = Brands::forDropdown($business_id);

        $units = Unit::forDropdown($business_id);

        // $tax_dropdown = TaxRate::forBusinessDropdown($business_id, false);
        // $taxes = $tax_dropdown['tax_rates'];

        $business_locations = BusinessLocation::forDropdown($business_id);
        $business_locations->prepend(__('lang_v1.none'), 'none');

        if ($this->moduleUtil->isModuleInstalled('Manufacturing') && (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module'))) {
            $show_manufacturing_data = true;
        } else {
            $show_manufacturing_data = false;
        }

        //list product screen filter from module
        $pos_module_data = $this->moduleUtil->getModuleData('get_filters_for_list_product_screen');

        $is_woocommerce = $this->moduleUtil->isModuleInstalled('Woocommerce');

        return view('product.index')
            ->with(compact(
                'rack_enabled',
                'categories',
                'brands',
                'units',
                // 'taxes',
                'business_locations',
                'show_manufacturing_data',
                'pos_module_data',
                'is_woocommerce'
            ));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not, then check for products quota
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        } elseif (!$this->moduleUtil->isQuotaAvailable('products', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('products', $business_id, action('ProductController@index'));
        }

        $categories = Category::forDropdown($business_id, 'product');

        $brands = Brands::forDropdown($business_id);
        $units = Unit::forDropdown($business_id, true);

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        $barcode_types = $this->barcode_types;
        $barcode_default =  $this->productUtil->barcode_default();

        $default_profit_percent = request()->session()->get('business.default_profit_percent');;

        //Get all business locations
        $business_locations = BusinessLocation::fortransferDropdown($business_id);

        //Duplicate product
        $duplicate_product = null;
        $rack_details = null;

        $sub_categories = [];
        if (!empty(request()->input('d'))) {
            $duplicate_product = Product::where('business_id', $business_id)->find(request()->input('d'));
            $duplicate_product->name .= ' (copy)';

            if (!empty($duplicate_product->category_id)) {
                $sub_categories = Category::where('business_id', $business_id)
                        ->where('parent_id', $duplicate_product->category_id)
                        ->pluck('name', 'id')
                        ->toArray();
            }

            //Rack details
            if (!empty($duplicate_product->id)) {
                $rack_details = $this->productUtil->getRackDetails($business_id, $duplicate_product->id);
            }
        }

        $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);

        $module_form_parts = $this->moduleUtil->getModuleData('product_form_part');
        $product_types = $this->product_types();

        $common_settings = session()->get('business.common_settings');
        $warranties = Warranty::forDropdown($business_id);

        //product screen view from module
        $pos_module_data = $this->moduleUtil->getModuleData('get_product_screen_top_view');

        return view('product.create')
            ->with(compact('categories', 'brands', 'units', 'taxes', 'barcode_types', 'default_profit_percent', 'tax_attributes', 'barcode_default', 'business_locations', 'duplicate_product', 'sub_categories', 'rack_details', 'selling_price_group_count', 'module_form_parts', 'product_types', 'common_settings', 'warranties', 'pos_module_data'));
    }

    private function product_types()
    {
        //Product types also includes modifier.
        return ['single' => __('lang_v1.single'),
                'variable' => __('lang_v1.variable'),
                'combo' => __('lang_v1.combo')
            ];
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
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $form_fields = ['name', 'brand_id', 'unit_id', 'category_id', 'tax', 'type', 'barcode_type', 'sku', 'alert_quantity', 'tax_type', 'weight', 'product_custom_field1', 'product_custom_field2', 'product_custom_field3', 'product_custom_field4', 'product_description', 'sub_unit_ids', 'barcode' ,'gender'];

            $module_form_fields = $this->moduleUtil->getModuleFormField('product_form_fields');
            if (!empty($module_form_fields)) {
                $form_fields = array_merge($form_fields, $module_form_fields);
            }
            
            $product_details = $request->only($form_fields);
            $product_details['business_id'] = $business_id;
            $product_details['created_by'] = $request->session()->get('user.id');

            $product_details['enable_stock'] = (!empty($request->input('enable_stock')) &&  $request->input('enable_stock') == 1) ? 1 : 0;
            $product_details['not_for_selling'] = (!empty($request->input('not_for_selling')) &&  $request->input('not_for_selling') == 1) ? 1 : 0;

            if (!empty($request->input('sub_category_id'))) {
                $product_details['sub_category_id'] = $request->input('sub_category_id') ;
            }

            if (empty($product_details['sku'])) {
                $product_details['sku'] = ' ';
            }

            $expiry_enabled = $request->session()->get('business.enable_product_expiry');
            if (!empty($request->input('expiry_period_type')) && !empty($request->input('expiry_period')) && !empty($expiry_enabled) && ($product_details['enable_stock'] == 1)) {
                $product_details['expiry_period_type'] = $request->input('expiry_period_type');
                $product_details['expiry_period'] = $this->productUtil->num_uf($request->input('expiry_period'));
            }

            if (!empty($request->input('enable_sr_no')) &&  $request->input('enable_sr_no') == 1) {
                $product_details['enable_sr_no'] = 1 ;
            }

            //upload document
            $product_details['image'] = $this->productUtil->uploadFile($request, 'image', config('constants.product_img_path'), 'image');
            $common_settings = session()->get('business.common_settings');

            $product_details['warranty_id'] = !empty($request->input('warranty_id')) ? $request->input('warranty_id') : null;

            DB::beginTransaction();

            $product = Product::create($product_details);

            if (empty(trim($request->input('sku')))) {
                $sku = $this->productUtil->generateProductSku($product->id);
                $product->sku = $sku;
                $product->save();
            }

            //Add product locations
            $product_locations = $request->input('product_locations');
            if (!empty($product_locations)) {
                $product->product_locations()->sync($product_locations);
            }
            
            if ($product->type == 'single') {
                $this->productUtil->createSingleProductVariation($product->id, $product->sku, $request->input('single_dpp'), $request->input('single_dpp_inc_tax'), $request->input('profit_percent'), $request->input('single_dsp'), $request->input('single_dsp_inc_tax'));
            } elseif ($product->type == 'variable') {
                if (!empty($request->input('product_variation'))) {
                    $input_variations = $request->input('product_variation');
                    $this->productUtil->createVariableProductVariations($product->id, $input_variations);
                }
            } elseif ($product->type == 'combo') {

                //Create combo_variations array by combining variation_id and quantity.
                $combo_variations = [];
                if (!empty($request->input('composition_variation_id'))) {
                    $composition_variation_id = $request->input('composition_variation_id');
                    $quantity = $request->input('quantity');
                    $unit = $request->input('unit');

                    foreach ($composition_variation_id as $key => $value) {
                        $combo_variations[] = [
                                'variation_id' => $value,
                                'quantity' => $this->productUtil->num_uf($quantity[$key]),
                                'unit_id' => $unit[$key]
                            ];
                    }
                }

                $this->productUtil->createSingleProductVariation($product->id, $product->sku, $request->input('item_level_purchase_price_total'), $request->input('purchase_price_inc_tax'), $request->input('profit_percent'), $request->input('selling_price'), $request->input('selling_price_inc_tax'), $combo_variations);
            }

            //Add product racks details.
            $product_racks = $request->get('product_racks', null);
            if (!empty($product_racks)) {
                $this->productUtil->addRackDetails($business_id, $product->id, $product_racks);
            }

            //Set Module fields
            if (!empty($request->input('has_module_data'))) {
                $this->moduleUtil->getModuleData('after_product_saved', ['product' => $product, 'request' => $request]);
            }

            Media::uploadMedia($product->business_id, $product, $request, 'product_brochure', true);

            DB::commit();
            $output = ['success' => 1,
                            'msg' => __('product.product_added_success')
                        ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __("messages.something_went_wrong")
                        ];
            return redirect('products')->with('status', $output);
        }

        if ($request->input('submit_type') == 'submit_n_add_opening_stock') {
            return redirect()->action(
                'OpeningStockController@add',
                ['product_id' => $product->id]
            );
        } elseif ($request->input('submit_type') == 'submit_n_add_selling_prices') {
            return redirect()->action(
                'ProductController@addSellingPrices',
                [$product->id]
            );
        } elseif ($request->input('submit_type') == 'save_n_add_another') {
            return redirect()->action(
                'ProductController@create'
            )->with('status', $output);
        }

        return redirect('products')->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $details = $this->productUtil->getRackDetails($business_id, $id, true);

        return view('product.show')->with(compact('details'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::forDropdown($business_id);
        
        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        $barcode_types = $this->barcode_types;
        
        $product = Product::where('business_id', $business_id)
                            ->with(['product_locations'])
                            ->where('id', $id)
                            ->firstOrFail();

        //Sub-category
        $sub_categories = [];
        $sub_categories = Category::where('business_id', $business_id)
                        ->where('parent_id', $product->category_id)
                        ->pluck('name', 'id')
                        ->toArray();
        $sub_categories = [ "" => "None"] + $sub_categories;
        
        $default_profit_percent = request()->session()->get('business.default_profit_percent');

        //Get units.
        $units = Unit::forDropdown($business_id, true);
        $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id, true);
        
        //Get all business locations
        $business_locations = BusinessLocation::fortransferDropdown($business_id);
        //Rack details
        $rack_details = $this->productUtil->getRackDetails($business_id, $id);

        $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);

        $module_form_parts = $this->moduleUtil->getModuleData('product_form_part');
        $product_types = $this->product_types();
        $common_settings = session()->get('business.common_settings');
        $warranties = Warranty::forDropdown($business_id);

        //product screen view from module
        $pos_module_data = $this->moduleUtil->getModuleData('get_product_screen_top_view');

        return view('product.edit')
                ->with(compact('categories', 'brands', 'units', 'sub_units', 'taxes', 'tax_attributes', 'barcode_types', 'product', 'sub_categories', 'default_profit_percent', 'business_locations', 'rack_details', 'selling_price_group_count', 'module_form_parts', 'product_types', 'common_settings', 'warranties', 'pos_module_data'));
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
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $product_details = $request->only(['name', 'brand_id', 'unit_id', 'category_id', 'tax', 'barcode_type', 'sku', 'alert_quantity', 'tax_type', 'weight', 'product_custom_field1', 'product_custom_field2', 'product_custom_field3', 'product_custom_field4', 'product_description', 'sub_unit_ids','barcode', 'gender']);

            DB::beginTransaction();
            
            $product = Product::where('business_id', $business_id)
                                ->where('id', $id)
                                ->with(['product_variations'])
                                ->first();

            $module_form_fields = $this->moduleUtil->getModuleFormField('product_form_fields');
            if (!empty($module_form_fields)) {
                foreach ($module_form_fields as $column) {
                    $product->$column = $request->input($column);
                }
            }
            
            $product->name = $product_details['name'];
            $product->brand_id = $product_details['brand_id'];
            $product->unit_id = $product_details['unit_id'];
            $product->category_id = $product_details['category_id'];
            $product->tax = $product_details['tax'];
            $product->barcode_type = $product_details['barcode_type'];
            $product->sku = $product_details['sku'];
            $product->barcode = $product_details['barcode'];
            $product->gender = $product_details['gender'];
            $product->alert_quantity = $product_details['alert_quantity'];
            $product->tax_type = $product_details['tax_type'];
            $product->weight = $product_details['weight'];
            $product->product_custom_field1 = $product_details['product_custom_field1'];
            $product->product_custom_field2 = $product_details['product_custom_field2'];
            $product->product_custom_field3 = $product_details['product_custom_field3'];
            $product->product_custom_field4 = $product_details['product_custom_field4'];
            $product->product_description = $product_details['product_description'];
            $product->sub_unit_ids = !empty($product_details['sub_unit_ids']) ? $product_details['sub_unit_ids'] : null;
            $product->warranty_id = !empty($request->input('warranty_id')) ? $request->input('warranty_id') : null;

            if (!empty($request->input('enable_stock')) &&  $request->input('enable_stock') == 1) {
                $product->enable_stock = 1;
            } else {
                $product->enable_stock = 0;
            }

            $product->not_for_selling = (!empty($request->input('not_for_selling')) &&  $request->input('not_for_selling') == 1) ? 1 : 0;

            if (!empty($request->input('sub_category_id'))) {
                $product->sub_category_id = $request->input('sub_category_id');
            } else {
                $product->sub_category_id = null;
            }
            
            $expiry_enabled = $request->session()->get('business.enable_product_expiry');
            if (!empty($expiry_enabled)) {
                if (!empty($request->input('expiry_period_type')) && !empty($request->input('expiry_period')) && ($product->enable_stock == 1)) {
                    $product->expiry_period_type = $request->input('expiry_period_type');
                    $product->expiry_period = $this->productUtil->num_uf($request->input('expiry_period'));
                } else {
                    $product->expiry_period_type = null;
                    $product->expiry_period = null;
                }
            }

            if (!empty($request->input('enable_sr_no')) &&  $request->input('enable_sr_no') == 1) {
                $product->enable_sr_no = 1;
            } else {
                $product->enable_sr_no = 0;
            }

            //upload document
            $file_name = $this->productUtil->uploadFile($request, 'image', config('constants.product_img_path'), 'image');
            if (!empty($file_name)) {

                //If previous image found then remove
                if (!empty($product->image_path) && file_exists($product->image_path)) {
                    unlink($product->image_path);
                }
                
                $product->image = $file_name;
                //If product image is updated update woocommerce media id
                if (!empty($product->woocommerce_media_id)) {
                    $product->woocommerce_media_id = null;
                }
            }

            $product->save();
            $product->touch();

            //Add product locations
            $product_locations = !empty($request->input('product_locations')) ?
                                $request->input('product_locations') : [];
            $product->product_locations()->sync($product_locations);
            
            if ($product->type == 'single') {
                $single_data = $request->only(['single_variation_id', 'single_dpp', 'single_dpp_inc_tax', 'single_dsp_inc_tax', 'profit_percent', 'single_dsp']);
                $variation = Variation::find($single_data['single_variation_id']);

                $variation->sub_sku = $product->sku;
                $variation->default_purchase_price = $this->productUtil->num_uf($single_data['single_dpp']);
                $variation->dpp_inc_tax = $this->productUtil->num_uf($single_data['single_dpp_inc_tax']);
                $variation->profit_percent = $this->productUtil->num_uf($single_data['profit_percent']);
                $variation->default_sell_price = $this->productUtil->num_uf($single_data['single_dsp']);
                $variation->sell_price_inc_tax = $this->productUtil->num_uf($single_data['single_dsp_inc_tax']);
                $variation->save();

                Media::uploadMedia($product->business_id, $variation, $request, 'variation_images');
            } elseif ($product->type == 'variable') {
                //Update existing variations
                $input_variations_edit = $request->get('product_variation_edit');
                if (!empty($input_variations_edit)) {
                    $this->productUtil->updateVariableProductVariations($product->id, $input_variations_edit);
                }

                //Add new variations created.
                $input_variations = $request->input('product_variation');
                if (!empty($input_variations)) {
                    $this->productUtil->createVariableProductVariations($product->id, $input_variations);
                }
            } elseif ($product->type == 'combo') {

                //Create combo_variations array by combining variation_id and quantity.
                $combo_variations = [];
                if (!empty($request->input('composition_variation_id'))) {
                    $composition_variation_id = $request->input('composition_variation_id');
                    $quantity = $request->input('quantity');
                    $unit = $request->input('unit');

                    foreach ($composition_variation_id as $key => $value) {
                        $combo_variations[] = [
                                'variation_id' => $value,
                                'quantity' => $quantity[$key],
                                'unit_id' => $unit[$key]
                            ];
                    }
                }

                $variation = Variation::find($request->input('combo_variation_id'));
                $variation->sub_sku = $product->sku;
                $variation->default_purchase_price = $this->productUtil->num_uf($request->input('item_level_purchase_price_total'));
                $variation->dpp_inc_tax = $this->productUtil->num_uf($request->input('purchase_price_inc_tax'));
                $variation->profit_percent = $this->productUtil->num_uf($request->input('profit_percent'));
                $variation->default_sell_price = $this->productUtil->num_uf($request->input('selling_price'));
                $variation->sell_price_inc_tax = $this->productUtil->num_uf($request->input('selling_price_inc_tax'));
                $variation->combo_variations = $combo_variations;
                $variation->save();
            }

            //Add product racks details.
            $product_racks = $request->get('product_racks', null);
            if (!empty($product_racks)) {
                $this->productUtil->addRackDetails($business_id, $product->id, $product_racks);
            }

            $product_racks_update = $request->get('product_racks_update', null);
            if (!empty($product_racks_update)) {
                $this->productUtil->updateRackDetails($business_id, $product->id, $product_racks_update);
            }

            //Set Module fields
            if (!empty($request->input('has_module_data'))) {
                $this->moduleUtil->getModuleData('after_product_saved', ['product' => $product, 'request' => $request]);
            }
            
            Media::uploadMedia($product->business_id, $product, $request, 'product_brochure', true);
            
            DB::commit();
            $output = ['success' => 1,
                            'msg' => __('product.product_updated_success')
                        ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => $e->getMessage()
                        ];
        }

        if ($request->input('submit_type') == 'update_n_edit_opening_stock') {
            return redirect()->action(
                'OpeningStockController@add',
                ['product_id' => $product->id]
            );
        } elseif ($request->input('submit_type') == 'submit_n_add_selling_prices') {
            return redirect()->action(
                'ProductController@addSellingPrices',
                [$product->id]
            );
        } elseif ($request->input('submit_type') == 'save_n_add_another') {
            return redirect()->action(
                'ProductController@create'
            )->with('status', $output);
        }

        return redirect('products')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('product.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $can_be_deleted = true;
                $error_msg = '';

                //Check if any purchase or transfer exists
                $count = PurchaseLine::join(
                    'transactions as T',
                    'purchase_lines.transaction_id',
                    '=',
                    'T.id'
                )
                                    ->whereIn('T.type', ['purchase'])
                                    ->where('T.business_id', $business_id)
                                    ->where('purchase_lines.product_id', $id)
                                    ->count();
                if ($count > 0) {
                    $can_be_deleted = false;
                    $error_msg = __('lang_v1.purchase_already_exist');
                } else {
                    //Check if any opening stock sold
                    $count = PurchaseLine::join(
                        'transactions as T',
                        'purchase_lines.transaction_id',
                        '=',
                        'T.id'
                     )
                                    ->where('T.type', 'opening_stock')
                                    ->where('T.business_id', $business_id)
                                    ->where('purchase_lines.product_id', $id)
                                    ->where('purchase_lines.quantity_sold', '>', 0)
                                    ->count();
                    if ($count > 0) {
                        $can_be_deleted = false;
                        $error_msg = __('lang_v1.opening_stock_sold');
                    } else {
                        //Check if any stock is adjusted
                        $count = PurchaseLine::join(
                            'transactions as T',
                            'purchase_lines.transaction_id',
                            '=',
                            'T.id'
                        )
                                    ->where('T.business_id', $business_id)
                                    ->where('purchase_lines.product_id', $id)
                                    ->where('purchase_lines.quantity_adjusted', '>', 0)
                                    ->count();
                        if ($count > 0) {
                            $can_be_deleted = false;
                            $error_msg = __('lang_v1.stock_adjusted');
                        }
                    }
                }

                $product = Product::where('id', $id)
                                ->where('business_id', $business_id)
                                ->with('variations')
                                ->first();
        
                //Check if product is added as an ingredient of any recipe
                if ($this->moduleUtil->isModuleInstalled('Manufacturing')) {
                    $variation_ids = $product->variations->pluck('id');

                    $exists_as_ingredient = \Modules\Manufacturing\Entities\MfgRecipeIngredient::whereIn('variation_id', $variation_ids)
                        ->exists();
                        if ($exists_as_ingredient) {
                            $can_be_deleted = false;
                            $error_msg = __('manufacturing::lang.added_as_ingredient');
                        }
                }

                if ($can_be_deleted) {
                    if (!empty($product)) {
                        DB::beginTransaction();
                        //Delete variation location details
                        VariationLocationDetails::where('product_id', $id)
                                                ->delete();
                        $product->delete();

                        DB::commit();
                    }

                    $output = ['success' => true,
                                'msg' => __("lang_v1.product_delete_success")
                            ];
                } else {
                    $output = ['success' => false,
                                'msg' => $error_msg
                            ];
                }
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                
                $output = ['success' => false,
                                'msg' => __("messages.something_went_wrong")
                            ];
            }

            return $output;
        }
    }
    
    /**
     * Get subcategories list for a category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getSubCategories(Request $request)
    {
        if (!empty($request->input('cat_id'))) {
            $category_id = $request->input('cat_id');
            $business_id = $request->session()->get('user.business_id');
            $sub_categories = Category::where('business_id', $business_id)
                        ->where('parent_id', $category_id)
                        ->select(['name', 'id'])
                        ->get();
            $html = '<option value="">None</option>';
            if (!empty($sub_categories)) {
                foreach ($sub_categories as $sub_category) {
                    $html .= '<option value="' . $sub_category->id .'">' .$sub_category->name . '</option>';
                }
            }
            echo $html;
            exit;
        }
    }

    /**
     * Get product form parts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getProductVariationFormPart(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $business = Business::findorfail($business_id);
        $profit_percent = $business->default_profit_percent;

        $action = $request->input('action');
        if ($request->input('action') == "add") {
            if ($request->input('type') == 'single') {
                return view('product.partials.single_product_form_part')
                        ->with(['profit_percent' => $profit_percent]);
            } elseif ($request->input('type') == 'variable') {
                $variation_templates = VariationTemplate::where('business_id', $business_id)->pluck('name', 'id')->toArray();
                $variation_templates = [ "" => __('messages.please_select')] + $variation_templates;

                return view('product.partials.variable_product_form_part')
                        ->with(compact('variation_templates', 'profit_percent', 'action'));
            } elseif ($request->input('type') == 'combo') {
                return view('product.partials.combo_product_form_part')
                ->with(compact('profit_percent', 'action'));
            }
        } elseif ($request->input('action') == "edit" || $request->input('action') == "duplicate") {
            $product_id = $request->input('product_id');
            $action = $request->input('action');
            if ($request->input('type') == 'single') {
                $product_deatails = ProductVariation::where('product_id', $product_id)
                    ->with(['variations', 'variations.media'])
                    ->first();

                return view('product.partials.edit_single_product_form_part')
                            ->with(compact('product_deatails', 'action'));
            } elseif ($request->input('type') == 'variable') {
                $product_variations = ProductVariation::where('product_id', $product_id)
                        ->with(['variations', 'variations.media'])
                        ->get();
                return view('product.partials.variable_product_form_part')
                        ->with(compact('product_variations', 'profit_percent', 'action'));
            } elseif ($request->input('type') == 'combo') {
                $product_deatails = ProductVariation::where('product_id', $product_id)
                    ->with(['variations', 'variations.media'])
                    ->first();
                $combo_variations = $this->productUtil->__getComboProductDetails($product_deatails['variations'][0]->combo_variations, $business_id);

                $variation_id = $product_deatails['variations'][0]->id;
                $profit_percent = $product_deatails['variations'][0]->profit_percent;
                return view('product.partials.combo_product_form_part')
                ->with(compact('combo_variations', 'profit_percent', 'action', 'variation_id'));
            }
        }
    }
    
    /**
     * Get product form parts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getVariationValueRow(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $business = Business::findorfail($business_id);
        $profit_percent = $business->default_profit_percent;

        $variation_index = $request->input('variation_row_index');
        $value_index = $request->input('value_index') + 1;

        $row_type = $request->input('row_type', 'add');

        return view('product.partials.variation_value_row')
                ->with(compact('profit_percent', 'variation_index', 'value_index', 'row_type'));
    }

    /**
     * Get product form parts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getProductVariationRow(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $business = Business::findorfail($business_id);
        $profit_percent = $business->default_profit_percent;

        $variation_templates = VariationTemplate::where('business_id', $business_id)
                                                ->pluck('name', 'id')->toArray();
        $variation_templates = [ "" => __('messages.please_select')] + $variation_templates;

        $row_index = $request->input('row_index', 0);
        $action = $request->input('action');

        return view('product.partials.product_variation_row')
                    ->with(compact('variation_templates', 'row_index', 'action', 'profit_percent'));
    }

    /**
     * Get product form parts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getVariationTemplate(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $business = Business::findorfail($business_id);
        $profit_percent = $business->default_profit_percent;

        $template = VariationTemplate::where('id', $request->input('template_id'))
                                                ->with(['values'])
                                                ->first();
        $row_index = $request->input('row_index');

        return view('product.partials.product_variation_template')
                    ->with(compact('template', 'row_index', 'profit_percent'));
    }

    /**
     * Return the view for combo product row
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getComboProductEntryRow(Request $request)
    {
        if (request()->ajax()) {
            $product_id = $request->input('product_id');
            $variation_id = $request->input('variation_id');
            $business_id = $request->session()->get('user.business_id');

            if (!empty($product_id)) {
                $product = Product::where('id', $product_id)
                        ->with(['unit'])
                        ->first();

                $query = Variation::where('product_id', $product_id)
                        ->with(['product_variation']);

                if ($variation_id !== '0') {
                    $query->where('id', $variation_id);
                }
                $variations =  $query->get();

                $sub_units = $this->productUtil->getSubUnits($business_id, $product['unit']->id);

                return view('product.partials.combo_product_entry_row')
                ->with(compact('product', 'variations', 'sub_units'));
            }
        }
    }

    /**
     * Retrieves products list.
     *
     * @param  string  $q
     * @param  boolean  $check_qty
     *
     * @return JSON
     */
    public function getProducts()
    {
        if (request()->ajax()) {
            $search_term = request()->input('term', '');
            $location_id = request()->input('location_id', null);
            $check_qty = request()->input('check_qty', false);
            $price_group_id = request()->input('price_group', null);
            $business_id = request()->session()->get('user.business_id');
            $not_for_selling = request()->get('not_for_selling', null);
            $price_group_id = request()->input('price_group', '');
            $product_types = request()->get('product_types', []);

            $search_fields = request()->get('search_fields', ['name', 'sku','barcode']);

            if (in_array('sku', $search_fields)) {
                $search_fields[] = 'sub_sku';
            }
            $search_fields[] = 'barcode';
            $result = $this->productUtil->filterProduct($business_id, $search_term, $location_id, $not_for_selling, $price_group_id, $product_types, $search_fields, $check_qty);

            return json_encode($result);
        }
    }

    /**
     * Retrieves products list without variation list
     *
     * @param  string  $q
     * @param  boolean  $check_qty
     *
     * @return JSON
     */
    public function getProductsWithoutVariations()
    {
        if (request()->ajax()) {
            $term = request()->input('term', '');
            //$location_id = request()->input('location_id', '');

            //$check_qty = request()->input('check_qty', false);

            $business_id = request()->session()->get('user.business_id');

            $products = Product::join('variations', 'products.id', '=', 'variations.product_id')
                ->where('products.business_id', $business_id)
                ->where('products.type', '!=', 'modifier');
                
            //Include search
            if (!empty($term)) {
                $products->where(function ($query) use ($term) {
                    $query->where('products.name', 'like', '%' . $term .'%');
                    $query->orWhere('sku', 'like', '%' . $term .'%');
                    $query->orWhere('sub_sku', 'like', '%' . $term .'%');
                });
            }

            //Include check for quantity
            // if($check_qty){
            //     $products->where('VLD.qty_available', '>', 0);
            // }
            
            $products = $products->groupBy('products.id')
                ->select(
                    'products.id as product_id',
                    'products.name',
                    'products.type',
                    'products.enable_stock',
                    'products.sku'
                )
                    ->orderBy('products.name')
                    ->get();
            return json_encode($products);
        }
    }

    /**
     * Checks if product sku already exists.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkProductSku(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $sku = $request->input('sku');
        $product_id = $request->input('product_id');

        //check in products table
        $query = Product::where('business_id', $business_id)
                        ->where('sku', $sku);
        if (!empty($product_id)) {
            $query->where('id', '!=', $product_id);
        }
        $count = $query->count();
        
        //check in variation table if $count = 0
        if ($count == 0) {
            $count = Variation::where('sub_sku', $sku)
                            ->join('products', 'variations.product_id', '=', 'products.id')
                            ->where('product_id', '!=', $product_id)
                            ->where('business_id', $business_id)
                            ->count();
        }
        if ($count == 0) {
            echo "true";
            exit;
        } else {
            echo "false";
            exit;
        }
    }

    /**
     * Loads quick add product modal.
     *
     * @return \Illuminate\Http\Response
     */
    public function quickAdd()
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $product_name = !empty(request()->input('product_name'))? request()->input('product_name') : '';

        $product_for = !empty(request()->input('product_for'))? request()->input('product_for') : null;

        $business_id = request()->session()->get('user.business_id');
        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::forDropdown($business_id);
        $units = Unit::forDropdown($business_id, true);

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        $barcode_types = $this->barcode_types;

        $default_profit_percent = Business::where('id', $business_id)->value('default_profit_percent');

        $locations = BusinessLocation::forDropdown($business_id);

        $enable_expiry = request()->session()->get('business.enable_product_expiry');
        $enable_lot = request()->session()->get('business.enable_lot_number');

        $module_form_parts = $this->moduleUtil->getModuleData('product_form_part');

        //Get all business locations
        $business_locations = BusinessLocation::forDropdown($business_id);

        $common_settings = session()->get('business.common_settings');
        $warranties = Warranty::forDropdown($business_id);

        return view('product.partials.quick_add_product')
                ->with(compact('categories', 'brands', 'units', 'taxes', 'barcode_types', 'default_profit_percent', 'tax_attributes', 'product_name', 'locations', 'product_for', 'enable_expiry', 'enable_lot', 'module_form_parts', 'business_locations', 'common_settings', 'warranties'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveQuickProduct(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }
        
        try {
            $business_id = $request->session()->get('user.business_id');
            $form_fields = ['name', 'brand_id', 'unit_id', 'category_id', 'tax', 'barcode_type','tax_type', 'sku',
                'alert_quantity', 'type', 'sub_unit_ids', 'sub_category_id', 'barcode' ,'gender'];

            $module_form_fields = $this->moduleUtil->getModuleData('product_form_fields');
            if (!empty($module_form_fields)) {
                foreach ($module_form_fields as $key => $value) {
                    if (!empty($value) && is_array($value)) {
                        $form_fields = array_merge($form_fields, $value);
                    }
                }
            }
            $product_details = $request->only($form_fields);
            
            $product_details['type'] = empty($product_details['type']) ? 'single' : $product_details['type'];
            $product_details['business_id'] = $business_id;
            $product_details['created_by'] = $request->session()->get('user.id');
            if (!empty($request->input('enable_stock')) &&  $request->input('enable_stock') == 1) {
                $product_details['enable_stock'] = 1 ;
                //TODO: Save total qty
                //$product_details['total_qty_available'] = 0;
            }
            if (!empty($request->input('not_for_selling')) &&  $request->input('not_for_selling') == 1) {
                $product_details['not_for_selling'] = 1 ;
            }
            if (empty($product_details['sku'])) {
                $product_details['sku'] = ' ';
            }

            $expiry_enabled = $request->session()->get('business.enable_product_expiry');
            if (!empty($request->input('expiry_period_type')) && !empty($request->input('expiry_period')) && !empty($expiry_enabled)) {
                $product_details['expiry_period_type'] = $request->input('expiry_period_type');
                $product_details['expiry_period'] = $this->productUtil->num_uf($request->input('expiry_period'));
            }
            
            if (!empty($request->input('enable_sr_no')) &&  $request->input('enable_sr_no') == 1) {
                $product_details['enable_sr_no'] = 1 ;
            }

            $product_details['warranty_id'] = !empty($request->input('warranty_id')) ? $request->input('warranty_id') : null;
            
            DB::beginTransaction();

            $product = Product::create($product_details);

            if (empty(trim($request->input('sku')))) {
                $sku = $this->productUtil->generateProductSku($product->id);
                $product->sku = $sku;
                $product->save();
            }
            
            $this->productUtil->createSingleProductVariation(
                $product->id,
                $product->sku,
                $request->input('single_dpp'),
                $request->input('single_dpp_inc_tax'),
                $request->input('profit_percent'),
                $request->input('single_dsp'),
                $request->input('single_dsp_inc_tax')
            );

            if ($product->enable_stock == 1 && !empty($request->input('opening_stock'))) {
                $user_id = $request->session()->get('user.id');

                $transaction_date = $request->session()->get("financial_year.start");
                $transaction_date = \Carbon::createFromFormat('Y-m-d', $transaction_date)->toDateTimeString();

                $this->productUtil->addSingleProductOpeningStock($business_id, $product, $request->input('opening_stock'), $transaction_date, $user_id);
            }

            //Add product locations
            $product_locations = $request->input('product_locations');
            if (!empty($product_locations)) {
                $product->product_locations()->sync($product_locations);
            }

            DB::commit();

            $output = ['success' => 1,
                            'msg' => __('product.product_added_success'),
                            'product' => $product,
                            'variation' => $product->variations->first(),
                            'locations' => $product_locations
                        ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __("messages.something_went_wrong")
                        ];
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function view($id)
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $product = Product::where('business_id', $business_id)
                        ->with(['brand', 'unit', 'category', 'sub_category', 'product_tax', 'variations', 'variations.product_variation', 'variations.group_prices', 'variations.media', 'product_locations', 'warranty', 'media'])
                        ->findOrFail($id);

            $price_groups = SellingPriceGroup::where('business_id', $business_id)->active()->pluck('name', 'id');

            $allowed_group_prices = [];
            foreach ($price_groups as $key => $value) {
                if (auth()->user()->can('selling_price_group.' . $key)) {
                    $allowed_group_prices[$key] = $value;
                }
            }

            $group_price_details = [];

            foreach ($product->variations as $variation) {
                foreach ($variation->group_prices as $group_price) {
                    $group_price_details[$variation->id][$group_price->price_group_id] = $group_price->price_inc_tax;
                }
            }

            $rack_details = $this->productUtil->getRackDetails($business_id, $id, true);

            $combo_variations = [];
            if ($product->type == 'combo') {
                $combo_variations = $this->productUtil->__getComboProductDetails($product['variations'][0]->combo_variations, $business_id);
            }

            return view('product.view-modal')->with(compact(
                'product',
                'rack_details',
                'allowed_group_prices',
                'group_price_details',
                'combo_variations'
            ));
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
        }
    }

    /**
     * Mass deletes products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function massDestroy(Request $request)
    {
        if (!auth()->user()->can('product.delete')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $purchase_exist = false;

            if (!empty($request->input('selected_rows'))) {
                $business_id = $request->session()->get('user.business_id');

                $selected_rows = explode(',', $request->input('selected_rows'));

                $products = Product::where('business_id', $business_id)
                                    ->whereIn('id', $selected_rows)
                                    ->with(['purchase_lines', 'variations'])
                                    ->get();
                $deletable_products = [];

                $is_mfg_installed = $this->moduleUtil->isModuleInstalled('Manufacturing');

                DB::beginTransaction();

                foreach ($products as $product) {
                    $can_be_deleted = true;
                    //Check if product is added as an ingredient of any recipe
                    if ($is_mfg_installed) {
                        $variation_ids = $product->variations->pluck('id');

                        $exists_as_ingredient = \Modules\Manufacturing\Entities\MfgRecipeIngredient::whereIn('variation_id', $variation_ids)
                            ->exists();
                        $can_be_deleted = !$exists_as_ingredient;
                    }

                    //Delete if no purchase found
                    if (empty($product->purchase_lines->toArray()) && $can_be_deleted) {
                        //Delete variation location details
                        VariationLocationDetails::where('product_id', $product->id)
                                                    ->delete();
                        $product->delete();
                    } else {
                        $purchase_exist = true;
                    }
                }

                DB::commit();
            }

            if (!$purchase_exist) {
                $output = ['success' => 1,
                            'msg' => __('lang_v1.deleted_success')
                        ];
            } else {
                $output = ['success' => 0,
                            'msg' => __('lang_v1.products_could_not_be_deleted')
                        ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __("messages.something_went_wrong")
                        ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * Shows form to add selling price group prices for a product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function addSellingPrices($id)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $product = Product::where('business_id', $business_id)
                    ->with(['variations', 'variations.group_prices', 'variations.product_variation'])
                            ->findOrFail($id);

        $price_groups = SellingPriceGroup::where('business_id', $business_id)
                                            ->active()
                                            ->get();
        $variation_prices = [];
        foreach ($product->variations as $variation) {
            foreach ($variation->group_prices as $group_price) {
                $variation_prices[$variation->id][$group_price->price_group_id] = $group_price->price_inc_tax;
            }
        }
        return view('product.add-selling-prices')->with(compact('product', 'price_groups', 'variation_prices'));
    }

    /**
     * Saves selling price group prices for a product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveSellingPrices(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $product = Product::where('business_id', $business_id)
                            ->with(['variations'])
                            ->findOrFail($request->input('product_id'));
            DB::beginTransaction();
            foreach ($product->variations as $variation) {
                $variation_group_prices = [];
                foreach ($request->input('group_prices') as $key => $value) {
                    if (isset($value[$variation->id])) {
                        $variation_group_price =
                        VariationGroupPrice::where('variation_id', $variation->id)
                                            ->where('price_group_id', $key)
                                            ->first();
                        if (empty($variation_group_price)) {
                            $variation_group_price = new VariationGroupPrice([
                                    'variation_id' => $variation->id,
                                    'price_group_id' => $key
                                ]);
                        }

                        $variation_group_price->price_inc_tax = $this->productUtil->num_uf($value[$variation->id]);
                        $variation_group_prices[] = $variation_group_price;
                    }
                }

                if (!empty($variation_group_prices)) {
                    $variation->group_prices()->saveMany($variation_group_prices);
                }
            }
            //Update product updated_at timestamp
            $product->touch();
            
            DB::commit();
            $output = ['success' => 1,
                            'msg' => __("lang_v1.updated_success")
                        ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __("messages.something_went_wrong")
                        ];
        }

        if ($request->input('submit_type') == 'submit_n_add_opening_stock') {
            return redirect()->action(
                'OpeningStockController@add',
                ['product_id' => $product->id]
            );
        } elseif ($request->input('submit_type') == 'save_n_add_another') {
            return redirect()->action(
                'ProductController@create'
            )->with('status', $output);
        }

        return redirect('products')->with('status', $output);
    }

    public function viewGroupPrice($id)
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $product = Product::where('business_id', $business_id)
                            ->where('id', $id)
                            ->with(['variations', 'variations.product_variation', 'variations.group_prices'])
                            ->first();

        $price_groups = SellingPriceGroup::where('business_id', $business_id)->active()->pluck('name', 'id');

        $allowed_group_prices = [];
        foreach ($price_groups as $key => $value) {
            if (auth()->user()->can('selling_price_group.' . $key)) {
                $allowed_group_prices[$key] = $value;
            }
        }

        $group_price_details = [];

        foreach ($product->variations as $variation) {
            foreach ($variation->group_prices as $group_price) {
                $group_price_details[$variation->id][$group_price->price_group_id] = $group_price->price_inc_tax;
            }
        }

        return view('product.view-product-group-prices')->with(compact('product', 'allowed_group_prices', 'group_price_details'));
    }

    /**
     * Mass deactivates products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function massDeactivate(Request $request)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            if (!empty($request->input('selected_products'))) {
                $business_id = $request->session()->get('user.business_id');

                $selected_products = explode(',', $request->input('selected_products'));

                DB::beginTransaction();

                $products = Product::where('business_id', $business_id)
                                    ->whereIn('id', $selected_products)
                                    ->update(['is_inactive' => 1]);

                DB::commit();
            }

            $output = ['success' => 1,
                            'msg' => __('lang_v1.products_deactivated_success')
                        ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __("messages.something_went_wrong")
                        ];
        }

        return $output;
    }

    /**
     * Activates the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function activate($id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');
                $product = Product::where('id', $id)
                                ->where('business_id', $business_id)
                                ->update(['is_inactive' => 0]);

                $output = ['success' => true,
                                'msg' => __("lang_v1.updated_success")
                            ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                
                $output = ['success' => false,
                                'msg' => __("messages.something_went_wrong")
                            ];
            }

            return $output;
        }
    }

    /**
     * Deletes a media file from storage and database.
     *
     * @param  int  $media_id
     * @return json
     */
    public function deleteMedia($media_id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');
                
                Media::deleteMedia($business_id, $media_id);

                $output = ['success' => true,
                                'msg' => __("lang_v1.file_deleted_successfully")
                            ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                
                $output = ['success' => false,
                                'msg' => __("messages.something_went_wrong")
                            ];
            }

            return $output;
        }
    }

    public function getProductsApi($id = null)
    {
        try {
            $api_token = request()->header('API-TOKEN');
            $filter_string = request()->header('FILTERS');
            $order_by = request()->header('ORDER-BY');

            parse_str($filter_string, $filters);

            $api_settings = $this->moduleUtil->getApiSettings($api_token);
            
            $limit = !empty(request()->input('limit')) ? request()->input('limit') : 10;

            $location_id = $api_settings->location_id;
            
            $query = Product::where('business_id', $api_settings->business_id)
                            ->active()
                            ->with(['brand', 'unit', 'category', 'sub_category',
                                'product_variations', 'product_variations.variations', 'product_variations.variations.media',
                                'product_variations.variations.variation_location_details' => function ($q) use ($location_id) {
                                    $q->where('location_id', $location_id);
                                }]);

            if (!empty($filters['categories'])) {
                $query->whereIn('category_id', $filters['categories']);
            }

            if (!empty($filters['brands'])) {
                $query->whereIn('brand_id', $filters['brands']);
            }

            if (!empty($filters['category'])) {
                $query->where('category_id', $filters['category']);
            }

            if (!empty($filters['sub_category'])) {
                $query->where('sub_category_id', $filters['sub_category']);
            }

            if ($order_by == 'name') {
                $query->orderBy('name', 'asc');
            } elseif ($order_by == 'date') {
                $query->orderBy('created_at', 'desc');
            }

            if (empty($id)) {
                $products = $query->paginate($limit);
            } else {
                $products = $query->find($id);
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            return $this->respondWentWrong($e);
        }

        return $this->respond($products);
    }

    public function getVariationsApi()
    {
        try {
            $api_token = request()->header('API-TOKEN');
            $variations_string = request()->header('VARIATIONS');

            if (is_numeric($variations_string)) {
                $variation_ids = intval($variations_string);
            } else {
                parse_str($variations_string, $variation_ids);
            }

            $api_settings = $this->moduleUtil->getApiSettings($api_token);
            $location_id = $api_settings->location_id;
            $business_id = $api_settings->business_id;

            $query = Variation::with([
                                'product_variation',
                                'product' => function ($q) use ($business_id) {
                                    $q->where('business_id', $business_id);
                                },
                                'product.unit',
                                'variation_location_details' => function ($q) use ($location_id) {
                                    $q->where('location_id', $location_id);
                                }
                            ]);

            $variations = is_array($variation_ids) ? $query->whereIn('id', $variation_ids)->get() : $query->where('id', $variation_ids)->first();
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            return $this->respondWentWrong($e);
        }

        return $this->respond($variations);
    }

    /**
     * Shows form to edit multiple products at once.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkEdit(Request $request)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        $selected_products_string = $request->input('selected_products');
        if (!empty($selected_products_string)) {
            $selected_products = explode(',', $selected_products_string);
            $business_id = $request->session()->get('user.business_id');
           
            $products = Product::where('business_id', $business_id)
                                ->whereIn('id', $selected_products)
                                ->with(['variations', 'variations.product_variation', 'variations.group_prices', 'product_locations'])
                                ->get();

            $all_categories = Category::catAndSubCategories($business_id);

            $categories = [];
            $sub_categories = [];
            foreach ($all_categories as $category) {
                $categories[$category['id']] = $category['name'];

                if (!empty($category['sub_categories'])) {
                    foreach ($category['sub_categories'] as $sub_category) {
                        $sub_categories[$category['id']][$sub_category['id']] = $sub_category['name'];
                    }
                }
            }

            $brands = Brands::forDropdown($business_id);

            $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
            $taxes = $tax_dropdown['tax_rates'];
            $tax_attributes = $tax_dropdown['attributes'];

            $price_groups = SellingPriceGroup::where('business_id', $business_id)->active()->pluck('name', 'id');
            $business_locations = BusinessLocation::forDropdown($business_id);

            return view('product.bulk-edit')->with(compact(
                'products',
                'categories',
                'brands',
                'taxes',
                'tax_attributes',
                'sub_categories',
                'price_groups',
                'business_locations'
            ));
        }
    }

    /**
     * Updates multiple products at once.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkUpdate(Request $request)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $products = $request->input('products');
            $business_id = $request->session()->get('user.business_id');

            DB::beginTransaction();
            foreach ($products as $id => $product_data) {
                $update_data = [
                    'category_id' => $product_data['category_id'],
                    'sub_category_id' => $product_data['sub_category_id'],
                    'brand_id' => $product_data['brand_id'],
                    'tax' => $product_data['tax'],
                ];

                //Update product
                $product = Product::where('business_id', $business_id)
                                ->findOrFail($id);

                $product->update($update_data);

                //Add product locations
                $product_locations = !empty($product_data['product_locations']) ?
                                    $product_data['product_locations'] : [];
                $product->product_locations()->sync($product_locations);

                $variations_data = [];

                //Format variations data
                foreach ($product_data['variations'] as $key => $value) {
                    $variation = Variation::where('product_id', $product->id)->findOrFail($key);
                    $variation->default_purchase_price = $this->productUtil->num_uf($value['default_purchase_price']);
                    $variation->dpp_inc_tax = $this->productUtil->num_uf($value['dpp_inc_tax']);
                    $variation->profit_percent = $this->productUtil->num_uf($value['profit_percent']);
                    $variation->default_sell_price = $this->productUtil->num_uf($value['default_sell_price']);
                    $variation->sell_price_inc_tax = $this->productUtil->num_uf($value['sell_price_inc_tax']);
                    $variations_data[] = $variation;

                    //Update price groups
                    if (!empty($value['group_prices'])) {
                        foreach ($value['group_prices'] as $k => $v) {
                            VariationGroupPrice::updateOrCreate(
                                ['price_group_id' => $k, 'variation_id' => $variation->id],
                                ['price_inc_tax' => $this->productUtil->num_uf($v)]
                            );
                        }
                    }
                }
                $product->variations()->saveMany($variations_data);
            }
            DB::commit();

            $output = ['success' => 1,
                            'msg' => __("lang_v1.updated_success")
                        ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __("messages.something_went_wrong")
                        ];
        }

        return redirect('products')->with('status', $output);
    }

    /**
     * Adds product row to edit in bulk edit product form
     *
     * @param  int  $product_id
     * @return \Illuminate\Http\Response
     */
    public function getProductToEdit($product_id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
       
        $product = Product::where('business_id', $business_id)
                            ->with(['variations', 'variations.product_variation', 'variations.group_prices'])
                            ->findOrFail($product_id);
        $all_categories = Category::catAndSubCategories($business_id);

        $categories = [];
        $sub_categories = [];
        foreach ($all_categories as $category) {
            $categories[$category['id']] = $category['name'];

            if (!empty($category['sub_categories'])) {
                foreach ($category['sub_categories'] as $sub_category) {
                    $sub_categories[$category['id']][$sub_category['id']] = $sub_category['name'];
                }
            }
        }

        $brands = Brands::forDropdown($business_id);

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        $price_groups = SellingPriceGroup::where('business_id', $business_id)->active()->pluck('name', 'id');

        return view('product.partials.bulk_edit_product_row')->with(compact(
            'product',
            'categories',
            'brands',
            'taxes',
            'tax_attributes',
            'sub_categories',
            'price_groups'
        ));
    }

    /**
     * Gets the sub units for the given unit.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $unit_id
     * @return \Illuminate\Http\Response
     */
    public function getSubUnits(Request $request)
    {
        if (!empty($request->input('unit_id'))) {
            $unit_id = $request->input('unit_id');
            $business_id = $request->session()->get('user.business_id');
            $sub_units = $this->productUtil->getSubUnits($business_id, $unit_id, true);

            //$html = '<option value="">' . __('lang_v1.all') . '</option>';
            $html = '';
            if (!empty($sub_units)) {
                foreach ($sub_units as $id => $sub_unit) {
                    $html .= '<option value="' . $id .'">' .$sub_unit['name'] . '</option>';
                }
            }

            return $html;
        }
    }

    public function updateProductLocation(Request $request)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $selected_products = $request->input('products');
            $update_type = $request->input('update_type');
            $location_ids = $request->input('product_location');

            $business_id = $request->session()->get('user.business_id');

            $product_ids = explode(',', $selected_products);
           
            $products = Product::where('business_id', $business_id)
                                ->whereIn('id', $product_ids)
                                ->with(['product_locations'])
                                ->get();
            DB::beginTransaction();
            foreach ($products as $product) {
                $product_locations = $product->product_locations->pluck('id')->toArray();

                if ($update_type == 'add') {
                    $product_locations = array_unique(array_merge($location_ids, $product_locations));
                    $product->product_locations()->sync($product_locations);
                } elseif ($update_type == 'remove') {
                    foreach ($product_locations as $key => $value) {
                        if (in_array($value, $location_ids)) {
                            unset($product_locations[$key]);
                        }
                    }
                    $product->product_locations()->sync($product_locations);
                }
            }
            DB::commit();
            $output = ['success' => 1,
                            'msg' => __("lang_v1.updated_success")
                        ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __("messages.something_went_wrong")
                        ];
        }

        return $output;
    }

    public function productStockHistory($id)
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {

            $stock_details = $this->productUtil->getVariationStockDetails($business_id, $id, request()->input('location_id'));
            $stock_history = $this->productUtil->getVariationStockHistory($business_id, $id, request()->input('location_id'));

            return view('product.stock_history_details')
                ->with(compact('stock_details', 'stock_history'));
        }
        
        $product = Product::where('business_id', $business_id)
                            ->with(['variations', 'variations.product_variation'])
                            ->findOrFail($id);
        
        //Get all business locations
        $business_locations = BusinessLocation::forDropdown($business_id);
        

        return view('product.stock_history')
                ->with(compact('product', 'business_locations'));
    }

    public function productHistoryAJAX($id)
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $product = Product::where('business_id', $business_id)
        ->with(['variations', 'variations.product_variation'])
        ->findOrFail($id);
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('product.product_history',compact('id','business_id','product','business_locations'));
    }


    public function productHistory(Request $request)
    {   
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $query = PurchaseLine::join('transactions','purchase_lines.transaction_id','=','transactions.id')
                    ->join('products', 'purchase_lines.product_id', '=', 'products.id')
                    ->join('users','transactions.created_by', '=', 'users.id')
                    ->where('transactions.type', 'purchase')
                    ->where('purchase_lines.product_id', $request->id)
                    ->select(
                        'products.sku as product_sku',
                        'products.image as product_image',
                        'transactions.ref_no as invoice_id',
                        'purchase_lines.quantity as purchase_quantity',
                        'transactions.transaction_date as transaction_date',
                        // DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS full_name")
                        DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) AS full_name")
                        )
                    ->get();
                    return Datatables::of($query)
                    ->editColumn('product_sku', function ($row) {
                        $product_sku = $row->product_sku;
    
                        return $product_sku;
                    })
                    ->editColumn('invoice_id', function ($row) {
                        $invoice_id = $row->invoice_id;
    
                        return $invoice_id;
                    })
                    ->editColumn('purchase_quantity', function ($row) {
                        return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->purchase_quantity . '" >' . (float) $row->purchase_quantity . '</span> ';

                    })
                    ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                    ->rawColumns(['product_sku', 'invoice_id', 'purchase_quantity', 'transaction_date'])
                    ->make(true);
        }                    

    }

    public function productSellHistory(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            // $variation_id = $request->get('variation_id', null);
            $query = TransactionSellLine::join('transactions', 'transaction_sell_lines.transaction_id','=','transactions.id')
                ->join('products', 'transaction_sell_lines.product_id', '=', 'products.id')
                ->join('business_locations', 'transactions.location_id', '=', 'business_locations.id')
                ->join('users','transactions.created_by', '=', 'users.id')
                ->whereIn('transactions.type', ['sell','sell_return'])
                ->where('transactions.status', 'final')
                ->where('transaction_sell_lines.product_id', $request->id)

                ->select(
                    'products.sku as product_sku',
                    'products.image as product_image',
                    'transactions.invoice_no',
                    'transactions.transaction_date as transaction_date',
                    'transaction_sell_lines.quantity as sell_quantity',
                    'business_locations.name as store_name',
                    // DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS full_name")
                    DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) AS full_name")
                )
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
            // })
            ->editColumn('product_sku', function ($row) {
                $product_sku = $row->product_sku;

                return $product_sku;
            })
            ->editColumn('invoice_no', function ($row) {
                $invoice_no = $row->invoice_no;

                return $invoice_no;
            })
            ->editColumn('sell_quantity', function ($row) {
                return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->sell_quantity . '" >' . (float) $row->sell_quantity . '</span> ';

            })
            ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
            ->editColumn('store_name', function ($row) {
                $store_name = $row->store_name;

                return $store_name;
            })
            ->rawColumns(['product_sku', 'invoice_no', 'sell_quantity', 'transaction_date','store_name'])
            ->make(true);
        }
    }

    public function productExchangeHistory(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            // $variation_id = $request->get('variation_id', null);
            $query = TransactionSellLine::join('transactions', 'transaction_sell_lines.transaction_id','=','transactions.return_parent_id')
                ->join('products', 'transaction_sell_lines.product_id', '=', 'products.id')
                ->join('business_locations', 'transactions.location_id', '=', 'business_locations.id')
                ->join('users','transactions.created_by', '=', 'users.id')
                ->where('transactions.type', 'sell_return')
                ->where('transactions.status', 'final')
                ->where('transaction_sell_lines.product_id', $request->id)
                ->whereRaw("transaction_sell_lines.quantity_returned > 0")

                ->select(
                    'products.sku as product_sku',
                    'products.image as product_image',
                    'transactions.invoice_no',
                    'transactions.transaction_date as transaction_date',
                    'transaction_sell_lines.quantity_returned as sell_quantity',
                    'business_locations.name as store_name',
                    // DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS full_name")
                    DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) AS full_name")
                )
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
            // })
            ->editColumn('product_sku', function ($row) {
                $product_sku = $row->product_sku;

                return $product_sku;
            })
            ->editColumn('invoice_no', function ($row) {
                $invoice_no = $row->invoice_no;

                return $invoice_no;
            })
            ->editColumn('sell_quantity', function ($row) {
                return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->sell_quantity . '" >' . (float) $row->sell_quantity . '</span> ';

            })
            ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
            ->editColumn('store_name', function ($row) {
                $store_name = $row->store_name;

                return $store_name;
            })
            ->rawColumns(['product_sku', 'invoice_no', 'sell_quantity', 'transaction_date','store_name'])
            ->make(true);
        }
    }

    public function productGiftHistory(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $query = TransactionSellLine::join('transactions', 'transaction_sell_lines.transaction_id','=','transactions.id')
                ->join('products', 'transaction_sell_lines.product_id', '=', 'products.id')
                ->join('users','transactions.created_by', '=', 'users.id')
                ->where('transaction_sell_lines.product_id', $request->id)
                ->where('transactions.type', 'gift')
                ->where('transactions.status', 'final')
                ->select(
                    'products.sku as product_sku',
                    'products.image as product_image',
                    'transactions.invoice_no',
                    'transactions.transaction_date as transaction_date',
                    'transaction_sell_lines.quantity as sell_quantity',
                    'transaction_sell_lines.quantity_returned as return_quantity',
                    // DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS full_name")
                    DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) AS full_name")

                )
                ->get();

            return Datatables::of($query)            
            ->editColumn('product_sku', function ($row) {
                $product_sku = $row->product_sku;

                return $product_sku;
            })
            ->editColumn('invoice_no', function ($row) {
                $invoice_no = $row->invoice_no;

                return $invoice_no;
            })
            ->editColumn('sell_quantity', function ($row) {
                return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->sell_quantity . '" >' . (float) $row->sell_quantity . '</span> ';

            })
            ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')

            ->editColumn('return_quantity', function ($row) {
                return '<span data-is_quantity="true" class="display_currency sell_qtyy" data-currency_symbol=false data-orig-value="' . (float)$row->return_quantity . '" >' . (float) $row->return_quantity . '</span> ';

            })
            ->rawColumns(['return_quantity','product_sku', 'invoice_no', 'sell_quantity', 'transaction_date','store_name'])
            ->make(true);
        }
    }

    public function productEcommerceHistory(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $query = EcommerceTransaction::
                leftJoin('ecommerce_sell_lines', 'ecommerce_sell_lines.ecommerce_transaction_id','=','ecommerce_transactions.id')
                ->leftJoin('products', 'products.id', '=' , 'ecommerce_sell_lines.product_id')
                ->leftJoin('business_locations', 'business_locations.id', '=' , 'ecommerce_sell_lines.location_id')
                ->join('users','ecommerce_transactions.created_by', '=', 'users.id')
                ->whereNull('ecommerce_transactions.return_parent_id')
                ->where('ecommerce_transactions.type','sell')
                ->where('ecommerce_sell_lines.product_id', $request->id )
                ->select(
                    'ecommerce_transactions.transaction_date AS transaction_date',
                    'ecommerce_transactions.invoice_no AS invoice_no',
                    'ecommerce_transactions.shipping_status AS shipping_status',
                    'products.sku AS product_sku',
                    'products.image as product_image',
                    'ecommerce_sell_lines.quantity as quantity',
                    'business_locations.name as store_name',
                    // DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS full_name")
                    DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) AS full_name")
                );

            return Datatables::of($query)
            ->editColumn('product_sku', function ($row) {
                $product_sku = $row->product_sku;

                return $product_sku;
            })
            ->editColumn('invoice_no', function ($row) {
                $invoice_no = $row->invoice_no;

                return $invoice_no;
            })
            ->editColumn('quantity', function ($row) {
                return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->quantity . '" >' . (float) $row->quantity . '</span> ';

            })
            ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
            ->editColumn('shipping_status', function ($row) {
                $shipping_status = $row->shipping_status;            
                $formattedShippingStatus = ucwords($shipping_status);
            
                return $formattedShippingStatus;
            })
            ->editColumn('store_name', function ($row) {
                $store_name = $row->store_name;

                return $store_name;
            })
            ->rawColumns(['quantity'])
            ->make(true);
        }
    }

    public function productEcommerceReturnHistory(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $query = EcommerceSellLine::
            join('ecommerce_transactions','ecommerce_transactions.return_parent_id','ecommerce_sell_lines.ecommerce_transaction_id')
            ->join('ecommerce_transactions as T1',
                'ecommerce_transactions.return_parent_id',
                '=',
                'T1.id')
                ->leftJoin('products', 'products.id', '=', 'ecommerce_sell_lines.product_id')
                    ->leftJoin('business_locations', 'business_locations.id', '=', 'ecommerce_sell_lines.location_id')
                    ->join('users','T1.created_by', '=', 'users.id')
                    ->select(
                        'ecommerce_transactions.transaction_date AS transaction_date',
                        'T1.invoice_no as sell_invoice_no',
                        'ecommerce_transactions.invoice_no as return_invoice_no',
                        'ecommerce_sell_lines.quantity_returned as quantity',
                        'products.sku as product_sku',
                        'business_locations.name as store_name',
                        // DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS full_name")
                        DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) AS full_name")

                        )
                        ->where('ecommerce_sell_lines.product_id', $request->id );

            return Datatables::of($query)
            ->editColumn('product_sku', function ($row) {
                $product_sku = $row->product_sku;

                return $product_sku;
            })
            ->editColumn('return_invoice_no', function ($row) {
                $return_invoice_no = $row->return_invoice_no;

                return $return_invoice_no;
            })
            ->editColumn('sell_invoice_no', function ($row) {
                $sell_invoice_no = $row->sell_invoice_no;

                return $sell_invoice_no;
            })
            ->editColumn('quantity', function ($row) {
                return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->quantity . '" >' . (float) $row->quantity . '</span> ';

            })
            ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
            ->editColumn('shipping_status', function ($row) {
                $shipping_status = "Return";

                return $shipping_status;
            })
            ->editColumn('store_name', function ($row) {
                $store_name = $row->store_name;

                return $store_name;
            })
            ->rawColumns(['quantity'])
            ->make(true);
        }
    }

    public function productStoreToStoreHistory(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $stock_transfers = Transaction::join(
                'business_locations AS l1',
                'transactions.location_id',
                '=',
                'l1.id'
            )
                    ->join('transactions as t2', 't2.transfer_parent_id', '=', 'transactions.id')
                    ->join(
                        'business_locations AS l2',
                        't2.location_id',
                        '=',
                        'l2.id'
                    )
                    ->join('transaction_sell_lines', 'transactions.id', '=' , 'transaction_sell_lines.transaction_id')
                    ->join('products','transaction_sell_lines.product_id', '=', 'products.id')
                    ->join('users','transactions.created_by', '=', 'users.id')
                    ->where('transactions.type', 'sell_transfer')
                    ->where('transactions.status','final')
                    ->where('transaction_sell_lines.product_id', $request->id)
                    ->where('l1.name', '<>', 'Warehouse')
                    ->where('l2.name', '<>','Warehouse')
                    ->select(
                        'transaction_sell_lines.quantity as quantity',
                        'transactions.id',
                        'transactions.transaction_date',
                        'transactions.ref_no',
                        'products.sku as product_sku',
                        'products.image as product_image',
                        'l1.name as sender',
                        'l2.name as receiver',
                        'transactions.id as DT_RowId',
                        'transactions.status',
                        // DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS full_name")
                        DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) AS full_name")

                    );

            return Datatables::of($stock_transfers)
            ->editColumn('product_sku', function ($row) {
                $product_sku = $row->product_sku;

                return $product_sku;
            })
            ->editColumn('return_invoice_no', function ($row) {
                $return_invoice_no = $row->return_invoice_no;

                return $return_invoice_no;
            })
            ->editColumn('sell_invoice_no', function ($row) {
                $sell_invoice_no = $row->sell_invoice_no;

                return $sell_invoice_no;
            })
            ->editColumn('quantity', function ($row) {
                return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->quantity . '" >' . (float) $row->quantity . '</span> ';

            })
            ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
            ->editColumn('shipping_status', function ($row) {
                $shipping_status = "Return";

                return $shipping_status;
            })
            ->editColumn('store_name', function ($row) {
                $store_name = $row->store_name;

                return $store_name;
            })
            ->rawColumns(['quantity'])
            ->make(true);
        }
    }

    public function productStoreToWarehouseHistory(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $stock_transfers = Transaction::join(
                'business_locations AS l1',
                'transactions.location_id',
                '=',
                'l1.id'
            )
                    ->join('transactions as t2', 't2.transfer_parent_id', '=', 'transactions.id')
                    ->join(
                        'business_locations AS l2',
                        't2.location_id',
                        '=',
                        'l2.id'
                    )
                    ->join('transaction_sell_lines', 'transactions.id', '=' , 'transaction_sell_lines.transaction_id')
                    ->join('products','transaction_sell_lines.product_id', '=', 'products.id')
                    ->join('users','transactions.created_by', '=', 'users.id')
                    ->where('transactions.type', 'sell_transfer')
                    ->where('transactions.status','final')
                    ->where('transaction_sell_lines.product_id', $request->id)
                    ->where('l2.name','Warehouse')
                    ->select(
                        'transaction_sell_lines.quantity as quantity',
                        'transactions.id',
                        'transactions.transaction_date',
                        'transactions.ref_no',
                        'products.sku as product_sku',
                        'products.image as product_image',
                        'l1.name as sender',
                        'l2.name as receiver',
                        'transactions.id as DT_RowId',
                        'transactions.status',
                        // DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS full_name")
                        DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) AS full_name")

                    );

            return Datatables::of($stock_transfers)
            ->editColumn('product_sku', function ($row) {
                $product_sku = $row->product_sku;

                return $product_sku;
            })
            ->editColumn('return_invoice_no', function ($row) {
                $return_invoice_no = $row->return_invoice_no;

                return $return_invoice_no;
            })
            ->editColumn('sell_invoice_no', function ($row) {
                $sell_invoice_no = $row->sell_invoice_no;

                return $sell_invoice_no;
            })
            ->editColumn('quantity', function ($row) {
                return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->quantity . '" >' . (float) $row->quantity . '</span> ';

            })
            ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
            ->editColumn('shipping_status', function ($row) {
                $shipping_status = "Return";

                return $shipping_status;
            })
            ->editColumn('store_name', function ($row) {
                $store_name = $row->store_name;

                return $store_name;
            })
            ->rawColumns(['quantity'])
            ->make(true);
        }
    }

    public function productWarehouseToStoreHistory(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $stock_transfers = Transaction::join(
                'business_locations AS l1',
                'transactions.location_id',
                '=',
                'l1.id'
            )
                    ->join('transactions as t2', 't2.transfer_parent_id', '=', 'transactions.id')
                    ->join(
                        'business_locations AS l2',
                        't2.location_id',
                        '=',
                        'l2.id'
                    )
                    ->join('transaction_sell_lines', 'transactions.id', '=' , 'transaction_sell_lines.transaction_id')
                    ->join('products','transaction_sell_lines.product_id', '=', 'products.id')
                    ->join('users','transactions.created_by', '=', 'users.id')
                    ->where('transactions.type', 'sell_transfer')
                    ->where('transactions.status','final')
                    ->where('transaction_sell_lines.product_id', $request->id)
                    ->where('l1.name','Warehouse')
                    ->select(
                        'transaction_sell_lines.quantity as quantity',
                        'transactions.id',
                        'transactions.transaction_date',
                        'transactions.ref_no',
                        'products.sku as product_sku',
                        'products.image as product_image',
                        'l1.name as sender',
                        'l2.name as receiver',
                        'transactions.id as DT_RowId',
                        'transactions.status',
                        // DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS full_name")
                        DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) AS full_name")
                    );

            return Datatables::of($stock_transfers)
            ->editColumn('product_sku', function ($row) {
                $product_sku = $row->product_sku;

                return $product_sku;
            })
            ->editColumn('return_invoice_no', function ($row) {
                $return_invoice_no = $row->return_invoice_no;

                return $return_invoice_no;
            })
            ->editColumn('sell_invoice_no', function ($row) {
                $sell_invoice_no = $row->sell_invoice_no;

                return $sell_invoice_no;
            })
            ->editColumn('quantity', function ($row) {
                return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->quantity . '" >' . (float) $row->quantity . '</span> ';

            })
            ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
            ->editColumn('shipping_status', function ($row) {
                $shipping_status = "Return";

                return $shipping_status;
            })
            ->editColumn('store_name', function ($row) {
                $store_name = $row->store_name;

                return $store_name;
            })
            ->rawColumns(['quantity'])
            ->make(true);
        }
    }

    public function productAdjustmentHistory(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $stock_adjustments = Transaction::join('product_adjustment_lines', 'product_adjustment_lines.transaction_id', '=', 'transactions.id')
            ->join('products', 'products.id', '=', 'product_adjustment_lines.product_id')
            ->join('users','transactions.created_by', '=', 'users.id')
            ->join(
                'business_locations AS BL',
                'transactions.location_id',
                '=',
                'BL.id'
            )
                    ->where('transactions.business_id', $business_id)
                    ->where('product_adjustment_lines.product_id', $request->id)
                    ->where('transactions.type', 'product_adjustment')
                    ->select(
                        'transactions.transaction_date as transaction_date',
                        'ref_no as invoice_no',
                        'BL.name as location_name',
                        'adjustment_type as type',
                        'product_adjustment_lines.quantity as quantity',
                        'products.sku as product_sku',
                        'products.image as product_image',
                        // DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS full_name")
                        DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) AS full_name")
                    );

            return Datatables::of($stock_adjustments)
            ->editColumn('product_sku', function ($row) {
                $product_sku = $row->product_sku;

                return $product_sku;
            })
            ->editColumn('invoice_no', function ($row) {
                $invoice_no = $row->invoice_no;

                return $invoice_no;
            })
            ->editColumn('type', function ($row) {
                $type = $row->type;
                $formattedtype = ucwords($type);

                return $formattedtype;
            })
            ->editColumn('quantity', function ($row) {
                return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->quantity . '" >' . (float) $row->quantity . '</span> ';

            })
            ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
            ->editColumn('location_name', function ($row) {
                $location_name = $row->location_name;

                return $location_name;
            })
            ->rawColumns(['quantity'])
            ->make(true);
        }
    }

    public function productCompaintHistory(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $stock_adjustments = StockAdjustmentLine::join('transactions', 'stock_adjustment_lines.transaction_id', '=', 'transactions.id')
            ->join('products', 'products.id', '=', 'stock_adjustment_lines.product_id')
            ->join('users','transactions.created_by', '=', 'users.id')
            ->join(
                'business_locations AS BL',
                'transactions.location_id',
                '=',
                'BL.id'
            )
                    ->where('transactions.business_id', $business_id)
                    ->where('stock_adjustment_lines.product_id', $request->id)
                    ->where('transactions.type', 'stock_adjustment')
                    ->select(
                        'transactions.transaction_date as transaction_date',
                        'ref_no as invoice_no',
                        'BL.name as location_name',
                        'adjustment_type as type',
                        'stock_adjustment_lines.quantity as quantity',
                        'products.sku as product_sku',
                        'products.image as product_image',
                        // DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS full_name")
                        DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) AS full_name")
                    );

            return Datatables::of($stock_adjustments)
            ->editColumn('product_sku', function ($row) {
                $product_sku = $row->product_sku;

                return $product_sku;
            })
            ->editColumn('invoice_no', function ($row) {
                $invoice_no = $row->invoice_no;

                return $invoice_no;
            })
            ->editColumn('type', function ($row) {
                $type = $row->type;
                $formattedtype = ucwords($type);

                return $formattedtype;
            })
            ->editColumn('quantity', function ($row) {
                return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->quantity . '" >' . (float) $row->quantity . '</span> ';

            })
            ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
            ->editColumn('location_name', function ($row) {
                $location_name = $row->location_name;

                return $location_name;
            })
            ->rawColumns(['quantity'])
            ->make(true);
        }
    }

    public function productInternationalExchangeHistory(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $stock_adjustments = TransactionSellLine::join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
            ->join('products', 'products.id', '=', 'transaction_sell_lines.product_id')
            ->join('users','transactions.created_by', '=', 'users.id')
            ->join(
                'business_locations AS BL',
                'transactions.location_id',
                '=',
                'BL.id'
            )
                    ->where('transactions.business_id', $business_id)
                    ->where('transaction_sell_lines.product_id', $request->id)
                    ->where('transactions.type', 'international_return')
                    // ->where('transaction_sell_lines.sell_line_note','<>','international_return')
                    ->select(
                        'transactions.transaction_date as transaction_date',
                        'invoice_no as invoice_no',
                        'BL.name as location_name',
                        'transaction_sell_lines.quantity as quantity',
                        'transaction_sell_lines.quantity_returned as quantity_returned',
                        'products.sku as product_sku',
                        'products.image as product_image',
                        // DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS full_name")
                        DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) AS full_name")
                    );

            return Datatables::of($stock_adjustments)
            ->editColumn('product_sku', function ($row) {
                $product_sku = $row->product_sku;

                return $product_sku;
            })
            ->editColumn('invoice_no', function ($row) {
                $invoice_no = $row->invoice_no;

                return $invoice_no;
            })
            ->editColumn('quantity', function ($row) {
                return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->quantity . '" >' . (float) $row->quantity . '</span> ';

            })
            ->editColumn('quantity_returned', function ($row) {
                return '<span data-is_quantity="true" class="display_currency returned_qty" data-currency_symbol=false data-orig-value="' . (float)$row->quantity_returned . '" >' . (float) $row->quantity_returned . '</span> ';

            })
            ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
            ->editColumn('location_name', function ($row) {
                $location_name = $row->location_name;

                return $location_name;
            })
            ->rawColumns(['quantity','quantity_returned'])
            ->make(true);
        }
    }


    public function productAudit(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        return view('product.audit',compact('business_id'));

    }
    public function productList(Request $request)
    {

        $permitted_locations = auth()->user()->permitted_locations();

        
        $query = Product::
        leftJoin('categories', 'products.category_id', '=', 'categories.id')
        ->join('variation_location_details','variation_location_details.product_id','products.id')
        ->where('products.barcode', $request->barcode);
        
        if($permitted_locations != "all"){
            $query->whereIn('variation_location_details.location_id',$permitted_locations);
        }
        $product = $query->select(
            'products.id',
            'products.name as product',
            'products.type',
            'categories.name as category',
            'products.sku',
            'products.image',
            DB::raw('CAST(SUM(variation_location_details.qty_available) AS UNSIGNED) as available_qty')
            )
            ->first();

            return response()->json([
                'data' => $product
            ]);
    }

    public function allProductsList()
    {

        $permitted_locations = auth()->user()->permitted_locations();

        $query = Product::
        leftJoin('categories', 'products.category_id', '=', 'categories.id')
        ->join('variation_location_details','variation_location_details.product_id','products.id');

        $permitted_locations = auth()->user()->permitted_locations();

        if($permitted_locations != "all"){
            $query->whereIn('variation_location_details.location_id',$permitted_locations);
        }

        $products = $query->select(
                'products.id',
                'products.name as product',
                'products.type',
                'categories.name as category',
                'products.sku',
                'products.image',
                DB::raw('CAST(SUM(variation_location_details.qty_available) AS UNSIGNED) as available_qty')
                )
            ->groupBy('products.id')
            ->get();
        
            return response()->json([
                'data' => $products
            ]);
    }


    public function updateProductPriceManually()
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        return view('product.update_sell_price');

    }

    public function storeProductPriceManually(Request $request)
    {
        try{
            $product_name = $request->input('p_name');
            $new_price = $request->input('sell_price');

            $products = DB::table('products')
            ->leftJoin('variations','products.id', '=', 'variations.product_id')
            ->select('products.id AS product_id','products.name AS product_sku', 'products.unit_id AS product_unit_id','products.sub_unit_ids AS sub_unit_id','variations.product_variation_id AS variation_id','variations.default_purchase_price AS pp_without_discount','variations.profit_percent AS profit_percent','variations.default_sell_price','variations.dpp_inc_tax AS dpp_inc_tax','products.tax AS tax_id')
            ->where('variations.sub_sku','LIKE', '%'.$product_name.'%')
            ->get();
            DB::beginTransaction();
            if(!$products->isEmpty()) {
                foreach($products as $product) {
                    $variation = Variation::where('sub_sku', $product->product_sku)
                    ->first();


                    $tax_percentage = DB::table('tax_rates')
                    ->where('id', $product->tax_id)
                    ->select('amount')
                    ->first();
                    // dd($tax_percentage);

                    $tax =  ($tax_percentage->amount/100) + 1;
                    // dd($tax);


                    $dpp_inc_tax = $variation->dpp_inc_tax;
                    $new_selling_price = $new_price;
                    $profit_margin_percentage = ($new_selling_price - $dpp_inc_tax) / $dpp_inc_tax * 100;
                    $profit_margin_percentages[$product->product_sku] = $profit_margin_percentage;
                    

                    $defaut_sell_price = $new_selling_price/$tax;
                    $selling_price_without_tax[$product->product_sku] = $defaut_sell_price;

                    // Update profit_percent and sell_price_inc_tax columns separately
                    $variation->update(['profit_percent' => $profit_margin_percentage]);
                    $variation->update(['sell_price_inc_tax' => $new_selling_price]);
                    $variation->update(['default_sell_price' => $defaut_sell_price]);
                }
                DB::commit();
                    $output = ['success' => 1,
                    'msg' => __('lang_v1.product_grp_prices_imported_successfully')
                ];
            } else {
                $error_msg = __('Product Not Found');

                throw new \Exception($error_msg);
            }


        } catch(Exception $e) {
            DB::rollBack();

            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => $e->getMessage()
                        ];
            return redirect('change-article-price')->with('notification', $output);
        }

        return redirect('change-article-price')->with('status', $output);

    }

}
