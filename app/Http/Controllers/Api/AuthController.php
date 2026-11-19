<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    private const OTP_TTL_MINUTES = 5;
    private const MAX_ATTEMPTS = 5;

    public function requestOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'regex:/^01[3-9][0-9]{8}$/'],
        ]);

        $phone = $data['phone'];

        // Purono unused OTP gulo bondho kore dao
        OtpCode::where('phone', $phone)->whereNull('used_at')->update(['used_at' => now()]);

        $code = (string) random_int(100000, 999999);

        OtpCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
        ]);

        // Ekhon SMS provider nai, tai log e print hobe. Pore SMS pathano hobe.
        Log::info("OTP for {$phone}: {$code}");

        return response()->json([
            'message' => 'OTP sent',
            'expires_in_seconds' => self::OTP_TTL_MINUTES * 60,
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'regex:/^01[3-9][0-9]{8}$/'],
            'code' => ['required', 'digits:6'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $otp = OtpCode::where('phone', $data['phone'])
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $otp || $otp->expires_at->isPast()) {
            return response()->json(['message' => 'OTP expired or not found'], 422);
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            return response()->json(['message' => 'Too many attempts. Request a new OTP'], 429);
        }

        if (! Hash::check($data['code'], $otp->code_hash)) {
            $otp->increment('attempts');
            return response()->json(['message' => 'Invalid OTP'], 422);
        }

        $otp->update(['used_at' => now()]);

        $user = User::firstOrCreate(
            ['phone' => $data['phone']],
            ['name' => $data['name'] ?? 'Player', 'role' => 'player', 'status' => 'active']
        );

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Account is blocked'], 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'role' => $user->role,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}