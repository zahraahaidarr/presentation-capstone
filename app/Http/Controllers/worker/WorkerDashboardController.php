<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

use App\Models\Announcement;
use App\Models\WorkerReservation;
use App\Models\Event;

use App\Models\EventCategory; 
use App\Models\RoleType;      
use Illuminate\Support\Facades\DB;

class WorkerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user   = Auth::user();
        $worker = optional($user)->worker;

        if (!$worker) abort(403, 'Worker profile not found for this user.');

        $workerId = $worker->worker_id;

        $today = Carbon::today();
        $in14  = Carbon::today()->addDays(14);

        // =========================
        // KPIs
        // =========================
       $upcomingEventsCount = Event::query()
    ->whereIn('status', ['PUBLISHED', 'ACTIVE'])
    ->whereNotNull('starts_at')
    ->whereBetween('starts_at', [
        $today->copy()->startOfDay(),
        $in14->copy()->endOfDay()
    ])
    ->count();


        $reservedCount = WorkerReservation::query()
            ->where('worker_id', $workerId)
            ->where('status', 'RESERVED')
            ->count();

        $completedAwaitingReviewCount = WorkerReservation::query()
            ->where('worker_id', $workerId)
            ->where('status', 'COMPLETED')
            ->whereNull('credited_hours')
            ->count();

        $hoursVolunteered = (float) WorkerReservation::query()
            ->where('worker_id', $workerId)
            ->where('status', 'COMPLETED')
            ->whereNotNull('credited_hours')
            ->sum('credited_hours');
$avgWorkerRating = DB::table('post_event_submissions')
    ->where('worker_id', $workerId)
    ->where('status', 'approved')          // ✅ add this
    ->whereNotNull('worker_rating')
    ->avg('worker_rating');

$avgWorkerRating = $avgWorkerRating !== null ? round($avgWorkerRating, 2) : null; // or 1 decimal if you want


        // =========================
        // Filters data
        // =========================
        $types = EventCategory::query()->orderBy('name')->get();
        $roleTypes = RoleType::query()->orderBy('name')->get();

       $locations = Event::query()
    ->whereNotNull('location')
    ->select('location')
    ->distinct()
    ->orderBy('location')
    ->pluck('location');


        // request filters
        $q          = trim((string) $request->get('q', ''));
        $typeId     = $request->integer('type_id');        // events.category_id
        $roleTypeId = $request->integer('role_type_id');   // work_roles.role_type_id
        $date       = $request->get('date');               // Y-m-d
        $location   = trim((string) $request->get('location', ''));

        // =========================
        // This Week’s Events (ALL)
        // =========================
        $weekStart = Carbon::today()->startOfWeek(); // Monday
        $weekEnd   = Carbon::today()->endOfWeek();   // Sunday
        $maxCards  = 12;

        $thisWeekQuery = Event::query()
            ->where('status', 'PUBLISHED')
            ->whereNotNull('starts_at')
            ->whereBetween('starts_at', [$weekStart->startOfDay(), $weekEnd->endOfDay()]);

        if ($q !== '') {
            $thisWeekQuery->where(function ($qq) use ($q) {
                $qq->where('title', 'like', "%{$q}%")
                   ->orWhere('location', 'like', "%{$q}%")
                   ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($typeId) {
            $thisWeekQuery->where('category_id', $typeId);
        }

        if ($roleTypeId) {
            $thisWeekQuery->whereHas('workRoles', function ($qr) use ($roleTypeId) {
                $qr->where('role_type_id', $roleTypeId);
            });
        }

        if ($date) {
            $thisWeekQuery->whereDate('starts_at', $date);
        }

        if ($location !== '') {
            $thisWeekQuery->where('location', $location);
        }

        $thisWeekEvents = $thisWeekQuery
            ->orderBy('starts_at')
            ->take($maxCards)
            ->get();

        $myReservationsByEvent = WorkerReservation::query()
            ->where('worker_id', $workerId)
            ->whereIn('status', ['PENDING', 'RESERVED', 'COMPLETED', 'CHECKED_IN', 'CANCELLED', 'REJECTED'])
            ->whereIn('event_id', $thisWeekEvents->pluck('event_id'))
            ->get()
            ->keyBy('event_id');

        // =========================
        // Announcements (last 2 weeks) - FIXED PK
        // =========================
        $twoWeeksAgo = Carbon::now()->subDays(14);

        $recentAnnouncements = Announcement::query()
            ->where('created_at', '>=', $twoWeeksAgo)
            ->orderByDesc('created_at')
            ->take(8)
            ->get(['announcement_id', 'title', 'created_at']);

        // =========================
        // ✅ Next Event (REAL from DB)
        // =========================
       $nextReservation = WorkerReservation::query()
    ->with('event')
    ->join('events', 'workers_reservations.event_id', '=', 'events.event_id')

    ->where('workers_reservations.worker_id', $workerId)
    ->where('workers_reservations.status', 'RESERVED')   // ✅ only reserved

    ->whereNotNull('events.starts_at')
    ->where('events.starts_at', '>=', now())
    ->where('events.status', 'PUBLISHED')                // ✅ optional (recommended)

    ->orderBy('events.starts_at', 'asc')
    ->select('workers_reservations.*')
    ->first();

$user = Auth::user();

// ✅ load role type name from DB
$worker = $user->worker()->with('roleType')->first();

if (!$worker) {
    abort(403, 'Worker profile not found for this user.');
}
$completedCount = WorkerReservation::query()
    ->where('worker_id', $workerId)
    ->where('status', 'COMPLETED')
    ->count();


$workerId = $worker->worker_id;

        return view('Worker.dashboard', compact(
            'upcomingEventsCount',
            'reservedCount',
            'completedAwaitingReviewCount',
            'hoursVolunteered',
            'thisWeekEvents',
            'myReservationsByEvent',
            'types',
            'roleTypes',
            'locations',
            'recentAnnouncements',
            'nextReservation',
            'avgWorkerRating',
            'completedCount'

        ));
    }
}
