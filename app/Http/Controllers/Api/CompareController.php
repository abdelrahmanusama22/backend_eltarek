<?php

namespace App\Http\Controllers\Api;

use App\Models\AppSetting;
use App\Models\Trim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompareController extends ApiController
{
    private const ATTRIBUTES = [
        // metric key => [group, label, label_ar]
        'hp' => ['performance', 'Horsepower', 'قوة الحصان'],
        'accel' => ['performance', '0-100 km/h', 'التسارع 0-100 كم/س'],
        'top' => ['performance', 'Top Speed', 'السرعة القصوى'],
        'engine' => ['engine_specs', 'Engine', 'المحرك'],
        'fuel' => ['engine_specs', 'Fuel Economy', 'استهلاك الوقود'],
        'airbags' => ['safety_tech', 'Airbags', 'الوسائد الهوائية'],
        'sunroof' => ['safety_tech', 'Sunroof', 'فتحة السقف'],
    ];

    public function selection(Request $request): JsonResponse
    {
        return $this->ok([
            'trim_ids' => $this->currentList($request)->values(),
            'compare_max' => (int) AppSetting::get('compare_max', 3),
        ]);
    }

    /** GET /compare?trim_ids=401,402 */
    public function results(Request $request): JsonResponse
    {
        $ids = collect(explode(',', $request->string('trim_ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        $max = (int) AppSetting::get('compare_max', 3);
        if ($ids->count() < 2) {
            return $this->fail('Please select at least 2 vehicles to compare.', 422);
        }
        if ($ids->count() > $max) {
            return $this->fail("You can compare a maximum of {$max} vehicles.", 422);
        }

        $trims = Trim::with('vehicle')
            ->where('active', true)
            ->whereHas('vehicle', fn ($query) => $query->where('active', true))
            ->findMany($ids)
            ->sortBy(fn (Trim $t) => $ids->search($t->id))
            ->values();
        if ($trims->count() !== $ids->count()) {
            return $this->fail('One or more trims were not found.', 404);
        }

        $groups = ['performance' => [], 'engine_specs' => [], 'safety_tech' => []];
        $wins = array_fill(0, $trims->count(), 0);
        $contested = 0;

        foreach (self::ATTRIBUTES as $key => [$group, $label, $labelAr]) {
            $values = [];
            $scores = [];
            foreach ($trims as $trim) {
                $metric = $trim->metrics[$key]
                    ?? ($key === 'top' ? ($trim->metrics['speed'] ?? null) : null);
                $values[] = $metric['display'] ?? '—';
                $scores[] = $metric['score'] ?? null;
            }

            $superior = null;
            if (! in_array(null, $scores, true)) {
                $best = max($scores);
                if (count(array_unique($scores)) > 1) {
                    $superior = array_search($best, $scores);
                    $wins[$superior]++;
                    $contested++;
                }
            }

            $groups[$group][] = [
                'attribute' => $label,
                'attribute_ar' => $labelAr,
                'values' => $values,
                'superior_index' => $superior,
            ];
        }

        $maxWins = max($wins);
        $winnerIndexes = array_keys($wins, $maxWins, true);
        $bestIndex = ($contested > 0 && count($winnerIndexes) === 1) ? $winnerIndexes[0] : null;
        $recommended = $bestIndex === null ? null : $trims[$bestIndex];

        return $this->ok([
            'vehicles' => $trims->map(fn (Trim $t) => [
                'trim_id' => $t->id,
                'name' => $t->vehicle->model,
                'name_ar' => $t->vehicle->model_ar,
                'year' => $t->vehicle->year,
                'price_egp' => $t->executive_price,
                'image_url' => $t->vehicle->resolved_image_url,
            ]),
            'comparison' => $groups,
            'recommended' => $recommended ? [
                'trim_id' => $recommended->id,
                'name' => $recommended->vehicle->model,
                'superior_in' => $wins[$bestIndex],
                'contested_rows' => $contested,
                'reason' => "Superior in {$wins[$bestIndex]} of {$contested} categories",
            ] : null,
        ]);
    }

    /** POST /compare ??? server-side list for cross-device continuity. */
    public function add(Request $request): JsonResponse
    {
        $request->validate(['trim_id' => ['required', 'integer', Rule::exists('trims', 'id')->where(fn ($query) => $query->where('active', true)->whereNull('deleted_at'))]]);
        $list = $this->currentList($request);
        $max = (int) AppSetting::get('compare_max', 3);

        if ($list->contains($request->integer('trim_id'))) {
            return $this->fail('This trim is already in your compare list.', 409);
        }
        if ($list->count() >= $max) {
            return $this->fail("You can only compare up to {$max} vehicles at a time. Please remove one first.", 422);
        }

        $trimId = $request->integer('trim_id');
        $list->push($trimId);
        if ($request->user('sanctum')) {
            DB::table('compare_items')->updateOrInsert(
                ['user_id' => $request->user('sanctum')->id, 'trim_id' => $trimId],
                ['updated_at' => now(), 'created_at' => now()],
            );
        } else {
            Cache::put($this->listKey($request), $list->values()->all(), now()->addDays(7));
        }

        return $this->ok([
            'compare_count' => $list->count(),
            'compare_max' => $max,
            'trims_in_compare' => $list->values(),
        ]);
    }

    /** DELETE /compare/{trimId} */
    public function remove(Request $request, int $trimId): JsonResponse
    {
        $list = $this->currentList($request);
        if (! $list->contains($trimId)) {
            return $this->fail('This trim is not in your compare list.', 404);
        }

        $list = $list->reject(fn ($id) => $id === $trimId)->values();
        if ($request->user('sanctum')) {
            DB::table('compare_items')->where('user_id', $request->user('sanctum')->id)->where('trim_id', $trimId)->delete();
        } else {
            Cache::put($this->listKey($request), $list->all(), now()->addDays(7));
        }

        return $this->ok([
            'compare_count' => $list->count(),
            'compare_max' => (int) AppSetting::get('compare_max', 3),
            'trims_in_compare' => $list,
        ]);
    }

    /** Authenticated users get a stable list; guests are keyed by device id. */
    private function listKey(Request $request): string
    {
        $user = $request->user('sanctum');
        $device = substr((string) $request->header('X-Device-Id', $request->ip()), 0, 100);

        return 'compare:'.($user ? "user:{$user->id}" : 'device:'.hash('sha256', $device));
    }

    private function currentList(Request $request)
    {
        $user = $request->user('sanctum');
        if ($user) {
            return DB::table('compare_items')
                ->join('trims', 'trims.id', '=', 'compare_items.trim_id')
                ->join('vehicles', 'vehicles.id', '=', 'trims.vehicle_id')
                ->where('compare_items.user_id', $user->id)
                ->where('trims.active', true)->whereNull('trims.deleted_at')
                ->where('vehicles.active', true)->whereNull('vehicles.deleted_at')
                ->orderBy('compare_items.created_at')
                ->pluck('compare_items.trim_id');
        }

        return collect(Cache::get($this->listKey($request), []));
    }
}
