<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SeedEventsForEmployee59OnFeb14Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // -----------------------------
            // 1) Resolve employee_id for user_id = 59
            // -----------------------------
            $userId = 59;

            $user = DB::table('users')->where('id', $userId)->first();
            if (!$user) {
                throw new \RuntimeException("User id={$userId} not found in users table.");
            }

            // Try common schema: employees.user_id
            $empId = DB::table('employees')->where('user_id', $userId)->value('employee_id');

            // Fallback: match by email (if employees has email column)
            if (!$empId) {
                $hasEmailCol = collect(DB::select("SHOW COLUMNS FROM employees"))->pluck('Field')->contains('email');
                if ($hasEmailCol) {
                    $empId = DB::table('employees')->where('email', $user->email)->value('employee_id');
                }
            }

            if (!$empId) {
                throw new \RuntimeException(
                    "Could not resolve employee_id for user_id=59. " .
                    "Tell me your employees table columns (at least: employee_id + how it links to users)."
                );
            }

            // -----------------------------
            // 2) Categories (same IDs you used)
            // -----------------------------
            $categories = [
                ['id' => 2, 'name' => 'wedding'],
                ['id' => 5, 'name' => 'Ashura'],
                ['id' => 6, 'name' => 'Graduation'],
                ['id' => 7, 'name' => 'Birthday'],
                ['id' => 8, 'name' => 'Anniversary'],
                ['id' => 9, 'name' => 'Family Gathering'],
            ];

            // We'll seed 3 events using the FIRST 3 categories in this list:
            // wedding, Ashura, Graduation
            $pickedCategories = array_slice($categories, 0, 3);

            // -----------------------------
            // 3) Role types map (from DB to avoid wrong IDs)
            // -----------------------------
            $roleTypeIds = DB::table('role_types')->pluck('role_type_id', 'name')->toArray();

            $ROLE = [
                'Organizer'     => $roleTypeIds['Organizer']     ?? null,
                'Civil Defense' => $roleTypeIds['Civil Defense'] ?? null,
                'Media Staff'   => $roleTypeIds['Media Staff']   ?? null,
                'Tech Support'  => $roleTypeIds['Tech Support']  ?? null,
                'Cleaner'       => $roleTypeIds['Cleaner']       ?? null,
                'Decorator'     => $roleTypeIds['Decorator']     ?? null,
                'Cooking Team'  => $roleTypeIds['Cooking Team']  ?? null,
                'Security'      => $roleTypeIds['Security']      ?? null,
            ];

            // -----------------------------
            // 4) Fixed date: 2026-02-14 (3 events same day)
            // -----------------------------
            $baseDate = Carbon::create(2026, 2, 14, 10, 0, 0);
            $startTimes = [
                ['h' => 10, 'm' => 0],  // 10:00
                ['h' => 14, 'm' => 0],  // 14:00
                ['h' => 18, 'm' => 0],  // 18:00
            ];

            $cities = ['Beirut','Dbayeh','Jounieh','Byblos','Saida','Tripoli','Zahle','Tyre','Nabatieh','Baalbek'];
            $now = Carbon::now();

            $pick = function(array $arr, int $i) {
                return $arr[$i % count($arr)];
            };

            $eventSpec = function(string $cat, int $i) use ($cities, $pick) {
                $city = $pick($cities, $i);

                switch (strtolower($cat)) {
                    case 'wedding':
                        $venue = ($i % 2 === 0) ? 'Al Saha Restaurant' : 'Wedding Hall';
                        $area = 800 + (($i % 4) * 150);
                        $att  = 180 + (($i % 4) * 40);
                        return [
                            "SEED: EMP59 - Wedding Reception - {$venue}",
                            "A joyful wedding celebration with organized seating, guest flow management, media coverage, and on-site coordination to ensure everything runs smoothly from arrival to closing.",
                            "{$venue}, {$city}",
                            $area,
                            $att
                        ];

                    case 'ashura':
                        $area = 2500 + (($i % 5) * 700);
                        $att  = 700 + (($i % 6) * 250);
                        return [
                            "SEED: EMP59 - Ashura Gathering & Procession Support",
                            "Public gathering requiring structured organization, safety coordination, crowd flow support, media documentation, and continuous cleaning to maintain a safe and respectful environment.",
                            "Main Square, {$city}",
                            $area,
                            $att
                        ];

                    case 'graduation':
                        $area = 900 + (($i % 4) * 200);
                        $att  = 150 + (($i % 6) * 50);
                        return [
                            "SEED: EMP59 - Graduation Ceremony",
                            "A formal graduation ceremony including stage coordination, sound/tech support, guest guidance, documentation, and post-event cleanup to keep the venue organized and welcoming.",
                            "University Hall, {$city}",
                            $area,
                            $att
                        ];

                    case 'birthday':
                        $area = 300 + (($i % 4) * 120);
                        $att  = 40 + (($i % 6) * 20);
                        return [
                            "SEED: EMP59 - Birthday Celebration",
                            "A friendly birthday event with decoration setup, guest management, light food service, photography coverage, and cleaning support to maintain comfort throughout the event.",
                            "Event Space, {$city}",
                            $area,
                            $att
                        ];

                    case 'anniversary':
                        $venue = ($i % 3 === 0) ? 'Al Saha Restaurant' : 'Restaurant Venue';
                        $area = 450 + (($i % 4) * 150);
                        $att  = 60 + (($i % 5) * 25);
                        return [
                            "SEED: EMP59 - Anniversary Dinner - {$venue}",
                            "An elegant anniversary dinner with table arrangement, guest reception, light media coverage, and smooth coordination to ensure a calm and high-quality experience.",
                            "{$venue}, {$city}",
                            $area,
                            $att
                        ];

                    case 'family gathering':
                    default:
                        $area = 500 + (($i % 5) * 160);
                        $att  = 60 + (($i % 6) * 30);
                        return [
                            "SEED: EMP59 - Family Gathering",
                            "A warm family gathering with organized seating, decoration support, food service coordination, and cleaning to keep the venue comfortable and well-managed.",
                            "Community Hall, {$city}",
                            $area,
                            $att
                        ];
                }
            };

            $rolePlan = function(string $cat, int $attendees, float $area) {
                $catLower = strtolower($cat);

                $organizer = max(1, (int)ceil($attendees / 200));
                $media     = max(1, (int)ceil($attendees / 180));
                $cleaner   = max(1, (int)ceil($attendees / 60));

                $decorator = max(1, (int)ceil($attendees / 90));
                $cooking   = max(1, (int)ceil($attendees / 80));

                $tech = 0; $security = 0; $civil = 0;

                if ($catLower === 'ashura') {
                    $organizer = max($organizer, 3);
                    $media     = max($media, 2);
                    $cleaner   = max($cleaner, 6);

                    $decorator = max(2, (int)ceil($attendees / 250));
                    $cooking   = max(3, (int)ceil($attendees / 220));

                    $tech     = max(1, (int)ceil($area / 2500));
                    $security = max(4, (int)ceil($attendees / 160));
                    $civil    = max(2, (int)ceil($attendees / 500));
                } else {
                    if (in_array($catLower, ['wedding','graduation'], true)) {
                        $tech = ($attendees >= 250 || $area >= 1200) ? 2 : 1;
                    }

                    if ($attendees >= 250) {
                        $security = max(2, (int)ceil($attendees / 220));
                    }
                }

                if (in_array($catLower, ['birthday','anniversary'], true)) {
                    $tech = 0;
                    $security = ($attendees >= 120) ? 1 : 0;
                    $civil = 0;
                }

                if ($catLower === 'family gathering') {
                    $security = ($attendees >= 180) ? 1 : 0;
                    $tech = ($area >= 900) ? 1 : 0;
                }

                $roles = [
                    'Organizer'    => $organizer,
                    'Media Staff'  => $media,
                    'Cleaner'      => $cleaner,
                    'Decorator'    => $decorator,
                    'Cooking Team' => $cooking,
                ];

                if ($tech > 0)     $roles['Tech Support']  = $tech;
                if ($security > 0) $roles['Security']      = $security;
                if ($civil > 0)    $roles['Civil Defense'] = $civil;

                return $roles;
            };

            // -----------------------------
            // 5) Clean only THIS seeder's events for THIS employee on 2026-02-14
            // -----------------------------
            $dayStart = Carbon::create(2026, 2, 14, 0, 0, 0)->format('Y-m-d H:i:s');
            $dayEnd   = Carbon::create(2026, 2, 14, 23, 59, 59)->format('Y-m-d H:i:s');

            $seededEventIds = DB::table('events')
                ->where('created_by', (int)$empId)
                ->whereBetween('starts_at', [$dayStart, $dayEnd])
                ->where('title', 'like', 'SEED: EMP59 - %')
                ->pluck('event_id')
                ->toArray();

            if (!empty($seededEventIds)) {
                DB::table('work_roles')->whereIn('event_id', $seededEventIds)->delete();
                DB::table('events')->whereIn('event_id', $seededEventIds)->delete();
            }

            // -----------------------------
            // 6) Insert 3 events + work_roles
            // -----------------------------
            for ($i = 0; $i < 3; $i++) {

                $cat = $pickedCategories[$i];
                [$title, $desc, $location, $area, $att] = $eventSpec($cat['name'], $i);

                // Same style: alternate duration 2 and 3 hours
                $duration = ($i % 2 === 0) ? 2.00 : 3.00;

                $start = $baseDate->copy()
                    ->setTime($startTimes[$i]['h'], $startTimes[$i]['m'], 0);

                $end = $start->copy()->addHours((int)$duration);

                $roles = $rolePlan($cat['name'], (int)$att, (float)$area);
                $totalSpots = array_sum($roles);

                $eventId = DB::table('events')->insertGetId([
                    'title' => $title,
                    'description' => $desc,
                    'image_path' => null,
                    'category_id' => $cat['id'],
                    'location' => $location,
                    'starts_at' => $start->format('Y-m-d H:i:s'),
                    'ends_at' => $end->format('Y-m-d H:i:s'),
                    'duration_hours' => $duration,
                    'venue_area_sqm' => (float)$area,
                    'expected_attendance' => (int)$att,
                    'total_spots' => (int)$totalSpots,
                    'status' => 'PUBLISHED',
                    'staffing_mode' => 'MANUAL',
                    'created_by' => (int)$empId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], 'event_id');

                foreach ($roles as $roleName => $spots) {
                    $roleTypeId = $ROLE[$roleName] ?? null;
                    if (!$roleTypeId) continue;

                    DB::table('work_roles')->insert([
                        'event_id' => $eventId,
                        'role_type_id' => $roleTypeId,
                        'role_name' => $roleName,
                        'required_spots' => (int)$spots,
                        'calc_source' => 'MANUAL',
                        'calc_confidence' => null,
                        'description' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }
}
