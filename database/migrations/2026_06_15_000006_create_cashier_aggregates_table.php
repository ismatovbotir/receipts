<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-cashier KPI snapshots per period.
     * Unique key (period_type, period_date, cashier_code) for upserts.
     */
    public function up(): void
    {
        Schema::create('cashier_aggregates', function (Blueprint $table) {
            $table->id();
            $table->string('period_type', 10)->index();
            $table->date('period_date')->index();
            $table->string('cashier', 150)->index();  // matches receipts.cashier
            $table->string('shop', 150)->nullable();  // matches receipts.shop

            $table->decimal('total_revenue', 18, 2)->default(0);
            $table->unsignedInteger('transaction_count')->default(0);
            $table->decimal('avg_transaction_value', 15, 2)->default(0);
            $table->decimal('items_sold', 12, 3)->default(0);
            $table->decimal('total_discount', 18, 2)->default(0);

            $table->timestamp('computed_at')->nullable();

            $table->unique(['period_type', 'period_date', 'cashier'], 'cashier_agg_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_aggregates');
    }
};
