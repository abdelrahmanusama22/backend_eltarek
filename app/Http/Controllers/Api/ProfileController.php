<?php

namespace App\Http\Controllers\Api;

use App\Models\AppSetting;
use App\Models\Reward;
use App\Models\User;
use App\Models\GarageLinkRequest;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

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

        $requestedEmail = array_key_exists('email', $validated) && $validated['email'] !== null
            ? mb_strtolower($validated['email']) : null;
        if ($requestedEmail !== null && $requestedEmail !== mb_strtolower((string) $user->email)) {
            $user->update(['pending_email' => $requestedEmail]);
        }

        $user->update(array_filter([
            'name' => $validated['full_name'] ?? null,
            'city_id' => $validated['city_id'] ?? null,
            'age' => $validated['age'] ?? null,
        ], fn ($v) => $v !== null));

        if ($requestedEmail !== null && $requestedEmail !== mb_strtolower((string) $user->email)) {
            app(AuthController::class)->sendPendingEmailVerification($requestedEmail);
        }

        return $this->ok($user->fresh()->load('city')->toApi(), 'Profile updated successfully.');
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        if ($request->has('avatar_base64')) {
            $request->validate(['avatar_base64' => ['required', 'string', 'max:12000000']]);
            $raw = (string) $request->input('avatar_base64');
            if (preg_match('/^data:image\/\w+;base64,/', $raw)) {
                $raw = substr($raw, strpos($raw, ',') + 1);
            }
            $bytes = base64_decode(preg_replace('/\s+/', '', $raw), true);
            $dimensions = $bytes === false ? false : @getimagesizefromstring($bytes);
            $allowedMimes = [
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];
            $mime = $dimensions !== false ? ($dimensions['mime'] ?? '') : '';
            if ($bytes === false || strlen($bytes) > 5 * 1024 * 1024
                || $dimensions === false || ! array_key_exists($mime, $allowedMimes)
                || $dimensions[0] > 5000 || $dimensions[1] > 5000) {
                return $this->fail('Please choose a valid image (JPEG, PNG, or WEBP) under 5 MB.', 422);
            }
            $ext = $allowedMimes[$mime] ?? 'jpg';
            $path = 'avatars/'.Str::uuid().'.'.$ext;
            if (! Storage::disk('public')->put($path, $bytes)) {
                return $this->fail('Could not save profile photo.', 500);
            }
        } else {
            $request->validate([
                'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=5000,max_height=5000'],
            ]);
            $path = $request->file('avatar')->store('avatars', 'public');
        }
        $user = $request->user();
        $oldPath = is_string($user->avatar_url) && str_starts_with($user->avatar_url, 'avatars/')
            ? $user->avatar_url
            : null;
        $user->update(['avatar_url' => $path]);
        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return $this->ok($user->fresh()->load('city')->toApi(), 'Profile photo updated.');
    }

    public function garage(Request $request): JsonResponse
    {
        return $this->ok($this->cursorItems(
            $request,
            $request->user()->garageCars()->with('vehicle')->withCount('serviceRecords')
                ->with(['serviceRecords' => fn ($query) => $query->orderByDesc('id')->limit(3)])
                ->orderByDesc('id'),
            fn ($car) => $car->toApi(),
        ));
    }

    public function garageServiceRecords(Request $request, \App\Models\GarageCar $garageCar): JsonResponse
    {
        abort_unless($garageCar->user_id === $request->user()->id, 404);

        return $this->ok($this->cursorItems(
            $request,
            $garageCar->serviceRecords()->reorder()->orderByDesc('id'),
            fn ($record) => $record->toApi(),
        ));
    }

    public function garageLinkRequests(Request $request): JsonResponse
    {
        return $this->ok($this->cursorItems($request, $request->user()->garageLinkRequests()->orderByDesc('id'), fn ($link) => $link->toApi()));
    }

    public function requestGarageLink(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required','string','min:5','max:64','regex:/^(?:[A-HJ-NPR-Z0-9]{17}|[A-Z0-9-]{5,64})$/i'],
            'car_name' => ['nullable','string','max:120'],
        ]);
        $identifier = Str::upper(trim($validated['identifier']));
        if (\App\Models\GarageCar::where('tracking_code', $identifier)->where('user_id', '!=', $request->user()->id)->exists()) {
            return $this->fail('This vehicle is already linked to another account.', 409);
        }
        if ($request->user()->garageCars()->where('tracking_code', $identifier)->exists()) {
            return $this->fail('This vehicle is already in your garage.', 409);
        }
        $existing = GarageLinkRequest::where('user_id', $request->user()->id)->where('identifier', $identifier)->first();
        if ($existing && in_array($existing->status, ['pending','approved'], true)) {
            return $this->fail('A request for this vehicle already exists.', 409);
        }
        $linkRequest = GarageLinkRequest::updateOrCreate(
            ['user_id'=>$request->user()->id,'identifier'=>$identifier],
            ['car_name'=>$validated['car_name'] ?? null,'status'=>'pending','admin_notes'=>null,'reviewed_by'=>null,'reviewed_at'=>null],
        );
        return $this->ok($linkRequest->toApi(), 'Vehicle link request submitted.', status: 201);
    }

    public function cancelGarageLinkRequest(Request $request, GarageLinkRequest $garageLinkRequest): JsonResponse
    {
        abort_unless($garageLinkRequest->user_id === $request->user()->id, 404);
        if ($garageLinkRequest->status !== 'pending') {
            return $this->fail('Only pending requests can be cancelled.', 409);
        }
        $garageLinkRequest->delete();
        return $this->ok(null, 'Vehicle link request cancelled.');
    }

    public function favorites(Request $request): JsonResponse
    {
        return $this->ok($this->cursorItems(
            $request,
            $request->user()->favorites()->published()->with('vehicle')->orderBy('trims.id'),
            fn ($trim) => [
                'trim_id' => $trim->id,
                'name' => $trim->vehicle->model,
                'name_ar' => $trim->vehicle->model_ar,
                'price_egp' => $trim->executive_price,
                'image_url' => $trim->vehicle->resolved_image_url,
            ],
        ));
    }

    public function rewards(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->ok($this->cursorItems(
            $request,
            Reward::where('active', true)
                ->withCount(['redemptions as user_redemptions_count' => fn ($query) => $query->where('user_id', $user->id)])
                ->orderBy('id'),
            fn (Reward $reward) => $reward->toApi($user),
        ));
    }

    public function redemptions(Request $request): JsonResponse
    {
        return $this->ok($this->cursorItems($request, $request->user()->redemptions()->with('reward')->orderByDesc('id'), fn ($redemption) => $redemption->toApi()));
    }

    public function pointsHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->ok([
            'points_balance' => $user->points,
            ...$this->cursorItems($request, $user->pointTransactions()->orderByDesc('id'), fn ($t) => [
                'id' => $t->id,
                'points' => $t->points,
                'type' => $t->type,
                'description' => $t->description,
                'date' => $t->created_at->toIso8601String(),
            ]),
        ]);
    }

    public function redeem(Request $request): JsonResponse
    {
        $request->validate(['reward_id' => ['required', 'integer']]);

        $reward = Reward::where('active', true)->find($request->integer('reward_id'));
        if (! $reward) {
            return $this->fail('Reward not found or no longer available.', 404);
        }

        $result = DB::transaction(function () use ($request, $reward) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->first();
            $reward = Reward::whereKey($reward->id)->lockForUpdate()->first();
            if (! $user || ! $reward || $user->points < $reward->points_cost) {
                return null;
            }
            if ($reward->stock !== null && $reward->stock < 1) return ['error' => 'out_of_stock'];
            if ($reward->per_user_limit !== null && $user->redemptions()->where('reward_id', $reward->id)->count() >= $reward->per_user_limit) return ['error' => 'limit_reached'];

            $user->decrement('points', $reward->points_cost);
            $user->pointTransactions()->create([
                'points' => -$reward->points_cost,
                'type' => 'debit',
                'description' => "Redeemed reward: {$reward->name}",
            ]);

            do {
                $code = 'RWD-'.now()->year.'-'.Str::upper(Str::random(6));
            } while (\App\Models\Redemption::where('code', $code)->exists());

            $redemption = $user->redemptions()->create([
                'reward_id' => $reward->id,
                'code' => $code,
                'points_spent' => $reward->points_cost,
                'valid_until' => now()->addDays($reward->validity_days)->toDateString(),
            ]);
            if ($reward->stock !== null) $reward->decrement('stock');
            UserNotification::create(['user_id'=>$user->id,'type'=>'reward','title'=>'Reward redeemed','title_ar'=>'تم استبدال المكافأة','body'=>"Your redemption code is {$code}.",'body_ar'=>"كود استبدال المكافأة هو {$code}."]);

            return [
                'points_remaining' => $user->points,
                'redemption' => $redemption,
            ];
        });

        if (! $result) {
            return $this->fail(
                sprintf(
                    'Insufficient points. You need %s points but currently have %s.',
                    number_format($reward->points_cost),
                    number_format($request->user()->fresh()->points),
                ),
                400,
            );
        }
        if (($result['error'] ?? null) === 'out_of_stock') return $this->fail('This reward is out of stock.', 409);
        if (($result['error'] ?? null) === 'limit_reached') return $this->fail('You reached the redemption limit for this reward.', 409);

        return $this->ok([
            'reward' => ['id' => $reward->id, 'name' => $reward->name],
            'points_spent' => $reward->points_cost,
            'points_remaining' => $result['points_remaining'],
            'redemption_code' => $result['redemption']->code,
            'valid_until' => $result['redemption']->valid_until,
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
