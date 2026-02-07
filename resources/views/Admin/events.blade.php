{{-- resources/views/admin/events.blade.php --}}
<!doctype html>
<html
    lang="{{ app()->getLocale() }}"
    dir="{{ app()->getLocale()==='ar' ? 'rtl' : 'ltr' }}"
    data-theme="dark"
>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Event Management • Employee Portal</title>

  <script src="{{ asset('js/preferences.js') }}" defer></script>

  {{-- CSRF for AJAX --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- CSS --}}
  <link rel="stylesheet" href="{{ asset('css/admin/events.css') }}">
</head>
<body>
@php
    $user = auth()->user();

    // Normalize role string to uppercase
    $rawRole = $user->role ?? null;
    $normalizedRole = $rawRole ? strtoupper($rawRole) : null;

    $hasSpatieEmployee =
        method_exists($user, 'hasRole') &&
        ($user->hasRole('EMPLOYEE') || $user->hasRole('employee'));

    $isEmployee = $user && (
        $hasSpatieEmployee ||
        $normalizedRole === 'EMPLOYEE'
    );
@endphp


  <div class="container {{ $isEmployee ? 'layout-employee' : 'layout-admin' }}">
    <!-- Sidebar -->
    @if (! $isEmployee)
        {{-- ========== ADMIN SIDEBAR (unchanged) ========== --}}
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
    @else
        {{-- ========== EMPLOYEE SIDEBAR (same style as admin) ========== --}}
            {{-- Sidebar --}}
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
                    <div class="logo-role">Client</div>
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
                   class="nav-item {{ request()->routeIs('events.*') ? 'active' : '' }}">
                    <span class="nav-icon">📅</span><span>Event Management</span>
                </a>

                <a href="{{ route('employee.volunteer.assignment') }}"
                   class="nav-item {{ request()->routeIs('employee.volunteer.assignment') ? 'active' : '' }}">
                    <span class="nav-icon">👥</span><span>Worker Application</span>
                </a>

                <a href="{{ route('employee.postEventReports.index') }}"
                   class="nav-item {{ request()->routeIs('employee.postEventReports.*') ? 'active' : '' }}">
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
                   class="nav-item {{ request()->routeIs('employee.announcements.*') ? 'active' : '' }}">
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
    @endif


    <!-- Main -->
    <main class="main-content">
      <div class="header">
        <div class="header-left">
          <h1>Event Management</h1>
          <p>Create and manage worker events</p>
        </div>
        <div class="header-actions">
          <button class="btn btn-primary" id="btn_open_create">
            <span>➕</span>Create Event
          </button>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tabs">
        <button class="tab active" data-filter="all">All Events</button>
        <button class="tab" data-filter="draft">Drafts</button>
        <button class="tab" data-filter="published">Published</button>
        <button class="tab" data-filter="completed">Completed</button>
        <button class="tab" data-filter="cancelled">Cancelled</button>
      </div>

      <!-- Events Table -->
      <div class="table-container">
        <table class="table">
          <thead>
          <tr>
            <th>Event Name</th>
            <th>Category</th>
            <th>Date</th>
            <th>Location</th>
            <th>Applicants</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
          </thead>
          <tbody id="eventsTableBody"></tbody>
        </table>
      </div>
    </main>
  </div>

  <!-- Create/Edit Event Modal (Wizard) -->
  <div class="modal" id="eventModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title" id="modalTitle">Create New Event</h2>
        <button class="modal-close" id="btn_close_modal">&times;</button>
      </div>

      <div class="modal-body">
        <div class="wizard-steps">
          <div id="wz1" class="wizard-step active"></div>
          <div id="wz2" class="wizard-step"></div>
          <div id="wz3" class="wizard-step"></div>
        </div>

        <form id="eventForm">
          <!-- STEP 1 -->
          <section id="step1" class="step active">
  <h3 class="h3">Event Space & Auto-Staffing</h3>

  <div class="grid2">
    <div class="field full">
      <label>Venue</label>
      <select id="venue_id" class="form-select">
        <option value="">Select venue...</option>
      </select>
    </div>

    <div class="field">
      <label>Venue Area (m²)</label>
      <input id="venue_area_m2" type="number" min="0" placeholder="Auto-filled" readonly>
    </div>

    <div class="field">
      <label>Expected Attendees</label>
      <input id="expected_attendees" type="number" min="0" placeholder="e.g., 1200">
    </div>

    <div class="field full">
      <label>Event Category</label>
      <select id="wizard_event_category" class="form-select"></select>
    </div>
  </div>
