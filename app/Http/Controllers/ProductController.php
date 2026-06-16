<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->session()->put('analytics_filter', $request->only('from', 'to', 'shop'));
            return redirect()->route('products.index');
        }

        $filter = $request->session()->get('analytics_filter', []);
        $from   = $filter['from']  ?? now()->startOfMonth()->toDateString();
        $to     = $filter['to']    ?? now()->toDateString();
        $shop   = ($filter['shop'] ?? '') ?: null;

        // Cache key v1: keyed by filter params (10 min TTL)
        $cacheKey = 'products:v1:' . md5("{$from}|{$to}|{$shop}");

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($from, $to, $shop) {

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

            // 1. Top products — Продажа only, active items, limit 20
            $topProducts = DB::table('items')
                ->joinSub(
                    $baseReceipts()->select('id')->where('type', 'Продажа'),
                    'r',
                    'r.id',
                    '=',
                    'items.receipt_id'
                )
                ->where('items.status', true)
                ->selectRaw("
                    items.name,
                    items.code,
                    SUM(items.total) as revenue,
                    SUM(items.qty) as qty_sold,
                    COUNT(DISTINCT items.receipt_id) as receipt_count,
                    AVG(items.price) as avg_price
                ")
                ->groupBy('items.name', 'items.code')
                ->orderByDesc('revenue')
                ->limit(20)
                ->get()
                ->map(fn($r) => (array) $r)
                ->all();

            // 2. Big items — largest single line totals, Продажа only, active items, limit 15
            $bigItems = DB::table('items')
                ->joinSub(
                    $baseReceipts()
                        ->select('id', 'number', 'date_close', 'shop', 'cashier')
                        ->where('type', 'Продажа'),
                    'r',
                    'r.id',
                    '=',
                    'items.receipt_id'
                )
                ->where('items.status', true)
                ->select(
                    'items.name',
                    'items.code',
                    'items.qty',
                    'items.price',
                    DB::raw('items.total as line_total'),
                    'r.number',
                    'r.date_close',
                    'r.shop',
                    'r.cashier'
                )
                ->orderByDesc('items.total')
                ->limit(15)
                ->get()
                ->map(fn($r) => (array) $r)
                ->all();

            // 3. Revenue by product code for chart — top 10 by revenue
            $chartProducts = DB::table('items')
                ->joinSub(
                    $baseReceipts()->select('id')->where('type', 'Продажа'),
                    'r',
                    'r.id',
                    '=',
                    'items.receipt_id'
                )
                ->where('items.status', true)
                ->selectRaw("
                    items.name,
                    SUM(items.total) as revenue,
                    SUM(items.qty) as qty_sold
                ")
                ->groupBy('items.name', 'items.code')
                ->orderByDesc('revenue')
                ->limit(10)
                ->get()
                ->map(fn($r) => (array) $r)
                ->all();

            return [
                'top_products'   => $topProducts,
                'big_items'      => $bigItems,
                'chart_products' => $chartProducts,
            ];
        });

        // Re-wrap plain arrays back to Collections of stdClass objects
        $top_products   = collect($data['top_products'])->map(fn($r) => (object) $r);
        $big_items      = collect($data['big_items'])->map(fn($r) => (object) $r);
        $chart_products = collect($data['chart_products'])->map(fn($r) => (object) $r);

        $total_product_revenue = $top_products->sum('revenue');

        // Shops list — cached separately for 1 hour (rarely changes)
        $shops_list = collect(Cache::remember('products:v1:shops_list', now()->addHour(), fn() =>
            DB::table('receipts')->select('shop')->distinct()->orderBy('shop')->pluck('shop')->all()
        ));

        return view('products.index', compact(
            'from', 'to', 'shop',
            'top_products',
            'big_items',
            'chart_products',
            'total_product_revenue',
            'shops_list'
        ));
    }
}
