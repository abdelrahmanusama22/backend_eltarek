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
        // Column is created by 2026_08_28_150559_add_import_fields_to_trims_table.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Compatibility no-op: do not remove a column owned by an earlier migration.
    }
};
