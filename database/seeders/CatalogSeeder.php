<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\City;
use App\Models\Reward;
use App\Models\Trim;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

/**
 * Seeds the full catalog — a direct port of the Flutter app's bundled mock
 * data, so the hydrated app looks identical on first deploy. Everything here
 * is meant to be managed from an admin panel later.
 */
class CatalogSeeder extends Seeder
{
    private const IMG = 'https://images.unsplash.com';

    public function run(): void
    {
        $this->seedCities();
        $this->seedBrands();
        $this->seedVehiclesAndTrims();
        $this->seedBranches();
        $this->seedRewards();
        $this->seedSettings();
    }

    private function seedCities(): void
    {
        foreach ([
            [1, 'Cairo', 'القاهرة'],
            [2, 'Alexandria', 'الإسكندرية'],
            [3, 'Giza', 'الجيزة'],
            [4, 'Mansoura', 'المنصورة'],
            [5, 'Tanta', 'طنطا'],
        ] as $i => [$id, $name, $nameAr]) {
            City::updateOrCreate(['id' => $id], [
                'name' => $name, 'name_ar' => $nameAr, 'sort' => $i,
            ]);
        }
    }

    private function seedBrands(): void
    {
        foreach ([
            [1, 'Mercedes', 'مرسيدس', 'Discover Mercedes Excellence', 'اكتشف تميّز مرسيدس', '★', 'premium'],
            [2, 'BMW', 'بي إم دبليو', 'Sheer Driving Pleasure', 'متعة قيادة خالصة', 'BMW', 'premium'],
            [3, 'Audi', 'أودي', 'Vorsprung durch Technik', 'التقدم عبر التقنية', 'OOOO', 'premium'],
            [4, 'Tesla', 'تسلا', 'Accelerating the Future', 'نُسرّع المستقبل', 'T', 'premium'],
            [5, 'Porsche', 'بورشه', 'There Is No Substitute', 'لا بديل لها', 'P', 'premium'],
            [6, 'Toyota', 'تويوتا', "Let's Go Places", 'لننطلق بعيداً', 'TY', 'standard'],
            [7, 'Kia', 'كيا', 'Movement That Inspires', 'حركة تُلهم', 'KIA', 'standard'],
            [8, 'Hyundai', 'هيونداي', 'Discover Hyundai Excellence', 'اكتشف تميّز هيونداي', 'H', 'standard'],
        ] as $i => [$id, $name, $nameAr, $tag, $tagAr, $mono, $tier]) {
            Brand::updateOrCreate(['id' => $id], [
                'name' => $name, 'name_ar' => $nameAr,
                'tagline' => $tag, 'tagline_ar' => $tagAr,
                'monogram' => $mono, 'tier' => $tier, 'sort' => $i,
            ]);
        }
    }

    private static function img(string $id): string
    {
        return self::IMG."/$id?auto=format&fit=crop&w=1200&q=80";
    }

    private static function gallery(string $heroId): array
    {
        return [
            self::img($heroId),
            self::img('photo-1503736334956-4c8f8e92946d'),
            self::img('photo-1489824904134-891ab64532f1'),
            self::img('photo-1449965408869-eaa3f722e40d'),
        ];
    }

    private static function highlight(string $icon, string $label, string $labelAr): array
    {
        return ['icon' => $icon, 'label' => $label, 'label_ar' => $labelAr];
    }

    private static function spec(string $label, string $labelAr, string $value): array
    {
        return ['label' => $label, 'label_ar' => $labelAr, 'value' => $value];
    }

    private static function metric(string $display, ?float $score): array
    {
        return ['display' => $display, 'score' => $score];
    }

