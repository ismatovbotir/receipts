<?php

namespace App\Http\Controllers;

use App\Models\DayNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to',   now()->toDateString());
        $shop = $request->input('shop', null);

        // Only closed receipts count; Продажа adds to revenue, Возврат subtracts.
        // Use direct string comparisons (not whereDate) so the composite index on
        // (status, date_close) is usable — whereDate wraps the column in strftime()
        // which defeats index lookups in SQLite.
        $baseReceipts = function () use ($from, $to, $shop) {
            $q = DB::table('receipts')
                ->where('status', 'Закрыт')
                ->where('date_close', '>=', $from . ' 00:00:00')
                ->where('date_close', '<=', $to   . ' 23:59:59');
            if ($shop) $q->where('shop', $shop);
            return $q;
        };

        $rev = "COALESCE(SUM(CASE WHEN type='Продажа' THEN total WHEN type='Возврат' THEN -total ELSE 0 END), 0)";
        $txn = "COUNT(CASE WHEN type='Продажа' THEN 1 END)";

        // Cache all heavy DB queries keyed by filter params (10 min TTL).
        // Day notes and anomalies are computed outside so they stay fresh.
        // SQLite uses strftime(); MySQL uses HOUR() / DAYOFWEEK().
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $hourExpr = $isSqlite
            ? "CAST(strftime('%H', date_close) AS INTEGER)"
            : "HOUR(date_close)";
        $dowExpr  = $isSqlite
            ? "CAST(strftime('%w', date_close) AS INTEGER)"
            : "(DAYOFWEEK(date_close) - 1)";  // MySQL: 1=Sun→0, 7=Sat→6, matches SQLite %w

        // v2: cache stores plain arrays only (v1 stored Collection objects — stale entries must be ignored)
        $cacheKey = 'dashboard:v2:' . md5("{$from}|{$to}|{$shop}");

        $analytics = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($baseReceipts, $rev, $txn, $hourExpr, $dowExpr) {

            // KPIs
            $totals = (clone $baseReceipts())
                ->selectRaw("$rev as total_revenue, $txn as transaction_count")
                ->first();

            $total_revenue     = (float) ($totals->total_revenue     ?? 0);
            $transaction_count = (int)   ($totals->transaction_count ?? 0);

            $itemsData = DB::table('items')
                ->joinSub($baseReceipts()->select('id')->where('type', 'Продажа'), 'r', 'r.id', '=', 'items.receipt_id')
                ->where('items.status', true)
                ->selectRaw('COALESCE(SUM(items.qty), 0) as items_sold')
                ->first();

            $items_sold = (float) ($itemsData->items_sold ?? 0);

            // Sales over time (daily)
            $sales_over_time = (clone $baseReceipts())
                ->selectRaw("DATE(date_close) as date, $rev as revenue, $txn as transactions")
                ->groupByRaw("DATE(date_close)")
                ->orderByRaw("DATE(date_close) ASC")
                ->get();

            // Sales by hour
            $raw_hourly = (clone $baseReceipts())
                ->selectRaw("$hourExpr as hour, $txn as transactions, $rev as revenue")
                ->groupByRaw($hourExpr)
                ->get()
                ->keyBy('hour');

            $sales_by_hour = collect(range(0, 23))->map(fn($h) => (object)[
                'hour'         => $h,
                'transactions' => (int)   ($raw_hourly[$h]->transactions ?? 0),
                'revenue'      => (float) ($raw_hourly[$h]->revenue      ?? 0),
            ]);

            // Sales by day of week (0=Sun … 6=Sat, same mapping on both drivers)
            $raw_by_dow = (clone $baseReceipts())
                ->selectRaw("$dowExpr as dow, $txn as transactions, $rev as revenue")
                ->groupByRaw($dowExpr)
                ->get()
                ->keyBy('dow');

            $sales_by_dow = collect([1, 2, 3, 4, 5, 6, 0])->map(fn($d) => (object)[
                'dow'          => $d,
                'transactions' => (int)   ($raw_by_dow[$d]->transactions ?? 0),
                'revenue'      => (float) ($raw_by_dow[$d]->revenue      ?? 0),
            ]);

            // Top shops
            $top_shops = (clone $baseReceipts())
                ->selectRaw("shop, $rev as revenue, $txn as transactions")
                ->groupBy('shop')->orderByDesc('revenue')->limit(5)->get();

            // Top cashiers
            $top_cashiers = (clone $baseReceipts())
                ->selectRaw("cashier, $rev as revenue, $txn as transactions")
                ->groupBy('cashier')->orderByDesc('revenue')->limit(5)->get();

            // Payment breakdown (Продажа only)
            $payment_breakdown = DB::table('payments')
                ->joinSub($baseReceipts()->select('id')->where('type', 'Продажа'), 'r', 'r.id', '=', 'payments.receipt_id')
                ->selectRaw('payments.type, SUM(payments.total) as total, COUNT(*) as count')
                ->groupBy('payments.type')->orderByDesc('total')->get();

            // Top products (Продажа only, active items)
            $top_products = DB::table('items')
                ->joinSub($baseReceipts()->select('id')->where('type', 'Продажа'), 'r', 'r.id', '=', 'items.receipt_id')
                ->where('items.status', true)
                ->selectRaw('items.name, items.code, SUM(items.total) as revenue, SUM(items.qty) as qty_sold, COUNT(DISTINCT items.receipt_id) as receipt_count')
                ->groupBy('items.name', 'items.code')->orderByDesc('revenue')->limit(10)->get();

            // Big receipts
            $big_receipts = (clone $baseReceipts())
                ->where('type', 'Продажа')
                ->select('id', 'number', 'date_close', 'shop', 'cashier', 'total', 'pos')
                ->orderByDesc('total')->limit(10)->get();

            // Big items
            $big_items = DB::table('items')
                ->joinSub($baseReceipts()->select('id', 'number', 'date_close', 'shop')->where('type', 'Продажа'), 'r', 'r.id', '=', 'items.receipt_id')
                ->where('items.status', true)
                ->select('items.name', 'items.code', 'items.qty', 'items.price', 'items.total as line_total', 'r.number', 'r.date_close', 'r.shop')
                ->orderByDesc('items.total')->limit(10)->get();

            // Store only plain arrays — Collections and stdClass objects cause
            // "incomplete object" errors on unserialize when the cache is cold.
            $toRows = fn($col) => $col->map(fn($r) => (array) $r)->all();

            return [
                'total_revenue'     => $total_revenue,
                'transaction_count' => $transaction_count,
                'items_sold'        => $items_sold,
                'sales_over_time'   => $toRows($sales_over_time),
                'sales_by_hour'     => $toRows($sales_by_hour),
                'sales_by_dow'      => $toRows($sales_by_dow),
                'top_shops'         => $toRows($top_shops),
                'top_cashiers'      => $toRows($top_cashiers),
                'payment_breakdown' => $toRows($payment_breakdown),
                'top_products'      => $toRows($top_products),
                'big_receipts'      => $toRows($big_receipts),
                'big_items'         => $toRows($big_items),
            ];
        });

        // Re-wrap plain arrays back into Collections of stdClass objects.
        $toCol = fn($arr) => collect($arr)->map(fn($r) => (object) $r);

        $total_revenue     = $analytics['total_revenue'];
        $transaction_count = $analytics['transaction_count'];
        $items_sold        = $analytics['items_sold'];
        $sales_over_time   = $toCol($analytics['sales_over_time']);
        $sales_by_hour     = $toCol($analytics['sales_by_hour']);
        $sales_by_dow      = $toCol($analytics['sales_by_dow']);
        $top_shops         = $toCol($analytics['top_shops']);
        $top_cashiers      = $toCol($analytics['top_cashiers']);
        $payment_breakdown = $toCol($analytics['payment_breakdown']);
        $top_products      = $toCol($analytics['top_products']);
        $big_receipts      = $toCol($analytics['big_receipts']);
        $big_items         = $toCol($analytics['big_items']);

        $avg_transaction_value = $transaction_count > 0 ? $total_revenue / $transaction_count : 0;
        $avg_basket_size       = $transaction_count > 0 ? $items_sold    / $transaction_count : 0;
        $peak_hour = $sales_by_hour->sortByDesc('transactions')->first();
        $peak_dow  = $sales_by_dow->sortByDesc('transactions')->first();

        // Shops list — cached separately (rarely changes)
        $shops_list = collect(Cache::remember('dashboard:v2:shops_list', now()->addHour(), fn() =>
            DB::table('receipts')->select('shop')->distinct()->orderBy('shop')->pluck('shop')->all()
        ));

        // --- Day notes for the period (shown on chart + tooltip) ---
        $day_notes = DayNote::whereBetween('date', [$from, $to])
            ->get()
            ->keyBy(fn($n) => $n->date->toDateString());

        // --- Day notes impact (avg daily revenue/transactions by note type) ---
        $note_impact = $sales_over_time->groupBy(function ($day) use ($day_notes) {
            $note = $day_notes[$day->date] ?? null;
            return $note ? $note->type : 'none';
        })->map(function ($days, $type) {
            return (object)[
                'type'             => $type,
                'days'             => $days->count(),
                'avg_revenue'      => round($days->avg('revenue') ?? 0),
                'avg_transactions' => round($days->avg('transactions') ?? 0),
            ];
        })->values()->sortBy('type');

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
            'sales_by_dow', 'peak_dow',
            'note_impact',
            'top_shops', 'top_cashiers',
            'payment_breakdown', 'top_products',
            'big_receipts', 'big_items',
            'shops_list',
            'day_notes', 'anomalies', 'unannotated_anomalies'
        ));
    }
}
