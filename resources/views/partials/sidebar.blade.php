<div class="cc-sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<aside class="cc-sidebar" id="sidebar">
  <a href="{{ route('dashboard') }}" class="cc-sidebar-brand text-decoration-none">
    <img src="{{ asset('images/campus-coin-logo-dark.png') }}" data-light-src="{{ asset('images/campus-coin-logo-dark.png') }}" data-dark-src="{{ asset('images/campus-coin-logo-dark.png') }}" class="cc-brand-logo" alt="Campus Coin">
  </a>

  <nav class="cc-sidebar-nav">
    <div class="cc-nav-section-title">Main</div>
    <a href="{{ route('dashboard') }}" class="cc-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
      <i class="bi bi-speedometer2"></i> <span>Dashboard </span>
    </a>
    <a href="{{ route('transactions') }}" class="cc-nav-item {{ request()->routeIs('transactions') && !request()->routeIs('transactions.create') ? 'active' : '' }}">
      <i class="bi bi-arrow-left-right"></i> <span>Transactions</span>
    </a>
    <a href="{{ route('transactions.create') }}" class="cc-nav-item {{ request()->routeIs('transactions.create') ? 'active' : '' }}">
      <i class="bi bi-plus-circle"></i> <span>Add Transaction</span>
    </a>
    <a href="{{ route('categories') }}" class="cc-nav-item {{ request()->routeIs('categories') ? 'active' : '' }}">
      <i class="bi bi-tag"></i> <span>Categories</span>
    </a>

    <div class="cc-nav-section-title">Financial</div>
    <a href="{{ route('budgets') }}" class="cc-nav-item {{ request()->routeIs('budgets') ? 'active' : '' }}">
      <i class="bi bi-bullseye"></i> <span>Budgets</span>
    </a>
    <a href="{{ route('reports') }}" class="cc-nav-item {{ request()->routeIs('reports') ? 'active' : '' }}">
      <i class="bi bi-file-earmark-bar-graph"></i> <span>Reports</span>
    </a>
    <a href="{{ route('forecast') }}" class="cc-nav-item {{ request()->routeIs('forecast') ? 'active' : '' }}">
      <i class="bi bi-graph-up-arrow"></i> <span>Forecast</span>
    </a>

    <div class="cc-nav-section-title">AI &amp; Insights</div>
    <a href="{{ route('insights') }}" class="cc-nav-item {{ request()->routeIs('insights') ? 'active' : '' }}">
      <i class="bi bi-stars"></i> <span>AI Insights</span>
    </a>
    <a href="{{ route('saving-tips') }}" class="cc-nav-item {{ request()->routeIs('saving-tips*') ? 'active' : '' }}">
      <i class="bi bi-lightbulb"></i> <span>Saving Tips</span>
    </a>

    <div class="cc-nav-section-title">Personal</div>
    <a href="{{ route('bookmarks') }}" class="cc-nav-item {{ request()->routeIs('bookmarks') ? 'active' : '' }}">
      <i class="bi bi-bookmark-star"></i> <span>Bookmarks</span>
    </a>
    <a href="{{ route('profile') }}" class="cc-nav-item {{ request()->routeIs('profile') ? 'active' : '' }}">
      <i class="bi bi-person"></i> <span>Profile</span>
    </a>
    <a href="{{ route('settings') }}" class="cc-nav-item {{ request()->routeIs('settings') ? 'active' : '' }}">
      <i class="bi bi-gear"></i> <span>Settings</span>
    </a>

    @if(auth()->user() && auth()->user()->isAdmin())
      <div class="cc-nav-section-title">Admin</div>
      <a href="{{ route('admin.dashboard') }}" class="cc-nav-item admin-link" style="color:rgba(245,158,11,.9);">
        <i class="bi bi-shield-lock"></i> <span>Admin Portal</span>
      </a>
    @endif
  </nav>

  <!-- Sidebar Footer -->
  <div class="cc-sidebar-footer">
    <div class="d-flex align-items-center justify-content-between gap-2">
      <a href="{{ route('profile') }}" class="d-flex align-items-center gap-2 text-decoration-none" style="min-width:0;flex:1;">
        <div class="cc-avatar" style="width:34px;height:34px;font-size:var(--fs-xs);">
          {{ auth()->user()->initials ?? ($user->initials ?? 'CC') }}
        </div>
        <div style="min-width:0;">
          <div style="font-size:var(--fs-xs);font-weight:var(--fw-bold);color:#ffffff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.25;">
            {{ auth()->user()->name ?? ($user->name ?? 'Student User') }}
          </div>
          <div style="font-size:var(--fs-2xs);color:var(--cc-sidebar-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.2;">
            {{ auth()->user()->program ?? ($user->program ?? (auth()->user()->email ?? 'Student')) }}
          </div>
        </div>
      </a>
      <button class="btn-cc btn-cc-ghost btn-cc-sm" style="color:var(--cc-expense);padding:0.35rem 0.45rem;" onclick="Modal.open('logoutModal')" title="Logout" aria-label="Logout">
        <i class="bi bi-box-arrow-left"></i>
      </button>
    </div>
  </div>
</aside>
