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
        // PRG: POST stores filter to shared session key, GET reads it — keeps URL clean.
        if ($request->isMethod('post')) {
            $request->session()->put('analytics_filter', $request->only('from', 'to', 'shop'));
            return redirect()->route('dashboard');
        }

        $filter = $request->session()->get('analytics_filter', []);
        $from   = $filter['from']  ?? now()->startOfMonth()->toDateString();
        $to     = $filter['to']    ?? now()->toDateString();
        $shop   = ($filter['shop'] ?? '') ?: null;

        // Only closed receipts; direct string comparisons preserve index on (status, date_close).
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

        $cacheKey = 'dashboard:v3:' . md5("{$from}|{$to}|{$shop}");

        $analytics = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($baseReceipts, $rev, $txn) {

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

            $sales_over_time = (clone $baseReceipts())
                ->selectRaw("DATE(date_close) as date, $rev as revenue, $txn as transactions")
                ->groupByRaw("DATE(date_close)")
                ->orderByRaw("DATE(date_close) ASC")
                ->get()
                ->map(fn($r) => (array) $r)
                ->all();

            return compact('total_revenue', 'transaction_count', 'items_sold', 'sales_over_time');
        });

        $total_revenue     = $analytics['total_revenue'];
        $transaction_count = $analytics['transaction_count'];
        $items_sold        = $analytics['items_sold'];
        $sales_over_time   = collect($analytics['sales_over_time'])->map(fn($r) => (object) $r);

        $avg_transaction_value = $transaction_count > 0 ? $total_revenue / $transaction_count : 0;
        $avg_basket_size       = $transaction_count > 0 ? $items_sold    / $transaction_count : 0;

        $shops_list = collect(Cache::remember('dashboard:v3:shops_list', now()->addHour(), fn() =>
            DB::table('receipts')->select('shop')->distinct()->orderBy('shop')->pluck('shop')->all()
        ));

        // Day notes — always fresh (user annotates anomalies immediately)
        $day_notes = DayNote::whereBetween('date', [$from, $to])
            ->get()
            ->keyBy(fn($n) => $n->date->toDateString());

        // Anomaly detection (σ × 1.5 threshold)
        $revenues  = $sales_over_time->filter(fn($r) => $r->revenue > 0)->pluck('revenue');
        $anomalies = collect();

        if ($revenues->count() >= 3) {
            $mean     = $revenues->avg();
            $variance = $revenues->map(fn($v) => pow($v - $mean, 2))->avg();
            $stddev   = sqrt($variance);

            $threshold_high = $mean + 1.5 * $stddev;
            $threshold_low  = max(0, $mean - 1.5 * $stddev);

            $anomalies = $sales_over_time->filter(function ($r) use ($threshold_high, $threshold_low) {
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
            'sales_over_time',
            'shops_list',
            'day_notes', 'anomalies', 'unannotated_anomalies'
        ));
    }
}
