<?php

use App\Models\Event;
use App\Models\WorkRole;
use App\Models\Worker;
use App\Models\User;
use App\Models\Employee;
use App\Models\WorkerReservation;
use Illuminate\Support\Facades\DB;

it('counts only RESERVED and CHECKED_IN as taken spots', function () {

    // ✅ Create employee (because events.created_by FK -> employees.employee_id)
    $employeeUser = User::factory()->create(['role' => 'EMPLOYEE']);
    $employee = Employee::create([
        'user_id' => $employeeUser->id,
    ]);

    // ✅ Category
    $categoryId = DB::table('event_categories')->insertGetId([
        'name' => 'Test Category',
        'description' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], 'category_id');

    // ✅ Role type
    $roleTypeId = DB::table('role_types')->insertGetId([
        'name' => 'Organizer',
        'description' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], 'role_type_id');

    // ✅ Event (created_by must be employee_id)
    $event = Event::create([
        'title' => 'Test Event',
        'category_id' => $categoryId,
        'location' => 'Beirut',
        'expected_attendance' => 100,
        'status' => 'PUBLISHED',
        'total_spots' => 10,
        'created_by' => $employee->employee_id,
    ]);

    // ✅ Work role
    $role = WorkRole::create([
        'event_id' => $event->event_id,
        'role_type_id' => $roleTypeId,
        'role_name' => 'Organizer',
        'required_spots' => 5,
    ]);

    // ✅ Workers
    $u1 = User::factory()->create(['role' => 'WORKER']);
    $u2 = User::factory()->create(['role' => 'WORKER']);
    $u3 = User::factory()->create(['role' => 'WORKER']);

    $w1 = Worker::create(['user_id'=>$u1->id, 'certificate_path'=>'x.pdf', 'approval_status'=>'APPROVED']);
    $w2 = Worker::create(['user_id'=>$u2->id, 'certificate_path'=>'x.pdf', 'approval_status'=>'APPROVED']);
    $w3 = Worker::create(['user_id'=>$u3->id, 'certificate_path'=>'x.pdf', 'approval_status'=>'APPROVED']);

    // ✅ Reservations
    WorkerReservation::create([
        'event_id' => $event->event_id,
        'work_role_id' => $role->role_id,
        'worker_id' => $w1->worker_id,
        'status' => 'RESERVED',
    ]);

    WorkerReservation::create([
        'event_id' => $event->event_id,
        'work_role_id' => $role->role_id,
        'worker_id' => $w2->worker_id,
        'status' => 'CHECKED_IN',
    ]);

    WorkerReservation::create([
        'event_id' => $event->event_id,
        'work_role_id' => $role->role_id,
        'worker_id' => $w3->worker_id,
        'status' => 'COMPLETED',
    ]);

    $spots = $event->calculateRoleSpots($roleTypeId);

    expect($spots['total'])->toBe(5);
    expect($spots['taken'])->toBe(2);
    expect($spots['remaining'])->toBe(3);
});
