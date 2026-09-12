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
use Illuminate\Support\Facades\Cache;

/**
 * One-shot catalog payload the mobile app hydrates from on launch. Works for
 * guests; when authenticated the personal sections (garage) are included too.
 */
class BootstrapController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $payload = Cache::remember('api:v1:bootstrap:public:v2', now()->addMinutes(5), function (): array {
            $fleetTrimIds = Trim::where('active', true)
                ->where('in_test_drive_fleet', true)
                ->orderBy('fleet_sort')
                ->pluck('id')
                ->values()
                ->all();

            return [
                'settings' => [
                    'compare_max' => (int) AppSetting::get('compare_max', 3),
                    'support_phone' => (string) AppSetting::get('support_phone', '19022'),
                    'support_whatsapp' => (string) AppSetting::get('support_whatsapp', '+201000000000'),
                    'finance' => AppSetting::get('finance', [
                        'interest_rate' => 15.0,
                        'min_down_payment_pct' => 20.0,
                        'admin_fee_pct' => 1.5,
                        'max_tenure_years' => 7,
                    ]),
                    'smart_matches' => AppSetting::get('smart_matches', []),
                    'budget_pick_trim_ids' => AppSetting::get('budget_pick_trim_ids', []),
                    'budget_section' => AppSetting::get('budget_section'),
                    'financing_banner' => AppSetting::get('financing_banner'),
                    'fleet_trim_ids' => $fleetTrimIds,
                ],
                'cities' => City::orderBy('sort')->get()->map->toApi()->values()->all(),
                'brands' => Brand::where('active', true)->orderBy('sort')->get()->map->toApi()->values()->all(),
                // Trims are returned once in the top-level collection below.
                'vehicles' => Vehicle::where('active', true)->orderBy('sort')->get()->map->toApi()->values()->all(),
                'trims' => Trim::where('active', true)->whereHas('vehicle', function ($q) {
                    $q->where('active', true);
                })->get()->map->toApi()->values()->all(),
                'branches' => Branch::where('active', true)->get()->map->toApi()->values()->all(),
            ];
        });

        // Dynamic parts (user-specific or highly volatile)
        $payload['rewards'] = Reward::where('active', true)->get()
            ->map(fn (Reward $r) => $r->toApi($user))->values()->all();
        $payload['slots'] = SlotService::upcoming();
        $payload['garage'] = $user?->garageCars()->get()->map->toApi()->values()->all();

        return $this->ok($payload)->header('Cache-Control', 'private, max-age=60');
    }
}
