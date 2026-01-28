<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PostEventReportsSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ from your events screenshot (events.created_by)
        $createdByEmployeeIds = [22, 23];

        // 1) Get events for these employees
        $events = DB::table('events')
            ->whereIn('created_by', $createdByEmployeeIds)
            ->get(['event_id', 'created_by']);

        if ($events->isEmpty()) {
            $this->command?->warn("No events found for created_by: " . implode(',', $createdByEmployeeIds));
            return;
        }

        $eventIds = $events->pluck('event_id')->toArray();

        // 2) Map employee_id -> user_id (needed because reviewed_by = users.id)
        // Assumes employees table has user_id column.
        $employeeToUser = DB::table('employees')
            ->whereIn('employee_id', $createdByEmployeeIds)
            ->pluck('user_id', 'employee_id')  // [employee_id => user_id]
            ->toArray();

        // If your employees table doesn't have user_id, stop safely (no guessing).
        foreach ($createdByEmployeeIds as $eid) {
            if (!array_key_exists($eid, $employeeToUser) || empty($employeeToUser[$eid])) {
                throw new \RuntimeException(
                    "employees.user_id not found for employee_id={$eid}. " .
                    "Since reviewed_by must be users.id, I need employees.user_id populated."
                );
            }
        }

        // 3) Pull reservations for these events
        $reservations = DB::table('workers_reservations')
            ->whereIn('event_id', $eventIds)
            ->orderBy('reservation_id')
            ->get([
                'reservation_id','event_id','work_role_id','worker_id',
                'reserved_at','status','check_in_time','check_out_time','credited_hours'
            ]);

        if ($reservations->isEmpty()) {
            $this->command?->warn("No workers_reservations found for events: " . implode(',', $eventIds));
            return;
        }

        // 4) Ensure they are COMPLETED + set missing times
        foreach ($reservations as $r) {
            $checkIn  = $r->check_in_time  ?: Carbon::parse($r->reserved_at)->addHours(8)->format('Y-m-d H:i:s');
            $checkOut = $r->check_out_time ?: Carbon::parse($checkIn)->addHours(2)->format('Y-m-d H:i:s');

            DB::table('workers_reservations')
                ->where('reservation_id', $r->reservation_id)
                ->update([
                    'status'         => 'COMPLETED',
                    'check_in_time'  => $checkIn,
                    'check_out_time' => $checkOut,
                    'credited_hours' => $r->credited_hours ?: 2.00,
                    'updated_at'     => now(),
                ]);
        }

        $reservationIds = $reservations->pluck('reservation_id')->toArray();

        // 5) Delete old submissions for these reservations (so rerun keeps ONE copy)
        DB::table('post_event_submissions')
            ->whereIn('worker_reservation_id', $reservationIds)
            ->delete();

        // 6) role_name from work_roles using work_role_id
        // ✅ role_name comes from work_roles
$roleMap = DB::table('work_roles')
    ->whereIn('role_id', $reservations->pluck('work_role_id')->unique()->toArray())
    ->pluck('role_name', 'role_id') // [role_id => role_name]
    ->toArray();


        // 7) event_id -> reviewed_by (users.id) based on events.created_by (employee_id)
        $eventToReviewedByUserId = [];
        foreach ($events as $ev) {
            $employeeId = (int)$ev->created_by;
            $eventToReviewedByUserId[(int)$ev->event_id] = (int)$employeeToUser[$employeeId];
        }

        // ratings (mixed)
        $ownerRatings  = [5,4,3,2,4,5,3,2,5,4,3,2,4,5,3,2,5,4,3,2];
        $workerRatings = [4,5,2,3,5,3,4,2,3,5,2,4,5,3,2,4,3,5,2,4];

        $rows = [];
        $i = 0;

        foreach ($reservations as $r) {
            $roleName = $roleMap[$r->work_role_id] ?? 'Role';
            $roleSlug = $this->toSlug($roleName);

            $submittedAt = Carbon::parse($r->check_out_time ?: now())->addMinutes(20);
            $reviewedAt  = (clone $submittedAt)->addHours(2);

            $reviewedByUserId = $eventToReviewedByUserId[(int)$r->event_id] ?? null;
            if (!$reviewedByUserId) {
                throw new \RuntimeException("No reviewed_by (users.id) mapping found for event_id={$r->event_id}");
            }

            $rows[] = [
                'worker_reservation_id' => (int)$r->reservation_id,
                'event_id'              => (int)$r->event_id,
                'worker_id'             => (int)$r->worker_id,
                'work_role_id'          => (int)$r->work_role_id,
                'role_slug'             => $roleSlug,

                'general_notes'         => null,
                'data'                  => json_encode($this->buildDataPayload($roleSlug), JSON_UNESCAPED_UNICODE),

                'status'                => 'approved',

                'owner_rating'          => $ownerRatings[$i % count($ownerRatings)],
                'worker_rating'         => $workerRatings[$i % count($workerRatings)],

                'submitted_at'          => $submittedAt->format('Y-m-d H:i:s'),
                'reviewed_at'           => $reviewedAt->format('Y-m-d H:i:s'),

                // ✅ reviewed_by is users.id (your confirmation)
                'reviewed_by'           => $reviewedByUserId,

                'review_notes'          => null,

                'created_at'            => $submittedAt->format('Y-m-d H:i:s'),
                'updated_at'            => $reviewedAt->format('Y-m-d H:i:s'),
            ];

            $i++;
        }

        DB::table('post_event_submissions')->insert($rows);

        $this->command?->info("Inserted " . count($rows) . " post_event_submissions rows for events created_by: " . implode(',', $createdByEmployeeIds));
    }

    private function toSlug(string $name): string
    {
        $name = trim(mb_strtolower($name));
        $name = preg_replace('/[^a-z0-9]+/i', '_', $name);
        return trim($name, '_') ?: 'role';
    }

    private function buildDataPayload(string $roleSlug): array
    {
        switch ($roleSlug) {
            case 'cooking_team':
                return ['meals' => "Rice plates: 180\nWater: 200", 'notes' => "Served on time."];
            case 'civil_defense':
                return ['cases' => "Minor incident handled.", 'notes' => "Safety checks completed."];
            case 'media_staff':
                return ['deliverables' => "Photos: 120\nClips: 6", 'notes' => "Captured key moments."];
            case 'tech_support':
                return ['setup' => "Audio tested, mic levels adjusted.", 'notes' => "Resolved minor issue."];
            case 'cleaner':
                return ['areas' => "Hall + entrance + restrooms", 'notes' => "Cleanup completed."];
            case 'decorator':
                return ['setup' => "Stage decor + tables", 'notes' => "Matched theme."];
            case 'security':
                return ['incidents' => "No major incidents.", 'notes' => "Managed entrance flow."];
            case 'organizer':
                return ['coordination' => "Managed timeline and tasks.", 'notes' => "Smooth operation."];
            default:
                return ['summary' => "Tasks completed.", 'notes' => "No issues."];
        }
    }
}
