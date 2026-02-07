{{-- resources/views/settings.blade.php --}}
<!doctype html>
<html lang="{{ app()->getLocale() }}"
      dir="{{ app()->getLocale()==='ar' ? 'rtl' : 'ltr' }}"
      data-theme="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Settings • Volunteer</title>

  {{-- Worker / Admin / Employee Settings CSS --}}
  <link rel="stylesheet" href="{{ asset('css/settings.css') }}">
</head>

@php
  // Settings array from controller, with safe fallback
  $s = $settings ?? [];
  $user = Auth::user();
  $role = strtoupper($user->role ?? '');
  $isAdmin    = $role === 'ADMIN';
  $isWorker   = $role === 'WORKER';
  $isEmployee = $role === 'EMPLOYEE';
@endphp

<body data-theme="{{ $s['ui_theme'] ?? 'dark' }}">

  {{-- ================= ADMIN ================= --}}
  @if($isAdmin)
    <div class="container">
      <!-- Sidebar -->
      <aside class="sidebar">

        {{-- Sidebar user block --}}
        <div class="logo">
          <a href="{{ Route::has('profile') ? route('profile') : '#' }}" class="logo-link">
            @if($user && $user->avatar_path)
              <img
                src="{{ asset('storage/' . ltrim($user->avatar_path, '/')) }}"
                alt="{{ $user->first_name ?? $user->name ?? 'Profile' }}"
                class="logo-avatar"
              >
            @else
              <div class="logo-icon">
                {{ strtoupper(substr($user->first_name ?? $user->name ?? 'U', 0, 1)) }}
              </div>
            @endif

            <div class="logo-id">
              <div class="logo-name">
                {{ trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->name ?? 'User') }}
              </div>
              <div class="logo-role">
                {{ strtoupper($user->role ?? 'USER') }}
              </div>
            </div>
          </a>
        </div>

        {{-- Main navigation --}}
        <nav class="nav-section">
          <a href="{{ Route::has('admin.dashboard') ? route('admin.dashboard') : '#' }}"
             class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <span class="nav-icon">📊</span><span>Dashboard</span>
          </a>

          <a href="{{ Route::has('employees.index') ? route('employees.index') : '#' }}"
             class="nav-item {{ request()->routeIs('employees.index') ? 'active' : '' }}">
            <span class="nav-icon">👔</span><span>Clients</span>
          </a>

          <a href="{{ Route::has('volunteers.index') ? route('volunteers.index') : '#' }}"
             class="nav-item {{ request()->routeIs('volunteers.index') ? 'active' : '' }}">
            <span class="nav-icon">👥</span><span>Workers</span>
          </a>

          <a href="{{ Route::has('events.index') ? route('events.index') : '#' }}"
             class="nav-item {{ request()->routeIs('events.index') ? 'active' : '' }}">
            <span class="nav-icon">📅</span><span>Events</span>
          </a>

          <a href="{{ route('taxonomies-venues.index') }}"
             class="nav-item {{ request()->routeIs('taxonomies-venues.index') ? 'active' : '' }}">
            <span class="nav-icon">🏷️</span><span>Taxonomies & Venues</span>
          </a>

          <a href="{{ route('announcements.create') }}"
             class="nav-item {{ request()->routeIs('announcements.create') ? 'active' : '' }}">
            <span class="nav-icon">📢</span><span>Send Announcement</span>
          </a>
                      <a href="{{ route('admin.rejected-content.index') }}"
   class="nav-item {{ request()->routeIs('admin.rejected-content.*') ? 'active' : '' }}">
    <span class="nav-icon">🧾</span><span>Rejected Content</span>
