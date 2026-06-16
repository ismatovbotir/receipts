<?php

namespace App\Http\Controllers;

use App\Models\DayNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $earliest = DB::table('receipts')->min(DB::raw('DATE(date_close)'));
        $latest   = DB::table('receipts')->max(DB::raw('DATE(date_close)'));

        $from = $request->input('from', $earliest ?? now()->subDays(30)->toDateString());
        $to   = $request->input('to',   $latest   ?? now()->toDateString());
        $shop = $request->input('shop', null);

        $baseReceipts = function () use ($from, $to, $shop) {
            $q = DB::table('receipts')
                ->whereDate('date_close', '>=', $from)
                ->whereDate('date_close', '<=', $to);
            if ($shop) $q->where('shop', $shop);
            return $q;
        };

        // --- KPIs ---
        $totals = (clone $baseReceipts())
            ->selectRaw('COALESCE(SUM(total), 0) as total_revenue, COUNT(*) as transaction_count')
            ->first();

        $total_revenue         = (float) ($totals->total_revenue ?? 0);
        $transaction_count     = (int)   ($totals->transaction_count ?? 0);
        $avg_transaction_value = $transaction_count > 0 ? $total_revenue / $transaction_count : 0;

        $itemsData = DB::table('items')
            ->joinSub($baseReceipts()->select('id'), 'r', 'r.id', '=', 'items.receipt_id')
            ->selectRaw('COALESCE(SUM(items.qty), 0) as items_sold')
            ->first();

        $items_sold      = (float) ($itemsData->items_sold ?? 0);
        $avg_basket_size = $transaction_count > 0 ? $items_sold / $transaction_count : 0;

        // --- Sales over time (daily) ---
        $sales_over_time = (clone $baseReceipts())
            ->selectRaw("DATE(date_close) as date, SUM(total) as revenue, COUNT(*) as transactions")
            ->groupByRaw("DATE(date_close)")
            ->orderByRaw("DATE(date_close) ASC")
            ->get();

        // --- Sales by hour (0-23) ---
        $raw_hourly = (clone $baseReceipts())
            ->selectRaw("CAST(strftime('%H', date_close) AS INTEGER) as hour, COUNT(*) as transactions, SUM(total) as revenue")
            ->groupByRaw("strftime('%H', date_close)")
            ->orderByRaw("hour ASC")
            ->get()
            ->keyBy('hour');

        // Fill missing hours with zeros
        $sales_by_hour = collect(range(0, 23))->map(fn($h) => (object)[
            'hour'         => $h,
            'transactions' => (int)   ($raw_hourly[$h]->transactions ?? 0),
            'revenue'      => (float) ($raw_hourly[$h]->revenue      ?? 0),
        ]);

        // --- Peak hour ---
        $peak_hour = $sales_by_hour->sortByDesc('transactions')->first();

        // --- Top shops ---
        $top_shops = (clone $baseReceipts())
            ->selectRaw('shop, SUM(total) as revenue, COUNT(*) as transactions')
            ->groupBy('shop')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // --- Top cashiers ---
        $top_cashiers = (clone $baseReceipts())
            ->selectRaw('cashier, SUM(total) as revenue, COUNT(*) as transactions')
            ->groupBy('cashier')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // --- Payment breakdown ---
        $payment_breakdown = DB::table('payments')
            ->joinSub($baseReceipts()->select('id'), 'r', 'r.id', '=', 'payments.receipt_id')
            ->selectRaw('payments.type, SUM(payments.total) as total, COUNT(*) as count')
            ->groupBy('payments.type')
            ->orderByDesc('total')
            ->get();

        // --- Top products ---
        $top_products = DB::table('items')
            ->joinSub($baseReceipts()->select('id'), 'r', 'r.id', '=', 'items.receipt_id')
            ->selectRaw('items.name, items.code, SUM(items.total) as revenue, SUM(items.qty) as qty_sold, COUNT(DISTINCT items.receipt_id) as receipt_count')
            ->groupBy('items.name', 'items.code')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        // --- Big receipts (top 10 by total) ---
        $big_receipts = (clone $baseReceipts())
            ->select('id', 'number', 'date_close', 'shop', 'cashier', 'total', 'pos')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // --- Big items (highest single line_total across all receipts in range) ---
        $big_items = DB::table('items')
            ->joinSub($baseReceipts()->select('id', 'number', 'date_close', 'shop'), 'r', 'r.id', '=', 'items.receipt_id')
            ->select('items.name', 'items.code', 'items.qty', 'items.price', 'items.total as line_total', 'r.number', 'r.date_close', 'r.shop')
            ->orderByDesc('items.total')
            ->limit(10)
            ->get();

        // --- Shops list for filter dropdown ---
        $shops_list = DB::table('receipts')->select('shop')->distinct()->orderBy('shop')->pluck('shop');

        // --- Day notes for the period (shown on chart + tooltip) ---
        $day_notes = DayNote::whereBetween('date', [$from, $to])
            ->get()
            ->keyBy(fn($n) => $n->date->toDateString());

        // --- Anomaly detection (min 3 data points with sales) ---
        $revenues = $sales_over_time->filter(fn($r) => $r->revenue > 0)->pluck('revenue');
        $anomalies = collect();

        if ($revenues->count() >= 3) {
            $mean     = $revenues->avg();
            $variance = $revenues->map(fn($v) => pow($v - $mean, 2))->avg();
            $stddev   = sqrt($variance);

            $threshold_high = $mean + 1.5 * $stddev;
            $threshold_low  = max(0, $mean - 1.5 * $stddev);

            $anomalies = $sales_over_time->filter(function ($r) use ($threshold_high, $threshold_low, $mean) {
                return $r->revenue > 0 && ($r->revenue > $threshold_high || $r->revenue < $threshold_low);
            })->map(function ($r) use ($threshold_high, $day_notes) {
                $note = $day_notes[$r->date] ?? null;
                return (object)[
                    'date'         => $r->date,
                    'revenue'      => $r->revenue,
                    'transactions' => $r->transactions,
                    'direction'    => $r->revenue > $threshold_high ? 'high' : 'low',
                    'note'         => $note,
                    'annotated'    => !is_null($note),
                ];
            })->values();
        }

        $unannotated_anomalies = $anomalies->filter(fn($a) => !$a->annotated);

        return view('dashboard', compact(
            'from', 'to', 'shop',
            'total_revenue', 'transaction_count', 'avg_transaction_value',
            'items_sold', 'avg_basket_size',
            'sales_over_time', 'sales_by_hour', 'peak_hour',
            'top_shops', 'top_cashiers',
            'payment_breakdown', 'top_products',
            'big_receipts', 'big_items',
            'shops_list',
            'day_notes', 'anomalies', 'unannotated_anomalies'
        ));
    }
}
