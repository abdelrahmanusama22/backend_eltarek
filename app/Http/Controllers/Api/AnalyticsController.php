<?php

namespace App\Http\Controllers\Api;

use App\Models\AnalyticsEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends ApiController
{
    /**
     * Track one or multiple mobile analytics events.
     * POST /api/v1/analytics/event
     */
    public function log(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        $ip = $request->ip();
        $device = $request->header('User-Agent') ?? $request->input('device_info');
        $sessionId = $request->input('session_id');

        // Handle batch of events
        if ($request->has('events') && is_array($request->input('events'))) {
            $events = $request->input('events');
            $records = [];
            $now = now();

            foreach ($events as $evt) {
                if (empty($evt['event_name'])) continue;

                $records[] = [
                    'user_id'     => $user?->id,
                    'event_name'  => (string) $evt['event_name'],
                    'category'    => $evt['category'] ?? 'general',
                    'properties'  => isset($evt['properties']) ? json_encode($evt['properties']) : null,
                    'session_id'  => $evt['session_id'] ?? $sessionId,
                    'device_info' => $evt['device_info'] ?? $device,
                    'ip_address'  => $ip,
                    'created_at'  => $evt['created_at'] ?? $now,
                ];
            }

            if (!empty($records)) {
                AnalyticsEvent::insert($records);
            }

            return $this->ok(['logged_count' => count($records)], 'Events recorded successfully.');
        }

        // Handle single event
        $request->validate([
            'event_name' => ['required', 'string', 'max:100'],
            'category'   => ['nullable', 'string', 'max:50'],
            'properties' => ['nullable', 'array'],
        ]);

        $event = AnalyticsEvent::create([
            'user_id'     => $user?->id,
            'event_name'  => $request->string('event_name')->toString(),
            'category'    => $request->input('category', 'general'),
            'properties'  => $request->input('properties'),
            'session_id'  => $sessionId,
            'device_info' => $device,
            'ip_address'  => $ip,
            'created_at'  => now(),
        ]);

        return $this->ok(['event_id' => $event->id], 'Event recorded successfully.');
    }
}
