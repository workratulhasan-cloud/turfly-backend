<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AvailabilityController extends Controller
{
    private const TZ = 'Asia/Dhaka';

    public function show(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $pitch = DB::table('pitches')
            ->where('id', $id)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->first();

        if (! $pitch) {
            return response()->json(['message' => 'Pitch not found'], 404);
        }

        // Turf er din Dhaka shomoy onujayi
        $day = Carbon::createFromFormat('Y-m-d', $data['date'], self::TZ)->startOfDay();
        $weekday = $day->dayOfWeek; // 0 = Sunday ... 6 = Saturday

        $hours = DB::table('operating_hours')
            ->where('turf_id', $pitch->turf_id)
            ->where('weekday', $weekday)
            ->first();

        if (! $hours || $hours->is_closed) {
            return response()->json(['data' => ['date' => $data['date'], 'slots' => []]]);
        }

        $open = $day->copy()->setTimeFromTimeString($hours->open_time);
        $close = $day->copy()->setTimeFromTimeString($hours->close_time);

        // Bangladesh e weekend = Friday (5) ar Saturday (6)
        $dayType = in_array($weekday, [5, 6]) ? 'weekend' : 'weekday';

        $rules = DB::table('pricing_rules')
            ->where('pitch_id', $id)
            ->whereIn('day_type', ['all', $dayType])
            ->get();

        // Ei din er shob active booking (CONFIRMED ba ekhono hold-e thaka PENDING)
        $bookings = DB::table('bookings')
            ->where('pitch_id', $id)
            ->where('start_at', '<', $close->copy()->utc())
            ->where('end_at', '>', $open->copy()->utc())
            ->where(function ($q) {
                $q->where('status', 'CONFIRMED')
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'PENDING')->where('expires_at', '>', now());
                    });
            })
            ->get(['start_at', 'end_at'])
            ->map(fn ($b) => [Carbon::parse($b->start_at)->getTimestamp(), Carbon::parse($b->end_at)->getTimestamp()]);

        $slots = [];
        $minutes = (int) $pitch->slot_minutes;
        $start = $open->copy();

        while ($start->copy()->addMinutes($minutes)->lte($close)) {
            $end = $start->copy()->addMinutes($minutes);
            $time = $start->format('H:i:s');

            $price = null;
            foreach ($rules as $r) {
                if ($r->start_time <= $time && $r->end_time > $time) {
                    $price = $r->price_poysha;
                    break;
                }
            }

            $s = $start->getTimestamp();
            $e = $end->getTimestamp();

            $booked = $bookings->contains(fn ($b) => $b[0] < $e && $b[1] > $s);
            $past = $start->lte(now());

            $slots[] = [
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'label' => $start->format('h:i A') . ' - ' . $end->format('h:i A'),
                'price_poysha' => $price,
                'available' => ! $booked && ! $past && $price !== null,
            ];

            $start = $end;
        }

        return response()->json([
            'data' => [
                'date' => $data['date'],
                'pitch_id' => $pitch->id,
                'timezone' => self::TZ,
                'slots' => $slots,
            ],
        ]);
    }
}