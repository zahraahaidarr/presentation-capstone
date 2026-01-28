<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Employee Content</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="{{ asset('css/employee/content.css') }}">
</head>
<body>

<div class="container">
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

  <main class="main-content"
        id="contentPage"
        data-api="{{ url('/employee/content') }}"
              data-comments-url="{{ url('/employee/content/comments') }}"
        data-delete-post-template="{{ url('/employee/content/posts') }}/:id"
        data-delete-reel-template="{{ url('/employee/content/reels') }}/:id"
        data-delete-story-template="{{ url('/employee/content/stories') }}/:id"
  >
    <div class="header">
      <div class="header-left">
        <h1>Create Content</h1>
        <p>Publish posts, reels, and stories.</p>
      </div>
    </div>

    {{-- Flash messages --}}
@if(session('ok'))
  <div class="notice success">{{ session('ok') }}</div>
@endif

@if(session('warning'))
  <div class="notice warning">{{ session('warning') }}</div>
@endif

@if(session('error'))
  <div class="notice danger">{{ session('error') }}</div>
@endif


    <div class="card">
      <nav class="tabs">
        <button class="tab active" data-tab="posts" type="button">Posts</button>
        <button class="tab" data-tab="reels" type="button">Reels</button>
        <button class="tab" data-tab="stories" type="button">Stories</button>
      </nav>

      {{-- POSTS --}}
      <section class="tabPane" id="tab-posts">
<form id="postForm" class="form" method="POST" action="{{ route('content.posts.store') }}" enctype="multipart/form-data">
          @csrf

          <div class="grid">
            <div class="field">
  <label>Event</label>

  <select name="event_id" required>
    <option value="" disabled selected>Select your event...</option>

    @forelse($events as $ev)
      <option value="{{ $ev->event_id }}" {{ old('event_id') == $ev->event_id ? 'selected' : '' }}>
        {{ $ev->title }} 
      </option>
    @empty
      {{-- no options --}}
    @endforelse
  </select>

  @if($events->isEmpty())
    <div class="muted" style="margin-top:8px;">
      You have no events yet. Create an event first.
    </div>
  @endif
</div>




            <div class="field">
              <label>Media (optional image)</label>
              <input name="media" type="file" accept="image/*">
            </div>

            <div class="field full">
              <label>Caption</label>
              <textarea name="content" rows="5" placeholder="Write something..." required></textarea>
            </div>
          </div>

          <button class="btn btn-primary" type="submit">Publish Post</button>
        </form>

        <div class="listTitle">Your latest posts</div>
        <div id="postsList" class="items"></div>
      </section>

      {{-- REELS --}}
      <section class="tabPane hidden" id="tab-reels">
<form id="reelForm" class="form" method="POST" action="{{ route('content.reels.store') }}" enctype="multipart/form-data">
          @csrf

          <div class="grid">
            <div class="field">
              <label>Video (mp4/mov/webm)</label>
              <input name="video" type="file" accept="video/*" required>
            </div>

            <div class="field full">
              <label>Caption (optional)</label>
              <textarea name="caption" rows="3" placeholder="Write a caption..."></textarea>
            </div>
          </div>

          <button class="btn btn-primary" type="submit">Upload Reel</button>
        </form>

        <div class="listTitle">Your latest reels</div>
        <div id="reelsList" class="items"></div>
      </section>

      {{-- STORIES --}}
      <section class="tabPane hidden" id="tab-stories">
<form id="storyForm" class="form" method="POST" action="{{ route('content.stories.store') }}" enctype="multipart/form-data">
          @csrf

          <div class="grid">
            <div class="field">
              <label>Story media (image/video)</label>
              <input name="media" type="file" accept="image/*,video/*" required>
            </div>

            
          </div>

          <button class="btn btn-primary" type="submit">Upload Story</button>
        </form>

        <div class="listTitle">Your latest stories</div>
        <div id="storiesList" class="items"></div>
      </section>
    </div>
  </main>
</div>
<div id="commentsModal" class="cModal hidden" aria-hidden="true">
  <div class="cModalOverlay" data-close="1"></div>

  <div class="cModalBox" role="dialog" aria-modal="true">
    <div class="cModalHeader">
      <div class="cModalTitle">Comments</div>
      <button type="button" class="cModalClose" data-close="1">✕</button>
    </div>

    <div id="commentsModalBody" class="cModalBody">
      <div class="muted">Loading...</div>
    </div>
  </div>
</div>


<script src="{{ asset('js/employee/content.js') }}"></script>
</body>
</html>
