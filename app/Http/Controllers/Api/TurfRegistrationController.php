<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TurfRegistrationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'address' => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'regex:/^01[3-9][0-9]{8}$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['string', 'max:50'],
            'trade_license_number' => ['nullable', 'string', 'max:100'],
            'nid_number' => ['nullable', 'string', 'max:30'],
        ]);

        $user = $request->user();

        if ($user->role !== 'player') {
            return response()->json(['message' => 'This account cannot register a turf'], 403);
        }

        $exists = DB::table('turfs')->where('owner_id', $user->id)->whereNull('deleted_at')->exists();
        if ($exists) {
            return response()->json(['message' => 'You already registered a turf'], 409);
        }

        $slug = Str::slug($data['name']);
        if ($slug === '' || DB::table('turfs')->where('slug', $slug)->exists()) {
            $slug = ($slug ?: 'turf') . '-' . Str::lower(Str::random(5));
        }

        $turfId = DB::transaction(function () use ($data, $user, $slug) {
            DB::table('owner_profiles')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'business_name' => $data['name'],
                    'nid_number' => $data['nid_number'] ?? null,
                    'trade_license_number' => $data['trade_license_number'] ?? null,
                    'verification_status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            return DB::table('turfs')->insertGetId([
                'owner_id' => $user->id,
                'area_id' => $data['area_id'],
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'address' => $data['address'],
                'contact_phone' => $data['contact_phone'],
                'facilities' => json_encode($data['facilities'] ?? []),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return response()->json([
            'data' => [
                'turf_id' => $turfId,
                'slug' => $slug,
                'status' => 'pending',
                'message' => 'Submitted. Admin approval pending.',
            ],
        ], 201);
    }

    public function status(Request $request): JsonResponse
    {
        $turf = DB::table('turfs')
            ->where('owner_id', $request->user()->id)
            ->whereNull('deleted_at')
            ->first(['id', 'name', 'slug', 'status']);

        return response()->json(['data' => $turf]);
    }
}