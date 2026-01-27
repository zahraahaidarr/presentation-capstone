<?php

use App\Models\Notification;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Notify;



it('creates notification when worker notifications are enabled', function () {
    $user = User::factory()->create();

    $prefix = "worker:{$user->id}:";

    SystemSetting::create(['key' => $prefix.'notifications_app', 'value' => '1']);
    SystemSetting::create(['key' => $prefix.'notifications_event_reminders', 'value' => '1']);

    $n = Notify::to($user->id, 'Title', 'Body', 'reservation');

    expect($n)->not->toBeNull();
    expect(Notification::count())->toBe(1);
});

it('does not create notification when master switch is off', function () {
    $user = User::factory()->create();
    $prefix = "worker:{$user->id}:";

    SystemSetting::create(['key' => $prefix.'notifications_app', 'value' => '0']);

    $n = Notify::to($user->id, 'Title', 'Body', 'reservation');

    expect($n)->toBeNull();
    expect(Notification::count())->toBe(0);
});

it('blocks announcement notifications when announcement type setting is off', function () {
    $user = User::factory()->create();
    $prefix = "worker:{$user->id}:";

    SystemSetting::create(['key' => $prefix.'notifications_app', 'value' => '1']);
    SystemSetting::create(['key' => $prefix.'notifications_announcements', 'value' => '0']);

    $n = Notify::to($user->id, 'Ann', '...', 'announcement');

    expect($n)->toBeNull();
    expect(Notification::count())->toBe(0);
});
