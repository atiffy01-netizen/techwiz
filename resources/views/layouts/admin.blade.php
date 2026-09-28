<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Campus Coin Admin Portal — System Management & Analytics">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Admin Portal') — Campus Coin</title>
  
  <!-- CSS Framework & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/design-tokens.css') }}">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  
  <!-- Chart.js for Statistics -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    /* Admin layout custom adjustments */
    .admin-nav-item i { font-size: 1.05rem; }
  </style>
</head>
<body>
<div class="cc-app-layout">
  <div class="cc-sidebar-overlay" onclick="Sidebar.toggle()"></div>

  <!-- ADMIN SIDEBAR -->
  <aside class="cc-sidebar">
    <a href="{{ route('admin.dashboard') }}" class="cc-sidebar-brand text-decoration-none d-flex align-items-center gap-2">
      <img src="{{ asset('images/campus-coin-logo-dark.png') }}" data-light-src="{{ asset('images/campus-coin-logo-dark.png') }}" data-dark-src="{{ asset('images/campus-coin-logo-dark.png') }}" class="cc-brand-logo" alt="Campus Coin Admin">
      <span class="badge bg-warning text-dark font-bold" style="font-size:0.65rem;letter-spacing:0.04em;">ADMIN</span>
    </a>

    <nav class="cc-sidebar-nav">
      <div class="cc-nav-section-title">System Overview</div>
      <a href="{{ route('admin.dashboard') }}" class="cc-nav-item admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i> Dashboard
      </a>
      <a href="{{ route('admin.users.index') }}" class="cc-nav-item admin-nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
        <i class="bi bi-people-fill"></i> User Accounts
      </a>
      <a href="{{ route('admin.categories.index') }}" class="cc-nav-item admin-nav-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
        <i class="bi bi-tags-fill"></i> System Categories
      </a>
      <a href="{{ route('admin.tip-templates.index') }}" class="cc-nav-item admin-nav-item {{ request()->routeIs('admin.tip-templates.*') ? 'active' : '' }}">
        <i class="bi bi-megaphone-fill"></i> Tip &amp; Announcements
      </a>
      <a href="{{ route('admin.statistics.index') }}" class="cc-nav-item admin-nav-item {{ request()->routeIs('admin.statistics.*') ? 'active' : '' }}">
        <i class="bi bi-bar-chart-line-fill"></i> Usage Statistics
      </a>

      <div class="cc-nav-section-title">Student App</div>
      <a href="{{ route('dashboard') }}" class="cc-nav-item" target="_blank" rel="noopener">
        <i class="bi bi-box-arrow-up-right"></i> View Student App
      </a>

      <div class="cc-nav-section-title">Admin Account</div>
      <a href="{{ route('profile') }}" class="cc-nav-item {{ request()->routeIs('profile') ? 'active' : '' }}">
        <i class="bi bi-person-gear"></i> Admin Profile
      </a>
    </nav>

    <div class="cc-sidebar-footer">
      <div class="d-flex align-items-center justify-content-between">
        <a href="{{ route('profile') }}" class="d-flex align-items-center gap-2 text-decoration-none" style="color:inherit;min-width:0;">
          <div class="cc-avatar" style="width:34px;height:34px;font-size:var(--fs-xs);background:linear-gradient(135deg, #d97706, #b45309);color:#fff;">
            {{ auth()->user()->initials ?? 'AD' }}
          </div>
          <div style="min-width:0;">
            <div style="font-size:var(--fs-xs);font-weight:var(--fw-bold);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              {{ auth()->user()->name }}
            </div>
            <div style="font-size:var(--fs-2xs);color:var(--cc-text-muted);">Administrator</div>
          </div>
        </a>
        <button class="btn-cc btn-cc-ghost btn-cc-sm" style="color:var(--cc-expense);" onclick="Modal.open('logoutModal')" title="Logout">
          <i class="bi bi-box-arrow-left"></i>
        </button>
      </div>
    </div>
  </aside>

  <!-- MAIN ADMIN CONTENT -->
  <main class="cc-main-content">
    <header class="cc-topbar">
      <div class="cc-topbar-left">
        <button class="cc-mobile-menu-btn" aria-label="Toggle sidebar" onclick="Sidebar.toggle()">
          <i class="bi bi-list"></i>
        </button>
        <div>
          <div class="cc-breadcrumb">
            <span>Admin</span>
            <i class="bi bi-chevron-right cc-breadcrumb-sep"></i>
            <span class="active">@yield('page_title', 'Dashboard')</span>
          </div>
          <h1 class="cc-page-title" style="font-size:1.25rem;">@yield('page_title', 'Dashboard')</h1>
        </div>
      </div>

      <div class="cc-topbar-right">
        <!-- Live status indicator -->
        <span class="d-none d-md-inline-flex align-items-center gap-1 badge bg-success-subtle text-success px-2 py-1" style="font-size:0.75rem;">
          <span style="width:7px;height:7px;border-radius:50%;background:#10b981;display:inline-block;"></span>
          System Live
        </span>

        <!-- Theme Toggle -->
        <button class="cc-theme-toggle btn-cc btn-cc-ghost btn-cc-icon" onclick="ThemeManager.toggle()" title="Toggle Dark/Light Mode" aria-label="Toggle theme">
          <i class="bi bi-moon-fill"></i>
        </button>

        <!-- Admin Profile Pill -->
        <div class="d-flex align-items-center gap-2 ps-2 border-start">
          <span class="admin-badge-shield d-none d-sm-inline-flex">
            <i class="bi bi-shield-fill"></i> Super Admin
          </span>
          <a href="{{ route('profile') }}" class="cc-avatar text-decoration-none" style="width:34px;height:34px;font-size:0.8rem;background:linear-gradient(135deg, #d97706, #b45309);color:#fff;" title="{{ auth()->user()->name }}">
            {{ auth()->user()->initials ?? 'AD' }}
          </a>
        </div>
      </div>
    </header>

    <div class="cc-content-body">
      <!-- Flash alerts -->
      @include('partials.alerts')

      <!-- Page Content -->
      @yield('content')
    </div>
  </main>
</div>

<!-- LOGOUT MODAL -->
<div class="cc-modal-overlay" id="logoutModal">
  <div class="cc-modal" style="max-width:380px;">
    <div class="cc-modal-header">
      <h3 class="cc-modal-title"><i class="bi bi-box-arrow-left text-danger me-2"></i> Confirm Logout</h3>
      <button class="cc-modal-close" data-modal-close aria-label="Close modal">&times;</button>
    </div>
    <div class="cc-modal-body text-center">
      <p class="text-secondary mb-0">Are you sure you want to sign out of the administrator portal?</p>
    </div>
    <div class="cc-modal-footer d-flex justify-content-end gap-2">
      <button class="btn-cc btn-cc-secondary btn-cc-sm" data-modal-close>Cancel</button>
      <form action="{{ route('logout') }}" method="POST" style="display:inline;">
        @csrf
        <button type="submit" class="btn-cc btn-cc-danger btn-cc-sm">Sign Out</button>
      </form>
    </div>
  </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script>
  // Initialize UI tokens & components
  document.addEventListener('DOMContentLoaded', () => {
    if (typeof ThemeManager !== 'undefined') ThemeManager.init();
    if (typeof Toast !== 'undefined') Toast.init();
  });
</script>
@stack('scripts')
</body>
</html>
