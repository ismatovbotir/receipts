<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_notes', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('type', 50)->default('other'); // holiday, weather, sport, promo, other
            $table->string('title', 200);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_notes');
    }
};
