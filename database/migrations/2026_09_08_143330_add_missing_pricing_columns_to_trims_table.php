<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trims', function (Blueprint $table) {
            $table->integer('total_price')->nullable();
            $table->integer('booking_deposit')->nullable();
            $table->integer('zero_interest_price')->nullable();
            $table->integer('price_9pct')->nullable();
            $table->json('colors')->nullable();
            $table->text('financing_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trims', function (Blueprint $table) {
            $table->dropColumn([
                'total_price',
                'booking_deposit',
                'zero_interest_price',
                'price_9pct',
                'colors',
                'financing_notes'
            ]);
        });
    }
};