</section>


          <!-- STEP 2 -->
          <section id="step2" class="step">
            <h3 class="h3">Role Capacities</h3>
            <p class="muted">Set how many workers/employees per role.</p>
            <div class="table-container">
              <table class="table table-min">
                <thead>
                  <tr><th>Role</th><th>Capacity</th></tr>
                </thead>
                <tbody id="wizard_role_capacity_rows"></tbody>
              </table>
            </div>
          </section>

          <!-- STEP 3 -->
          <section id="step3" class="step">
            <div class="form-grid">
              <div class="form-group full-width">
                <label class="form-label required">Event Title</label>
                <input type="text" class="form-input" id="eventTitle" required placeholder="Enter event title">
              </div>
              <div class="form-group full-width">
                <label class="form-label required">Description</label>
                <textarea class="form-textarea" id="eventDescription" required placeholder="Describe the event..."></textarea>
              </div>

              <div class="form-group full-width">
                <label class="form-label">Event Image</label>
                <div class="image-upload">
                    <input type="file"
                           class="form-input"
                           id="eventImage"
                           accept="image/*">
                    <small class="muted">
                        Recommended 1200×600px, JPG or PNG, max 2MB.
                    </small>
                    <img id="eventImagePreview"
                         class="image-preview"
                         style="display:none;margin-top:8px;max-height:140px;border-radius:10px;object-fit:cover;">
                </div>
              </div>

              <div class="form-group">
                <label class="form-label required">Category</label>
                <select class="form-select" id="eventCategory" required>
                  <option value="">Select category...</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label required">Location</label>
                <input type="text" class="form-input" id="eventLocation" required placeholder="Event location">
              </div>
              <div class="form-group">
                <label class="form-label required">Date</label>
                <input type="date" class="form-input" id="eventDate" required>
              </div>
              <div class="form-group">
                <label class="form-label required">Time</label>
                <input type="time" class="form-input" id="eventTime" required>
              </div>
              <div class="form-group">
                <label class="form-label required">Duration (hours)</label>
                <input type="number" class="form-input" id="eventDuration" required min="1" step="0.5" placeholder="hours">
              </div>
              <div class="form-group">
                <label class="form-label required">Total Spots</label>
                <input type="number" class="form-input" id="eventSpots" required min="1" placeholder="Spots">
              </div>

              <div class="form-group full-width">
                <div class="roles-section">
                  <div class="roles-header">
                    <label class="form-label required">Roles</label>
                    <button type="button" class="btn btn-secondary btn-sm" id="btn_add_role">Add Role</button>
                  </div>
                  <div id="rolesContainer"></div>
                  <small class="muted">
                    Choose a worker type and set its spots. Leave 0 to skip.
                  </small>
                </div>
              </div>
            </div>
          </section>
        </form>
      </div>

      <div class="wizard-nav">
        <button id="btn_back" class="btn btn-secondary" type="button">Back</button>
        <button id="btn_next" class="btn btn-primary" type="button">Next</button>
        <button id="btn_save_draft" class="btn btn-secondary" type="button" style="display:none">
          Save as Draft
        </button>
        <button id="btn_publish" class="btn btn-primary" type="button" style="display:none">
          Publish Event
        </button>
      </div>
    </div>
  </div>

  {{-- JS bootstrapping --}}
  <script>
    window.csrfToken                         = "{{ csrf_token() }}";
    window.ENDPOINT_CREATE_EVENT             = "{{ route('events.store') }}";

    {{-- AI staffing endpoint (JSON) --}}
    window.ENDPOINT_AI_STAFFING              = "{{ url('/ai/staffing') }}";

    window.ENDPOINT_UPDATE_EVENT_STATUS_BASE = "{{ url('/admin/events') }}";
    window.ENDPOINT_UPDATE_EVENT_BASE        = "{{ url('/events') }}";

    window.initialEvents     = @json($eventsPayload);
    window.initialCategories = @json($categoriesPayload);
    window.initialRoleTypes  = @json($roleTypesPayload);
    window.initialVenues = @json($venuesPayload);
  </script>

  <script src="{{ asset('js/admin/events.js') }}"></script>
  @include('notify.widget')
  <script src="{{ asset('js/notify-poll.js') }}" defer></script>

</body>
</html>
