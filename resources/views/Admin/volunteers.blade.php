{{-- resources/views/Admin/volunteers.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale()==='ar' ? 'rtl' : 'ltr' }}" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Worker Oversight - Admin Portal</title>
<script src="{{ asset('js/preferences.js') }}" defer></script>
    <link rel="stylesheet" href="{{ asset('css/Admin/volunteers.css') }}">
    <script>window.initialVolunteers = @json($volunteers ?? []);</script>
    <script src="{{ asset('js/Admin/volunteers.js') }}" defer></script>
</head>
<body>
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
                {{ strtoupper($user->role ?? 'USER') }}
            </div>
        </div>
    </a>
</div>



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

    {{-- Main --}}
    <main class="main-content">
        <div class="header">
            <div class="header-left">
                <h1>Worker Oversight</h1>
                <p>Monitor and manage Worker accounts</p>
            </div>
           
        </div>

        {{-- Filters (Approval removed) --}}
        <div class="filters-section">
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Role</label>
                   <select class="filter-select" id="filterRole" onchange="renderVolunteers()">
    <option value="">All Roles</option>
    @foreach($roles as $role)
        <option value="{{ $role->name }}">{{ $role->name }}</option>
    @endforeach
</select>

                </div>

                <div class="filter-group">
                    <label class="filter-label">Location</label>
                    <select class="filter-select" id="filterLocation" onchange="renderVolunteers()">
    <option value="">All Locations</option>
    @foreach($locations as $loc)
        <option value="{{ $loc }}">{{ $loc }}</option>
    @endforeach
</select>

                </div>

                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select class="filter-select" id="filterStatus" onchange="renderVolunteers()">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Table (Approval column removed) --}}
        <div class="table-container">
            <table class="table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Location</th>
                    <th>Events</th>
                    <th>Hours</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody id="volunteersTable"><!-- Rows injected by volunteers.js --></tbody>
            </table>
        </div>
    </main>
</div>

{{-- View Volunteer Modal --}}
<div id="volunteerModal" class="modal hidden">
    <div class="modal-backdrop" onclick="closeVolunteerModal()"></div>

    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title-group">
                <h2 id="vm-name">Workers details</h2>
                <p id="vm-email" class="modal-subtitle"></p>
            </div>
            <button type="button" class="icon-btn modal-close-btn" onclick="closeVolunteerModal()">✕</button>
        </div>

        <div class="modal-body">
            <form class="modal-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label>First name</label>
                        <input id="vm-first_name" type="text" disabled>
                    </div>
                    <div class="form-group">
                        <label>Last name</label>
                        <input id="vm-last_name" type="text" disabled>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input id="vm-phone" type="text" disabled>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <input id="vm-role" type="text" disabled>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input id="vm-location" type="text" disabled>
                    </div>
                    <div class="form-group">
                        <label>Engagement kind</label>
                        <input id="vm-engagement_kind" type="text" disabled>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <input id="vm-is_volunteer" type="text" disabled>
                    </div>
                    <div class="form-group">
                        <label>Account status</label>
                        <input id="vm-status" type="text" disabled>
                    </div>
                    <div class="form-group">
                        <label>Hourly rate</label>
                        <input id="vm-hourly_rate" type="text" disabled>
                    </div>
                    <div class="form-group">
                        <label>Total events</label>
                        <input id="vm-events" type="text" disabled>
                    </div>
                    <div class="form-group">
                        <label>Total hours</label>
                        <input id="vm-hours" type="text" disabled>
                    </div>
                    <div class="form-group">
                        <label>Joined at</label>
                        <input id="vm-joined_at" type="text" disabled>
                    </div>
                </div>

                <div class="certificate-row">
                    <span>Certificate:</span>
                    <a id="vm-certificate_link" href="#" target="_blank" class="certificate-link">
                        No certificate uploaded
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@include('notify.widget')
<script src="{{ asset('js/notify-poll.js') }}" defer></script>

</body>
</html>
