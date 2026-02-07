<!DOCTYPE html>
<html lang="en" dir="ltr" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Client managment</title>
<script src="{{ asset('js/preferences.js') }}" defer></script>
  <link rel="stylesheet" href="{{ asset('css/Admin/employees.css') }}">
  <script src="{{ asset('js/Admin/employees.js') }}" defer></script>
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

  <!-- Main -->
  <main class="main-content">
    <div class="header">
      <div class="header-left">
        <h1>Client Management</h1>
        <p>Manage Client accounts and permissions</p>
      </div>

    </div>

    {{-- Flash / validation --}}
    @if (session('status'))
      <div class="flash success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
      <div class="flash danger">
        <ul>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <!-- Search -->
    <div class="search-section">
      <div class="search-input-wrapper">
        <span class="search-icon">🔍</span>
        <input type="text" class="search-input" placeholder="Search client by name, email, or role..." id="searchInput">
      </div>
    </div>

    <!-- NEW: Filters -->
    <section class="filters-bar">
      <div class="filters-grid">
        <div class="filter-item">
          <label class="filter-label">Sort by Name</label>
          <select id="sortName" class="filter-select">
            <option value="az" selected>A → Z</option>
            <option value="za">Z → A</option>
          </select>
        </div>

        <div class="filter-item">
          <label class="filter-label">Status</label>
          <select id="statusFilter" class="filter-select">
            <option value="all" selected>All</option>
            <option value="active">Active</option>
            <option value="suspended">Suspended</option>

          </select>
        </div>

        <div class="filter-item">
          <label class="filter-label">Year</label>
          <input id="yearFilter" class="filter-input" type="number" min="1900" max="2100" placeholder="YYYY">
        </div>

        <div class="filter-item">
          <label class="filter-label">Month</label>
          <select id="monthFilter" class="filter-select">
            <option value="">—</option>
            <option value="01">Jan</option><option value="02">Feb</option>
            <option value="03">Mar</option><option value="04">Apr</option>
            <option value="05">May</option><option value="06">Jun</option>
            <option value="07">Jul</option><option value="08">Aug</option>
            <option value="09">Sep</option><option value="10">Oct</option>
            <option value="11">Nov</option><option value="12">Dec</option>
          </select>
        </div>

        <div class="filter-item">
          <label class="filter-label">Day</label>
          <select id="dayFilter" class="filter-select">
            <option value="">—</option>
            <!-- 1..31 injected by JS for convenience -->
          </select>
        </div>

        <div class="filter-actions">
          <button id="clearFilters" class="btn btn-secondary btn-sm">Clear</button>
        </div>
      </div>
      
    </section>

    <!-- Cards -->
    <div class="employees-grid" id="employeesGrid"><!-- rendered by JS --></div>
  </main>
</div>

<script>window.initialEmployees = @json($employees ?? []);</script>
@include('notify.widget')
<script src="{{ asset('js/notify-poll.js') }}" defer></script>

</body>
</html>
