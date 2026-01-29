<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale()==='ar' ? 'rtl' : 'ltr' }}" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rejected Content - Admin</title>

    <script src="{{ asset('js/preferences.js') }}" defer></script>
    <link rel="stylesheet" href="{{ asset('css/Admin/rejected-content.css') }}">
    <script>window.initialRejected = @json($rejected ?? []);</script>
    <script src="{{ asset('js/Admin/rejected-content.js') }}" defer></script>
</head>
<body>
<div class="container">

    {{-- Sidebar (same style as your page) --}}
    <aside class="sidebar">
        @php($user = Auth::user())

        <div class="logo">
            <a href="{{ Route::has('profile') ? route('profile') : '#' }}" class="logo-link">
                @if($user && $user->avatar_path)
                    <img src="{{ asset('storage/' . ltrim($user->avatar_path, '/')) }}"
                         alt="{{ $user->first_name ?? $user->name ?? 'Profile' }}"
                         class="logo-avatar">
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

            {{-- NEW LINK --}}
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
                <h1>Rejected Content</h1>
                <p>Review AI-rejected posts, reels, and stories.</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="filters-section">
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Type</label>
                    <select class="filter-select" id="filterType">
                        <option value="">All</option>
                        <option value="post">Posts</option>
                        <option value="reel">Reels</option>
                        <option value="story">Stories</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select class="filter-select" id="filterStatus">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div class="filter-group">
                <label class="filter-label">Search (User Name)</label>
<input class="filter-input" id="filterUser" placeholder="e.g. Zahraa Haidar">

                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-container">
            <table class="table">
                <thead>
                <tr>
                    <th>Type</th>
                    <th>User</th>
                    <th>Preview</th>
                    <th>Text</th>
                    <th>Status</th>
                    <th>AI</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody id="rejectedTable"></tbody>
            </table>
        </div>
    </main>
</div>

{{-- Preview Modal (FIXED) --}}
<div id="previewModal" class="modal hidden">
  <div class="modal-backdrop" data-close="1"></div>

  <div class="modal-dialog">
    <div class="modal-header">
      <div class="modal-title-group">
        <h2 id="pm-title">Content Preview</h2>
        <p id="pm-sub" class="modal-subtitle"></p>
      </div>

      <button type="button" class="icon-btn modal-close-btn" data-close="1">✕</button>
    </div>

    <div class="modal-body">
      <div id="pm-media" class="preview-media"></div>
      <div id="pm-text" class="preview-text"></div>
    </div>
  </div>
</div>


</body>
</html>
