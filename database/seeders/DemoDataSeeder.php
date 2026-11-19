<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // City + Areas
        DB::table('cities')->updateOrInsert(
            ['slug' => 'dhaka'],
            ['name' => 'Dhaka', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]
        );
        $cityId = DB::table('cities')->where('slug', 'dhaka')->value('id');

        $areas = ['Dhanmondi', 'Uttara', 'Mirpur', 'Mohammadpur'];
        $areaIds = [];
        foreach ($areas as $name) {
            $slug = strtolower($name);
            DB::table('areas')->updateOrInsert(
                ['city_id' => $cityId, 'slug' => $slug],
                ['name' => $name, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
            $areaIds[$slug] = DB::table('areas')->where('city_id', $cityId)->where('slug', $slug)->value('id');
        }

        // Demo turf account (ek account = ek turf, tai prottek turf er alada user)
        $turfs = [
            [
                'name' => 'Green Arena', 'slug' => 'green-arena', 'area' => 'dhanmondi',
                'address' => 'Road 5, Dhanmondi', 'facilities' => ['parking', 'washroom', 'floodlight'],
                'phone' => '01800000001', 'email' => 'owner@turfly.test',
            ],
            [
                'name' => 'Sky Turf', 'slug' => 'sky-turf', 'area' => 'uttara',
                'address' => 'Sector 7, Uttara', 'facilities' => ['floodlight', 'washroom'],
                'phone' => '01800000002', 'email' => 'sky@turfly.test',
            ],
            [
                'name' => 'Kick Off Ground', 'slug' => 'kick-off-ground', 'area' => 'mirpur',
                'address' => 'Section 10, Mirpur', 'facilities' => ['parking'],
                'phone' => '01800000003', 'email' => 'kickoff@turfly.test',
            ],
        ];

        foreach ($turfs as $t) {
            $account = User::updateOrCreate(
                ['phone' => $t['phone']],
                ['name' => $t['name'], 'email' => $t['email'], 'role' => 'turf', 'status' => 'active']
            );

            DB::table('owner_profiles')->updateOrInsert(
                ['user_id' => $account->id],
                [
                    'business_name' => $t['name'],
                    'verification_status' => 'approved',
                    'verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            DB::table('turfs')->updateOrInsert(
                ['slug' => $t['slug']],
                [
                    'owner_id' => $account->id,
                    'area_id' => $areaIds[$t['area']],
                    'name' => $t['name'],
                    'address' => $t['address'],
                    'contact_phone' => $t['phone'],
                    'facilities' => json_encode($t['facilities']),
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $turfId = DB::table('turfs')->where('slug', $t['slug'])->value('id');

            // Operating hours: 7 din, 06:00 - 23:00 (local time)
            for ($d = 0; $d <= 6; $d++) {
                DB::table('operating_hours')->updateOrInsert(
                    ['turf_id' => $turfId, 'weekday' => $d],
                    ['open_time' => '06:00', 'close_time' => '23:00', 'is_closed' => false, 'created_at' => $now, 'updated_at' => $now]
                );
            }

            // Pitches
            $pitches = [
                ['name' => 'Pitch A', 'sport_type' => 'football', 'size' => '5v5'],
                ['name' => 'Pitch B', 'sport_type' => 'football', 'size' => '7v7'],
            ];

            foreach ($pitches as $p) {
                $exists = DB::table('pitches')->where('turf_id', $turfId)->where('name', $p['name'])->first();
                if ($exists) {
                    continue;
                }

                $pitchId = DB::table('pitches')->insertGetId([
                    'turf_id' => $turfId,
                    'name' => $p['name'],
                    'sport_type' => $p['sport_type'],
                    'size' => $p['size'],
                    'slot_minutes' => 60,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $base = $p['size'] === '5v5' ? 120000 : 180000; // poysha: 1200 / 1800 taka

                // Pricing: din (06-17) kom, shondha (17-23) beshi
                $rules = [
                    ['all', '06:00', '17:00', $base],
                    ['all', '17:00', '23:00', $base + 40000],
                ];
                foreach ($rules as [$dayType, $start, $end, $price]) {
                    DB::table('pricing_rules')->insert([
                        'pitch_id' => $pitchId,
                        'day_type' => $dayType,
                        'start_time' => $start,
                        'end_time' => $end,
                        'price_poysha' => $price,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }
}