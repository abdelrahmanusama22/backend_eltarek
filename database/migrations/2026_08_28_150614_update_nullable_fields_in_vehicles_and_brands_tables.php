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
        // Allow Arabic names to be empty — will be filled manually after import
        Schema::table('brands', function (Blueprint $table) {
            $table->string('name_ar')->default('')->change();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('model_ar')->default('')->change();
            $table->string('category')->default('Other')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles_and_brands_tables', function (Blueprint $table) {
            //
        });
    }
};
