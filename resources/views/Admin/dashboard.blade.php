{{-- resources/views/Admin/dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale()==='ar' ? 'rtl' : 'ltr' }}" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - VolunteerHub</title>
    <script src="{{ asset('js/preferences.js') }}" defer></script>
    <link rel="stylesheet" href="{{ asset('css/Admin/dashboard.css') }}">
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
                <h1>Admin Dashboard</h1>
                <p>System overview and management</p>
            </div>

            <div class="header-actions">
                {{-- Header Logout --}}
                @if(Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}" class="logout-form">
                        @csrf
                        <button type="submit" class="icon-btn logout-btn">Logout</button>
                    </form>
                @endif
            </div>
        </div>

               {{-- Stats --}}
        <div class="stats-grid">
            {{-- Total Volunteers --}}
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Total Volunteers</span>
                    <div class="stat-icon" style="background:rgba(79,124,255,.18);color:var(--primary)">👥</div>
                </div>
                <div class="stat-value">{{ number_format($totalVolunteers) }}</div>
                <div class="stat-change positive">Active registered volunteers</div>
            </div>

            {{-- Total Paid Workers --}}
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Total Paid Workers</span>
                    <div class="stat-icon" style="background:rgba(54,211,153,.18);color:var(--success)">💼</div>
                </div>
                <div class="stat-value">{{ number_format($totalPaidWorkers) }}</div>
                <div class="stat-change">Paid staff members</div>
            </div>

            {{-- Total Employees --}}
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Total Clients</span>
                    <div class="stat-icon" style="background:rgba(156,108,255,.18);color:var(--accent)">👔</div>
                </div>
                <div class="stat-value">{{ number_format($totalEmployees) }}</div>
                <div class="stat-change positive">Registered clients</div>
            </div>

            {{-- Total Events --}}
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Total Events</span>
                    <div class="stat-icon" style="background:rgba(244,191,80,.18);color:var(--warning)">📅</div>
                </div>
                <div class="stat-value">{{ number_format($totalEvents) }}</div>
                <div class="stat-change positive">All events created</div>
            </div>
        </div>

        {{-- Performance Insights --}}
<div class="charts-section">

  {{-- Top Workers & Volunteers --}}
  <div class="chart-card">
      <h2 class="chart-title">Top Workers & Volunteers Rating</h2>
      <canvas id="workersRatingChart" height="180"></canvas>
  </div>

  {{-- Top Clients Rating --}}
  <div class="chart-card">
      <h2 class="chart-title">Top Clients Rating</h2>
      
      <canvas id="clientsRatingChart" height="180"></canvas>
  </div>

</div>



        {{-- Recent Activity --}}
        <div class="recent-section">

            {{-- Recent Employees --}}
            <div class="activity-card" id="recent-employees-card">
                <h2 class="section-title">Recent Clients</h2>
                <div class="activity-list" id="recent-employees-list">
                    <p class="placeholder">Loading...</p>
                </div>
            </div>

            {{-- Recent Events --}}
            <div class="activity-card" id="recent-events-card">
                <h2 class="section-title">Recent Events</h2>
                <div class="activity-list" id="recent-events-list">
                    <p class="placeholder">Loading...</p>
                </div>
            </div>

        </div> {{-- /.recent-section --}}

    </main>
</div>

@include('notify.widget')

{{-- Expose data for JS --}}
<script>
    window.dashboardData = {
        totalVolunteers: {{ (int) $totalVolunteers }},
        totalPaidWorkers: {{ (int) $totalPaidWorkers }},
        totalEmployees: {{ (int) $totalEmployees }},
        totalEvents: {{ (int) $totalEvents }},
        recentEmployees: @json($recentEmployees),
        recentEvents: @json($recentEvents),

        topWorkersRating: @json($topWorkersRating),
topClientsRating: @json($topClientsRating),

    };
</script>


<script src="{{ asset('js/Admin/dashboard.js') }}"></script>
<script src="{{ asset('js/notify-poll.js') }}" defer></script>

</body>
</html>
