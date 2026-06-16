<?php

namespace App\Http\Controllers;

use App\Models\DayNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->session()->put('analytics_filter', $request->only('from', 'to', 'shop'));
            return redirect()->route('sales.index');
        }

        $filter = $request->session()->get('analytics_filter', []);
        $from   = $filter['from']  ?? now()->startOfMonth()->toDateString();
        $to     = $filter['to']    ?? now()->toDateString();
        $shop   = ($filter['shop'] ?? '') ?: null;

        // Only closed receipts; Продажа adds, Возврат subtracts.
        // Direct string date comparisons preserve the (status, date_close) index —
        // whereDate() wraps the column in strftime() and defeats index lookups in SQLite.
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

        // SQLite uses strftime(); MySQL uses HOUR() / DAYOFWEEK().
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $hourExpr = $isSqlite
            ? "CAST(strftime('%H', date_close) AS INTEGER)"
            : "HOUR(date_close)";
        $dowExpr  = $isSqlite
            ? "CAST(strftime('%w', date_close) AS INTEGER)"
            : "(DAYOFWEEK(date_close) - 1)"; // MySQL: 1=Sun→0 matches SQLite %w

        // Cache heavy DB queries 10 min; store plain PHP arrays only
        // (Collections/stdClass cause unserialize errors on cold-cache reads).
        $cacheKey = 'sales:v1:' . md5("{$from}|{$to}|{$shop}");

        $data = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            function () use ($baseReceipts, $rev, $txn, $hourExpr, $dowExpr, $from, $to, $shop) {

                // 1. Sales over time (daily)
                $sales_over_time = (clone $baseReceipts())
                    ->selectRaw("DATE(date_close) as date, $rev as revenue, $txn as transactions")
                    ->groupByRaw("DATE(date_close)")
                    ->orderByRaw("DATE(date_close) ASC")
                    ->get();

                // 2. Sales by hour (fill missing hours 0–23 with zeros)
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

                // 3. Sales by day of week (0=Sun … 6=Sat)
                // Display order: Mon→Sun mapped as [1,2,3,4,5,6,0]
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

                // 4. Top shops (limit 10)
                $top_shops = (clone $baseReceipts())
                    ->selectRaw("shop, $rev as revenue, $txn as transactions")
                    ->groupBy('shop')
                    ->orderByDesc('revenue')
                    ->limit(10)
                    ->get();

                // 5. Payment breakdown (Продажа only)
                $payment_breakdown = DB::table('payments')
                    ->joinSub(
                        $baseReceipts()->select('id')->where('type', 'Продажа'),
                        'r', 'r.id', '=', 'payments.receipt_id'
                    )
                    ->selectRaw('payments.type, SUM(payments.total) as total, COUNT(*) as count')
                    ->groupBy('payments.type')
                    ->orderByDesc('total')
                    ->get();

                // Store only plain arrays — no Collection / stdClass in cache.
                $toRows = fn($col) => $col->map(fn($r) => (array) $r)->all();

                return [
                    'sales_over_time'   => $toRows($sales_over_time),
                    'sales_by_hour'     => $toRows($sales_by_hour),
                    'sales_by_dow'      => $toRows($sales_by_dow),
                    'top_shops'         => $toRows($top_shops),
                    'payment_breakdown' => $toRows($payment_breakdown),
                ];
            }
        );

        // Re-wrap plain arrays back into Collections of stdClass objects.
        $toCol = fn($arr) => collect($arr)->map(fn($r) => (object) $r);

        $sales_over_time   = $toCol($data['sales_over_time']);
        $sales_by_hour     = $toCol($data['sales_by_hour']);
        $sales_by_dow      = $toCol($data['sales_by_dow']);
        $top_shops         = $toCol($data['top_shops']);
        $payment_breakdown = $toCol($data['payment_breakdown']);

        // Peak hour / peak DOW computed in PHP from the Collections.
        $peak_hour = $sales_by_hour->sortByDesc('transactions')->first();
        $peak_dow  = $sales_by_dow->sortByDesc('transactions')->first();

        // Shops list cached separately (rarely changes); 1 h TTL.
        $shops_list = collect(Cache::remember(
            'sales:v1:shops_list',
            now()->addHour(),
            fn() => DB::table('receipts')
                ->select('shop')
                ->distinct()
                ->orderBy('shop')
                ->pluck('shop')
                ->all()
        ));

        // Day notes stay fresh (outside cache) — shown as chart annotations.
        $day_notes = DayNote::whereBetween('date', [$from, $to])
            ->get()
            ->keyBy(fn($n) => $n->date->toDateString());

        // Note impact: avg daily revenue / transactions grouped by note type.
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

        return view('sales.index', compact(
            'from', 'to', 'shop',
            'sales_over_time',
            'sales_by_hour', 'peak_hour',
            'sales_by_dow',  'peak_dow',
            'top_shops',
            'payment_breakdown',
            'note_impact',
            'day_notes',
            'shops_list'
        ));
    }
}
