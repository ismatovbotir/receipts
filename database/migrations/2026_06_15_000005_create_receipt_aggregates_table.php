<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pre-computed KPI snapshots refreshed by AggregateReceiptsCommand.
     * period_type: daily | weekly | monthly
     * shop_code:   null  = all shops combined for that period
     *
     * Unique key (period_type, period_date, shop_code) lets the command
     * do INSERT … ON DUPLICATE KEY UPDATE (upsert) safely.
     */
    public function up(): void
    {
        Schema::create('receipt_aggregates', function (Blueprint $table) {
            $table->id();
            $table->string('period_type', 10)->index();   // daily | weekly | monthly
            $table->date('period_date')->index();          // day-start / week-start / month-start
            $table->string('shop', 150)->nullable()->index(); // null = all shops combined

            // Core KPIs
            $table->decimal('total_revenue', 18, 2)->default(0);
            $table->unsignedInteger('transaction_count')->default(0);
            $table->decimal('avg_transaction_value', 15, 2)->default(0);
            $table->decimal('items_sold', 12, 3)->default(0);
            $table->decimal('avg_basket_size', 10, 3)->default(0);
            $table->decimal('total_discount', 18, 2)->default(0);
            $table->decimal('discount_pct', 6, 2)->default(0); // %
            $table->decimal('vat_amount', 18, 2)->default(0);
            $table->decimal('revenue_ex_vat', 18, 2)->default(0);

            // JSON breakdowns (payment methods, categories, hourly peaks)
            $table->json('payment_breakdown')->nullable(); // [{method, amount, count}, …]
            $table->json('category_breakdown')->nullable(); // [{category, revenue, qty}, …]
            $table->json('hourly_breakdown')->nullable();   // [{hour, count, revenue}, …]

            $table->timestamp('computed_at')->nullable();

            $table->unique(['period_type', 'period_date', 'shop'], 'agg_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_aggregates');
    }
};
