<?php

namespace App\Console\Commands;

use App\Services\AggregateService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AggregateReceiptsCommand extends Command
{
    protected $signature = 'receipts:aggregate
                            {--from= : Start date (Y-m-d). Defaults to earliest receipt.}
                            {--to=   : End date (Y-m-d). Defaults to today.}
                            {--all   : Recompute everything from the very first receipt.}';

    protected $description = 'Compute daily / weekly / monthly KPI aggregates from receipts.';

    public function handle(AggregateService $service): int
    {
        if ($this->option('all')) {
            $earliest = DB::table('receipts')->min(DB::raw('DATE(date_close)'));
            if (! $earliest) {
                $this->info('No receipts found.');
                return self::SUCCESS;
            }
            $from = Carbon::parse($earliest);
        } else {
            $from = $this->option('from')
                ? Carbon::parse($this->option('from'))
                : Carbon::today();
        }

        $to = $this->option('to')
            ? Carbon::parse($this->option('to'))
            : Carbon::today();

        $this->info("Aggregating {$from->toDateString()} → {$to->toDateString()} …");

        $service->compute($from, $to);

        $this->info('Done.');
        $this->table(
            ['Table', 'Rows'],
            [
                ['receipt_aggregates', DB::table('receipt_aggregates')->count()],
                ['cashier_aggregates', DB::table('cashier_aggregates')->count()],
                ['product_aggregates', DB::table('product_aggregates')->count()],
            ]
        );

        return self::SUCCESS;
    }
}
