<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    public function index(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->session()->put('analytics_filter', $request->only('from', 'to', 'shop'));
            return redirect()->route('cashiers.index');
        }

        $filter = $request->session()->get('analytics_filter', []);
        $from   = $filter['from']  ?? now()->startOfMonth()->toDateString();
        $to     = $filter['to']    ?? now()->toDateString();
        $shop   = ($filter['shop'] ?? '') ?: null;

        // Detect driver: SQLite uses strftime(), MySQL uses TIMESTAMPDIFF()
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $timeExpr = $isSqlite
            ? "(strftime('%s', date_close) - strftime('%s', date_open))"
            : "TIMESTAMPDIFF(SECOND, date_open, date_close)";

        // Cache key v1: keyed by filter params (10 min TTL)
        $cacheKey = 'cashiers:v1:' . md5("{$from}|{$to}|{$shop}");

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($from, $to, $shop, $timeExpr) {

            // Base query builder factory: status=Закрыт + date range + optional shop
            $baseReceipts = function () use ($from, $to, $shop) {
                $q = DB::table('receipts')
                    ->where('status', 'Закрыт')
                    ->where('date_close', '>=', $from . ' 00:00:00')
                    ->where('date_close', '<=', $to   . ' 23:59:59');
                if ($shop) {
                    $q->where('shop', $shop);
                }
                return $q;
            };

            // 1. Main stats per cashier (from closed receipts in date range)
            $mainStats = (clone $baseReceipts())
                ->selectRaw("
                    cashier,
                    COUNT(CASE WHEN type='Продажа' THEN 1 END) as sales_count,
                    COALESCE(SUM(CASE WHEN type='Продажа' THEN total ELSE 0 END), 0) as sales_total,
                    COUNT(CASE WHEN type='Возврат' THEN 1 END) as refund_count,
                    COALESCE(SUM(CASE WHEN type='Возврат' THEN total ELSE 0 END), 0) as refund_total,
                    COALESCE(SUM(CASE WHEN type='Продажа' THEN total WHEN type='Возврат' THEN -total ELSE 0 END), 0) as net_revenue,
                    COALESCE(AVG({$timeExpr}), 0) as avg_time_sec,
                    COUNT(*) as completed_count
                ")
                ->groupBy('cashier')
                ->get()
                ->keyBy('cashier');

            // 2. Cancelled receipts per cashier (any status except Закрыт, same date/shop filter)
            $cancelledQuery = DB::table('receipts')
                ->where('status', '!=', 'Закрыт')
                ->where('date_close', '>=', $from . ' 00:00:00')
                ->where('date_close', '<=', $to   . ' 23:59:59');
            if ($shop) {
                $cancelledQuery->where('shop', $shop);
            }
            $cancelled = $cancelledQuery
                ->selectRaw("cashier, COUNT(*) as cancelled_count")
                ->groupBy('cashier')
                ->get()
                ->keyBy('cashier');

            // 3. Items qty sold per cashier (Продажа only, active items)
            $itemsQty = DB::table('items')
                ->joinSub(
                    $baseReceipts()->select('id', 'cashier')->where('type', 'Продажа'),
                    'r',
                    'r.id',
                    '=',
                    'items.receipt_id'
                )
                ->where('items.status', true)
                ->selectRaw("r.cashier, COALESCE(SUM(items.qty), 0) as qty_sold")
                ->groupBy('r.cashier')
                ->get()
                ->keyBy('cashier');

            // Merge all three result sets in PHP
            $merged = $mainStats->map(function ($row, $cashier) use ($cancelled, $itemsQty) {
                $cancelledCount = (int)   ($cancelled[$cashier]->cancelled_count ?? 0);
                $qtySold        = (float) ($itemsQty[$cashier]->qty_sold         ?? 0);
                $salesCount     = (int)   $row->sales_count;
                $avgTimeSec     = (float) $row->avg_time_sec;

                // avg_time_per_item_sec = total service time / total items sold
                // = avg_time_sec * sales_count / qty_sold
                $avgTimePerItemSec = ($qtySold > 0 && $salesCount > 0)
                    ? ($avgTimeSec * $salesCount / $qtySold)
                    : 0;

                return [
                    'cashier'               => $cashier,
                    'sales_count'           => $salesCount,
                    'sales_total'           => (float) $row->sales_total,
                    'refund_count'          => (int)   $row->refund_count,
                    'refund_total'          => (float) $row->refund_total,
                    'net_revenue'           => (float) $row->net_revenue,
                    'avg_time_sec'          => $avgTimeSec,
                    'avg_time_per_item_sec' => $avgTimePerItemSec,
                    'completed_count'       => (int)   $row->completed_count,
                    'cancelled_count'       => $cancelledCount,
                    'qty_sold'              => $qtySold,
                ];
            });

            // Sort by net_revenue DESC; store as plain arrays to avoid unserialize errors
            return $merged
                ->sortByDesc('net_revenue')
                ->values()
                ->all();
        });

        // Re-wrap plain arrays back to Collection of stdClass objects
        $cashier_stats = collect($data)->map(fn($r) => (object) $r);

        // Shops list — cached separately for 1 hour (rarely changes)
        $shops_list = collect(Cache::remember('cashiers:v1:shops_list', now()->addHour(), fn() =>
            DB::table('receipts')->select('shop')->distinct()->orderBy('shop')->pluck('shop')->all()
        ));

        return view('cashiers.index', compact(
            'from', 'to', 'shop',
            'cashier_stats',
            'shops_list'
        ));
    }
}
