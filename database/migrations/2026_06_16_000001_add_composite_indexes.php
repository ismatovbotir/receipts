<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            // Main dashboard filter: status + date range (all queries)
            $table->index(['status', 'date_close'], 'receipts_status_date_idx');
            // Revenue/transaction queries: status + type + date range
            $table->index(['status', 'type', 'date_close'], 'receipts_status_type_date_idx');
            // Shop-filtered queries
            $table->index(['status', 'type', 'shop', 'date_close'], 'receipts_status_type_shop_date_idx');
        });

        Schema::table('items', function (Blueprint $table) {
            // Item joins always filter by receipt_id + status
            $table->index(['receipt_id', 'status'], 'items_receipt_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropIndex('receipts_status_date_idx');
            $table->dropIndex('receipts_status_type_date_idx');
            $table->dropIndex('receipts_status_type_shop_date_idx');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('items_receipt_status_idx');
        });
    }
};
