<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-product KPI snapshots per period — powers TOP-N product reports.
     * Unique key (period_type, period_date, product_code) for upserts.
     */
    public function up(): void
    {
        Schema::create('product_aggregates', function (Blueprint $table) {
            $table->id();
            $table->string('period_type', 10)->index();
            $table->date('period_date')->index();
            $table->string('product_code', 100)->index();
            $table->string('product_name');
            $table->string('category', 100)->index();

            $table->decimal('total_revenue', 18, 2)->default(0);
            $table->decimal('quantity_sold', 12, 3)->default(0);
            $table->unsignedInteger('transaction_count')->default(0); // receipts this product appeared in
            $table->decimal('total_discount', 15, 2)->default(0);

            $table->timestamp('computed_at')->nullable();

            $table->unique(['period_type', 'period_date', 'product_code'], 'product_agg_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_aggregates');
    }
};
