<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('garage_cars', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('trim_id')->nullable()->after('vehicle_id')->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique('tracking_code');
        });

        Schema::create('garage_service_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_car_id')->constrained()->cascadeOnDelete();
            $table->string('type', 80);
            $table->date('serviced_at');
            $table->unsignedInteger('odometer_km')->nullable();
            $table->string('service_center')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garage_service_records');
        Schema::table('garage_cars', function (Blueprint $table) {
            $table->dropUnique(['tracking_code']);
            $table->dropConstrainedForeignId('trim_id');
            $table->dropConstrainedForeignId('vehicle_id');
            $table->dropTimestamps();
        });
    }
};
