<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    private const TZ = 'Asia/Dhaka';
    private const HOLD_MINUTES = 10;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pitch_id' => ['required', 'integer'],
            'start' => ['required', 'date'],
        ]);

        $pitch = DB::table('pitches')
            ->where('id', $data['pitch_id'])
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->first();

        if (! $pitch) {
            return response()->json(['message' => 'Pitch not found'], 404);
        }

        $start = Carbon::parse($data['start'])->setTimezone(self::TZ);
        $minutes = (int) $pitch->slot_minutes;
        $end = $start->copy()->addMinutes($minutes);

        if ($start->lte(now())) {
            return response()->json(['message' => 'Slot is in the past'], 422);
        }

        // Turf er khola-bondho shomoy check
        $day = $start->copy()->startOfDay();
        $weekday = $day->dayOfWeek;

        $hours = DB::table('operating_hours')
            ->where('turf_id', $pitch->turf_id)
            ->where('weekday', $weekday)
            ->first();

        if (! $hours || $hours->is_closed) {
            return response()->json(['message' => 'Turf is closed on this day'], 422);
        }

        $open = $day->copy()->setTimeFromTimeString($hours->open_time);
        $close = $day->copy()->setTimeFromTimeString($hours->close_time);

        $offsetMinutes = ($start->getTimestamp() - $open->getTimestamp()) / 60;

        if ($start->lt($open) || $end->gt($close) || $offsetMinutes % $minutes !== 0) {
            return response()->json(['message' => 'Invalid slot time'], 422);
        }

        // Dam server nije hishab kore (client er dam trust kora hoy na)
        $dayType = in_array($weekday, [5, 6]) ? 'weekend' : 'weekday';
        $time = $start->format('H:i:s');

        $price = DB::table('pricing_rules')
            ->where('pitch_id', $pitch->id)
            ->whereIn('day_type', ['all', $dayType])
            ->where('start_time', '<=', $time)
            ->where('end_time', '>', $time)
            ->value('price_poysha');

        if ($price === null) {
            return response()->json(['message' => 'No price set for this slot'], 422);
        }

        // Expire hoye jawa purono hold gulo khali kore dao
        DB::table('bookings')
            ->where('pitch_id', $pitch->id)
            ->where('status', 'PENDING')
            ->where('expires_at', '<', now())
            ->update(['status' => 'EXPIRED', 'updated_at' => now()]);

        $reference = 'TRF-' . $start->format('Ymd') . '-' . Str::upper(Str::random(4));
        $expiresAt = now()->addMinutes(self::HOLD_MINUTES);

        try {
            $id = DB::table('bookings')->insertGetId([
                'reference' => $reference,
                'user_id' => $request->user()->id,
                'pitch_id' => $pitch->id,
                'start_at' => $start->copy()->utc()->toDateTimeString(),
                'end_at' => $end->copy()->utc()->toDateTimeString(),
                'status' => 'PENDING',
                'source' => 'app',
                'total_amount_poysha' => $price,
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            // 23P01 = exclusion violation, mane onno keu ei slot nie nise
            if ($e->getCode() === '23P01') {
                return response()->json(['message' => 'Slot already booked'], 409);
            }
            throw $e;
        }

        return response()->json([
            'data' => [
                'id' => $id,
                'reference' => $reference,
                'status' => 'PENDING',
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'total_amount_poysha' => $price,
                'expires_at' => $expiresAt->copy()->setTimezone(self::TZ)->toIso8601String(),
            ],
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $bookings = DB::table('bookings')
            ->join('pitches', 'pitches.id', '=', 'bookings.pitch_id')
            ->join('turfs', 'turfs.id', '=', 'pitches.turf_id')
            ->where('bookings.user_id', $request->user()->id)
            ->orderByDesc('bookings.start_at')
            ->get([
                'bookings.reference', 'bookings.status', 'bookings.start_at', 'bookings.end_at',
                'bookings.total_amount_poysha', 'turfs.name as turf', 'pitches.name as pitch',
            ]);

        $bookings->transform(function ($b) {
            $b->start_at = Carbon::parse($b->start_at)->setTimezone(self::TZ)->toIso8601String();
            $b->end_at = Carbon::parse($b->end_at)->setTimezone(self::TZ)->toIso8601String();
            return $b;
        });

        return response()->json(['data' => $bookings]);
    }
}