<?php

namespace App\Http\Controllers\Api;

use App\Models\AppSetting;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProfileController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        return $this->ok($request->user()->load('city')->toApi());
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['sometimes', 'string', 'min:3', 'max:120'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190'],
            'city_id' => ['sometimes', 'integer', 'exists:cities,id'],
            'age' => ['sometimes', 'integer', 'min:18', 'max:100'],
        ]);

        $user = $request->user();

        if (isset($validated['email']) && $validated['email'] !== null) {
            $taken = User::where('email', $validated['email'])->whereKeyNot($user->id)->exists();
            if ($taken) {
                return $this->fail('This email is already associated with another account.', 409);
            }
        }

        $user->update(array_filter([
            'name' => $validated['full_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'city_id' => $validated['city_id'] ?? null,
            'age' => $validated['age'] ?? null,
        ], fn ($v) => $v !== null));

        return $this->ok($user->fresh()->load('city')->toApi(), 'Profile updated successfully.');
    }

    public function garage(Request $request): JsonResponse
    {
        return $this->ok($request->user()->garageCars()->get()->map->toApi());
    }

    public function favorites(Request $request): JsonResponse
    {
        return $this->ok(
            $request->user()->favorites()->with('vehicle')->get()->map(fn ($trim) => [
                'trim_id' => $trim->id,
                'name' => $trim->vehicle->model,
                'name_ar' => $trim->vehicle->model_ar,
                'price_egp' => $trim->price_egp,
                'image_url' => $trim->vehicle->resolved_image_url,
            ]),
        );
    }

    public function rewards(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->ok(
            Reward::where('active', true)->get()->map(fn (Reward $r) => $r->toApi($user)),
        );
    }

    public function pointsHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return $this->ok([
            'points_balance' => $user->points,
            'transactions' => $user->pointTransactions()->latest()->get()->map(fn ($t) => [
                'id' => $t->id,
                'points' => $t->points,
                'type' => $t->type,
                'description' => $t->description,
                'date' => $t->created_at->toIso8601String(),
            ])
        ]);
    }

    public function redeem(Request $request): JsonResponse
    {
        $request->validate(['reward_id' => ['required', 'integer']]);

        $reward = Reward::where('active', true)->find($request->integer('reward_id'));
        if (! $reward) {
            return $this->fail('Reward not found or no longer available.', 404);
        }

        $user = $request->user();
        if ($user->points < $reward->points_cost) {
            return $this->fail(
                sprintf(
                    'Insufficient points. You need %s points but currently have %s.',
                    number_format($reward->points_cost),
                    number_format($user->points),
                ),
                400,
            );
        }

        $user->decrement('points', $reward->points_cost);
        $user->pointTransactions()->create([
            'points' => -$reward->points_cost,
            'type' => 'debit',
            'description' => "Redeemed reward: {$reward->name}"
        ]);

        $redemption = $user->redemptions()->create([
            'reward_id' => $reward->id,
            'code' => 'RWD-'.now()->year.'-'.Str::upper(Str::random(4)),
            'points_spent' => $reward->points_cost,
            'valid_until' => now()->addMonths(6)->toDateString(),
        ]);

        return $this->ok([
            'reward' => ['id' => $reward->id, 'name' => $reward->name],
            'points_spent' => $reward->points_cost,
            'points_remaining' => $user->fresh()->points,
            'redemption_code' => $redemption->code,
            'valid_until' => $redemption->valid_until,
        ], 'Reward redeemed successfully.');
    }

    public function benefits(Request $request): JsonResponse
    {
        $vip = $request->user()->vipSummary();
        $all = AppSetting::get('vip_benefits', []);

        return $this->ok([
            'tier' => $vip['tier'],
            'tier_ar' => $vip['tier_ar'],
            'benefits' => $all[$vip['tier']] ?? [],
        ]);
    }
}