    private function seedVehiclesAndTrims(): void
    {
        $vehicles = [
            [101, 1, 'Mercedes-Benz EQS', 'مرسيدس-بنز EQS', 2024, 'Electric', 9500000, 'photo-1622194993926-3ff09d9ffb75', 'Electric • 770km Range', null, 'New Arrival'],
            [102, 1, 'Mercedes-Benz C200', 'مرسيدس-بنز C200', 2024, 'Sedan', 3850000, 'photo-1542362567-b07e54358753', '1.5L Turbo • 204hp', 44900, null],
            [103, 1, 'Mercedes-Benz S 500', 'مرسيدس-بنز S 500', 2024, 'Sedan', 12900000, 'photo-1563720223185-11003d516935', 'S 500 4MATIC • 435hp', null, 'Luxury'],
            [104, 1, 'Mercedes-Benz GLE 450', 'مرسيدس-بنز GLE 450', 2024, 'SUV', 7100000, 'photo-1606016159991-dfe4f2746ad5', '3.0L I6 Turbo • 381hp', null, null],
            [110, 2, 'BMW X5 M50i', 'بي إم دبليو X5 M50i', 2024, 'SUV', 6850000, 'photo-1556189250-72ba954cfc2b', 'V8 TwinPower Turbo • AWD', null, 'Performance'],
            [111, 2, 'BMW 320i M-Sport', 'بي إم دبليو 320i M-سبورت', 2024, 'Sedan', 3700000, 'photo-1555215695-3004980ad54e', '2.0L Turbo • 184hp', 42800, null],
            [112, 2, 'BMW i7 xDrive60', 'بي إم دبليو i7 xDrive60', 2024, 'Electric', 9800000, 'photo-1617531653332-bd46c24f2068', 'Electric • 625km Range', null, 'Luxury'],
            [120, 3, 'Audi e-tron GT', 'أودي e-tron GT', 2024, 'Electric', 3900000, 'photo-1614026480209-cd9934144671', 'EV • AWD • 522hp', null, null],
            [130, 4, 'Tesla Model S Plaid', 'تسلا موديل S بلياد', 2024, 'Electric', 4250000, 'photo-1560958089-b8a1929cea89', 'EV • AWD • 1020hp', null, null],
            [140, 5, 'Porsche Panamera', 'بورشه باناميرا', 2024, 'Sedan', 9200000, 'photo-1503376780353-7e6692767b70', '2.9L V6 Turbo • 353hp', null, 'Luxury'],
            [150, 6, 'Toyota Camry Grande', 'تويوتا كامري جراند', 2025, 'Sedan', 1850000, 'photo-1621007947382-bb3c3994e3fb', '2.5L • 8-Speed AT', 28400, null],
            [160, 7, 'Kia Sportage GT-Line', 'كيا سبورتاج GT-لاين', 2025, 'SUV', 1780000, 'photo-1558618666-fcd25c85cd64', '1.6L Turbo • DCT', 26200, null],
            [201, 8, 'Tucson', 'توسان', 2026, 'SUV', 1750000, 'photo-1633695400996-6b7d0c1b3f1e', '1.6L Turbo • DCT', 24100, null],
            [202, 8, 'Elantra CN7', 'إلنترا CN7', 2025, 'Sedan', 1350000, 'photo-1502877338535-766e1452684a', '1.6L • CVT', 19800, null],
            [203, 8, 'IONIQ 5', 'أيونيك 5', 2025, 'Electric', 2900000, 'photo-1617788138017-80ad40651399', 'EV • 481km Range', null, null],
            [204, 8, 'Creta', 'كريتا', 2025, 'SUV', 1200000, 'photo-1568605117036-5fe5e7bab0b7', '1.5L • IVT', 17500, null],
        ];
        foreach ($vehicles as $i => [$id, $brandId, $model, $modelAr, $year, $cat, $price, $img, $engine, $monthly, $badge]) {
            Vehicle::updateOrCreate(['id' => $id], [
                'brand_id' => $brandId, 'model' => $model, 'model_ar' => $modelAr,
                'year' => $year, 'category' => $cat, 'starting_price_egp' => $price,
                'image_url' => self::img($img), 'engine_summary' => $engine,
                'monthly_from_egp' => $monthly, 'badge' => $badge, 'sort' => $i,
            ]);
        }

        $this->seedTrims();
    }

