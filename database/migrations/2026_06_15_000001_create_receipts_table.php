<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();   // POS-issued UUID
            $table->unsignedInteger('number')->index();
            $table->dateTime('date_open');
            $table->dateTime('date_close')->index();
            $table->string('type', 50)->index();      // Продажа, Возврат, …
            $table->string('cashier', 150)->index();
            $table->string('status', 50)->index();    // Закрыт, …
            $table->string('card', 100)->nullable();
            $table->unsignedSmallInteger('pos')->index();
            $table->decimal('total', 15, 2);
            $table->string('shop', 150)->index();
            $table->unsignedInteger('shift')->nullable();
            //$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
