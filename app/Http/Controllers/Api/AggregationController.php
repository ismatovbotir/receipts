<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AggregateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AggregationController extends Controller
{
    public function generate(Request $request, AggregateService $service): JsonResponse
    {
        $request->validate([
            'all'  => ['sometimes', 'boolean'],
            'from' => ['required_without:all', 'date_format:Y-m-d'],
            'to'   => ['sometimes', 'date_format:Y-m-d'],
        ]);

        if ($request->boolean('all')) {
            $earliest = DB::table('receipts')->min(DB::raw('DATE(date_close)'));
            if (! $earliest) {
                return response()->json(['message' => 'No receipts found.'], 422);
            }
            $from = Carbon::parse($earliest);
        } else {
            $from = Carbon::parse($request->input('from'));
        }

        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))
            : Carbon::today();

        $service->compute($from, $to);

        return response()->json([
            'from'    => $from->toDateString(),
            'to'      => $to->toDateString(),
            'counts'  => [
                'receipt_aggregates' => DB::table('receipt_aggregates')->count(),
                'cashier_aggregates' => DB::table('cashier_aggregates')->count(),
                'product_aggregates' => DB::table('product_aggregates')->count(),
            ],
        ]);
    }
}
