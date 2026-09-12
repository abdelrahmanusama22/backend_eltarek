<?php

namespace Tests\Feature;

use App\Filament\Pages\AppSettingsPage;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_can_mount_and_save_app_settings(): void
    {
        $role = Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole($role);

        // Pre-populate some existing settings, including Home Content settings
        AppSetting::put('home_hero_vehicle_ids', [10, 20]);
        AppSetting::put('smart_matches', [['budget_from' => 100000]]);
        AppSetting::put('budget_pick_trim_ids', [5, 6]);
        AppSetting::put('support_phone', '19022');

        Cache::put('api:v1:home:payload', 'cached_home_data');

        $this->actingAs($admin);

        Livewire::test(AppSettingsPage::class)
            ->assertSuccessful()
            ->assertSet('data.support_phone', '19022')
            ->set('data.support_phone', '19999')
            ->set('data.support_whatsapp', '+201111111111')
            ->set('data.finance_interest_rate', 16.5)
            ->set('data.min_down_payment_pct', 25)
            ->set('data.admin_fee_pct', 2.0)
            ->set('data.max_tenure_years', 5)
            ->set('data.banner_title_ar', 'عرض ترويجي جديد')
            ->set('data.banner_title_en', 'New Promo Offer')
            ->call('save')
            ->assertHasNoErrors();

        // Verify values were updated in AppSetting
        $this->assertSame('19999', AppSetting::get('support_phone'));
        $this->assertSame('+201111111111', AppSetting::get('support_whatsapp'));

        $finance = AppSetting::get('finance');
        $this->assertEquals(16.5, $finance['interest_rate']);
        $this->assertEquals(25, $finance['min_down_payment_pct']);
        $this->assertEquals(2.0, $finance['admin_fee_pct']);
        $this->assertEquals(5, $finance['max_tenure_years']);

        $banner = AppSetting::get('financing_banner');
        $this->assertSame('عرض ترويجي جديد', $banner['title_ar']);
        $this->assertSame('New Promo Offer', $banner['title_en']);

        // CRITICAL: Verify Home Content settings were NOT wiped or overwritten
        $this->assertSame([10, 20], AppSetting::get('home_hero_vehicle_ids'));
        $this->assertSame([['budget_from' => 100000]], AppSetting::get('smart_matches'));
        $this->assertSame([5, 6], AppSetting::get('budget_pick_trim_ids'));

        // Verify cache keys were invalidated
        $this->assertNull(Cache::get('api:v1:home:payload'));
    }
}