</a>
        </nav>

        {{-- Account section --}}
        <nav class="nav-section">
          <div class="nav-label">Account</div>

          <a href="{{ Route::has('settings') ? route('settings') : '#' }}"
             class="nav-item {{ request()->routeIs('settings') ? 'active' : '' }}">
            <span class="nav-icon">🔧</span><span>Settings</span>
          </a>
        </nav>

      </aside>

      <!-- Main Content for ADMIN settings -->
      <main class="main-content" id="main">
        {{-- Page Header --}}
        <section class="page-header">
          <h1 id="pageTitle">Settings</h1>
          <p id="pageSubtitle">Manage your account preferences and notifications.</p>
        </section>

        <!-- ROW 1: Notifications + Interface -->
        <section class="row">
          {{-- Notifications card --}}
          <article class="card">
            <h2 class="section-title">Notifications</h2>
            <div class="settings-list">

              <div class="setting-item">
                <div class="setting-info">
                  <h4>App Notifications</h4>
                  <p>Receive in-app notifications for updates inside VolunteerHub.</p>
                </div>
                <div class="toggle {{ ($s['notifications_app'] ?? '1') === '1' ? 'active' : '' }}"
                     data-toggle
                     data-setting="notifications_app"></div>
              </div>

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Announcements</h4>
                  <p>Get notified when new announcements are published.</p>
                </div>
                <div class="toggle {{ ($s['notifications_announcements'] ?? '1') === '1' ? 'active' : '' }}"
                     data-toggle
                     data-setting="notifications_announcements"></div>
              </div>

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Chat Messages</h4>
                  <p>Receive alerts for new chat messages.</p>
                </div>
                <div class="toggle {{ ($s['notifications_chat'] ?? '1') === '1' ? 'active' : '' }}"
                     data-toggle
                     data-setting="notifications_chat"></div>
              </div>

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Event Reminders</h4>
                  <p>Get reminders before your accepted events start.</p>
                </div>
                <div class="toggle {{ ($s['notifications_event_reminders'] ?? '1') === '1' ? 'active' : '' }}"
                     data-toggle
                     data-setting="notifications_event_reminders"></div>
              </div>

            </div>
          </article>

          {{-- Interface & Preferences card --}}
          <article class="card">
            <h2 class="section-title">Interface & Preferences</h2>
            <div class="settings-list">

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Language</h4>
                  <p>Switch between English and Arabic.</p>
                </div>
                <button class="btn small ghost"
                        type="button"
                        id="langToggleSecondary">
                  Toggle EN / AR
                </button>
              </div>

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Theme</h4>
                  <p>Toggle between light and dark mode.</p>
                </div>
                <button class="btn small ghost"
                        type="button"
                        id="themeToggleSecondary">
                  Toggle Theme
                </button>
              </div>

            </div>
          </article>
        </section>

        <!-- ROW 2: Security | Account Management -->
        <section class="row">
          {{-- Security card --}}
          <article class="card">
            <h2 class="section-title">Security</h2>
            <div class="settings-list">

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Logout From All Devices</h4>
                  <p>Log out from all active sessions on other devices.</p>
                </div>
                <form method="POST" action="{{ route('settings.logoutAll') }}">
                  @csrf
                  <button class="btn small" type="submit">Logout All</button>
                </form>
              </div>

            </div>
          </article>

          {{-- Account Management card --}}
          <article class="card">
            <h2 class="section-title">Account Management</h2>
            <div class="settings-list">

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Delete Account</h4>
                  <p>Permanently delete your account and information.</p>
                </div>
                <form method="POST" action="{{ route('settings.deleteAccount') }}"
                      onsubmit="return confirm('Are you sure you want to delete your account?');">
                  @csrf
                  @method('DELETE')
                  <button class="btn small danger" type="submit">Delete</button>
                </form>
              </div>

            </div>
          </article>
        </section>
      </main>
    </div> {{-- .container (admin) --}}

  {{-- ================= WORKER ================= --}}
  @elseif($isWorker)
    <div class="container">
      <!-- Sidebar -->
      <aside class="sidebar">
        @php
            $user   = Auth::user();
            $worker = optional($user)->worker;
            $roleLabel = $worker
                ? ($worker->is_volunteer ? 'VOLUNTEER' : 'WORKER')
                : 'WORKER';
        @endphp

        <div class="logo">
            <a href="{{ Route::has('profile') ? route('profile') : '#' }}" class="logo-link">
                @if($user && $user->avatar_path)
                    <img
                        src="{{ asset('storage/' . ltrim($user->avatar_path, '/')) }}"
                        alt="{{ $user->first_name ?? $user->name ?? 'Profile' }}"
                        class="logo-avatar"
                    >
                @else
                    <div class="logo-icon">
                        {{ strtoupper(substr($user->first_name ?? $user->name ?? 'U', 0, 1)) }}
                    </div>
                @endif

                <div class="logo-id">
                    <div class="logo-name">
                        {{ trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->name ?? 'User') }}
                    </div>

                    {{-- Dynamic WORKER / VOLUNTEER label --}}
                    <div class="logo-role">
                        {{ $roleLabel }}
                    </div>
                </div>
            </a>
        </div>

        <nav class="nav-section">
            <a href="{{ route('worker.dashboard') }}"
               class="nav-item {{ request()->routeIs('worker.dashboard') ? 'active' : '' }}">
                <span class="nav-icon">🏠</span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('worker.events.discover') }}"
               class="nav-item {{ request()->routeIs('worker.events.discover') ? 'active' : '' }}">
                <span class="nav-icon">🗓️</span>
                <span>Discover Events</span>
            </a>

            <a href="{{ route('worker.reservations') }}"
               class="nav-item {{ request()->routeIs('worker.reservations') ? 'active' : '' }}">
                <span class="nav-icon">✅</span>
                <span>My Reservations</span>
            </a>

            <a href="{{ route('worker.submissions') }}"
               class="nav-item {{ request()->routeIs('worker.submissions') ? 'active' : '' }}">
                <span class="nav-icon">📝</span>
                <span>Post-Event Submissions</span>
            </a>
             <a href="{{ route('worker.follow.index') }}"
   class="nav-item {{ request()->routeIs('worker.following.*') ? 'active' : '' }}">
  <span class="nav-icon">👥</span><span>Follow clients</span>
