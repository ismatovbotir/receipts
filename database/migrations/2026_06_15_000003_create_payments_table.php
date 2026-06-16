<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('receipt_id')->constrained()->cascadeOnDelete();
            $table->string('type', 100)->index();  // Наличные, Безналичные, …
            $table->decimal('total', 15, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
