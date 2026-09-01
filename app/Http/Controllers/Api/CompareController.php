<?php

namespace App\Http\Controllers\Api;

use App\Models\AppSetting;
use App\Models\Trim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CompareController extends ApiController
{
    private const ATTRIBUTES = [
        // metric key ??? [group, label, label_ar]
        'hp' => ['performance', 'Horsepower', '?????????? ????????????????'],
        'accel' => ['performance', '0-100 km/h', '??????????????'],
        'top' => ['performance', 'Top Speed', '???????????? ????????????'],
        'engine' => ['engine_specs', 'Engine', '????????????'],
        'fuel' => ['engine_specs', 'Fuel Economy', '?????????????? ????????????'],
        'airbags' => ['safety_tech', 'Airbags', '?????????????? ????????????????'],
        'sunroof' => ['safety_tech', 'Sunroof', '???????? ??????'],
    ];

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

        $trims = Trim::with('vehicle')->findMany($ids)
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
                $metric = $trim->metrics[$key] ?? null;
                $values[] = $metric['display'] ?? '???';
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

        $bestIndex = array_search(max($wins), $wins);
        $recommended = $trims[$bestIndex];

        return $this->ok([
            'vehicles' => $trims->map(fn (Trim $t) => [
                'trim_id' => $t->id,
                'name' => $t->vehicle->model,
                'name_ar' => $t->vehicle->model_ar,
                'year' => $t->vehicle->year,
                'price_egp' => $t->price_egp,
                'image_url' => $t->vehicle->resolved_image_url,
            ]),
            'comparison' => $groups,
            'recommended' => [
                'trim_id' => $recommended->id,
                'name' => $recommended->vehicle->model,
                'superior_in' => $wins[$bestIndex],
                'contested_rows' => $contested,
                'reason' => "Superior in {$wins[$bestIndex]} of {$contested} categories",
            ],
        ]);
    }

    /** POST /compare ??? server-side list for cross-device continuity. */
    public function add(Request $request): JsonResponse
    {
        $request->validate(['trim_id' => ['required', 'integer', 'exists:trims,id']]);
        $key = $this->listKey($request);
        $list = collect(Cache::get($key, []));
        $max = (int) AppSetting::get('compare_max', 3);

        if ($list->contains($request->integer('trim_id'))) {
            return $this->fail('This trim is already in your compare list.', 409);
        }
        if ($list->count() >= $max) {
            return $this->fail("You can only compare up to {$max} vehicles at a time. Please remove one first.", 422);
        }

        $list->push($request->integer('trim_id'));
        Cache::put($key, $list->values()->all(), now()->addDays(7));

        return $this->ok([
            'compare_count' => $list->count(),
            'compare_max' => $max,
            'trims_in_compare' => $list->values(),
        ]);
    }

    /** DELETE /compare/{trimId} */
    public function remove(Request $request, int $trimId): JsonResponse
    {
        $key = $this->listKey($request);
        $list = collect(Cache::get($key, []));
        if (! $list->contains($trimId)) {
            return $this->fail('This trim is not in your compare list.', 404);
        }

        $list = $list->reject(fn ($id) => $id === $trimId)->values();
        Cache::put($key, $list->all(), now()->addDays(7));

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
        $device = $request->header('X-Device-Id', $request->ip());

        return 'compare:'.($user ? "user:{$user->id}" : "device:{$device}");
    }
}
