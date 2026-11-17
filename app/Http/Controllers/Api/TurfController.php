<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TurfController extends Controller
{
    public function areas(): JsonResponse
    {
        $areas = DB::table('areas')
            ->join('cities', 'cities.id', '=', 'areas.city_id')
            ->where('areas.is_active', true)
            ->orderBy('areas.name')
            ->get(['areas.id', 'areas.name', 'areas.slug', 'cities.name as city']);

        return response()->json(['data' => $areas]);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'area_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $query = DB::table('turfs')
            ->join('areas', 'areas.id', '=', 'turfs.area_id')
            ->where('turfs.status', 'active')
            ->whereNull('turfs.deleted_at');

        if ($request->filled('area_id')) {
            $query->where('turfs.area_id', $request->integer('area_id'));
        }
        if ($request->filled('q')) {
            $query->where('turfs.name', 'ilike', '%' . $request->input('q') . '%');
        }

        $turfs = $query->orderBy('turfs.name')->get([
            'turfs.id', 'turfs.name', 'turfs.slug', 'turfs.address',
            'turfs.facilities', 'areas.name as area',
        ]);

        $turfs->transform(function ($t) {
            $t->facilities = json_decode($t->facilities ?? '[]');
            return $t;
        });

        return response()->json(['data' => $turfs]);
    }

    public function show(string $slug): JsonResponse
    {
        $turf = DB::table('turfs')
            ->join('areas', 'areas.id', '=', 'turfs.area_id')
            ->where('turfs.slug', $slug)
            ->where('turfs.status', 'active')
            ->whereNull('turfs.deleted_at')
            ->first([
                'turfs.id', 'turfs.name', 'turfs.slug', 'turfs.description',
                'turfs.address', 'turfs.latitude', 'turfs.longitude',
                'turfs.contact_phone', 'turfs.facilities', 'areas.name as area',
            ]);

        if (! $turf) {
            return response()->json(['message' => 'Turf not found'], 404);
        }

        $turf->facilities = json_decode($turf->facilities ?? '[]');

        $pitches = DB::table('pitches')
            ->where('turf_id', $turf->id)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'sport_type', 'size', 'slot_minutes']);

        foreach ($pitches as $pitch) {
            $pitch->pricing = DB::table('pricing_rules')
                ->where('pitch_id', $pitch->id)
                ->orderBy('start_time')
                ->get(['day_type', 'start_time', 'end_time', 'price_poysha']);
        }

        $turf->pitches = $pitches;

        return response()->json(['data' => $turf]);
    }
}