<?php

namespace Database\Seeders;

use App\Models\GarageCar;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(CatalogSeeder::class);

        // Demo VIP member matching the app's demo profile.
        $demo = User::updateOrCreate(['phone' => '+201001234567'], [
            'name' => 'Ahmed Hassan',
            'email' => 'ahmed.hassan@eltarek.com',
            'age' => 30,
            'city_id' => 1,
            'vip_points' => 12450,
            'member_since' => '2022-01-15',
            'profile_complete' => true,
        ]);

        GarageCar::updateOrCreate(
            ['user_id' => $demo->id, 'tracking_code' => 'TRK-2024'],
            [
                'name' => 'Audi RS e-tron GT',
                'image_url' => 'https://images.unsplash.com/photo-1614026480209-cd9934144671?auto=format&fit=crop&w=800&q=80',
                'warranty_active' => true,
                'warranty_expires_at' => '2027-06-01',
                'next_service_at' => now()->addDays(15)->toDateString(),
                'vip_service' => true,
            ],
        );
        GarageCar::updateOrCreate(
            ['user_id' => $demo->id, 'tracking_code' => 'G-5050'],
            [
                'name' => 'Mercedes G-Class',
                'image_url' => 'https://images.unsplash.com/photo-1520031441872-265e4ff70366?auto=format&fit=crop&w=800&q=80',
                'warranty_active' => true,
                'warranty_expires_at' => '2026-11-20',
                'vip_service' => false,
            ],
        );

        $demo->favorites()->syncWithoutDetaching([510, 511]);

        UserNotification::firstOrCreate(
            ['user_id' => $demo->id, 'type' => 'new_arrival'],
            [
                'title' => 'New Arrival: Mercedes-Benz EQS',
                'title_ar' => 'وصل حديثاً: مرسيدس EQS',
                'body' => 'The future of electric luxury has landed in our showroom.',
                'body_ar' => 'مستقبل الفخامة الكهربائية وصل إلى معرضنا.',
            ],
        );
    }
}
