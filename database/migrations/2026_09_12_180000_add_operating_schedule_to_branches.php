<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->json('opening_hours')->nullable()->after('hours_ar');
            $table->string('timezone')->default('Africa/Cairo')->after('opening_hours');
        });

        $cityAliases = ['Alexandria' => 'Alexandria', 'Cairo' => 'Cairo', 'Giza' => 'Giza'];
        foreach ($cityAliases as $prefix => $cityName) {
            $cityId = DB::table('cities')->where('name', $cityName)->value('id');
            if ($cityId) DB::table('branches')->whereNull('city_id')->where('name', 'like', $prefix.'%')->update(['city_id' => $cityId]);
        }
    }

    public function down(): void
    {
        Schema::table('branches', fn (Blueprint $table) => $table->dropColumn(['opening_hours', 'timezone']));
    }
};
