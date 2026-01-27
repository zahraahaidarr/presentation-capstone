<?php

use App\Models\User;
use App\Models\Worker;

it('syncs user.status when worker approval_status changes', function () {
    $user = User::factory()->create(['status' => 'PENDING']);

    $worker = Worker::create([
        'user_id' => $user->id,
        'certificate_path' => 'cert.pdf',
        'approval_status' => 'PENDING',
    ]);

    $worker->update(['approval_status' => 'APPROVED']);

    $user->refresh();

    expect($user->status)->toBe('ACTIVE');
});
