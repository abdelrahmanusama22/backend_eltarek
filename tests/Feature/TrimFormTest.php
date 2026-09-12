<?php

namespace Tests\Feature;

use App\Filament\Resources\Trims\Pages\CreateTrim;
use App\Filament\Resources\Trims\Pages\EditTrim;
use App\Models\Brand;
use App\Models\Trim;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrimFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_can_create_and_edit_trim_with_highlights_and_gallery(): void
    {
        Storage::fake('public');

        $role = Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole($role);
        $this->actingAs($admin);

        $brand = Brand::create([
            'name' => 'Toyota',
            'name_ar' => 'تويوتا',
            'tier' => 'standard',
        ]);

        $vehicle = Vehicle::create([
            'brand_id' => $brand->id,
            'model' => 'Corolla',
            'model_ar' => 'كورولا',
            'year' => 2025,
            'category' => 'Sedan',
            'starting_price_egp' => 1200000,
            'image_url' => 'https://images.unsplash.com/photo-test',
            'engine_summary' => '1.6L',
            'active' => true,
        ]);

        // 1. Test Create Trim with highlights and gallery
        $file1 = UploadedFile::fake()->image('trim_front.jpg');
        $file2 = UploadedFile::fake()->image('trim_side.jpg');

        Livewire::test(CreateTrim::class)
            ->assertSuccessful()
            ->fillForm([
                'vehicle_id' => $vehicle->id,
                'name' => 'Highline',
                'name_ar' => 'هاي لاين',
                'price_egp' => 1350000,
                'subtitle' => '1.6L • Automatic',
                'gallery' => [$file1, $file2],
                'highlights' => [
                    [
                        'icon' => 'engine',
                        'label' => '1600 CC',
                        'label_ar' => '١٦٠٠ سي سي',
                    ],
                    [
                        'icon' => 'safety',
                        'label' => '6 Airbags',
                        'label_ar' => '٦ وسائد هوائية',
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $trim = Trim::where('name', 'Highline')->first();
        $this->assertNotNull($trim);
        $this->assertSame(1350000, $trim->price_egp);
        $this->assertCount(2, $trim->highlights);
        $this->assertSame('1600 CC', $trim->highlights[0]['label']);
        $this->assertSame('١٦٠٠ سي سي', $trim->highlights[0]['label_ar']);
        $this->assertCount(2, $trim->gallery);

        // Verify API transformation matches mobile app expectations
        $apiData = $trim->toApi();
        $this->assertCount(2, $apiData['highlights']);
        $this->assertCount(2, $apiData['gallery']);
        $this->assertStringContainsString('/media/', $apiData['gallery'][0]);

        // 2. Test Edit Trim adding a new highlight
        Livewire::test(EditTrim::class, ['record' => $trim->id])
            ->assertSuccessful()
            ->fillForm([
                'highlights' => [
                    [
                        'icon' => 'engine',
                        'label' => '1600 CC',
                        'label_ar' => '١٦٠٠ سي سي',
                    ],
                    [
                        'icon' => 'safety',
                        'label' => '6 Airbags',
                        'label_ar' => '٦ وسائد هوائية',
                    ],
                    [
                        'icon' => 'transmission',
                        'label' => 'CVT Transmission',
                        'label_ar' => 'ناقل حركة CVT',
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $trim->refresh();
        $this->assertCount(3, $trim->highlights);
        $this->assertSame('transmission', $trim->highlights[2]['icon']);
        $this->assertSame('CVT Transmission', $trim->highlights[2]['label']);
        $this->assertSame('ناقل حركة CVT', $trim->highlights[2]['label_ar']);
    }
}
