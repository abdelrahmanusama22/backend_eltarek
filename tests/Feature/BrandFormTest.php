<?php

namespace Tests\Feature;

use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Models\Brand;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BrandFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_can_create_brand_with_auto_monogram_and_logo(): void
    {
        Storage::fake('public');

        $superAdminRole = Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create([
            'email' => 'superadmin@eltarek.com',
            'is_admin' => true,
        ]);
        $admin->assignRole($superAdminRole);
        $this->actingAs($admin);

        $logo = UploadedFile::fake()->image('lexus_logo.png');

        Livewire::test(CreateBrand::class)
            ->assertSuccessful()
            ->fillForm([
                'name' => 'Lexus',
                'name_ar' => 'لكزس',
                'tier' => 'premium',
                'logo_url' => $logo,
                'tagline' => 'Experience Amazing',
                'tagline_ar' => 'تجربة مذهلة',
                'sort' => 10,
                'active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $brand = Brand::where('name', 'Lexus')->first();
        $this->assertNotNull($brand);
        // Monogram should NOT be '?'
        $this->assertNotSame('?', $brand->monogram);
        $this->assertSame('LE', $brand->monogram);
        $this->assertNotNull($brand->logo_url);

        // Check toApi format
        $apiData = $brand->toApi();
        $this->assertSame('LE', $apiData['monogram']);
        $this->assertStringContainsString('/media/brands/', $apiData['logo_url']);
    }

    public function test_brand_model_cleans_legacy_question_mark_monogram(): void
    {
        $brand = Brand::create([
            'name' => 'Alfa Romeo',
            'name_ar' => 'ألفا روميو',
            'monogram' => '?',
            'tier' => 'premium',
            'active' => true,
        ]);

        // Accessor must dynamically replace '?' with uppercase initial letters
        $this->assertSame('AL', $brand->monogram);
        $this->assertSame('AL', $brand->toApi()['monogram']);
    }
}
