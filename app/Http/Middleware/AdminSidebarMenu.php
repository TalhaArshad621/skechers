<?php

namespace App\Http\Middleware;

use App\Utils\ModuleUtil;
use Closure;
use Menu;

class AdminSidebarMenu
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->ajax()) {
            return $next($request);
        }

        Menu::create('admin-sidebar-menu', function ($menu) {
            $enabled_modules = !empty(session('business.enabled_modules')) ? session('business.enabled_modules') : [];
            // dd($enabled_modules);   
            $is_admin = auth()->user()->hasRole('Admin#' . session('business.id')) ? true : false;
            //Home
            $menu->url(action('HomeController@index'), __('home.home'), ['icon' => 'fa fas fa-tachometer-alt', 'active' => request()->segment(1) == 'home'])->order(5);

            //POS
            $menu->url(action('SellPosController@create'), __('sale.pos_sale'), ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'pos' && request()->segment(2) == 'create'])->order(5);

            //User management dropdown
            if (auth()->user()->can('user.view') || auth()->user()->can('user.create') || auth()->user()->can('roles.view')) {
                $menu->dropdown(
                    __('user.user_management'),
                    function ($sub) {
                        if (auth()->user()->can('user.view')) {
                            $sub->url(
                                action('ManageUserController@index'),
                                __('user.users'),
                                ['icon' => 'fa fas fa-user', 'active' => request()->segment(1) == 'users']
                            );
                        }
                        if (auth()->user()->can('roles.view')) {
                            $sub->url(
                                action('RoleController@index'),
                                __('user.roles'),
                                ['icon' => 'fa fas fa-briefcase', 'active' => request()->segment(1) == 'roles']
                            );
                        }
                        // if (auth()->user()->can('user.create')) {
                        //     $sub->url(
                        //         action('SalesCommissionAgentController@index'),
                        //         __('lang_v1.sales_commission_agents'),
                        //         ['icon' => 'fa fas fa-handshake', 'active' => request()->segment(1) == 'sales-commission-agents']
                        //     );
                        // }
                    },
                    ['icon' => 'fa fas fa-users']
                )->order(10);
            }

            //Contacts dropdown
            if (auth()->user()->can('supplier.view') || auth()->user()->can('customer.view')) {
                $menu->dropdown(
                    __('contact.contacts'),
                    function ($sub) {
                        // if (auth()->user()->can('supplier.view')) {
                        //     $sub->url(
                        //         action('ContactController@index', ['type' => 'supplier']),
                        //         __('report.supplier'),
                        //         ['icon' => 'fa fas fa-star', 'active' => request()->input('type') == 'supplier']
                        //     );
                        // }
                        if (auth()->user()->can('customer.view')) {
                            $sub->url(
                                action('ContactController@index', ['type' => 'customer']),
                                __('report.customer'),
                                ['icon' => 'fa fas fa-star', 'active' => request()->input('type') == 'customer']
                            );
                            // $sub->url(
                            //     action('CustomerGroupController@index'),
                            //     __('lang_v1.customer_groups'),
                            //     ['icon' => 'fa fas fa-users', 'active' => request()->segment(1) == 'customer-group']
                            // );
                        }
                        if (auth()->user()->can('supplier.create') || auth()->user()->can('customer.create')) {
                            $sub->url(
                                action('ContactController@getImportContacts'),
                                __('lang_v1.import_contacts'),
                                ['icon' => 'fa fas fa-download', 'active' => request()->segment(1) == 'contacts' && request()->segment(2) == 'import']
                            );
                        }

                        if (!empty(env('GOOGLE_MAP_API_KEY'))) {
                            $sub->url(
                                action('ContactController@contactMap'),
                                __('lang_v1.map'),
                                ['icon' => 'fa fas fa-map-marker-alt', 'active' => request()->segment(1) == 'contacts' && request()->segment(2) == 'map']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-address-book', 'id' => "tour_step4"]
                )->order(15);
            }

            //Products dropdown
            if (
                auth()->user()->can('product.view') || auth()->user()->can('product.create') ||
                auth()->user()->can('brand.view') || auth()->user()->can('unit.view') ||
                auth()->user()->can('category.view') || auth()->user()->can('brand.create') ||
                auth()->user()->can('unit.create') || auth()->user()->can('category.create') || auth()->user()->can('product.audit')
            ) {
                $menu->dropdown(
                    __('sale.products'),
                    function ($sub) {
                        if (auth()->user()->can('product.view')) {
                            $sub->url(
                                action('ProductController@index'),
                                __('lang_v1.list_products'),
                                ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'products' && request()->segment(2) == '']
                            );
                        }
                        // if (auth()->user()->can('product.create')) {
                        //     $sub->url(
                        //         action('ProductController@create'),
                        //         __('product.add_product'),
                        //         ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'products' && request()->segment(2) == 'create']
                        //     );
                        // }
                        if (auth()->user()->can('product.view')) {
                            $sub->url(
                                action('LabelsController@show'),
                                __('barcode.print_labels'),
                                ['icon' => 'fa fas fa-barcode', 'active' => request()->segment(1) == 'labels' && request()->segment(2) == 'show']
                            );
                        }
                        if (auth()->user()->can('product.create')) {
                            // $sub->url(
                            //     action('VariationTemplateController@index'),
                            //     __('product.variations'),
                            //     ['icon' => 'fa fas fa-circle', 'active' => request()->segment(1) == 'variation-templates']
                            // );
                            $sub->url(
                                action('ImportProductsController@index'),
                                __('product.import_products'),
                                ['icon' => 'fa fas fa-download', 'active' => request()->segment(1) == 'import-products']
                            );
                        }
                        if (auth()->user()->can('product.opening_stock')) {
                            $sub->url(
                                action('ImportOpeningStockController@index'),
                                __('lang_v1.import_opening_stock'),
                                ['icon' => 'fa fas fa-download', 'active' => request()->segment(1) == 'import-opening-stock']
                            );
                        }
                        if (auth()->user()->can('product.create')) {
                            $sub->url(
                                action('SellController@indexForSellPriceUpdate'),
                                __('Update Selling Price'),
                                ['icon' => 'fa fas fa-circle', 'active' => request()->segment(1) == 'indexForSellPriceUpdate']
                            );
                        }
                        if (auth()->user()->can('product.create')) {
                            $sub->url(
                                action('ProductController@updateProductPriceManually'),
                                __('Change Price Manually'),
                                ['icon' => 'fa fas fa-circle', 'active' => request()->segment(1) == 'change-article-price']
                            );
                        }
                        // if (auth()->user()->can('product.create')) {
                        //     $sub->url(
                        //         action('SellingPriceGroupController@index'),
                        //         __('lang_v1.selling_price_group'),
                        //         ['icon' => 'fa fas fa-circle', 'active' => request()->segment(1) == 'selling-price-group']
                        //     );
                        // }
                        // if (auth()->user()->can('unit.view') || auth()->user()->can('unit.create')) {
                        //     $sub->url(
                        //         action('UnitController@index'),
                        //         __('unit.units'),
                        //         ['icon' => 'fa fas fa-balance-scale', 'active' => request()->segment(1) == 'units']
                        //     );
                        // }
                        if (auth()->user()->can('category.view') || auth()->user()->can('category.create')) {
                            $sub->url(
                                action('TaxonomyController@index') . '?type=product',
                                __('category.categories'),
                                ['icon' => 'fa fas fa-tags', 'active' => request()->segment(1) == 'taxonomies' && request()->get('type') == 'product']
                            );
                        }
                        // if (auth()->user()->can('brand.view') || auth()->user()->can('brand.create')) {
                        //     $sub->url(
                        //         action('BrandController@index'),
                        //         __('brand.brands'),
                        //         ['icon' => 'fa fas fa-gem', 'active' => request()->segment(1) == 'brands']
                        //     );
                        // }

                        if (auth()->user()->can('product.create')) {
                            $sub->url(
                                action('ProductAdjustmentController@index'),
                                __('product.product_adjustment'),
                                ['icon' => 'fa fas fa-gem', 'active' => request()->segment(1) == 'product-adjustment']
                            );
                        }
                        if (auth()->user()->can('product.audit')) {
                            $sub->url(
                                action('AuditController@productAudit'),
                                __('Audit'),
                                ['icon' => 'fa fas fa-gem', 'active' => request()->segment(1) == 'products-audit']
                            );
                        }

                        // $sub->url(
                        //     action('WarrantyController@index'),
                        //     __('lang_v1.warranties'),
                        //     ['icon' => 'fa fas fa-shield-alt', 'active' => request()->segment(1) == 'warranties']
                        // );
                    },
                    ['icon' => 'fa fas fa-cubes', 'id' => 'tour_step5']
                )->order(20);
            }

            //Purchase dropdown
            if (in_array('purchases', $enabled_modules) && (auth()->user()->can('purchase.view') || auth()->user()->can('purchase.create') || auth()->user()->can('purchase.update'))) {
                $menu->dropdown(
                    __('purchase.purchases'),
                    function ($sub) {
                        if (auth()->user()->can('purchase.view') || auth()->user()->can('view_own_purchase')) {
                            $sub->url(
                                action('PurchaseController@index'),
                                __('purchase.list_purchase'),
                                ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'purchases' && request()->segment(2) == null]
                            );
                        }
                        if (auth()->user()->can('purchase.create')) {
                            $sub->url(
                                action('PurchaseController@create'),
                                __('purchase.add_purchase'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'purchases' && request()->segment(2) == 'create']
                            );
                        }
                        // if (auth()->user()->can('purchase.update')) {
                        //     $sub->url(
                        //         action('PurchaseReturnController@index'),
                        //         __('lang_v1.list_purchase_return'),
                        //         ['icon' => 'fa fas fa-undo', 'active' => request()->segment(1) == 'purchase-return']
                        //     );
                        // }
                        if (auth()->user()->can('purchase.create')) {
                            $sub->url(
                                action('ImportPurchaseController@index'),
                                __('Import Purchase'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'purchases' && request()->segment(2) == 'import']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-arrow-circle-down', 'id' => 'tour_step6']
                )->order(25);
            }
            //Sell dropdown
            if ($is_admin || auth()->user()->hasAnyPermission(['sell.view', 'sell.create', 'direct_sell.access', 'view_own_sell_only', 'view_commission_agent_sell', 'access_shipping', 'access_own_shipping', 'access_commission_agent_shipping', 'access_sell_return'])) {
                $menu->dropdown(
                    __('sale.sale'),
                    function ($sub) use ($enabled_modules, $is_admin) {
                        // if ($is_admin || auth()->user()->hasAnyPermission(['sell.view', 'sell.create', 'direct_sell.access', 'view_own_sell_only', 'view_commission_agent_sell', 'access_shipping', 'access_own_shipping', 'access_commission_agent_shipping']) ) {
                        //     $sub->url(
                        //         action('SellController@index'),
                        //         __('lang_v1.all_sales'),
                        //         ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'sells' && request()->segment(2) == null]
                        //     );
                        // }
                        // if (in_array('add_sale', $enabled_modules) && auth()->user()->can('direct_sell.access')) {
                        //     $sub->url(
                        //         action('SellController@create'),
                        //         __('sale.add_sale'),
                        //         ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'sells' && request()->segment(2) == 'create' && empty(request()->get('status'))]
                        //     );
                        // }
                        if (auth()->user()->can('sell.view')) {
                            $sub->url(
                                action('SellPosController@index'),
                                __('Invoices'),
                                ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'pos' && request()->segment(2) == null]
                            );
                        }
                        // if (auth()->user()->can('sell.create')) {
                        //     if (in_array('pos_sale', $enabled_modules)) {
                        //         $sub->url(
                        //             action('SellPosController@create'),
                        //             __('sale.pos_sale'),
                        //             ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'pos' && request()->segment(2) == 'create']
                        //         );
                        //     }
                        // }
                        // if (in_array('add_sale', $enabled_modules) && auth()->user()->can('direct_sell.access')) {
                        //     $sub->url(
                        //         action('SellController@create', ['status' => 'draft']),
                        //         __('lang_v1.add_draft'),
                        //         ['icon' => 'fa fas fa-plus-circle', 'active' => request()->get('status') == 'draft']
                        //     );
                        // }
                        // if (auth()->user()->can('list_drafts')) {
                        //     $sub->url(
                        //         action('SellController@getDrafts'),
                        //         __('lang_v1.list_drafts'),
                        //         ['icon' => 'fa fas fa-pen-square', 'active' => request()->segment(1) == 'sells' && request()->segment(2) == 'drafts']
                        //     );
                        // }
                        // if (in_array('add_sale', $enabled_modules) && auth()->user()->can('direct_sell.access')) {
                        //     $sub->url(
                        //         action('SellController@create', ['status' => 'quotation']),
                        //         __('lang_v1.add_quotation'),
                        //         ['icon' => 'fa fas fa-plus-circle', 'active' => request()->get('status') == 'quotation']
                        //     );
                        // }
                        // if (auth()->user()->can('list_quotations')) {
                        //     $sub->url(
                        //         action('SellController@getQuotations'),
                        //         __('lang_v1.list_quotations'),
                        //         ['icon' => 'fa fas fa-pen-square', 'active' => request()->segment(1) == 'sells' && request()->segment(2) == 'quotations']
                        //     );
                        // }
                        if (auth()->user()->can('access_sell_return')) {
                            $sub->url(
                                action('SellReturnController@newSellReturn'),
                                __('Add Exchange'),
                                ['icon' => 'fa fas fa-undo', 'active' => request()->segment(1) == 'sell-return-new' && request()->segment(2) == null]
                            );
                        }

                        if (auth()->user()->can('access_sell_return')) {
                            $sub->url(
                                action('SellReturnController@index'),
                                __('List Exchange'),
                                ['icon' => 'fa fas fa-undo', 'active' => request()->segment(1) == 'sell-return' && request()->segment(2) == null]
                            );
                        }

                        // if (auth()->user()->can('access_sell_return')) {
                        //     $sub->url(
                        //         action('GiftController@index'),
                        //         __('List Gifts'),
                        //         ['icon' => 'fa fas fa-undo', 'active' => request()->segment(1) == 'gift-index' && request()->segment(2) == null]
                        //     );
                        // }

                        // if ($is_admin || auth()->user()->hasAnyPermission(['access_shipping', 'access_own_shipping', 'access_commission_agent_shipping']) ) {
                        //     $sub->url(
                        //         action('SellController@shipments'),
                        //         __('lang_v1.shipments'),
                        //         ['icon' => 'fa fas fa-truck', 'active' => request()->segment(1) == 'shipments']
                        //     );
                        // }

                        if (auth()->user()->can('discount.access')) {
                            $sub->url(
                                action('DiscountController@index'),
                                __('lang_v1.discounts'),
                                ['icon' => 'fa fas fa-percent', 'active' => request()->segment(1) == 'discount']
                            );
                            $sub->url(
                                action('ImportDiscountController@create'),
                                __('Import Discount'),
                                ['icon' => 'fa fas fa-percent', 'active' => request()->segment(1) == 'discount']
                            );
                        }
                        // if (auth()->user()->can('discount.access')) {
                        //     $sub->url(
                        //         action('DiscountController@index'),
                        //         __('lang_v1.discounts'),
                        //         ['icon' => 'fa fas fa-percent', 'active' => request()->segment(1) == 'discount']
                        //     );
                        // }
                        if (in_array('subscription', $enabled_modules) && auth()->user()->can('direct_sell.access')) {
                            $sub->url(
                                action('SellPosController@listSubscriptions'),
                                __('lang_v1.subscriptions'),
                                ['icon' => 'fa fas fa-recycle', 'active' => request()->segment(1) == 'subscriptions']
                            );
                        }

                        // if (auth()->user()->can('sell.create')) {
                        //     $sub->url(
                        //         action('ImportSalesController@index'),
                        //         __('lang_v1.import_sales'),
                        //         ['icon' => 'fa fas fa-file-import', 'active' => request()->segment(1) == 'import-sales']
                        //     );
                        // }
                    },
                    ['icon' => 'fa fas fa-arrow-circle-up', 'id' => 'tour_step7']
                )->order(30);
            }

            //Stock transfer dropdown
            if (in_array('stock_transfers', $enabled_modules) && (auth()->user()->can('stocktransfer.view') || auth()->user()->can('stocktransfer.create'))) {
                $menu->dropdown(
                    __('lang_v1.stock_transfers'),
                    function ($sub) {
                        // if (auth()->user()->can('stocktransfer.view')) {
                        $sub->url(
                            action('StockTransferController@index'),
                            __('lang_v1.list_stock_transfers'),
                            ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'stock-transfers' && request()->segment(2) == null]
                        );
                        // }
                        if (auth()->user()->can('stocktransfer.create')) {
                            $sub->url(
                                action('StockTransferController@create'),
                                __('lang_v1.add_stock_transfer'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'stock-transfers' && request()->segment(2) == 'create']
                            );
                        }
                        if (auth()->user()->can('stocktransfer.create')) {
                            $sub->url(
                                action('ImportStockTrasferController@create'),
                                __('Stock Transfer Import'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'import-stock-transfer ' && request()->segment(2) == 'create']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-truck']
                )->order(35);
            }
            //Ecommerce dropdown
            if (in_array('ecommerce', $enabled_modules) && (auth()->user()->can('view_ecommerce') || auth()->user()->can('edit_ecommerce'))) {
                $menu->dropdown(
                    __('lang_v1.ecommerce'),
                    function ($sub) {
                        if (auth()->user()->can('view_ecommerce')) {
                            $sub->url(
                                action('EcommerceController@index'),
                                __('lang_v1.list_ecommerce'),
                                ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'ecommerce' && request()->segment(2) == null]
                            );
                        }
                    },
                    ['icon' => 'fa fa-shopping-cart']
                )->order(36);
            }

            //stock adjustment dropdown
            if (in_array('stock_adjustment', $enabled_modules) && (auth()->user()->can('purchase.view') || auth()->user()->can('purchase.create'))) {
                $menu->dropdown(
                    __('Complaint'),
                    function ($sub) {
                        if (auth()->user()->can('purchase.view')) {
                            $sub->url(
                                action('StockAdjustmentController@index'),
                                __('List Complaints'),
                                ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'stock-adjustments' && request()->segment(2) == null]
                            );
                        }
                        if (auth()->user()->can('purchase.create')) {
                            $sub->url(
                                action('StockAdjustmentController@create'),
                                __('Add Complaint'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'stock-adjustments' && request()->segment(2) == 'create']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-database']
                )->order(40);
            }

            if (in_array('stock_adjustment', $enabled_modules) && (auth()->user()->can('create_bank_transfer') || auth()->user()->can('view_bank_transfer'))) {
                $menu->dropdown(
                    __('Bank Transfer'),
                    function ($sub) {
                        if (auth()->user()->can('view_bank_transfer')) {
                            $sub->url(
                                action('BankTransferController@index'),
                                __('List Bank Transfer'),
                                ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'bank-transfers' && request()->segment(2) == null]
                            );
                        }
                        if (auth()->user()->can('create_bank_transfer')) {
                            $sub->url(
                                action('BankTransferController@create'),
                                __('Add Bank Transfer'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'bank-transfers' && request()->segment(2) == 'create']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-university']
                )->order(40);
            }

            //Expense dropdown
            // if (in_array('expenses', $enabled_modules) && (auth()->user()->can('expense.access') || auth()->user()->can('view_own_expense'))) {
            //     $menu->dropdown(
            //         __('expense.expenses'),
            //         function ($sub) {
            //             $sub->url(
            //                 action('ExpenseController@index'),
            //                 __('lang_v1.list_expenses'),
            //                 ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'expenses' && request()->segment(2) == null]
            //             );
            //             $sub->url(
            //                 action('ExpenseController@create'),
            //                 __('expense.add_expense'),
            //                 ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'expenses' && request()->segment(2) == 'create']
            //             );
            //             $sub->url(
            //                 action('ExpenseCategoryController@index'),
            //                 __('expense.expense_categories'),
            //                 ['icon' => 'fa fas fa-circle', 'active' => request()->segment(1) == 'expense-categories']
            //             );
            //         },
            //         ['icon' => 'fa fas fa-minus-circle']
            //     )->order(45);
            // }
            //Accounts dropdown
            if (auth()->user()->can('account.access') && in_array('account', $enabled_modules)) {
                $menu->dropdown(
                    __('lang_v1.payment_accounts'),
                    function ($sub) {
                        $sub->url(
                            action('AccountController@index'),
                            __('account.list_accounts'),
                            ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'account']
                        );
                        $sub->url(
                            action('AccountReportsController@balanceSheet'),
                            __('account.balance_sheet'),
                            ['icon' => 'fa fas fa-book', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'balance-sheet']
                        );
                        $sub->url(
                            action('AccountReportsController@trialBalance'),
                            __('account.trial_balance'),
                            ['icon' => 'fa fas fa-balance-scale', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'trial-balance']
                        );
                        $sub->url(
                            action('AccountController@cashFlow'),
                            __('lang_v1.cash_flow'),
                            ['icon' => 'fa fas fa-exchange-alt', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'cash-flow']
                        );
                        $sub->url(
                            action('AccountReportsController@paymentAccountReport'),
                            __('account.payment_account_report'),
                            ['icon' => 'fa fas fa-file-alt', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'payment-account-report']
                        );
                    },
                    ['icon' => 'fa fas fa-money-check-alt']
                )->order(50);
            }

            //Reports dropdown
            if (
                auth()->user()->can('purchase_n_sell_report.view') || auth()->user()->can('contacts_report.view')
                || auth()->user()->can('stock_report.view') || auth()->user()->can('tax_report.view')
                || auth()->user()->can('trending_product_report.view') || auth()->user()->can('sales_representative.view') || auth()->user()->can('register_report.view')
                || auth()->user()->can('expense_report.view') || auth()->user()->can('view_ecommerce')
            ) {
                $menu->dropdown(
                    __('report.reports'),
                    function ($sub) use ($enabled_modules) {
                        if (auth()->user()->can('profit_loss_report.view')) {
                            $sub->url(
                                action('ReportController@getProfitLoss'),
                                __('report.profit_loss'),
                                ['icon' => 'fa fas fa-file-invoice-dollar', 'active' => request()->segment(2) == 'profit-loss']
                            );
                            $sub->url(
                                action('ReportController@overviewReport'),
                                __('Overview Report'),
                                ['icon' => 'fa fas fa-file-invoice-dollar', 'active' => request()->segment(2) == 'overview-report']
                            );
                        }
                        if (config('constants.show_report_606') == true) {
                            $sub->url(
                                action('ReportController@purchaseReport'),
                                'Report 606 (' . __('lang_v1.purchase') . ')',
                                ['icon' => 'fa fas fa-arrow-circle-down', 'active' => request()->segment(2) == 'purchase-report']
                            );
                        }
                        if (config('constants.show_report_607') == true) {
                            $sub->url(
                                action('ReportController@saleReport'),
                                'Report 607 (' . __('business.sale') . ')',
                                ['icon' => 'fa fas fa-arrow-circle-up', 'active' => request()->segment(2) == 'sale-report']
                            );
                        }
                        // if ((in_array('purchases', $enabled_modules) || in_array('add_sale', $enabled_modules) || in_array('pos_sale', $enabled_modules)) && auth()->user()->can('purchase_n_sell_report.view')) {
                        //     $sub->url(
                        //         action('ReportController@getPurchaseSell'),
                        //         __('report.purchase_sell_report'),
                        //         ['icon' => 'fa fas fa-exchange-alt', 'active' => request()->segment(2) == 'purchase-sell']
                        //     );
                        // }

                        if (auth()->user()->can('tax_report.view')) {
                            $sub->url(
                                action('ReportController@getTaxReport'),
                                __('report.tax_report'),
                                ['icon' => 'fa fas fa-percent', 'active' => request()->segment(2) == 'tax-report']
                            );
                        }
                        // if (auth()->user()->can('contacts_report.view')) {
                        //     $sub->url(
                        //         action('ReportController@getCustomerSuppliers'),
                        //         __('report.contacts'),
                        //         ['icon' => 'fa fas fa-address-book', 'active' => request()->segment(2) == 'customer-supplier']
                        //     );
                        //     $sub->url(
                        //         action('ReportController@getCustomerGroup'),
                        //         __('lang_v1.customer_groups_report'),
                        //         ['icon' => 'fa fas fa-users', 'active' => request()->segment(2) == 'customer-group']
                        //     );
                        // }
                        if (auth()->user()->can('stock_report.view')) {
                            $sub->url(
                                action('ReportController@getStockReport'),
                                __('report.stock_report'),
                                ['icon' => 'fa fas fa-hourglass-half', 'active' => request()->segment(2) == 'stock-report']
                            );
                            if (session('business.enable_product_expiry') == 1) {
                                $sub->url(
                                    action('ReportController@getStockExpiryReport'),
                                    __('report.stock_expiry_report'),
                                    ['icon' => 'fa fas fa-calendar-times', 'active' => request()->segment(2) == 'stock-expiry']
                                );
                            }
                            if (session('business.enable_lot_number') == 1) {
                                $sub->url(
                                    action('ReportController@getLotReport'),
                                    __('lang_v1.lot_report'),
                                    ['icon' => 'fa fas fa-hourglass-half', 'active' => request()->segment(2) == 'lot-report']
                                );
                            }

                            // if (in_array('stock_adjustment', $enabled_modules)) {
                            //     $sub->url(
                            //         action('ReportController@getStockAdjustmentReport'),
                            //         __('report.stock_adjustment_report'),
                            //         ['icon' => 'fa fas fa-sliders-h', 'active' => request()->segment(2) == 'stock-adjustment-report']
                            //     );
                            // }
                        }

                        if (auth()->user()->can('trending_product_report.view')) {
                            $sub->url(
                                action('ReportController@getTrendingProducts'),
                                __('report.trending_products'),
                                ['icon' => 'fa fas fa-chart-line', 'active' => request()->segment(2) == 'trending-products']
                            );
                        }
                        if (auth()->user()->can('product_purchase_report.view')) {

                            $sub->url(
                                action('ReportController@getproductPurchaseReport'),
                                __('lang_v1.product_purchase_report'),
                                ['icon' => 'fa fas fa-arrow-circle-down', 'active' => request()->segment(2) == 'product-purchase-report']
                            );
                        }
                        if (auth()->user()->can('purchase_n_sell_report.view')) {
                            // $sub->url(
                            //     action('ReportController@getSellReport'),
                            //     __('lang_v1.product_sell_report'),
                            //     ['icon' => 'fa fas fa-tasks', 'active' => request()->segment(2) == 'items-report']
                            // );


                            $sub->url(
                                action('ReportController@getproductSellReport'),
                                __('lang_v1.product_sell_report'),
                                ['icon' => 'fa fas fa-arrow-circle-up', 'active' => request()->segment(2) == 'product-sell-report']
                            );


                            // $sub->url(
                            //     action('ReportController@purchasePaymentReport'),
                            //     __('lang_v1.purchase_payment_report'),
                            //     ['icon' => 'fa fas fa-search-dollar', 'active' => request()->segment(2) == 'purchase-payment-report']
                            // );

                            // $sub->url(
                            //     action('ReportController@sellPaymentReport'),
                            //     __('lang_v1.sell_payment_report'),
                            //     ['icon' => 'fa fas fa-search-dollar', 'active' => request()->segment(2) == 'sell-payment-report']
                            // );

                        }

                        if (auth()->user()->can('product_sell_detail_report.view')) {
                            $sub->url(
                                action('ReportController@getproductSellGroupedReportDetailed'),
                                __('Product Sell Report Detailed'),
                                ['icon' => 'fa fas fa-arrow-circle-up', 'active' => request()->segment(2) == 'product-sell-grouped-report-detailed']
                            );
                        }
                        if (auth()->user()->can('brand_folio_report.view')) {

                            $sub->url(
                                action('ReportController@getbrandfolioReport'),
                                __('Brandfolio Report'),
                                ['icon' => 'fa fas fa-search-dollar', 'active' => request()->segment(2) == 'brandfolio-report']
                            );
                        }
                        if (in_array('expenses', $enabled_modules) && auth()->user()->can('expense_report.view')) {
                            $sub->url(
                                action('ReportController@getExpenseReport'),
                                __('report.expense_report'),
                                ['icon' => 'fa fas fa-search-minus', 'active' => request()->segment(2) == 'expense-report']
                            );
                        }
                        if (auth()->user()->can('register_report.view')) {
                            $sub->url(
                                action('ReportController@getRegisterReport'),
                                __('report.register_report'),
                                ['icon' => 'fa fas fa-briefcase', 'active' => request()->segment(2) == 'register-report']
                            );
                        }
                        if (auth()->user()->can('monthly_report.view')) {
                            $sub->url(
                                action('ReportController@getMonthlyReport'),
                                __('Monthly Report'),
                                ['icon' => 'fa fas fa-briefcase', 'active' => request()->segment(2) == 'monthly-report']
                            );
                        }
                        if (auth()->user()->can('sales_representative.view')) {
                            $sub->url(
                                action('ReportController@getSalesRepresentativeReport'),
                                __('Employee Report'),
                                ['icon' => 'fa fas fa-user', 'active' => request()->segment(2) == 'sales-representative-report']
                            );
                        }
                        if (auth()->user()->can('purchase_n_sell_report.view') && in_array('tables', $enabled_modules)) {
                            $sub->url(
                                action('ReportController@getTableReport'),
                                __('restaurant.table_report'),
                                ['icon' => 'fa fas fa-table', 'active' => request()->segment(2) == 'table-report']
                            );
                        }

                        if (auth()->user()->can('sales_representative.view') && in_array('service_staff', $enabled_modules)) {
                            $sub->url(
                                action('ReportController@getServiceStaffReport'),
                                __('restaurant.service_staff_report'),
                                ['icon' => 'fa fas fa-user-secret', 'active' => request()->segment(2) == 'service-staff-report']
                            );
                        }
                        if (auth()->user()->can('view_ecommerce')) {
                            $sub->url(
                                action('ReportController@ecommerceSellReport'),
                                __('E-commerce Sell Report'),
                                ['icon' => 'fa fas fa-tasks', 'active' => request()->segment(2) == 'ecommerce-sell-report']
                            );
                        }
                        if (auth()->user()->can('view_exchange_report')) {
                            $sub->url(
                                action('ReportController@exchangeReport'),
                                __('Exchange Report'),
                                ['icon' => 'fa fas fa-tasks', 'active' => request()->segment(2) == 'exchange-report']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-chart-bar', 'id' => 'tour_step8']
                )->order(55);
            }

            //Backup menu
            // if (auth()->user()->can('backup')) {
            //     $menu->url(action('BackUpController@index'), __('lang_v1.backup'), ['icon' => 'fa fas fa-hdd', 'active' => request()->segment(1) == 'backup'])->order(60);
            // }

            //Modules menu
            // if (auth()->user()->can('manage_modules')) {
            //     $menu->url(action('Install\ModulesController@index'), __('lang_v1.modules'), ['icon' => 'fa fas fa-plug', 'active' => request()->segment(1) == 'manage-modules'])->order(60);
            // }

            //Booking menu
            if (in_array('booking', $enabled_modules) && (auth()->user()->can('crud_all_bookings') || auth()->user()->can('crud_own_bookings'))) {
                $menu->url(action('Restaurant\BookingController@index'), __('restaurant.bookings'), ['icon' => 'fas fa fa-calendar-check', 'active' => request()->segment(1) == 'bookings'])->order(65);
            }

            //Kitchen menu
            if (in_array('kitchen', $enabled_modules)) {
                $menu->url(action('Restaurant\KitchenController@index'), __('restaurant.kitchen'), ['icon' => 'fa fas fa-fire', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'kitchen'])->order(70);
            }

            //Service Staff menu
            if (in_array('service_staff', $enabled_modules)) {
                $menu->url(action('Restaurant\OrderController@index'), __('restaurant.orders'), ['icon' => 'fa fas fa-list-alt', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'orders'])->order(75);
            }

            //Notification template menu
            // if (auth()->user()->can('send_notifications')) {
            //     $menu->url(action('NotificationTemplateController@index'), __('lang_v1.notification_templates'), ['icon' => 'fa fas fa-envelope', 'active' => request()->segment(1) == 'notification-templates'])->order(80);
            // }

            //Gift Module
            if (
                auth()->user()->can('view_gifts') ||
                auth()->user()->can('add_gifts')
            ) {
                $menu->dropdown(
                    __('Gift'),
                    function ($sub) {
                        if (auth()->user()->can('add_gifts')) {
                            $sub->url(
                                action('GiftController@create'),
                                __('Add Gift'),
                                ['icon' => 'fa fas fa-gift', 'active' => request()->segment(1) == 'gift-create']
                            );
                        }
                        if (auth()->user()->can('view_gifts')) {
                            $sub->url(
                                action('GiftController@index'),
                                __('List Gifts'),
                                ['icon' => 'fa fas fa-undo', 'active' => request()->segment(1) == 'gift-index' && request()->segment(2) == null]
                            );
                        }
                        if (auth()->user()->can('view_gifts')) {
                            $sub->url(
                                action('SellReturnController@addGiftReturn'),
                                __('Add Gift Return'),
                                ['icon' => 'fa fas fa-undo', 'active' => request()->segment(1) == 'new-sell-return-gift' && request()->segment(2) == null]
                            );
                        }
                    },
                    ['icon' => 'fa fa-gift']
                )->order(36);
            }

            // //Gift Module
            // if (auth()->user()->can('purchase.create')) {
            //     $menu->url(action('GiftController@create'), __('Gift'), ['icon' => 'fa fas fa-gift', 'active' => request()->segment(1) == 'gift-create'])->order(80);
            // }

            if (auth()->user()->can('view_international_exchange') || auth()->user()->can('create_international_exchange')) {
                //International Exchange
                $menu->dropdown(
                    __('International Exchange'),
                    function ($sub) {
                        if (auth()->user()->can('create_international_exchange')) {
                            $sub->url(
                                action('InternationalExchangeController@create'),
                                __('Add International Exchange'),
                                ['icon' => 'fa fas fa-swap-arrows', 'active' => request()->segment(1) == 'international-exchange' && request()->segment(2) == 'create']
                            );
                        }
                        if (auth()->user()->can('view_international_exchange')) {
                            $sub->url(
                                action('InternationalExchangeController@index'),
                                __('List International Exchange'),
                                ['icon' => 'fa fas fa-swap-arrows', 'active' => request()->segment(1) == 'international-exchange' && request()->segment(2) == null]
                            );
                        }
                    },
                    ['icon' => 'fa fa-american-sign-language-interpreting']
                )->order(36);
            }
            //Settings Dropdown
            if (
                auth()->user()->can('business_settings.access') ||
                auth()->user()->can('barcode_settings.access') ||
                auth()->user()->can('invoice_settings.access') ||
                auth()->user()->can('tax_rate.view') ||
                auth()->user()->can('tax_rate.create') ||
                auth()->user()->can('access_package_subscriptions')
            ) {
                $menu->dropdown(
                    __('business.settings'),
                    function ($sub) use ($enabled_modules) {
                        if (auth()->user()->can('business_settings.access')) {
                            $sub->url(
                                action('BusinessController@getBusinessSettings'),
                                __('business.business_settings'),
                                ['icon' => 'fa fas fa-cogs', 'active' => request()->segment(1) == 'business', 'id' => "tour_step2"]
                            );
                            $sub->url(
                                action('BusinessLocationController@index'),
                                __('business.business_locations'),
                                ['icon' => 'fa fas fa-map-marker', 'active' => request()->segment(1) == 'business-location']
                            );
                        }
                        if (auth()->user()->can('invoice_settings.access')) {
                            $sub->url(
                                action('InvoiceSchemeController@index'),
                                __('invoice.invoice_settings'),
                                ['icon' => 'fa fas fa-file', 'active' => in_array(request()->segment(1), ['invoice-schemes', 'invoice-layouts'])]
                            );
                        }
                        if (auth()->user()->can('barcode_settings.access')) {
                            $sub->url(
                                action('BarcodeController@index'),
                                __('barcode.barcode_settings'),
                                ['icon' => 'fa fas fa-barcode', 'active' => request()->segment(1) == 'barcodes']
                            );
                        }
                        if (auth()->user()->can('access_printers')) {
                            $sub->url(
                                action('PrinterController@index'),
                                __('printer.receipt_printers'),
                                ['icon' => 'fa fas fa-share-alt', 'active' => request()->segment(1) == 'printers']
                            );
                        }

                        if (auth()->user()->can('tax_rate.view') || auth()->user()->can('tax_rate.create')) {
                            $sub->url(
                                action('TaxRateController@index'),
                                __('tax_rate.tax_rates'),
                                ['icon' => 'fa fas fa-bolt', 'active' => request()->segment(1) == 'tax-rates']
                            );
                        }

                        if (in_array('tables', $enabled_modules) && auth()->user()->can('access_tables')) {
                            $sub->url(
                                action('Restaurant\TableController@index'),
                                __('restaurant.tables'),
                                ['icon' => 'fa fas fa-table', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'tables']
                            );
                        }

                        if (in_array('modifiers', $enabled_modules) && (auth()->user()->can('product.view') || auth()->user()->can('product.create'))) {
                            $sub->url(
                                action('Restaurant\ModifierSetsController@index'),
                                __('restaurant.modifiers'),
                                ['icon' => 'fa fas fa-pizza-slice', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'modifiers']
                            );
                        }

                        if (in_array('types_of_service', $enabled_modules) && auth()->user()->can('access_types_of_service')) {
                            $sub->url(
                                action('TypesOfServiceController@index'),
                                __('lang_v1.types_of_service'),
                                ['icon' => 'fa fas fa-user-circle', 'active' => request()->segment(1) == 'types-of-service']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-cog', 'id' => 'tour_step3']
                )->order(85);
            }
        });

        //Add menus from modules
        $moduleUtil = new ModuleUtil;
        $moduleUtil->getModuleData('modifyAdminMenu');

        return $next($request);
    }
}