    private function seedTrims(): void
    {
        $h = self::highlight(...);
        $s = self::spec(...);
        $m = self::metric(...);

        $tucsonSpecs = fn (string $hp, string $accel, string $top, string $airbags, string $comfort) => [
            'tech' => [
                $s('Horsepower', 'القوة', $hp),
                $s('Acceleration', 'التسارع', $accel),
                $s('Top Speed', 'السرعة القصوى', $top),
            ],
            'safety' => [
                $s('Airbags', 'الوسائد الهوائية', $airbags),
                $s('ABS + EBD', 'نظام الفرامل', 'Yes'),
            ],
            'int' => [
                $s('Comfort', 'الرفاهية', $comfort),
                $s('Display', 'الشاشة', '12.3"'),
            ],
            'ext' => [$s('LED Lights', 'إضاءة LED', 'Yes')],
        ];

        $trims = [
            // ------ Hyundai Tucson
            [301, 201, 'Baseline', 'الفئة الأولى', 1750000, null, false, '1.6L MPI • FWD', false, 460, [
                $h('engine', '1600 CC', '١٦٠٠ سي سي'),
                $h('transmission', '6-SPEED AT', '٦ سرعات أوتوماتيك'),
                $h('safety', '2 AIRBAGS', 'وسادتان هوائيتان'),
            ], $tucsonSpecs('123 hp', '11.8s (0-100)', '185 km/h', '2', 'Manual AC'), [
                'hp' => $m('123 hp', 123), 'accel' => $m('11.8s', -11.8), 'top' => $m('185 km/h', 185),
                'engine' => $m('1.6L MPI', null), 'fuel' => $m('7.2 L/100km', -7.2),
                'airbags' => $m('2', 2), 'sunroof' => $m('—', 0),
            ], 'photo-1633695400996-6b7d0c1b3f1e', false],
            [302, 201, 'Midline', 'الفئة الوسطى', 1890000, null, true, '1.6L Turbo • DCT', false, 460, [
                $h('engine', '1600 CC TURBO', '١٦٠٠ سي سي تيربو'),
                $h('transmission', '7-SPEED DCT', '٧ سرعات DCT'),
                $h('safety', '4 AIRBAGS', '٤ وسائد هوائية'),
            ], $tucsonSpecs('180 hp', '9.1s (0-100)', '201 km/h', '4', 'Dual-Zone AC'), [
                'hp' => $m('180 hp', 180), 'accel' => $m('9.1s', -9.1), 'top' => $m('201 km/h', 201),
                'engine' => $m('1.6L Turbo', null), 'fuel' => $m('7.6 L/100km', -7.6),
                'airbags' => $m('4', 4), 'sunroof' => $m('Electric', 1),
            ], 'photo-1633695400996-6b7d0c1b3f1e', false],
            [303, 201, 'Highline', 'الفئة العليا', 2050000, null, false, '1.6L Turbo • DCT • Panorama', false, 460, [
                $h('engine', '1600 CC TURBO', '١٦٠٠ سي سي تيربو'),
                $h('transmission', '7-SPEED DCT', '٧ سرعات DCT'),
                $h('safety', '6 AIRBAGS', '٦ وسائد هوائية'),
            ], $tucsonSpecs('180 hp', '9.1s (0-100)', '201 km/h', '6', 'Panoramic Roof'), [
                'hp' => $m('180 hp', 180), 'accel' => $m('9.1s', -9.1), 'top' => $m('201 km/h', 201),
                'engine' => $m('1.6L Turbo', null), 'fuel' => $m('7.6 L/100km', -7.6),
                'airbags' => $m('6', 6), 'sunroof' => $m('Panoramic', 2),
            ], 'photo-1633695400996-6b7d0c1b3f1e', false],

            // ------ BMW X5 M50i
            [310, 110, 'M50i', 'M50i', 6850000, 7200000, false, 'V8 TwinPower Turbo • AWD', true, 450, [
                $h('engine', '4.4L V8', '٤.٤ لتر V8'),
                $h('transmission', '8-SPEED AT', '٨ سرعات أوتوماتيك'),
                $h('safety', '8 AIRBAGS', '٨ وسائد هوائية'),
            ], [
                'tech' => [
                    $s('Engine', 'المحرك', '4.4L V8'),
                    $s('Horsepower', 'القوة', '523 hp'),
                    $s('Acceleration', 'التسارع', '4.1s (0-100)'),
                    $s('Top Speed', 'السرعة القصوى', '250 km/h'),
                ],
                'safety' => [
                    $s('Airbags', 'الوسائد الهوائية', '8'),
                    $s('ABS + DSC', 'نظام الثبات', 'Yes'),
                    $s('Parking Sensors', 'حساسات الركن', 'Front & Rear'),
                    $s('Lane Assist', 'مساعد الحارة', 'Yes'),
                ],
                'int' => [
                    $s('Sunroof', 'فتحة السقف', 'Panoramic'),
                    $s('Seats', 'المقاعد', 'Merino Leather'),
                    $s('Display', 'الشاشة', '14.9" Curved'),
                ],
                'ext' => [
                    $s('Wheels', 'الجنوط', '21" M-Sport'),
                    $s('Color Options', 'خيارات الألوان', '8'),
                    $s('LED Laserlight', 'إضاءة ليزر', 'Yes'),
                ],
            ], [
                'hp' => $m('523 hp', 523), 'accel' => $m('4.1s', -4.1), 'top' => $m('250 km/h', 250),
                'engine' => $m('4.4L V8', null), 'fuel' => $m('11.5 L/100km', -11.5),
                'airbags' => $m('8', 8), 'sunroof' => $m('Panoramic', 2),
            ], 'photo-1556189250-72ba954cfc2b', true],

            // ------ Compare pair
            [401, 102, 'C200 AMG Line', 'C200 خط AMG', 3850000, null, false, '1.5L Turbo • RWD', false, 402, [
                $h('engine', '1.5L TURBO', '١.٥ لتر تيربو'),
                $h('transmission', '9G-TRONIC', '٩ سرعات'),
                $h('safety', '9 AIRBAGS', '٩ وسائد هوائية'),
            ], [
                'tech' => [
                    $s('Engine', 'المحرك', '1.5L Turbo'),
                    $s('Horsepower', 'القوة', '204 hp'),
                    $s('Acceleration', 'التسارع', '7.3s (0-100)'),
                    $s('Top Speed', 'السرعة القصوى', '245 km/h'),
                ],
                'safety' => [
                    $s('Airbags', 'الوسائد الهوائية', '9'),
                    $s('Blind Spot Assist', 'مراقبة النقطة العمياء', 'Yes'),
                ],
                'int' => [
                    $s('Sunroof', 'فتحة السقف', 'Panoramic'),
                    $s('Ambient Light', 'إضاءة محيطية', '64 Colors'),
                ],
                'ext' => [$s('Wheels', 'الجنوط', '18" AMG')],
            ], [
                'hp' => $m('204 hp', 204), 'accel' => $m('7.3s', -7.3), 'top' => $m('245 km/h', 245),
                'engine' => $m('1.5L Turbo', null), 'fuel' => $m('6.5 L/100km', -6.5),
                'airbags' => $m('9', 9), 'sunroof' => $m('Panoramic', 2),
            ], 'photo-1542362567-b07e54358753', false],
            [402, 111, '320i M-Sport', '320i M-سبورت', 3700000, null, false, '2.0L Turbo • RWD', false, 401, [
                $h('engine', '2.0L TURBO', '٢ لتر تيربو'),
                $h('transmission', '8-SPEED AT', '٨ سرعات أوتوماتيك'),
                $h('safety', '7 AIRBAGS', '٧ وسائد هوائية'),
            ], [
                'tech' => [
                    $s('Engine', 'المحرك', '2.0L Turbo'),
                    $s('Horsepower', 'القوة', '184 hp'),
                    $s('Acceleration', 'التسارع', '7.6s (0-100)'),
                    $s('Top Speed', 'السرعة القصوى', '235 km/h'),
                ],
                'safety' => [
                    $s('Airbags', 'الوسائد الهوائية', '7'),
                    $s('Parking Assistant', 'مساعد الركن', 'Yes'),
                ],
                'int' => [
                    $s('Sunroof', 'فتحة السقف', 'Electric'),
                    $s('Curved Display', 'شاشة منحنية', 'Yes'),
                ],
                'ext' => [$s('Wheels', 'الجنوط', '18" M')],
            ], [
                'hp' => $m('184 hp', 184), 'accel' => $m('7.6s', -7.6), 'top' => $m('235 km/h', 235),
                'engine' => $m('2.0L Turbo', null), 'fuel' => $m('7.1 L/100km', -7.1),
                'airbags' => $m('7', 7), 'sunroof' => $m('Electric', 1),
            ], 'photo-1555215695-3004980ad54e', false],

            // ------ Mercedes GLE 450
            [450, 104, 'GLE 450 4MATIC', 'GLE 450 4MATIC', 7100000, null, false, '3.0L I6 Turbo • AWD', false, 310, [
                $h('engine', '3.0L I6 TURBO', '٣ لتر ٦ سلندر'),
                $h('transmission', '9G-TRONIC', '٩ سرعات'),
                $h('safety', '9 AIRBAGS', '٩ وسائد هوائية'),
            ], [
                'tech' => [
                    $s('Engine', 'المحرك', '3.0L I6 Turbo'),
                    $s('Horsepower', 'القوة', '381 hp'),
                    $s('Acceleration', 'التسارع', '5.7s (0-100)'),
                    $s('Top Speed', 'السرعة القصوى', '250 km/h'),
                ],
                'safety' => [
                    $s('Airbags', 'الوسائد الهوائية', '9'),
                    $s('PRE-SAFE®', 'نظام الحماية المسبقة', 'Yes'),
                ],
                'int' => [
                    $s('Sunroof', 'فتحة السقف', 'Panoramic'),
                    $s('MBUX', 'نظام MBUX', 'Dual 12.3"'),
                ],
                'ext' => [$s('Wheels', 'الجنوط', '20" AMG')],
            ], [
                'hp' => $m('381 hp', 381), 'accel' => $m('5.7s', -5.7), 'top' => $m('250 km/h', 250),
                'engine' => $m('3.0L I6 Turbo', null), 'fuel' => $m('9.4 L/100km', -9.4),
                'airbags' => $m('9', 9), 'sunroof' => $m('Panoramic', 2),
            ], 'photo-1606016159991-dfe4f2746ad5', false],

            // ------ Kia Sportage GT-Line
            [460, 160, 'GT-Line', 'GT-لاين', 1780000, null, false, '1.6L Turbo • DCT', false, 302, [
                $h('engine', '1600 CC TURBO', '١٦٠٠ سي سي تيربو'),
                $h('transmission', '7-SPEED DCT', '٧ سرعات DCT'),
                $h('safety', '6 AIRBAGS', '٦ وسائد هوائية'),
            ], [
                'tech' => [
                    $s('Engine', 'المحرك', '1.6L Turbo'),
                    $s('Horsepower', 'القوة', '177 hp'),
                    $s('Acceleration', 'التسارع', '9.4s (0-100)'),
                    $s('Top Speed', 'السرعة القصوى', '195 km/h'),
                ],
                'safety' => [
                    $s('Airbags', 'الوسائد الهوائية', '6'),
                    $s('Highway Assist', 'مساعد الطريق السريع', 'Yes'),
                ],
                'int' => [
                    $s('Sunroof', 'فتحة السقف', 'Panoramic'),
                    $s('Display', 'الشاشة', 'Dual 12.3"'),
                ],
                'ext' => [$s('Wheels', 'الجنوط', '19" Alloy')],
            ], [
                'hp' => $m('177 hp', 177), 'accel' => $m('9.4s', -9.4), 'top' => $m('195 km/h', 195),
                'engine' => $m('1.6L Turbo', null), 'fuel' => $m('7.5 L/100km', -7.5),
                'airbags' => $m('6', 6), 'sunroof' => $m('Panoramic', 2),
            ], 'photo-1558618666-fcd25c85cd64', false],

            // ------ Flagship fleet
            [510, 130, 'Plaid', 'بلياد', 4250000, null, false, 'Tri-Motor AWD • 1020hp', false, 511, [
                $h('engine', 'TRI-MOTOR EV', 'ثلاثي المحركات'),
                $h('transmission', 'SINGLE-SPEED', 'سرعة واحدة'),
                $h('safety', '8 AIRBAGS', '٨ وسائد هوائية'),
            ], [
                'tech' => [
                    $s('Motor', 'المحرك', 'Tri-Motor EV'),
                    $s('Horsepower', 'القوة', '1020 hp'),
                    $s('Acceleration', 'التسارع', '2.1s (0-100)'),
                    $s('Range', 'المدى', '600 km'),
                ],
                'safety' => [$s('Airbags', 'الوسائد الهوائية', '8')],
                'int' => [$s('Display', 'الشاشة', '17" Landscape')],
                'ext' => [$s('Wheels', 'الجنوط', '21" Arachnid')],
            ], [
                'hp' => $m('1020 hp', 1020), 'accel' => $m('2.1s', -2.1), 'top' => $m('322 km/h', 322),
                'engine' => $m('Tri-Motor EV', null), 'fuel' => $m('EV', null),
                'airbags' => $m('8', 8), 'sunroof' => $m('Glass Roof', 2),
            ], 'photo-1560958089-b8a1929cea89', false],
            [511, 120, 'RS Performance', 'RS بيرفورمانس', 3900000, null, false, 'Dual-Motor AWD • 522hp', false, 510, [
                $h('engine', 'DUAL-MOTOR EV', 'ثنائي المحركات'),
                $h('transmission', '2-SPEED', 'سرعتان'),
                $h('safety', '8 AIRBAGS', '٨ وسائد هوائية'),
            ], [
                'tech' => [
                    $s('Motor', 'المحرك', 'Dual-Motor EV'),
                    $s('Horsepower', 'القوة', '522 hp'),
                    $s('Acceleration', 'التسارع', '3.3s (0-100)'),
                    $s('Range', 'المدى', '488 km'),
                ],
                'safety' => [$s('Airbags', 'الوسائد الهوائية', '8')],
                'int' => [$s('Display', 'الشاشة', 'Virtual Cockpit')],
                'ext' => [$s('Wheels', 'الجنوط', '20" Aero')],
            ], [
                'hp' => $m('522 hp', 522), 'accel' => $m('3.3s', -3.3), 'top' => $m('245 km/h', 245),
                'engine' => $m('Dual-Motor EV', null), 'fuel' => $m('EV', null),
                'airbags' => $m('8', 8), 'sunroof' => $m('Panoramic', 2),
            ], 'photo-1614026480209-cd9934144671', true],
            [512, 103, 'S 500 4MATIC', 'S 500 4MATIC', 12900000, null, false, 'Pure Excellence', false, 310, [
                $h('engine', '3.0L I6 + EQ', '٣ لتر + EQ بوست'),
                $h('transmission', '9G-TRONIC', '٩ سرعات'),
                $h('safety', '10 AIRBAGS', '١٠ وسائد هوائية'),
            ], [
                'tech' => [
                    $s('Engine', 'المحرك', '3.0L I6 + EQ Boost'),
                    $s('Horsepower', 'القوة', '435 hp'),
                    $s('Acceleration', 'التسارع', '4.9s (0-100)'),
                    $s('Top Speed', 'السرعة القصوى', '250 km/h'),
                ],
                'safety' => [$s('Airbags', 'الوسائد الهوائية', '10')],
                'int' => [$s('Seats', 'المقاعد', 'Executive Nappa')],
                'ext' => [$s('Digital Light', 'إضاءة رقمية', 'Yes')],
            ], [
                'hp' => $m('435 hp', 435), 'accel' => $m('4.9s', -4.9), 'top' => $m('250 km/h', 250),
                'engine' => $m('3.0L I6 + EQ', null), 'fuel' => $m('8.4 L/100km', -8.4),
                'airbags' => $m('10', 10), 'sunroof' => $m('Panoramic', 2),
            ], 'photo-1563720223185-11003d516935', true],
            [513, 112, 'xDrive60', 'xDrive60', 9800000, null, false, 'Pure Excellence', false, 512, [
                $h('engine', 'DUAL-MOTOR EV', 'ثنائي المحركات'),
                $h('transmission', 'SINGLE-SPEED', 'سرعة واحدة'),
                $h('safety', '8 AIRBAGS', '٨ وسائد هوائية'),
            ], [
                'tech' => [
                    $s('Motor', 'المحرك', 'Dual-Motor EV'),
                    $s('Horsepower', 'القوة', '544 hp'),
                    $s('Acceleration', 'التسارع', '4.7s (0-100)'),
                    $s('Range', 'المدى', '625 km'),
                ],
                'safety' => [$s('Airbags', 'الوسائد الهوائية', '8')],
                'int' => [$s('Theatre Screen', 'شاشة سينمائية', '31.3" 8K')],
                'ext' => [$s('Wheels', 'الجنوط', '21" Aero')],
            ], [
                'hp' => $m('544 hp', 544), 'accel' => $m('4.7s', -4.7), 'top' => $m('240 km/h', 240),
                'engine' => $m('Dual-Motor EV', null), 'fuel' => $m('EV', null),
                'airbags' => $m('8', 8), 'sunroof' => $m('Panoramic', 2),
            ], 'photo-1617531653332-bd46c24f2068', true],

            // ------ Budget picks
            [520, 150, 'Grande', 'جراند', 1850000, null, false, '2.5L • 8-Speed AT', false, 302, [
                $h('engine', '2500 CC', '٢٥٠٠ سي سي'),
                $h('transmission', '8-SPEED AT', '٨ سرعات أوتوماتيك'),
                $h('safety', '7 AIRBAGS', '٧ وسائد هوائية'),
            ], [
                'tech' => [
                    $s('Engine', 'المحرك', '2.5L'),
                    $s('Horsepower', 'القوة', '206 hp'),
                    $s('Acceleration', 'التسارع', '8.3s (0-100)'),
                    $s('Top Speed', 'السرعة القصوى', '210 km/h'),
                ],
                'safety' => [$s('Airbags', 'الوسائد الهوائية', '7')],
                'int' => [$s('Seats', 'المقاعد', 'Leather')],
                'ext' => [$s('Wheels', 'الجنوط', '18" Alloy')],
            ], [
                'hp' => $m('206 hp', 206), 'accel' => $m('8.3s', -8.3), 'top' => $m('210 km/h', 210),
                'engine' => $m('2.5L', null), 'fuel' => $m('7.8 L/100km', -7.8),
                'airbags' => $m('7', 7), 'sunroof' => $m('Electric', 1),
            ], 'photo-1621007947382-bb3c3994e3fb', false],
            [521, 202, 'Smart Plus', 'سمارت بلس', 1350000, null, false, '1.6L • CVT', false, 302, [
                $h('engine', '1600 CC', '١٦٠٠ سي سي'),
                $h('transmission', 'CVT', 'CVT'),
                $h('safety', '4 AIRBAGS', '٤ وسائد هوائية'),
            ], [
                'tech' => [
                    $s('Engine', 'المحرك', '1.6L'),
                    $s('Horsepower', 'القوة', '128 hp'),
                    $s('Acceleration', 'التسارع', '11.6s (0-100)'),
                    $s('Top Speed', 'السرعة القصوى', '190 km/h'),
                ],
                'safety' => [$s('Airbags', 'الوسائد الهوائية', '4')],
                'int' => [$s('Display', 'الشاشة', '10.25"')],
                'ext' => [$s('Wheels', 'الجنوط', '17" Alloy')],
            ], [
                'hp' => $m('128 hp', 128), 'accel' => $m('11.6s', -11.6), 'top' => $m('190 km/h', 190),
                'engine' => $m('1.6L', null), 'fuel' => $m('6.9 L/100km', -6.9),
                'airbags' => $m('4', 4), 'sunroof' => $m('Electric', 1),
            ], 'photo-1502877338535-766e1452684a', false],
            [522, 203, 'Long Range AWD', 'مدى طويل AWD', 2900000, null, false, 'EV • 481km Range', false, 302, [
                $h('engine', 'DUAL-MOTOR EV', 'ثنائي المحركات'),
                $h('transmission', 'SINGLE-SPEED', 'سرعة واحدة'),
                $h('safety', '6 AIRBAGS', '٦ وسائد هوائية'),
            ], [
                'tech' => [
                    $s('Motor', 'المحرك', 'Dual-Motor EV'),
                    $s('Horsepower', 'القوة', '325 hp'),
                    $s('Acceleration', 'التسارع', '5.2s (0-100)'),
                    $s('Range', 'المدى', '481 km'),
                ],
                'safety' => [$s('Airbags', 'الوسائد الهوائية', '6')],
                'int' => [$s('Display', 'الشاشة', 'Dual 12.3"')],
                'ext' => [$s('V2L Power', 'مخرج طاقة', 'Yes')],
            ], [
                'hp' => $m('325 hp', 325), 'accel' => $m('5.2s', -5.2), 'top' => $m('185 km/h', 185),
                'engine' => $m('Dual-Motor EV', null), 'fuel' => $m('EV', null),
                'airbags' => $m('6', 6), 'sunroof' => $m('Vision Roof', 2),
            ], 'photo-1617788138017-80ad40651399', false],
            [523, 204, 'Premium', 'بريميوم', 1200000, null, false, '1.5L • IVT', false, 460, [
                $h('engine', '1500 CC', '١٥٠٠ سي سي'),
                $h('transmission', 'IVT', 'IVT'),
                $h('safety', '6 AIRBAGS', '٦ وسائد هوائية'),
            ], [
                'tech' => [
                    $s('Engine', 'المحرك', '1.5L'),
                    $s('Horsepower', 'القوة', '115 hp'),
                    $s('Acceleration', 'التسارع', '12.4s (0-100)'),
                    $s('Top Speed', 'السرعة القصوى', '175 km/h'),
                ],
                'safety' => [$s('Airbags', 'الوسائد الهوائية', '6')],
                'int' => [$s('Display', 'الشاشة', '10.25"')],
                'ext' => [$s('Wheels', 'الجنوط', '17" Alloy')],
            ], [
                'hp' => $m('115 hp', 115), 'accel' => $m('12.4s', -12.4), 'top' => $m('175 km/h', 175),
                'engine' => $m('1.5L', null), 'fuel' => $m('6.6 L/100km', -6.6),
                'airbags' => $m('6', 6), 'sunroof' => $m('Electric', 1),
            ], 'photo-1568605117036-5fe5e7bab0b7', false],
        ];

        $fleetSort = 0;
        foreach ($trims as [$id, $vehicleId, $name, $nameAr, $price, $original, $popular, $subtitle, $has360, $rivalId, $highlights, $specs, $metrics, $img, $inFleet]) {
            Trim::updateOrCreate(['id' => $id], [
                'vehicle_id' => $vehicleId,
                'name' => $name, 'name_ar' => $nameAr,
                'price_egp' => $price, 'original_price_egp' => $original,
                'is_most_popular' => $popular, 'subtitle' => $subtitle,
                'has_360_view' => $has360,
                'suggested_comparison_trim_id' => $rivalId,
                'highlights' => $highlights, 'specs' => $specs,
                'metrics' => $metrics, 'gallery' => self::gallery($img),
                'in_test_drive_fleet' => $inFleet,
                'fleet_sort' => $inFleet ? $fleetSort++ : 0,
            ]);
        }
    }

