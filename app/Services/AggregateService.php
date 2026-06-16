<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AggregateService
{
    /**
     * Recompute all aggregate tables for every receipt whose date_close
     * falls within [$from, $to] (inclusive, date comparison).
     */
    public function compute(Carbon $from, Carbon $to): void
    {
        $receipts = DB::table('receipts')
            ->whereBetween(DB::raw('DATE(date_close)'), [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->get();

        if ($receipts->isEmpty()) {
            return;
        }

        $receiptIds = $receipts->pluck('id')->all();

        $items = DB::table('items')
            ->whereIn('receipt_id', $receiptIds)
            ->get()
            ->groupBy('receipt_id');

        $payments = DB::table('payments')
            ->whereIn('receipt_id', $receiptIds)
            ->get()
            ->groupBy('receipt_id');

        // Determine which period buckets are affected
        $dates = $receipts
            ->map(fn($r) => Carbon::parse($r->date_close)->toDateString())
            ->unique();

        $periods = [];
        foreach ($dates as $date) {
            $d = Carbon::parse($date);
            $periods['daily'][$d->toDateString()]   = true;
            $periods['weekly'][$d->startOfWeek()->toDateString()] = true;
            $periods['monthly'][$d->startOfMonth()->toDateString()] = true;
        }

        foreach ($periods as $type => $bucketDates) {
            foreach (array_keys($bucketDates) as $bucketStart) {
                $this->upsertPeriod($type, $bucketStart, $receipts, $items, $payments);
            }
        }
    }

    private function upsertPeriod(
        string $periodType,
        string $bucketStart,
        $allReceipts,
        $allItems,
        $allPayments
    ): void {
        $start = Carbon::parse($bucketStart);
        $end   = match ($periodType) {
            'daily'   => $start->copy()->endOfDay(),
            'weekly'  => $start->copy()->endOfWeek(),
            'monthly' => $start->copy()->endOfMonth(),
        };

        $subset = $allReceipts->filter(
            fn($r) => Carbon::parse($r->date_close)->between($start, $end)
        );

        if ($subset->isEmpty()) {
            return;
        }

        // All-shops aggregate
        $this->upsertReceiptAggregate($periodType, $bucketStart, null, $subset, $allItems, $allPayments);

        // Per-shop aggregates
        foreach ($subset->groupBy('shop') as $shop => $shopReceipts) {
            $this->upsertReceiptAggregate($periodType, $bucketStart, $shop, $shopReceipts, $allItems, $allPayments);
        }

        // Per-cashier aggregates
        foreach ($subset->groupBy('cashier') as $cashier => $cashierReceipts) {
            $this->upsertCashierAggregate($periodType, $bucketStart, $cashier, $cashierReceipts, $allItems);
        }

        // Per-product aggregates (from items)
        $subsetIds   = $subset->pluck('id')->flip();
        $subsetItems = $allItems->filter(fn($rows, $rid) => isset($subsetIds[$rid]))->flatten(1);
        foreach ($subsetItems->groupBy('code') as $code => $productItems) {
            $this->upsertProductAggregate($periodType, $bucketStart, $productItems);
        }
    }

    private function upsertReceiptAggregate(
        string $periodType,
        string $periodDate,
        ?string $shop,
        $receipts,
        $allItems,
        $allPayments
    ): void {
        $ids           = $receipts->pluck('id');
        $revenue       = $receipts->sum('total');
        $count         = $receipts->count();
        $totalDiscount = $receipts->sum('total') - $receipts->sum('total'); // placeholder — no discount col on receipts

        // Items for this subset
        $subsetItems = $allItems->filter(fn($rows, $rid) => $ids->contains($rid))->flatten(1);
        $itemsSold   = $subsetItems->sum('qty');

        // Payments breakdown
        $subsetPayments = $allPayments->filter(fn($rows, $rid) => $ids->contains($rid))->flatten(1);
        $payBreakdown   = $subsetPayments->groupBy('type')->map(fn($rows) => [
            'type'   => $rows->first()->type,
            'total'  => $rows->sum('total'),
            'count'  => $rows->count(),
        ])->values()->all();

        // Category breakdown
        $catBreakdown = $subsetItems->groupBy('name')->map(fn($rows) => [
            'name'    => $rows->first()->name,
            'revenue' => $rows->sum('total'),
            'qty'     => $rows->sum('qty'),
        ])->values()->sortByDesc('revenue')->take(20)->values()->all();

        // Hourly breakdown
        $hourBreakdown = $receipts->groupBy(fn($r) => (int) Carbon::parse($r->date_close)->format('H'))
            ->map(fn($rows, $hour) => [
                'hour'    => $hour,
                'count'   => $rows->count(),
                'revenue' => $rows->sum('total'),
            ])->sortBy('hour')->values()->all();

        $atv         = $count > 0 ? round($revenue / $count, 2) : 0;
        $basketSize  = $count > 0 ? round($itemsSold / $count, 3) : 0;

        DB::table('receipt_aggregates')->upsert(
            [[
                'period_type'           => $periodType,
                'period_date'           => $periodDate,
                'shop'                  => $shop,
                'total_revenue'         => $revenue,
                'transaction_count'     => $count,
                'avg_transaction_value' => $atv,
                'items_sold'            => $itemsSold,
                'avg_basket_size'       => $basketSize,
                'total_discount'        => 0,
                'discount_pct'          => 0,
                'vat_amount'            => 0,
                'revenue_ex_vat'        => $revenue,
                'payment_breakdown'     => json_encode($payBreakdown),
                'category_breakdown'    => json_encode($catBreakdown),
                'hourly_breakdown'      => json_encode($hourBreakdown),
                'computed_at'           => now()->toDateTimeString(),
            ]],
            ['period_type', 'period_date', 'shop'],
            [
                'total_revenue', 'transaction_count', 'avg_transaction_value',
                'items_sold', 'avg_basket_size', 'payment_breakdown',
                'category_breakdown', 'hourly_breakdown', 'computed_at',
            ]
        );
    }

    private function upsertCashierAggregate(
        string $periodType,
        string $periodDate,
        string $cashier,
        $receipts,
        $allItems
    ): void {
        $ids        = $receipts->pluck('id');
        $revenue    = $receipts->sum('total');
        $count      = $receipts->count();
        $itemsSold  = $allItems->filter(fn($rows, $rid) => $ids->contains($rid))->flatten(1)->sum('qty');
        $shop       = $receipts->first()->shop ?? null;

        DB::table('cashier_aggregates')->upsert(
            [[
                'period_type'           => $periodType,
                'period_date'           => $periodDate,
                'cashier'               => $cashier,
                'shop'                  => $shop,
                'total_revenue'         => $revenue,
                'transaction_count'     => $count,
                'avg_transaction_value' => $count > 0 ? round($revenue / $count, 2) : 0,
                'items_sold'            => $itemsSold,
                'total_discount'        => 0,
                'computed_at'           => now()->toDateTimeString(),
            ]],
            ['period_type', 'period_date', 'cashier'],
            ['shop', 'total_revenue', 'transaction_count', 'avg_transaction_value', 'items_sold', 'computed_at']
        );
    }

    private function upsertProductAggregate(
        string $periodType,
        string $periodDate,
        $items
    ): void {
        $first = $items->first();

        DB::table('product_aggregates')->upsert(
            [[
                'period_type'       => $periodType,
                'period_date'       => $periodDate,
                'product_code'      => $first->code,
                'product_name'      => $first->name,
                'category'          => $first->name, // no category col in items — use name as fallback
                'total_revenue'     => $items->sum('total'),
                'quantity_sold'     => $items->sum('qty'),
                'transaction_count' => $items->count(),
                'total_discount'    => $items->sum('discountTotal'),
                'computed_at'       => now()->toDateTimeString(),
            ]],
            ['period_type', 'period_date', 'product_code'],
            ['product_name', 'category', 'total_revenue', 'quantity_sold', 'transaction_count', 'total_discount', 'computed_at']
        );
    }
}
