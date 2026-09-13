<?php

namespace Tests\Feature;

use App\Filament\Pages\HomeContentPage;
use App\Models\AppSetting;
use App\Models\City;
use App\Models\User;
use App\Support\CatalogEvents;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HomeContentPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_super_admin_can_save_home_content_and_invalidate_catalog_version(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->assignRole(Role::findOrCreate('super_admin', 'web'));
        $this->actingAs($admin);
        $before = CatalogEvents::version();

        Livewire::test(HomeContentPage::class)
            ->assertSuccessful()
            ->set('data.hero_vehicle_ids', [])
            ->set('data.smart_matches', [])
            ->set('data.budget_trim_ids', [])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([], AppSetting::get('home_hero_vehicle_ids'));
        $this->assertNotSame($before, CatalogEvents::version());
    }

    public function test_admin_without_page_permission_cannot_open_home_content(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(HomeContentPage::getUrl(panel: 'admin'))
            ->assertForbidden();
    }

    public function test_city_changes_invalidate_catalog_version(): void
    {
        $before = CatalogEvents::version();
        City::query()->create(['name' => 'Suez', 'name_ar' => 'السويس']);

        $this->assertNotSame($before, CatalogEvents::version());
    }
}
