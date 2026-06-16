<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('receipt_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('code')->index();   // product barcode / PLU
            $table->string('name')->index();
            $table->string('category')->nullable()->index();
            $table->decimal('price', 15, 2);               // unit price
            $table->decimal('total', 15, 2);               // line total
            $table->decimal('discountTotal', 15, 2)->default(0);
            $table->decimal('qty', 10, 3);
            $table->decimal('roundTotal', 15, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->unsignedSmallInteger('no');            // line number
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