    private function seedBranches(): void
    {
        $services = ['Showroom', 'Test Drive', 'Finance Center', 'Service & Maintenance'];
        foreach ([
            [1, 'Alexandria - Desert Road', 'الاسكندرية - الطريق الصحراوي',
                'Km 21 Cairo-Alex Desert Rd, Amreya', 'الكيلو ٢١ طريق القاهرة الإسكندرية الصحراوي، العامرية',
                '19022', '9:00 AM - 10:00 PM', '٩:٠٠ صباحاً - ١٠:٠٠ مساءً', true, 31.0025, 29.7285],
            [2, 'Cairo - El Tagamoa', 'القاهرة - التجمع الخامس',
                '90th North St, Behind Air Force Hospital', 'شارع التسعين الشمالي، خلف مستشفى الجوي',
                '19023', '10:00 AM - 11:00 PM', '١٠:٠٠ صباحاً - ١١:٠٠ مساءً', true, 30.0097, 31.4698],
            [3, 'Giza - Sheikh Zayed', 'الجيزة - الشيخ زايد',
                'Waslet Dahshur Rd, Beside Arkan Plaza', 'طريق وصلة دهشور، بجوار أركان بلازا',
                '19024', '10:00 AM - 10:00 PM', '١٠:٠٠ صباحاً - ١٠:٠٠ مساءً', false, 30.0392, 30.9857],
        ] as [$id, $name, $nameAr, $addr, $addrAr, $phone, $hours, $hoursAr, $open, $lat, $lng]) {
            Branch::updateOrCreate(['id' => $id], [
                'name' => $name, 'name_ar' => $nameAr,
                'address' => $addr, 'address_ar' => $addrAr,
                'phone' => $phone, 'hours' => $hours, 'hours_ar' => $hoursAr,
                'is_open' => $open, 'lat' => $lat, 'lng' => $lng,
                'services' => $services,
            ]);
        }
    }

