<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OwnerController extends Controller
{
    private const TZ = 'Asia/Dhaka';

    public function turfs(Request $request): JsonResponse
    {
        $turfs = DB::table('turfs')
            ->join('areas', 'areas.id', '=', 'turfs.area_id')
            ->where('turfs.owner_id', $request->user()->id)
            ->whereNull('turfs.deleted_at')
            ->get(['turfs.id', 'turfs.name', 'turfs.slug', 'turfs.status', 'areas.name as area']);

        foreach ($turfs as $turf) {
            $turf->pitches = DB::table('pitches')
                ->where('turf_id', $turf->id)
                ->whereNull('deleted_at')
                ->get(['id', 'name', 'sport_type', 'size', 'slot_minutes', 'status']);
        }

        return response()->json(['data' => $turfs]);
    }

    public function bookings(Request $request): JsonResponse
    {
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);

        $dayStart = Carbon::createFromFormat('Y-m-d', $data['date'], self::TZ)->startOfDay();
        $dayEnd = $dayStart->copy()->addDay();

        $rows = DB::table('bookings')
            ->join('pitches', 'pitches.id', '=', 'bookings.pitch_id')
            ->join('turfs', 'turfs.id', '=', 'pitches.turf_id')
            ->leftJoin('users', 'users.id', '=', 'bookings.user_id')
            ->where('turfs.owner_id', $request->user()->id)
            ->where('bookings.start_at', '>=', $dayStart->copy()->utc())
            ->where('bookings.start_at', '<', $dayEnd->copy()->utc())
            ->orderBy('bookings.start_at')
            ->get([
                'bookings.reference', 'bookings.status', 'bookings.source',
                'bookings.start_at', 'bookings.end_at', 'bookings.total_amount_poysha',
                'turfs.name as turf', 'pitches.name as pitch',
                'bookings.customer_name', 'bookings.customer_phone',
                'users.name as user_name', 'users.phone as user_phone',
            ]);

        $rows->transform(function ($b) {
            $b->start_at = Carbon::parse($b->start_at)->setTimezone(self::TZ)->toIso8601String();
            $b->end_at = Carbon::parse($b->end_at)->setTimezone(self::TZ)->toIso8601String();
            return $b;
        });

        return response()->json(['data' => $rows]);
    }

    public function storeManual(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pitch_id' => ['required', 'integer'],
            'start' => ['required', 'date'],
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'regex:/^01[3-9][0-9]{8}$/'],
            'amount_poysha' => ['nullable', 'integer', 'min:0'],
        ]);

        // Pitch ta ei owner er kina
        $pitch = DB::table('pitches')
            ->join('turfs', 'turfs.id', '=', 'pitches.turf_id')
            ->where('pitches.id', $data['pitch_id'])
            ->where('turfs.owner_id', $request->user()->id)
            ->whereNull('pitches.deleted_at')
            ->first(['pitches.id', 'pitches.turf_id', 'pitches.slot_minutes']);

        if (! $pitch) {
            return response()->json(['message' => 'Pitch not found'], 404);
        }

        $start = Carbon::parse($data['start'])->setTimezone(self::TZ);
        $end = $start->copy()->addMinutes((int) $pitch->slot_minutes);

        $day = $start->copy()->startOfDay();
        $hours = DB::table('operating_hours')
            ->where('turf_id', $pitch->turf_id)
            ->where('weekday', $day->dayOfWeek)
            ->first();

        if (! $hours || $hours->is_closed) {
            return response()->json(['message' => 'Turf is closed on this day'], 422);
        }

        $open = $day->copy()->setTimeFromTimeString($hours->open_time);
        $close = $day->copy()->setTimeFromTimeString($hours->close_time);

        if ($start->lt($open) || $end->gt($close)) {
            return response()->json(['message' => 'Outside operating hours'], 422);
        }

        // Dam na dile pricing rule theke nao
        $amount = $data['amount_poysha'] ?? null;
        if ($amount === null) {
            $dayType = in_array($day->dayOfWeek, [5, 6]) ? 'weekend' : 'weekday';
            $amount = DB::table('pricing_rules')
                ->where('pitch_id', $pitch->id)
                ->whereIn('day_type', ['all', $dayType])
                ->where('start_time', '<=', $start->format('H:i:s'))
                ->where('end_time', '>', $start->format('H:i:s'))
                ->value('price_poysha') ?? 0;
        }

        $reference = 'TRF-' . $start->format('Ymd') . '-' . Str::upper(Str::random(4));

        try {
            DB::table('bookings')->insert([
                'reference' => $reference,
                'user_id' => null,
                'pitch_id' => $pitch->id,
                'start_at' => $start->copy()->utc()->toDateTimeString(),
                'end_at' => $end->copy()->utc()->toDateTimeString(),
                'status' => 'CONFIRMED',
                'source' => 'manual',
                'total_amount_poysha' => $amount,
                'paid_amount_poysha' => 0,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23P01') {
                return response()->json(['message' => 'Slot already booked'], 409);
            }
            throw $e;
        }

        return response()->json([
            'data' => [
                'reference' => $reference,
                'status' => 'CONFIRMED',
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'total_amount_poysha' => $amount,
            ],
        ], 201);
    }

    public function cancel(Request $request, string $reference): JsonResponse
    {
        $booking = DB::table('bookings')
            ->join('pitches', 'pitches.id', '=', 'bookings.pitch_id')
            ->join('turfs', 'turfs.id', '=', 'pitches.turf_id')
            ->where('bookings.reference', $reference)
            ->where('turfs.owner_id', $request->user()->id)
            ->first(['bookings.id', 'bookings.status']);

        if (! $booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        if (! in_array($booking->status, ['PENDING', 'CONFIRMED'])) {
            return response()->json(['message' => 'Cannot cancel a ' . $booking->status . ' booking'], 422);
        }

        DB::table('bookings')->where('id', $booking->id)->update([
            'status' => 'CANCELLED',
            'cancelled_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['data' => ['reference' => $reference, 'status' => 'CANCELLED']]);
    }
}