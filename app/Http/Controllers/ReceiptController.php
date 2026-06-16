<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = Receipt::query()->orderByDesc('date_close');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('number',  'like', '%' . $search . '%')
                  ->orWhere('cashier', 'like', '%' . $search . '%')
                  ->orWhere('shop',    'like', '%' . $search . '%');
            });
        }

        if ($request->filled('shop')) {
            $query->where('shop', $request->input('shop'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date_close', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_close', '<=', $request->input('date_to'));
        }

        $receipts = $query->withCount('payments')->paginate(20);

        $shops_list = Receipt::select('shop')->distinct()->orderBy('shop')->pluck('shop');

        return view('receipts.index', compact('receipts', 'shops_list'));
    }

    public function show($id)
    {
        $receipt = Receipt::with(['items', 'payments', 'discounts'])->findOrFail($id);

        return view('receipts.show', compact('receipt'));
    }
}
