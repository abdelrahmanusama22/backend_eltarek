<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_can_edit_user_registered_with_email_and_null_phone(): void
    {
        $superAdminRole = Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create([
            'email' => 'superadmin@eltarek.com',
            'phone' => '1902200000',
            'is_admin' => true,
        ]);
        $admin->assignRole($superAdminRole);
        $this->actingAs($admin);

        // User registered via Google or Email (phone is null)
        $googleUser = User::create([
            'name' => 'Karim Mostafa',
            'email' => 'karim.mostafa@gmail.com',
            'phone' => null,
            'google_id' => '1029384756',
            'is_admin' => false,
            'is_active' => true,
        ]);

        Livewire::test(EditUser::class, ['record' => $googleUser->id])
            ->assertSuccessful()
            ->assertSet('data.phone', null)
            ->assertSet('data.email', 'karim.mostafa@gmail.com')
            ->fillForm([
                'name' => 'Karim Mostafa Updated',
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $googleUser->refresh();
        $this->assertSame('Karim Mostafa Updated', $googleUser->name);
        $this->assertNull($googleUser->phone);
        $this->assertFalse($googleUser->is_active);
    }

    public function test_admin_can_edit_user_registered_with_phone_and_null_email(): void
    {
        $superAdminRole = Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create([
            'email' => 'superadmin@eltarek.com',
            'phone' => '1902200001',
            'is_admin' => true,
        ]);
        $admin->assignRole($superAdminRole);
        $this->actingAs($admin);

        // User registered via Phone OTP (email is null)
        $phoneUser = User::create([
            'name' => 'Ahmed Ali',
            'phone' => '+201012345678',
            'email' => null,
            'is_admin' => false,
            'is_active' => true,
        ]);

        Livewire::test(EditUser::class, ['record' => $phoneUser->id])
            ->assertSuccessful()
            ->assertSet('data.phone', '+201012345678')
            ->fillForm([
                'name' => 'Ahmed Ali Hassan',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $phoneUser->refresh();
        $this->assertSame('Ahmed Ali Hassan', $phoneUser->name);
        $this->assertNull($phoneUser->email);
    }

    public function test_creating_admin_requires_email_and_password(): void
    {
        $superAdminRole = Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create([
            'email' => 'superadmin@eltarek.com',
            'phone' => '1902200002',
            'is_admin' => true,
        ]);
        $admin->assignRole($superAdminRole);
        $this->actingAs($admin);

        // Creating admin with is_admin = true
        Livewire::test(CreateUser::class)
            ->assertSuccessful()
            ->fillForm([
                'name' => 'New Admin',
                'is_admin' => true,
                'email' => 'newadmin@eltarek.com',
                'password' => 'SecurePass123!',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $newAdmin = User::where('email', 'newadmin@eltarek.com')->first();
        $this->assertNotNull($newAdmin);
        $this->assertTrue($newAdmin->is_admin);
        $this->assertNull($newAdmin->phone);
    }
}
