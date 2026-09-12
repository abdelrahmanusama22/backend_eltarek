<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->index(['user_id', 'status', 'date'], 'bookings_user_status_date_index');
        });
        Schema::table('redemptions', function (Blueprint $table): void {
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex('bookings_user_status_date_index');
        });
        Schema::table('redemptions', function (Blueprint $table): void {
            $table->dropUnique(['code']);
        });
    }
};
