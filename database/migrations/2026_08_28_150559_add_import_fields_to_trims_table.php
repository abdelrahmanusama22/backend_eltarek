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
            // Legacy system tracking — prevents duplicates on re-import
            $table->string('legacy_car_id')->nullable()->after('vehicle_id');

            // Extra price fields from Excel
            $table->unsignedInteger('total_price')->nullable()->after('original_price_egp');
            $table->unsignedInteger('booking_deposit')->nullable()->after('total_price');
            $table->unsignedInteger('zero_interest_price')->nullable()->after('booking_deposit');
            $table->unsignedInteger('price_9pct')->nullable()->after('zero_interest_price');

            // Markup % controls executive/display price (default 5%)
            // executive_price = price_egp * (1 + markup_percentage / 100)
            $table->decimal('markup_percentage', 5, 2)->default(5.00)->after('price_9pct');

            // Availability & details
            $table->boolean('is_on_hold')->default(false)->after('markup_percentage');
            $table->string('colors')->nullable()->after('is_on_hold');
            $table->text('financing_notes')->nullable()->after('colors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trims', function (Blueprint $table) {
            //
        });
    }
};
