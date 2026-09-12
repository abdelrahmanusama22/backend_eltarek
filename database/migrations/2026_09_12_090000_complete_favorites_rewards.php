<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (! Schema::hasColumn('rewards', 'stock')) Schema::table('rewards', function (Blueprint $table) {
            $table->unsignedInteger('stock')->nullable()->after('points_cost');
            $table->unsignedInteger('per_user_limit')->nullable()->after('stock');
            $table->unsignedSmallInteger('validity_days')->default(180)->after('per_user_limit');
            $table->timestamps();
        });
        if (! Schema::hasColumn('redemptions', 'status')) Schema::table('redemptions', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('valid_until')->index();
            $table->timestamp('used_at')->nullable()->after('status');
            $table->foreignId('used_by')->nullable()->after('used_at')->constrained('users')->nullOnDelete();
        });
        if (! Schema::hasColumn('bookings', 'points_awarded_at')) Schema::table('bookings', fn (Blueprint $table) => $table->timestamp('points_awarded_at')->nullable());
    }
    public function down(): void {
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn('points_awarded_at'));
        Schema::table('redemptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('used_by'); $table->dropColumn(['status','used_at']);
        });
        Schema::table('rewards', fn (Blueprint $table) => $table->dropColumn(['stock','per_user_limit','validity_days','created_at','updated_at']));
    }
};
