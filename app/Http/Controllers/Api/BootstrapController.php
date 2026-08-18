<?php

namespace App\Http\Controllers\Api;

use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\City;
use App\Models\Reward;
use App\Models\Trim;
use App\Models\Vehicle;
use App\Services\SlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * One-shot catalog payload the mobile app hydrates from on launch. Works for
 * guests; when authenticated the personal sections (garage) are included too.
 */
class BootstrapController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $fleetTrimIds = Trim::where('active', true)
            ->where('in_test_drive_fleet', true)
            ->orderBy('fleet_sort')
            ->pluck('id');

        return $this->ok([
            'settings' => [
                'compare_max' => AppSetting::get('compare_max', 3),
                'support_phone' => AppSetting::get('support_phone', '19022'),
                'finance' => AppSetting::get('finance'),
                'smart_matches' => AppSetting::get('smart_matches', []),
                'budget_pick_trim_ids' => AppSetting::get('budget_pick_trim_ids', []),
                'budget_section' => AppSetting::get('budget_section'),
                'financing_banner' => AppSetting::get('financing_banner'),
                'fleet_trim_ids' => $fleetTrimIds,
            ],
            'cities' => City::orderBy('sort')->get()->map->toApi(),
            'brands' => Brand::where('active', true)->orderBy('sort')->get()->map->toApi(),
            'vehicles' => Vehicle::where('active', true)->orderBy('sort')->get()->map->toApi(),
            'trims' => Trim::where('active', true)->get()->map->toApi(),
            'branches' => Branch::where('active', true)->get()->map->toApi(),
            'rewards' => Reward::where('active', true)->get()
                ->map(fn (Reward $r) => $r->toApi($user)),
            'slots' => SlotService::upcoming(),
            'garage' => $user?->garageCars()->get()->map->toApi(),
        ]);
    }
}
