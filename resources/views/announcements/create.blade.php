<!doctype html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Send Announcement</title>
  <script src="{{ asset('js/preferences.js') }}" defer></script>
  <link rel="stylesheet" href="{{ asset('css/announcements/create.css') }}">
</head>

@php
    $user     = auth()->user();
    $roleValue = strtoupper($user->role ?? '');
    $isAdmin   = ($roleValue === 'ADMIN');
@endphp

<body class="{{ $isAdmin ? 'has-admin' : 'has-employee' }}">

{{-- ========================================================
     EMPLOYEE LAYOUT
======================================================== --}}
@if(!$isAdmin)
<div class="app-container">

    <aside class="sidebar employee-sidebar">
        @php($user = Auth::user())

        {{-- USER BLOCK (avatar + name + role) --}}
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

        {{-- 🔁 SAME LINKS AS VOLUNTEER ASSIGNMENT PAGE --}}
        <nav>
            <div class="nav-section">
                {{-- (Volunteer Assignment page has no label here, so left empty to match) --}}

                <a href="{{ Route::has('employee.dashboard') ? route('employee.dashboard') : '#' }}"
                   class="nav-item {{ request()->routeIs('employee.dashboard') ? 'active' : '' }}">
                    <span class="nav-icon">📊</span><span>Dashboard</span>
                </a>

                <a href="{{ Route::has('events.index') ? route('events.index') : '#' }}"
                   class="nav-item">
                    <span class="nav-icon">📅</span><span>Event Management</span>
                </a>

                <a href="{{ route('employee.volunteer.assignment') }}"
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
                   class="nav-item">
                    <span class="nav-icon">💬</span><span>Messages</span>
                </a>

                <a href="{{ route('announcements.create') }}" class="nav-item {{ request()->routeIs('announcements.create') ? 'active' : '' }}">
                    <span class="nav-icon">📢</span><span>Send Announcement</span>
                </a>

                <a href="{{ Route::has('employee.announcements.index') ? route('employee.announcements.index') : '#' }}"
                   class="nav-item">
                    <span class="nav-icon">📢</span><span>Announcements</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-label">Account</div>

                <a href="{{ Route::has('settings') ? route('settings') : '#' }}" class="nav-item">
                    <span class="nav-icon">⚙️</span><span>Settings</span>
                </a>
            </div>
        </nav>
    </aside>

    {{-- EMPLOYEE FORM AREA --}}
    <main class="content-area">
        <div class="container">
            <h1>📢 Send Announcement</h1>

            @if(session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert danger">
                    <ul>
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('announcements.store') }}">
                @csrf

                <label>Title</label>
                <input type="text" name="title" value="{{ old('title') }}" required>

                <label>Description</label>
                <textarea name="body" required>{{ old('body') }}</textarea>

                {{-- Employees cannot select audience --}}
                <input type="hidden" name="audience" value="workers">
                <div class="hint">Audience: Workers you manage</div>

                <button type="submit">Send Announcement</button>
            </form>
        </div>
    </main>
</div>

{{-- ========================================================
     ADMIN LAYOUT
======================================================== --}}
@else
<div class="admin-app">

    <aside class="sidebar admin-sidebar">

        {{-- USER BLOCK --}}
        @php($user = Auth::user())
        <div class="logo">
            <a href="{{ route('profile') }}" class="logo-link">

                @if($user->avatar_path)
                    <img src="{{ asset('storage/'.$user->avatar_path) }}" class="logo-avatar">
                @else
                    <div class="logo-icon">{{ strtoupper(substr($user->first_name ?? 'A', 0, 1)) }}</div>
                @endif

                <div class="logo-id">
                    <div class="logo-name">{{ trim($user->first_name.' '.$user->last_name) }}</div>
                    <div class="logo-role">ADMIN</div>
                </div>
            </a>
        </div>

        {{-- ADMIN NAVIGATION (unchanged) --}}
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

        <nav class="nav-section">
            <div class="nav-label">Account</div>

            <a href="{{ Route::has('settings') ? route('settings') : '#' }}"
               class="nav-item {{ request()->routeIs('settings') ? 'active' : '' }}">
                <span class="nav-icon">🔧</span><span>Settings</span>
            </a>
        </nav>

    </aside>

    {{-- ADMIN FORM AREA --}}
    <main class="admin-content-area">
        <div class="container">
            <h1>📢 Send Announcement</h1>

            @if(session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert danger">
                    <ul>
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('announcements.store') }}">
                @csrf

                <label>Title</label>
                <input type="text" name="title" value="{{ old('title') }}" placeholder="Announcement Title" required>

                <label>Description</label>
                <textarea name="body" required>{{ old('body') }}</textarea>

                <label>Audience</label>
                <select name="audience" required>
                    <option value="workers"   {{ old('audience')==='workers' ? 'selected' : '' }}>Workers</option>
                    <option value="employees" {{ old('audience')==='employees' ? 'selected' : '' }}>Clients</option>
                    <option value="both"      {{ old('audience')==='both' ? 'selected' : '' }}>Both</option>
                </select>

                <button type="submit">Send Announcement</button>
            </form>
        </div>
    </main>

</div>
@endif

<script src="{{ asset('js/announcements/create.js') }}"></script>
@include('notify.widget')
<script src="{{ asset('js/notify-poll.js') }}" defer></script>

</body>
</html>
