<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DataFetcher
{
    private string $driver;

    public function __construct()
    {
        $this->driver = DB::getDriverName();
    }

    public function fetch(string $question): array
    {
        $category = $this->detectCategory($question);

        $base = [
            'question' => $question,
            'category' => $category,
            'period'   => $this->period(),
            'summary'  => $this->summary(),
        ];

        return match ($category) {
            'products' => $base + $this->products(),
            'cashiers' => $base + $this->cashiers(),
            'payments' => $base + $this->payments(),
            'time'     => $base + $this->time(),
            default    => $base + $this->sales(),
        };
    }

    // ──────────────────────────────── Category detection ─────────────────────

    private function detectCategory(string $question): string
    {
        $q = mb_strtolower($question);

        $map = [
            'products' => [
                'mahsulot', 'tovar', 'product', 'item', 'товар', 'продукт',
                'kategori', 'category', 'top', 'bestsell', 'narx', 'price',
                'нарх', 'код', 'sku', 'eng ko', 'sotilgan',
            ],
            'cashiers' => [
                'kassir', 'cashier', 'кассир', 'xodim', 'sotuvchi',
                'employee', 'staff', 'продавец', 'сотрудник',
            ],
            'payments' => [
                'to\'lov', "to'lov", 'tolov', 'payment', 'оплата',
                'naqd', 'cash', 'нал', 'karta', 'card', 'карт',
                'beznal', 'безнал', 'method', 'usul', 'способ',
            ],
            'time' => [
                'soat', 'hour', 'час', 'kun', 'day', 'день',
                'hafta', 'week', 'неделя', 'oy', 'month', 'месяц',
                'trend', 'динамика', 'vaqt', 'time', 'weekday',
                'hafta kuni', 'день недели', 'dinamika', 'graf',
            ],
        ];

        foreach ($map as $cat => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($q, $kw)) {
                    return $cat;
                }
            }
        }

        return 'sales';
    }

    // ──────────────────────────────── Base queries ────────────────────────────

    private function salesQuery()
    {
        return DB::table('receipts')->where('status', 'Закрыт')->where('type', 'Продажа');
    }

    private function rows(object $query): array
    {
        return $query->get()->map(fn($r) => (array) $r)->all();
    }

    // ──────────────────────────────── Common ─────────────────────────────────

    private function period(): array
    {
        $r = $this->salesQuery()
            ->selectRaw('MIN(date_close) as from_date, MAX(date_close) as to_date')
            ->first();
        return ['from' => $r->from_date ?? null, 'to' => $r->to_date ?? null];
    }

    private function summary(): array
    {
        $s = $this->salesQuery()
            ->selectRaw('COUNT(*) as cnt, SUM(total) as rev')
            ->first();
        $rf = DB::table('receipts')
            ->where('status', 'Закрыт')->where('type', 'Возврат')
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total),0) as amt')
            ->first();

        return [
            'total_sales'       => (int)   ($s->cnt  ?? 0),
            'total_revenue_uzs' => (float) ($s->rev  ?? 0),
            'total_refunds'     => (int)   ($rf->cnt ?? 0),
            'refund_amount_uzs' => (float) ($rf->amt ?? 0),
            'net_revenue_uzs'   => (float) (($s->rev ?? 0) - ($rf->amt ?? 0)),
            'avg_txn_uzs'       => (float) round(($s->rev ?? 0) / max((int)($s->cnt ?? 1), 1), 2),
        ];
    }

    // ──────────────────────────────── Sales breakdown ─────────────────────────

    private function sales(): array
    {
        $byShop = $this->rows(
            $this->salesQuery()
                ->selectRaw('shop, COUNT(*) as count, ROUND(SUM(total),2) as revenue')
                ->groupBy('shop')
                ->orderByDesc('revenue')
                ->limit(10)
        );

        $byDay = $this->rows(
            $this->salesQuery()
                ->where('date_close', '>=', now()->subDays(30)->toDateString())
                ->selectRaw('DATE(date_close) as date, COUNT(*) as count, ROUND(SUM(total),2) as revenue')
                ->groupByRaw('DATE(date_close)')
                ->orderBy('date')
        );

        return [
            'sales_by_shop'      => $byShop,
            'sales_last_30_days' => $byDay,
        ];
    }

    // ──────────────────────────────── Products ────────────────────────────────

    private function products(): array
    {
        $base = DB::table('items')
            ->join('receipts', 'receipts.id', '=', 'items.receipt_id')
            ->where('receipts.status', 'Закрыт')
            ->where('receipts.type', 'Продажа')
            ->where('items.status', true);

        $top = $this->rows(
            $base->clone()
                ->selectRaw('items.name, items.category,
                             ROUND(SUM(items.qty),3) as total_qty,
                             ROUND(SUM(items.total),2) as total_revenue,
                             COUNT(DISTINCT receipts.id) as in_receipts')
                ->groupBy('items.code', 'items.name', 'items.category')
                ->orderByDesc('total_revenue')
                ->limit(20)
        );

        $byCategory = $this->rows(
            $base->clone()
                ->whereNotNull('items.category')
                ->selectRaw('items.category,
                             ROUND(SUM(items.qty),3) as total_qty,
                             ROUND(SUM(items.total),2) as total_revenue')
                ->groupBy('items.category')
                ->orderByDesc('total_revenue')
                ->limit(10)
        );

        return [
            'top_products' => $top,
            'by_category'  => $byCategory,
        ];
    }

    // ──────────────────────────────── Cashiers ────────────────────────────────

    private function cashiers(): array
    {
        $stats = $this->rows(
            $this->salesQuery()
                ->selectRaw('cashier, shop,
                             COUNT(*) as sales_count,
                             ROUND(SUM(total),2) as revenue,
                             ROUND(AVG(total),2) as avg_total')
                ->groupBy('cashier', 'shop')
                ->orderByDesc('revenue')
                ->limit(15)
        );

        $refunds = $this->rows(
            DB::table('receipts')
                ->where('status', 'Закрыт')->where('type', 'Возврат')
                ->selectRaw('cashier, COUNT(*) as refund_count')
                ->groupBy('cashier')
        );

        $refundMap = array_column($refunds, 'refund_count', 'cashier');
        foreach ($stats as &$row) {
            $row['refund_count'] = (int) ($refundMap[$row['cashier']] ?? 0);
        }

        return ['cashier_stats' => $stats];
    }

    // ──────────────────────────────── Payments ────────────────────────────────

    private function payments(): array
    {
        $byMethod = $this->rows(
            DB::table('payments')
                ->join('receipts', 'receipts.id', '=', 'payments.receipt_id')
                ->where('receipts.status', 'Закрыт')
                ->where('receipts.type', 'Продажа')
                ->selectRaw('payments.type as method,
                             COUNT(*) as count,
                             ROUND(SUM(payments.total),2) as amount')
                ->groupBy('payments.type')
                ->orderByDesc('amount')
        );

        return ['payment_breakdown' => $byMethod];
    }

    // ──────────────────────────────── Time analysis ───────────────────────────

    private function time(): array
    {
        $isMysql = $this->driver === 'mysql';

        $hourExpr  = $isMysql
            ? 'HOUR(date_close)'
            : "CAST(strftime('%H', date_close) AS INTEGER)";

        $dowExpr   = $isMysql
            ? 'DAYOFWEEK(date_close) - 1'
            : "CAST(strftime('%w', date_close) AS INTEGER)";

        $monthExpr = $isMysql
            ? "DATE_FORMAT(date_close, '%Y-%m')"
            : "strftime('%Y-%m', date_close)";

        $byHour = $this->rows(
            $this->salesQuery()
                ->selectRaw("{$hourExpr} as hour, COUNT(*) as count, ROUND(SUM(total),2) as revenue")
                ->groupByRaw("{$hourExpr}")
                ->orderBy('hour')
        );

        $byDow = $this->rows(
            $this->salesQuery()
                ->selectRaw("{$dowExpr} as dow, COUNT(*) as count, ROUND(SUM(total),2) as revenue")
                ->groupByRaw("{$dowExpr}")
                ->orderBy('dow')
        );

        $dowNames = [0 => 'Yak', 1 => 'Dush', 2 => 'Sesh', 3 => 'Chor', 4 => 'Pay', 5 => 'Juma', 6 => 'Shan'];
        foreach ($byDow as &$row) {
            $row['day_name'] = $dowNames[(int) $row['dow']] ?? (string) $row['dow'];
        }

        $byMonth = $this->rows(
            $this->salesQuery()
                ->selectRaw("{$monthExpr} as month, COUNT(*) as count, ROUND(SUM(total),2) as revenue")
                ->groupByRaw($monthExpr)
                ->orderBy('month')
        );

        return [
            'by_hour'  => $byHour,
            'by_dow'   => $byDow,
            'by_month' => $byMonth,
        ];
    }
}