    private function seedRewards(): void
    {
        foreach ([
            [1, 'Full Detail & Wax', 'تلميع وغسيل شامل',
                'Premium exterior polish and interior detailing.', 'تلميع خارجي فاخر وتنظيف داخلي شامل.', 2500],
            [2, 'Complimentary Oil Service', 'خدمة زيت مجانية',
                'Synthetic oil change with filter replacement.', 'تغيير زيت تخليقي مع استبدال الفلتر.', 5000],
            [3, 'Weekend Test Drive: SUV', 'تجربة نهاية أسبوع: SUV',
                '48-hour trial of any available SUV model.', 'تجربة ٤٨ ساعة لأي سيارة SUV متاحة.', 10000],
        ] as [$id, $name, $nameAr, $desc, $descAr, $cost]) {
            Reward::updateOrCreate(['id' => $id], [
                'name' => $name, 'name_ar' => $nameAr,
                'description' => $desc, 'description_ar' => $descAr,
                'points_cost' => $cost,
            ]);
        }
    }

    private function seedSettings(): void
    {
        AppSetting::put('compare_max', 3);
        AppSetting::put('support_phone', '19022');
        AppSetting::put('finance', [
            'min_down_payment_percent' => 20,
            'max_down_payment_percent' => 50,
            'default_down_payment_percent' => 30,
            'min_duration_months' => 12,
            'max_duration_months' => 84,
            'duration_step_months' => 12,
            'default_duration_months' => 60,
            'interest_rate_percent' => 0,
            'min_eligible_income_ratio' => 0.35,
        ]);
        AppSetting::put('smart_matches', [
            ['trim_id' => 510, 'match_percentage' => 98],
            ['trim_id' => 511, 'match_percentage' => 94],
            ['trim_id' => 522, 'match_percentage' => 91],
        ]);
        AppSetting::put('budget_pick_trim_ids', [520, 460, 302]);
        AppSetting::put('budget_section', [
            'label' => 'Best Under 2,000,000 EGP',
            'label_ar' => 'الأفضل تحت ٢٬٠٠٠٬٠٠٠ ج.م',
            'subtitle' => 'Premium value selected by AI',
            'subtitle_ar' => 'قيمة مميزة مختارة بالذكاء الاصطناعي',
        ]);
        AppSetting::put('financing_banner', [
            'title' => 'Instant Financing',
            'title_ar' => 'تمويل فوري',
            'subtitle' => 'Get pre-approved in 5 minutes',
            'subtitle_ar' => 'احصل على موافقة مبدئية في ٥ دقائق',
            'cta_label' => 'Check Eligibility',
            'cta_label_ar' => 'تحقق من الأهلية',
            'cta_action' => 'financing_eligibility',
        ]);
        // Weekday key (0=Sun … 6=Sat, Carbon dayOfWeek) → available times.
        AppSetting::put('test_drive_times', [
            'default' => ['10:00 AM', '11:30 AM', '1:00 PM', '2:30 PM', '4:30 PM', '7:00 PM'],
            'fri' => ['2:00 PM', '4:00 PM', '6:00 PM'],
            'days_ahead' => 7,
            'min_notice_minutes' => 60,
        ]);
        AppSetting::put('vip_benefits', [
            'silver' => [
                'Priority test drive scheduling',
                'Dedicated relationship manager',
                '1 complimentary car wash per month',
                'Early access to new arrivals',
            ],
            'gold' => [
                'Everything in Silver',
                'Free annual detailing',
                'Airport valet on service days',
                'Exclusive launch event invitations',
            ],
            'platinum' => [
                'Everything in Gold',
                'Home test drives',
                'Free pick-up & delivery for service',
                'Dedicated VIP hotline',
            ],
        ]);
    }
}
