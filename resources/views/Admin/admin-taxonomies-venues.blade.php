<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'ar' ? 'ar' : 'en' }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
      data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Taxonomies & Venues - Admin</title>
  <script>
    window.initialWorkerTypes     = @json($workerTypes ?? []);
    window.initialEventCategories = @json($eventCategories ?? []);
    window.initialVenues          = @json($venues ?? []);
</script>

<script src="{{ asset('js/preferences.js') }}" defer></script>
  {{-- If you use Vite, swap to @vite(['resources/js/...','resources/css/...']) --}}
  <link rel="stylesheet" href="{{ asset('css/Admin/admin-taxonomies-venues.css') }}">

  {{-- Expose backend route URLs to JS (with safe fallbacks if route names are missing) --}}
<script>
  window.VH = {
    routes: {
      // Page
      page: "{{ Route::has('admin.taxonomies-venues.index')
                ? route('admin.taxonomies-venues.index')
                : (Route::has('taxonomies-venues.index')
                    ? route('taxonomies-venues.index')
                    : url('admin/taxonomies-venues')) }}",

      // Worker Types
      wtIndex:  "{{ Route::has('admin.taxonomies-venues.worker-types.index')
                    ? route('admin.taxonomies-venues.worker-types.index')
                    : (Route::has('taxonomies-venues.worker-types.index')
                        ? route('taxonomies-venues.worker-types.index')
                        : url('admin/taxonomies-venues/worker-types')) }}",
      wtStore:  "{{ Route::has('admin.taxonomies-venues.worker-types.store')
                    ? route('admin.taxonomies-venues.worker-types.store')
                    : (Route::has('taxonomies-venues.worker-types.store')
                        ? route('taxonomies-venues.worker-types.store')
                        : url('admin/taxonomies-venues/worker-types')) }}",
      wtDelete: "{{ Route::has('admin.taxonomies-venues.worker-types.destroy')
                    ? route('admin.taxonomies-venues.worker-types.destroy', ['roleType' => '__ID__'])
                    : (Route::has('taxonomies-venues.worker-types.destroy')
                        ? route('taxonomies-venues.worker-types.destroy', ['roleType' => '__ID__'])
                        : url('admin/taxonomies-venues/worker-types/__ID__')) }}",

      // Event Categories
      catIndex:  "{{ Route::has('admin.taxonomies-venues.event-categories.index')
                      ? route('admin.taxonomies-venues.event-categories.index')
                      : (Route::has('taxonomies-venues.event-categories.index')
                          ? route('taxonomies-venues.event-categories.index')
                          : url('admin/taxonomies-venues/event-categories')) }}",
      catStore:  "{{ Route::has('admin.taxonomies-venues.event-categories.store')
                      ? route('admin.taxonomies-venues.event-categories.store')
                      : (Route::has('taxonomies-venues.event-categories.store')
                          ? route('taxonomies-venues.event-categories.store')
                          : url('admin/taxonomies-venues/event-categories')) }}",
      catDelete: "{{ Route::has('admin.taxonomies-venues.event-categories.destroy')
                      ? route('admin.taxonomies-venues.event-categories.destroy', ['eventCategory' => '__ID__'])
                      : (Route::has('taxonomies-venues.event-categories.destroy')
                          ? route('taxonomies-venues.event-categories.destroy', ['eventCategory' => '__ID__'])
                          : url('admin/taxonomies-venues/event-categories/__ID__')) }}",

      // Venues (NEW)
      venuesIndex:  "{{ Route::has('admin.taxonomies-venues.venues.index')
                        ? route('admin.taxonomies-venues.venues.index')
                        : (Route::has('taxonomies-venues.venues.index')
                            ? route('taxonomies-venues.venues.index')
                            : url('admin/taxonomies-venues/venues')) }}",
      venuesStore:  "{{ Route::has('admin.taxonomies-venues.venues.store')
                        ? route('admin.taxonomies-venues.venues.store')
                        : (Route::has('taxonomies-venues.venues.store')
                            ? route('taxonomies-venues.venues.store')
                            : url('admin/taxonomies-venues/venues')) }}",
      venuesDelete: "{{ Route::has('admin.taxonomies-venues.venues.destroy')
                        ? route('admin.taxonomies-venues.venues.destroy', ['venue' => '__ID__'])
                        : (Route::has('taxonomies-venues.venues.destroy')
                            ? route('taxonomies-venues.venues.destroy', ['venue' => '__ID__'])
                            : url('admin/taxonomies-venues/venues/__ID__')) }}"
    }
  };
</script>

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
          <h1>Taxonomies & Venues</h1>
          <p class="muted">Keep your categories, worker types, and venues consistent across the app.</p>
        </div>

      </div>

      <!-- Venues -->
      <section class="card section-spacer">
        <div class="card-header">
          <div class="card-title">Venues</div>
          <div class="muted">Add a venue and its area (m²). These can feed your auto-staffing calculator.</div>
        </div>
        <div class="card-body">
          <div class="form-row row-spacer">
            <input id="v_name" class="form-input" placeholder="Venue name (e.g., Al-Mahdi Hall)"/>
            <input id="v_city" class="form-input" placeholder="City (optional)"/>
            <input id="v_area" class="form-input" type="number" min="0" placeholder="Area (m²)"/>
            <button id="v_add" class="btn btn-primary btn-sm">Add Venue</button>
          </div>

          <div class="table-container">
            <table class="table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>City</th>
                  <th>Area (m²)</th>
                  <th style="width:140px;text-align:right">Actions</th>
                </tr>
              </thead>
              <tbody id="v_rows"></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- Taxonomies -->
      <section class="grid-2">
        <!-- Categories -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Event Categories</div>
          </div>
          <div class="card-body">
            <div class="form-row">
              <input id="cat_input" class="form-input" placeholder="Add category…"/>
              <button id="cat_add" class="btn btn-primary btn-sm">Add</button>
            </div>
            <div id="cat_list" class="list"></div>
          </div>
        </div>

        <!-- Worker Types -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Worker Types</div>
          </div>
          <div class="card-body">
            <div class="form-row">
              <input id="wt_input" class="form-input" placeholder="Add worker type…"/>
              <button id="wt_add" class="btn btn-primary btn-sm">Add</button>
            </div>
            <div id="wt_list" class="list"></div>
          </div>
        </div>
      </section>
    </main>
  </div>

  {{-- If you use Vite, replace with @vite('resources/js/Admin/admin-taxonomies-venues.js') --}}
  <script src="{{ asset('js/Admin/admin-taxonomies-venues.js') }}" defer></script>
</body>
</html>
