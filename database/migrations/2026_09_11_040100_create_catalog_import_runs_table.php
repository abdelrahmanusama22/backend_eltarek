<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_import_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_name');
            $table->string('status')->default('queued')->index();
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('rejected_rows')->default(0);
            $table->unsignedInteger('new_vehicles')->default(0);
            $table->unsignedInteger('new_trims')->default(0);
            $table->json('errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_import_runs');
    }
};
