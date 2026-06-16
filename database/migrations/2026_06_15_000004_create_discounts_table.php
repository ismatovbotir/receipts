<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('receipt_id')->constrained()->cascadeOnDelete();
           // $table->string('type', 100)->index();   // loyalty, promo, manual, …
            $table->boolean('receipt')->default(true);
            $table->decimal('total', 15, 2);
            
            $table->unsignedSmallInteger('no')->nullable();  // line_no when scope=item
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
