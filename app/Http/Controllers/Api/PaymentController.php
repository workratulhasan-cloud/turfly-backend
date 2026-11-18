<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    // Asol gateway na ashar age fake payment. Pore eta gateway webhook diye replace hobe.
    public function payTest(Request $request, string $reference): JsonResponse
    {
        return DB::transaction(function () use ($request, $reference) {
            $booking = DB::table('bookings')
                ->where('reference', $reference)
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            if (! $booking) {
                return response()->json(['message' => 'Booking not found'], 404);
            }

            if ($booking->status === 'CONFIRMED') {
                return response()->json(['message' => 'Already confirmed'], 200);
            }

            if ($booking->status !== 'PENDING') {
                return response()->json(['message' => 'Booking is ' . $booking->status], 422);
            }

            if ($booking->expires_at && now()->gt($booking->expires_at)) {
                DB::table('bookings')->where('id', $booking->id)
                    ->update(['status' => 'EXPIRED', 'updated_at' => now()]);
                return response()->json(['message' => 'Hold expired. Please book again'], 422);
            }

            try {
                DB::table('payments')->insert([
                    'booking_id' => $booking->id,
                    'gateway' => 'test',
                    'gateway_txn_id' => 'TEST-' . Str::upper(Str::random(10)),
                    'amount_poysha' => $booking->total_amount_poysha,
                    'status' => 'SUCCESS',
                    'paid_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (QueryException $e) {
                return response()->json(['message' => 'Duplicate payment'], 409);
            }

            DB::table('bookings')->where('id', $booking->id)->update([
                'status' => 'CONFIRMED',
                'paid_amount_poysha' => $booking->total_amount_poysha,
                'expires_at' => null,
                'updated_at' => now(),
            ]);

            return response()->json([
                'data' => [
                    'reference' => $reference,
                    'status' => 'CONFIRMED',
                    'paid_amount_poysha' => $booking->total_amount_poysha,
                ],
            ]);
        });
    }

    public function cancel(Request $request, string $reference): JsonResponse
    {
        $booking = DB::table('bookings')
            ->where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->first();

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

        // Refund policy pore add hobe (24h/6h rule). Ekhon shudhu slot khali hocche.
        return response()->json(['data' => ['reference' => $reference, 'status' => 'CANCELLED']]);
    }
}