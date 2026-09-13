<?php

namespace Tests\Feature;

use App\Filament\Resources\Branches\Pages\CreateBranch;
use App\Models\Branch;
use App\Models\City;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_super_admin_can_upload_branch_image_and_save_weekly_schedule(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->assignRole(Role::findOrCreate('super_admin', 'web'));
        $city = City::query()->create(['name' => 'Cairo', 'name_ar' => 'القاهرة']);
        $schedule = [
            ['day' => 'sat', 'open' => '09:00', 'close' => '22:00', 'closed' => false],
            ['day' => 'sun', 'open' => '09:00', 'close' => '22:00', 'closed' => false],
            ['day' => 'mon', 'open' => '09:00', 'close' => '22:00', 'closed' => false],
            ['day' => 'tue', 'open' => '09:00', 'close' => '22:00', 'closed' => false],
            ['day' => 'wed', 'open' => '09:00', 'close' => '22:00', 'closed' => false],
            ['day' => 'thu', 'open' => '09:00', 'close' => '22:00', 'closed' => false],
            ['day' => 'fri', 'open' => null, 'close' => null, 'closed' => true],
        ];
        $this->actingAs($admin);

        Livewire::test(CreateBranch::class)
            ->assertSuccessful()
            ->fillForm([
                'image' => UploadedFile::fake()->image('branch.webp'),
                'name' => 'Cairo Main',
                'name_ar' => 'فرع القاهرة',
                'city_id' => $city->id,
                'phone' => '19022',
                'address' => 'Nasr City',
                'address_ar' => 'مدينة نصر',
                'opening_hours' => $schedule,
                'services' => ['Showroom', 'Test Drive'],
                'active' => true,
                'lat' => 30.0444,
                'lng' => 31.2357,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $branch = Branch::query()->where('name', 'Cairo Main')->firstOrFail();
        $this->assertNotNull($branch->image);
        Storage::disk('public')->assertExists($branch->image);
        $this->assertCount(7, $branch->opening_hours);
        $this->assertSame('Closed', collect($branch->opening_hours)->firstWhere('day', 'fri')['closed'] ? 'Closed' : 'Open');
    }
}
