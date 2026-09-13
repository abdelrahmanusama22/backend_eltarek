<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->unique('name', 'brands_name_unique');
        });
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->unique(['brand_id', 'model', 'year'], 'vehicles_catalog_identity_unique');
        });
        Schema::table('trims', function (Blueprint $table): void {
            $table->unique(['vehicle_id', 'name'], 'trims_catalog_identity_unique');
            $table->index('legacy_car_id', 'trims_legacy_car_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('trims', function (Blueprint $table): void {
            $table->dropIndex('trims_legacy_car_id_index');
            $table->dropUnique('trims_catalog_identity_unique');
        });
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropUnique('vehicles_catalog_identity_unique');
        });
        Schema::table('brands', function (Blueprint $table): void {
            $table->dropUnique('brands_name_unique');
        });
    }
};
