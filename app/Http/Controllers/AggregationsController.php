<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AggregationsController extends Controller
{
    public function index(Request $request)
    {
        $tables = [
            'receipt_aggregates',
            'cashier_aggregates',
            'product_aggregates',
        ];

        $aggregations = [];

        foreach ($tables as $table) {
            try {
                $stats = DB::table($table)
                    ->selectRaw('COUNT(*) as row_count, MAX(computed_at) as latest_computed_at, MIN(period_date) as min_date, MAX(period_date) as max_date')
                    ->first();

                $aggregations[$table] = [
                    'table'             => $table,
                    'row_count'         => $stats->row_count ?? 0,
                    'latest_computed_at'=> $stats->latest_computed_at ?? null,
                    'min_date'          => $stats->min_date ?? null,
                    'max_date'          => $stats->max_date ?? null,
                ];
            } catch (\Exception $e) {
                $aggregations[$table] = [
                    'table'             => $table,
                    'row_count'         => 0,
                    'latest_computed_at'=> null,
                    'min_date'          => null,
                    'max_date'          => null,
                    'error'             => $e->getMessage(),
                ];
            }
        }

        return view('aggregations.index', compact('aggregations'));
    }
}
