<?php

namespace App\Http\Controllers;
use App\BusinessLocation;

use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function productAudit(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('product.audit',compact('business_id', 'business_locations'));

    }
}
