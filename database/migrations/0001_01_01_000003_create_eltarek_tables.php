<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phone-first auth: extend the default users table.
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->unique()->after('email');
            $table->unsignedTinyInteger('age')->nullable();
            $table->foreignId('city_id')->nullable();
            $table->string('vip_tier')->default('silver');
            $table->unsignedInteger('vip_points')->default(0);
            $table->date('member_since')->nullable();
            $table->boolean('profile_complete')->default(false);
            $table->string('avatar_url')->nullable();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar');
            $table->unsignedInteger('sort')->default(0);
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar');
            $table->string('tagline')->default('');
            $table->string('tagline_ar')->default('');
            $table->string('monogram', 8)->default('?');
            $table->string('tier')->default('standard'); // premium | standard
            $table->string('logo_url')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true);
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('model');
            $table->string('model_ar');
            $table->unsignedSmallInteger('year');
            $table->string('category'); // SUV | Sedan | Electric | Coupe
            $table->unsignedInteger('starting_price_egp');
            $table->string('image_url')->default('');
            $table->string('engine_summary')->default('');
            $table->unsignedInteger('monthly_from_egp')->nullable();
            $table->string('badge')->nullable(); // non-null → home hero banner
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true);
        });

        Schema::create('trims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_ar');
            $table->unsignedInteger('price_egp');
            $table->unsignedInteger('original_price_egp')->nullable();
            $table->boolean('is_most_popular')->default(false);
            $table->string('subtitle')->default('');
            $table->boolean('has_360_view')->default(false);
            $table->string('view_360_url')->nullable();
            $table->unsignedBigInteger('suggested_comparison_trim_id')->nullable();
            $table->json('highlights');   // [{icon,label,label_ar}]
            $table->json('specs');        // {tech:[{label,label_ar,value}],...}
            $table->json('metrics');      // {hp:{display,score},...}
            $table->json('gallery');      // [url,...]
            $table->boolean('in_test_drive_fleet')->default(false);
            $table->unsignedInteger('fleet_sort')->default(0);
            $table->boolean('active')->default(true);
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar');
            $table->string('address');
            $table->string('address_ar');
            $table->string('phone');
            $table->string('hours');
            $table->string('hours_ar');
            $table->boolean('is_open')->default(true);
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->json('services')->nullable();
            $table->boolean('active')->default(true);
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar');
            $table->string('description');
            $table->string('description_ar');
            $table->unsignedInteger('points_cost');
            $table->boolean('active')->default(true);
        });

        Schema::create('redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained();
            $table->string('code');
            $table->unsignedInteger('points_spent');
            $table->date('valid_until');
            $table->timestamps();
        });

        Schema::create('garage_cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tracking_code');
            $table->string('name');
            $table->string('image_url')->default('');
            $table->boolean('warranty_active')->default(true);
            $table->date('warranty_expires_at')->nullable();
            $table->date('next_service_at')->nullable();
            $table->boolean('vip_service')->default(false);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trim_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'trim_id']);
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trim_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->date('date')->nullable();
            $table->string('day_label')->default('');
            $table->string('day_label_ar')->default('');
            $table->string('time');
            $table->string('status')->default('confirmed'); // confirmed | completed | cancelled
            $table->string('reference')->unique();
            $table->timestamps();
        });

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->index();
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('general');
            $table->string('title');
            $table->string('title_ar')->default('');
            $table->string('body')->default('');
            $table->string('body_ar')->default('');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        // Free-form app configuration: finance rates, home sections, fleet…
        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('garage_cars');
        Schema::dropIfExists('redemptions');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('trims');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('cities');
    }
};
