<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SeedThreeWeekEventsForAmalSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /**
             * 1) Force owner = Amal Hussein
             *    user_id = 59, employee_id = 21
             */
            $userId = 59;

            $employeeId = DB::table('employees')
                ->where('user_id', $userId)
                ->value('employee_id');

            // If not found, fallback to fixed employee_id = 21 (as you said)
            if (!$employeeId) {
                $employeeId = 21;
            }

            /**
             * 2) Clean old seeded events for safe re-run
             */
            $seededEventIds = DB::table('events')
                ->where('title', 'like', 'SEED:AMALWEEK:%')
                ->pluck('event_id')
                ->toArray();

            if (!empty($seededEventIds)) {
                DB::table('work_roles')->whereIn('event_id', $seededEventIds)->delete();
                DB::table('events')->whereIn('event_id', $seededEventIds)->delete();
            }

            /**
             * 3) Role types map (from DB to avoid wrong IDs)
             */
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

            /**
             * 4) Categories (use YOUR real IDs)
             */
            $CATS = [
                'wedding'     => 2,
                'ashura'      => 5,
                'graduation'  => 6,
            ];

            /**
             * 5) Put 3 events inside THIS week
             *    Mon / Wed / Sat (you can change times easily)
             */
            $startOfWeek = Carbon::now()->startOfWeek(); // Monday
            $now = Carbon::now();

            $events = [
                [
                    'title' => 'SEED:AMALWEEK: Wedding Reception - Al Saha',
                    'description' => 'Wedding reception with guest coordination, media coverage, and smooth on-site management.',
                    'category_id' => $CATS['wedding'],
                    'location' => 'Al Saha Restaurant, Beirut',
                    'starts_at' => $startOfWeek->copy()->addDays(0)->setTime(18, 0, 0), // Mon 18:00
                    'duration_hours' => 4.00,
                    'venue_area_sqm' => 1100,
                    'expected_attendance' => 260,
                    'roles' => [
                        'Organizer'    => 2,
                        'Media Staff'  => 2,
                        'Cleaner'      => 5,
                        'Decorator'    => 3,
                        'Cooking Team' => 4,
                        'Tech Support' => 2,
                        'Security'     => 2,
                    ],
                ],
                [
                    'title' => 'SEED:AMALWEEK: Graduation Ceremony - University Hall',
                    'description' => 'Graduation ceremony with stage coordination, tech support, media documentation, and cleanup.',
                    'category_id' => $CATS['graduation'],
                    'location' => 'University Hall, Jounieh',
                    'starts_at' => $startOfWeek->copy()->addDays(2)->setTime(16, 0, 0), // Wed 16:00
                    'duration_hours' => 3.00,
                    'venue_area_sqm' => 950,
                    'expected_attendance' => 180,
                    'roles' => [
                        'Organizer'    => 1,
                        'Media Staff'  => 2,
                        'Cleaner'      => 4,
                        'Decorator'    => 2,
                        'Cooking Team' => 2,
                        'Tech Support' => 2,
                    ],
                ],
                [
                    'title' => 'SEED:AMALWEEK: Ashura Gathering & Procession Support',
                    'description' => 'Ashura gathering requiring structured organization, safety coordination, crowd flow support, and cleaning.',
                    'category_id' => $CATS['ashura'],
                    'location' => 'Main Square, Nabatieh',
                    'starts_at' => $startOfWeek->copy()->addDays(5)->setTime(12, 0, 0), // Sat 12:00
                    'duration_hours' => 6.00,
                    'venue_area_sqm' => 4200,
                    'expected_attendance' => 1200,
                    'roles' => [
                        'Organizer'     => 4,
                        'Media Staff'   => 3,
                        'Cleaner'       => 10,
                        'Decorator'     => 3,
                        'Cooking Team'  => 6,
                        'Tech Support'  => 2,
                        'Security'      => 8,
                        'Civil Defense' => 3,
                    ],
                ],
            ];

            /**
             * 6) Insert events + work_roles
             */
            foreach ($events as $e) {
                $end = $e['starts_at']->copy()->addHours((int)$e['duration_hours']);

                $eventId = DB::table('events')->insertGetId([
                    'title' => $e['title'],
                    'description' => $e['description'],
                    'image_path' => null,
                    'category_id' => $e['category_id'],
                    'location' => $e['location'],
                    'starts_at' => $e['starts_at']->format('Y-m-d H:i:s'),
                    'ends_at' => $end->format('Y-m-d H:i:s'),
                    'duration_hours' => (float)$e['duration_hours'],
                    'venue_area_sqm' => (float)$e['venue_area_sqm'],
                    'expected_attendance' => (int)$e['expected_attendance'],
                    'total_spots' => (int)array_sum($e['roles']),
                    'status' => 'PUBLISHED',
                    'staffing_mode' => 'MANUAL',
                    'created_by' => (int)$employeeId, // ✅ Amal Hussein employee_id = 21
                    'created_at' => $now,
                    'updated_at' => $now,
                ], 'event_id');

                foreach ($e['roles'] as $roleName => $spots) {
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