</a>
        </nav>

        <nav class="nav-section">
            <div class="nav-label">Account</div>
            @php($worker = optional(auth()->user())->worker)

            @if($worker && !$worker->is_volunteer)
                <a href="{{ route('worker.payments.index') }}"
                   class="nav-item {{ request()->routeIs('worker.payments.index') ? 'active' : '' }}">
                    <span class="nav-icon">💰</span>
                    <span>Payments</span>
                </a>
            @endif

            <a href="{{ route('worker.messages') }}"
               class="nav-item {{ request()->routeIs('worker.messages') ? 'active' : '' }}">
                <span class="nav-icon">💬</span>
                <span>Chat</span>
            </a>

            <a href="{{ route('worker.announcements.index') }}"
               class="nav-item {{ request()->routeIs('worker.announcements.index') ? 'active' : '' }}">
                <span class="nav-icon">📢</span>
                <span>Announcements</span>
            </a>

            <a href="{{ route('settings') }}"
               class="nav-item {{ request()->routeIs('settings') ? 'active' : '' }}">
                <span class="nav-icon">⚙️</span>
                <span>Settings</span>
            </a>
        </nav>
      </aside>

      <!-- Main Content for WORKER settings -->
      <main class="main-content" id="main">
        <!-- Page Header -->
        <section class="page-header">
          <h1 id="pageTitle">Settings</h1>
          <p id="pageSubtitle">Manage your account preferences and notifications.</p>
        </section>

        <!-- ROW 1: Notifications + Interface -->
        <section class="row">
          {{-- Notifications card --}}
          <article class="card">
            <h2 class="section-title">Notifications</h2>
            <div class="settings-list">

              <div class="setting-item">
                <div class="setting-info">
                  <h4>App Notifications</h4>
                  <p>Receive in-app notifications for updates inside VolunteerHub.</p>
                </div>
                <div class="toggle {{ ($s['notifications_app'] ?? '1') === '1' ? 'active' : '' }}"
                     data-toggle
                     data-setting="notifications_app"></div>
              </div>

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Announcements</h4>
                  <p>Get notified when new announcements are published.</p>
                </div>
                <div class="toggle {{ ($s['notifications_announcements'] ?? '1') === '1' ? 'active' : '' }}"
                     data-toggle
                     data-setting="notifications_announcements"></div>
              </div>

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Chat Messages</h4>
                  <p>Receive alerts for new chat messages.</p>
                </div>
                <div class="toggle {{ ($s['notifications_chat'] ?? '1') === '1' ? 'active' : '' }}"
                     data-toggle
                     data-setting="notifications_chat"></div>
              </div>

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Event Reminders</h4>
                  <p>Get reminders before your accepted events start.</p>
                </div>
                <div class="toggle {{ ($s['notifications_event_reminders'] ?? '1') === '1' ? 'active' : '' }}"
                     data-toggle
                     data-setting="notifications_event_reminders"></div>
              </div>

            </div>
          </article>

          {{-- Interface & Preferences card --}}
          <article class="card">
            <h2 class="section-title">Interface & Preferences</h2>
            <div class="settings-list">

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Language</h4>
                  <p>Switch between English and Arabic.</p>
                </div>
                <button class="btn small ghost"
                        type="button"
                        id="langToggleSecondary">
                  Toggle EN / AR
                </button>
              </div>

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Theme</h4>
                  <p>Toggle between light and dark mode.</p>
                </div>
                <button class="btn small ghost"
                        type="button"
                        id="themeToggleSecondary">
                  Toggle Theme
                </button>
              </div>

            </div>
          </article>
        </section>

        <!-- ROW 2: Security | Account Management -->
        <section class="row">
          {{-- Security card --}}
          <article class="card">
            <h2 class="section-title">Security</h2>
            <div class="settings-list">

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Logout From All Devices</h4>
                  <p>Log out from all active sessions on other devices.</p>
                </div>
                <form method="POST" action="{{ route('settings.logoutAll') }}">
                  @csrf
                  <button class="btn small" type="submit">Logout All</button>
                </form>
              </div>

            </div>
          </article>

          {{-- Account Management card --}}
          <article class="card">
            <h2 class="section-title">Account Management</h2>
            <div class="settings-list">

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Delete Account</h4>
                  <p>Permanently delete your account and information.</p>
                </div>
                <form method="POST" action="{{ route('settings.deleteAccount') }}"
                      onsubmit="return confirm('Are you sure you want to delete your account?');">
                  @csrf
                  @method('DELETE')
                  <button class="btn small danger" type="submit">Delete</button>
                </form>
              </div>

            </div>
          </article>
        </section>
      </main>
    </div> {{-- .container (worker) --}}

  {{-- ================= EMPLOYEE ================= --}}
  @elseif($isEmployee)
    <div class="container">
      <!-- Sidebar -->
      <aside class="sidebar">
        @php($user = Auth::user())

        <div class="logo">
          <a href="{{ Route::has('profile') ? route('profile') : '#' }}" class="logo-link">
            @if($user && $user->avatar_path)
              <img
                  src="{{ asset('storage/' . ltrim($user->avatar_path, '/')) }}"
                  alt="{{ $user->first_name ?? $user->name ?? 'Profile' }}"
                  class="logo-avatar"
              >
            @else
              <div class="logo-icon">
                {{ strtoupper(substr($user->first_name ?? $user->name ?? 'U', 0, 1)) }}
              </div>
            @endif

            <div class="logo-id">
              <div class="logo-name">
                {{ trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->name ?? 'User') }}
              </div>
              <div class="logo-role">
                Client
              </div>
            </div>
          </a>
        </div>

        <nav>
          <div class="nav-section">
            <a href="{{ Route::has('employee.dashboard') ? route('employee.dashboard') : '#' }}"
               class="nav-item {{ request()->routeIs('employee.dashboard') ? 'active' : '' }}">
              <span class="nav-icon">📊</span><span>Dashboard</span>
            </a>

            <a href="{{ Route::has('events.index') ? route('events.index') : '#' }}"
               class="nav-item {{ request()->routeIs('events.index') ? 'active' : '' }}">
              <span class="nav-icon">📅</span><span>Event Management</span>
            </a>

            <a href="{{ Route::has('volunteers.assign') ? route('volunteers.assign') : '#' }}"
               class="nav-item {{ request()->routeIs('volunteers.assign') ? 'active' : '' }}">
              <span class="nav-icon">👥</span><span>Worker Application</span>
            </a>

            <a href="{{ route('employee.postEventReports.index') }}"
               class="nav-item">
              <span class="nav-icon">📝</span><span>Post-Event Reports</span>
            </a>
            <a href="{{ route('content.index') }}" class="nav-item {{ request()->routeIs('employee.content.*') ? 'active' : '' }}">
                    <span class="nav-icon">📝</span><span>Create Content</span>
                </a>
          </div>

          <div class="nav-section">
            <div class="nav-label">Communication</div>

            <a href="{{ route('employee.messages') }}"
               class="nav-item {{ request()->routeIs('employee.messages') ? 'active' : '' }}">
              <span class="nav-icon">💬</span><span>Messages</span>
            </a>

            <a href="{{ route('announcements.create') }}"
               class="nav-item {{ request()->routeIs('announcements.create') ? 'active' : '' }}">
              <span class="nav-icon">📢</span><span>Send Announcement</span>
            </a>

            <a href="{{ Route::has('employee.announcements.index') ? route('employee.announcements.index') : '#' }}"
               class="nav-item {{ request()->routeIs('employee.announcements.index') ? 'active' : '' }}">
              <span class="nav-icon">📢</span><span>Announcements</span>
            </a>
          </div>

          <div class="nav-section">
            <div class="nav-label">Account</div>

            <a href="{{ Route::has('settings') ? route('settings') : '#' }}"
               class="nav-item {{ request()->routeIs('settings') ? 'active' : '' }}">
              <span class="nav-icon">⚙️</span><span>Settings</span>
            </a>
          </div>
        </nav>
      </aside>

      <!-- Main Content for EMPLOYEE settings -->
      <main class="main-content" id="main">
        <!-- Page Header -->
        <section class="page-header">
          <h1 id="pageTitle">Settings</h1>
          <p id="pageSubtitle">Manage your account preferences and notifications.</p>
        </section>

        <!-- ROW 1: Notifications + Interface -->
        <section class="row">
          {{-- Notifications card --}}
          <article class="card">
            <h2 class="section-title">Notifications</h2>
            <div class="settings-list">

              <div class="setting-item">
                <div class="setting-info">
                  <h4>App Notifications</h4>
                  <p>Receive in-app notifications for updates inside VolunteerHub.</p>
                </div>
                <div class="toggle {{ ($s['notifications_app'] ?? '1') === '1' ? 'active' : '' }}"
                     data-toggle
                     data-setting="notifications_app"></div>
              </div>

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Announcements</h4>
                  <p>Get notified when new announcements are published.</p>
                </div>
                <div class="toggle {{ ($s['notifications_announcements'] ?? '1') === '1' ? 'active' : '' }}"
                     data-toggle
                     data-setting="notifications_announcements"></div>
              </div>

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Chat Messages</h4>
                  <p>Receive alerts for new chat messages.</p>
                </div>
                <div class="toggle {{ ($s['notifications_chat'] ?? '1') === '1' ? 'active' : '' }}"
                     data-toggle
                     data-setting="notifications_chat"></div>
              </div>

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Event Reminders</h4>
                  <p>Get reminders before your accepted events start.</p>
                </div>
                <div class="toggle {{ ($s['notifications_event_reminders'] ?? '1') === '1' ? 'active' : '' }}"
                     data-toggle
                     data-setting="notifications_event_reminders"></div>
              </div>

            </div>
          </article>

          {{-- Interface & Preferences card --}}
          <article class="card">
            <h2 class="section-title">Interface & Preferences</h2>
            <div class="settings-list">

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Language</h4>
                  <p>Switch between English and Arabic.</p>
                </div>
                <button class="btn small ghost"
                        type="button"
                        id="langToggleSecondary">
                  Toggle EN / AR
                </button>
              </div>

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Theme</h4>
                  <p>Toggle between light and dark mode.</p>
                </div>
                <button class="btn small ghost"
                        type="button"
                        id="themeToggleSecondary">
                  Toggle Theme
                </button>
              </div>

            </div>
          </article>
        </section>

        <!-- ROW 2: Security | Account Management -->
        <section class="row">
          {{-- Security card --}}
          <article class="card">
            <h2 class="section-title">Security</h2>
            <div class="settings-list">

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Logout From All Devices</h4>
                  <p>Log out from all active sessions on other devices.</p>
                </div>
                <form method="POST" action="{{ route('settings.logoutAll') }}">
                  @csrf
                  <button class="btn small" type="submit">Logout All</button>
                </form>
              </div>

            </div>
          </article>

          {{-- Account Management card --}}
          <article class="card">
            <h2 class="section-title">Account Management</h2>
            <div class="settings-list">

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Delete Account</h4>
                  <p>Permanently delete your account and information.</p>
                </div>
                <form method="POST" action="{{ route('settings.deleteAccount') }}"
                      onsubmit="return confirm('Are you sure you want to delete your account?');">
                  @csrf
                  @method('DELETE')
                  <button class="btn small danger" type="submit">Delete</button>
                </form>
              </div>

            </div>
          </article>
        </section>
      </main>
    </div> {{-- .container (employee) --}}

  {{-- ================= FALLBACK ================= --}}
  @else
    <div class="wrap">
      <!-- Main Content -->
      <main class="content" id="main">
        <!-- Page Header -->
        <section class="page-header">
          <h1 id="pageTitle">Settings</h1>
          <p id="pageSubtitle">Manage your account preferences and notifications.</p>
        </section>

        <!-- ROW 1: Notifications + Interface -->
        <section class="row">
          {{-- Notifications card --}}
          <article class="card">
            <h2 class="section-title">Notifications</h2>
            <div class="settings-list">

              <div class="setting-item">
                <div class="setting-info">
                  <h4>App Notifications</h4>
                  <p>Receive in-app notifications for updates inside VolunteerHub.</p>
                </div>
                <div class="toggle {{ ($s['notifications_app'] ?? '1') === '1' ? 'active' : '' }}"
                     data-toggle
                     data-setting="notifications_app"></div>
              </div>

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Announcements</h4>
                  <p>Get notified when new announcements are published.</p>
                </div>
                <div class="toggle {{ ($s['notifications_announcements'] ?? '1') === '1' ? 'active' : '' }}"
                     data-toggle
                     data-setting="notifications_announcements"></div>
              </div>

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Chat Messages</h4>
                  <p>Receive alerts for new chat messages.</p>
                </div>
                <div class="toggle {{ ($s['notifications_chat'] ?? '1') === '1' ? 'active' : '' }}"
                     data-toggle
                     data-setting="notifications_chat"></div>
              </div>

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Event Reminders</h4>
                  <p>Get reminders before your accepted events start.</p>
                </div>
                <div class="toggle {{ ($s['notifications_event_reminders'] ?? '1') === '1' ? 'active' : '' }}"
                     data-toggle
                     data-setting="notifications_event_reminders"></div>
              </div>

            </div>
          </article>

          {{-- Interface & Preferences card --}}
          <article class="card">
            <h2 class="section-title">Interface & Preferences</h2>
            <div class="settings-list">

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Language</h4>
                  <p>Switch between English and Arabic.</p>
                </div>
                <button class="btn small ghost"
                        type="button"
                        id="langToggleSecondary">
                  Toggle EN / AR
                </button>
              </div>

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Theme</h4>
                  <p>Toggle between light and dark mode.</p>
                </div>
                <button class="btn small ghost"
                        type="button"
                        id="themeToggleSecondary">
                  Toggle Theme
                </button>
              </div>

            </div>
          </article>
        </section>

        <!-- ROW 2: Security | Account Management -->
        <section class="row">
          {{-- Security card --}}
          <article class="card">
            <h2 class="section-title">Security</h2>
            <div class="settings-list">

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Logout From All Devices</h4>
                  <p>Log out from all active sessions on other devices.</p>
                </div>
                <form method="POST" action="{{ route('settings.logoutAll') }}">
                  @csrf
                  <button class="btn small" type="submit">Logout All</button>
                </form>
              </div>

            </div>
          </article>

          {{-- Account Management card --}}
          <article class="card">
            <h2 class="section-title">Account Management</h2>
            <div class="settings-list">

              <div class="setting-item">
                <div class="setting-info">
                  <h4>Delete Account</h4>
                  <p>Permanently delete your account and information.</p>
                </div>
                <form method="POST" action="{{ route('settings.deleteAccount') }}"
                      onsubmit="return confirm('Are you sure you want to delete your account?');">
                  @csrf
                  @method('DELETE')
                  <button class="btn small danger" type="submit">Delete</button>
                </form>
              </div>

            </div>
          </article>
        </section>
      </main>
    </div> {{-- .wrap (other roles) --}}
  @endif

  <script>
    window.WORKER_SETTINGS_UPDATE_URL = "{{ route('settings.update') }}";
    window.WORKER_SETTINGS_LOGOUT_ALL = "{{ route('settings.logoutAll') }}";
    window.WORKER_SETTINGS_DELETE_URL = "{{ route('settings.deleteAccount') }}";
    window.WORKER_SETTINGS = @json($settings ?? []);
    window.CSRF_TOKEN = "{{ csrf_token() }}";
  </script>

  {{-- Settings JS --}}
  <script src="{{ asset('js/settings.js') }}" defer></script>
  <script src="{{ asset('js/notify-poll.js') }}" defer></script>
</body>
</html>
