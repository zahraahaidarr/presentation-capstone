{{-- resources/views/worker/event-discovery.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover Events - Worker Portal</title>
<script src="{{ asset('js/preferences.js') }}" defer></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ asset('css/worker/event-discovery.css') }}">
</head>
<body>
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

                {{-- 🔥 Dynamic Worker/Volunteer label --}}
                <div class="logo-role">
                    {{ $roleLabel }}
                </div>
            </div>
        </a>
    </div>

    <nav class="nav-section">
        <a href="{{ route('worker.dashboard') }}" class="nav-item">
            <span class="nav-icon">🏠</span>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('worker.events.discover') }}" class="nav-item">
            <span class="nav-icon">🗓️</span>
            <span>Discover Events</span>
        </a>

        <a href="{{ route('worker.reservations') }}" class="nav-item">
            <span class="nav-icon">✅</span>
            <span>My Reservations</span>
        </a>

        <a href="{{ route('worker.submissions') }}" class="nav-item">
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

        @if($worker && !$worker->is_volunteer)
            <a href="{{ route('worker.payments.index') }}"
               class="nav-item {{ request()->routeIs('worker.payments.index') ? 'active' : '' }}">
                <span class="nav-icon">💰</span>
                <span>Payments</span>
            </a>
        @endif

        <a href="{{ route('worker.messages') }}" class="nav-item">
            <span class="nav-icon">💬</span>
            <span>Chat</span>
        </a>

        <a href="{{ route('worker.announcements.index') }}" class="nav-item">
            <span class="nav-icon">📢</span>
            <span>Announcements</span>
        </a>

        <a href="{{ route('settings') }}" class="nav-item">
            <span class="nav-icon">⚙️</span>
            <span>Settings</span>
        </a>
    </nav>
</aside>


    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <div class="header-left">
                <h1>Discover Events</h1>
                <p>Find worker opportunities that match your interests and skills.</p>
                @if(!empty($workerRoleName))
    <p>Your primary role: <strong>{{ $workerRoleName }}</strong></p>
@endif

            </div>
            
        </div>

        <!-- Search and Filters -->
        <div class="search-filter-section">
            <div class="search-bar">
                <div class="search-input-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text"
                           id="searchInput"
                           class="search-input"
                           placeholder="Search events by name, location, or description...">
                </div>
                
            </div>

            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Category</label>
                    <select class="filter-select" id="categoryFilter">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->category_id }}">{{ $cat->name }}</option>

                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Date Range</label>
                    <select class="filter-select" id="dateFilter">
                        <option value="">Any Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Location</label>
                    <select class="filter-select" id="locationFilter">
                        <option value="">All Locations</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc }}">{{ ucfirst($loc) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Availability</label>
                    <select class="filter-select" id="availabilityFilter">
                        <option value="">All Events</option>
                        <option value="open">Open for Applications</option>
                        <option value="full">Full</option>
                    </select>
                </div>
            </div>

            <div class="active-filters" id="activeFilters" style="display:none;"></div>
        </div>

        <!-- Results Header -->
        <div class="results-header">
            <div class="results-count">
                Showing <strong id="resultsCount">0</strong> events
            </div>
            <div class="view-toggle">
                <button class="view-btn active" id="gridViewBtn">Grid</button>
                <button class="view-btn" id="listViewBtn">List</button>
            </div>
        </div>

        <!-- Events Grid -->
        <div class="events-grid" id="eventsGrid"></div>

        <!-- Simple Pagination UI (wired by JS if using AJAX list) -->
        <div class="pagination" id="pagination"></div>
    </main>
</div>

<!-- Event Details Modal -->
<div class="modal" id="eventModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Event Details</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Close</button>
            <button class="btn btn-primary" onclick="applyToEvent()">Apply Now</button>
        </div>
    </div>
</div>

<script>
    // Bootstrapped data for first render
    window.initialEvents      = @json($eventsBootstrap);
    window.eventsListEndpoint = "{{ route('worker.events.discover.list') }}";
    window.workerRoleName     = @json($workerRoleName);

    // 🔴 ADD THESE TWO LINES
    window.applyEventBase = "{{ url('/worker/events') }}";
    window.csrfToken      = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute('content');
</script>

<script src="{{ asset('js/worker/event-discovery.js') }}" defer></script>
@include('notify.widget')
<script src="{{ asset('js/notify-poll.js') }}" defer></script>

</body>
</html>
