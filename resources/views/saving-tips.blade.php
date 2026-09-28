<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Personalized Saving Tips — Campus Coin. Real data-driven money-saving recommendations for students.">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Saving Tips — Campus Coin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<div class="cc-app-layout">
  @include('partials.sidebar')

  <!-- MAIN CONTENT -->
  <main class="cc-main-content">
    <header class="cc-topbar">
      <div class="cc-topbar-left">
        <button class="cc-mobile-menu-btn" aria-label="Toggle sidebar" onclick="Sidebar.toggle()"><i class="bi bi-list"></i></button>
        <div>
          <div class="cc-breadcrumb">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <span class="sep"><i class="bi bi-chevron-right"></i></span>
            <span class="current">Saving Tips</span>
          </div>
          <div class="cc-topbar-title">Personalized Saving Tips</div>
        </div>
      </div>
      <div class="cc-topbar-right">
        <!-- NOTIFICATION BELL -->
        <div class="cc-notif-wrapper">
          <button class="cc-notif-bell" id="notifBellBtn" aria-label="Notifications" type="button">
            <i class="bi bi-bell-fill"></i>
            @if($unreadNotificationsCount > 0)
              <span class="cc-notif-badge" id="notifBadge">{{ $unreadNotificationsCount }}</span>
            @endif
          </button>
          <div class="cc-notif-dropdown" id="notifDropdown">
            <div class="cc-notif-dropdown-header">
              <span><i class="bi bi-bell me-1"></i> Notifications</span>
              @if($unreadNotificationsCount > 0)
                <form action="{{ route('notifications.read-all') }}" method="POST" style="display:inline;">
                  @csrf
                  <button type="submit" class="btn-cc btn-cc-ghost btn-cc-xs" style="font-size:11px;">Mark all read</button>
                </form>
              @endif
            </div>
            <div class="cc-notif-dropdown-body">
              @forelse($recentNotifications as $notif)
                <div class="cc-notif-item {{ is_null($notif->read_at) ? 'unread' : '' }}">
                  <div class="cc-notif-icon {{ $notif->type === 'budget_exceeded' ? 'danger' : ($notif->type === 'budget_near_limit' ? 'warning' : 'info') }}">
                    <i class="bi {{ $notif->type === 'budget_exceeded' ? 'bi-exclamation-octagon' : ($notif->type === 'budget_near_limit' ? 'bi-exclamation-triangle' : 'bi-info-circle') }}"></i>
                  </div>
                  <div class="cc-notif-text">
                    <div class="cc-notif-title">{{ $notif->title }}</div>
                    <div class="cc-notif-msg">{{ Str::limit($notif->message, 90) }}</div>
                    <div class="cc-notif-time">{{ $notif->created_at ? $notif->created_at->diffForHumans() : 'Just now' }}</div>
                  </div>
                </div>
              @empty
                <div class="cc-notif-empty">
                  <i class="bi bi-bell-slash" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
                  No notifications yet
                </div>
              @endforelse
            </div>
          </div>
        </div>

        <button class="cc-theme-toggle" aria-label="Toggle theme" onclick="ThemeManager.toggle()"><i class="bi bi-moon-fill"></i></button>
        <a href="{{ route('profile') }}" class="cc-avatar" style="font-size:var(--fs-xs);">{{ $user->initials }}</a>
      </div>
    </header>

    <div class="cc-content">
      @include('partials.alerts')

      <!-- Page Header with Month Selector & Refresh Button -->
      <div class="cc-page-header">
        <div>
          <h1 class="cc-page-title">Personalized Saving Tips</h1>
          <p class="cc-page-subtitle">Data-driven recommendations generated directly from your actual transaction and budget history.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <div class="cc-month-selector">
            <a href="{{ route('saving-tips', ['month' => $prevMonthKey, 'category' => $categoryFilter, 'status' => $statusFilter]) }}" class="cc-month-btn" title="Previous Month"><i class="bi bi-chevron-left"></i></a>
            <span class="cc-month-label">{{ $monthLabel }}</span>
            <a href="{{ route('saving-tips', ['month' => $nextMonthKey, 'category' => $categoryFilter, 'status' => $statusFilter]) }}" class="cc-month-btn" title="Next Month"><i class="bi bi-chevron-right"></i></a>
          </div>

          <form action="{{ route('saving-tips.regenerate') }}" method="POST" class="d-inline">
            @csrf
            <input type="hidden" name="month" value="{{ $currentMonthKey }}">
            <button type="submit" class="btn-cc btn-cc-secondary" title="Re-analyze spending patterns">
              <i class="bi bi-arrow-repeat"></i> Refresh Tips
            </button>
          </form>
        </div>
      </div>

      <!-- Savings Potential Banner -->
      <div class="cc-card mb-4" style="background:linear-gradient(135deg, rgba(99,102,241,0.18) 0%, rgba(124,58,237,0.12) 50%, rgba(6,182,212,0.08) 100%), var(--cc-surface-card); border: 1px solid var(--cc-border-medium);">
        <div class="cc-card-body">
          <div class="row align-items-center g-3">
            <div class="col-md-8">
              <div style="font-size:var(--fs-xs);color:var(--cc-text-muted);font-weight:var(--fw-semibold);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.5rem;">
                Your Savings Potential for {{ $monthLabel }}
              </div>
              <div class="cc-amount" style="font-size:var(--fs-3xl);font-weight:var(--fw-extrabold);color:var(--cc-text-main);letter-spacing:-.03em;">
                Rs. {{ number_format($totalPotentialSavings, 2) }} 
                <span style="font-size:var(--fs-sm);font-weight:var(--fw-normal);color:var(--cc-text-muted);">/ month</span>
              </div>
              <p style="color:var(--cc-text-secondary);font-size:var(--fs-sm);margin:0.5rem 0 0;">
                @if($totalActiveCount > 0)
                  Apply and pin the recommended tips below to unlock up to Rs. {{ number_format($totalPotentialSavings, 2) }} in potential monthly savings.
                @else
                  Keep logging transactions to discover personalized savings opportunities.
                @endif
              </p>
            </div>
            <div class="col-md-4 text-center text-md-end">
              <div class="cc-ring-gauge" style="background:conic-gradient(#6366F1 0% {{ $appliedPercentage }}%, var(--cc-surface-subtle) {{ $appliedPercentage }}% 100%);margin:0 auto 0.5rem auto;box-shadow:var(--cc-shadow-sm);">
                <div class="cc-ring-gauge-inner" style="background:var(--cc-surface-card);">
                  <span class="cc-amount" style="font-size:var(--fs-xl);font-weight:var(--fw-bold);color:var(--cc-primary);">{{ $appliedPercentage }}%</span>
                  <span style="font-size:10px;color:var(--cc-text-muted);font-weight:600;">Pinned</span>
                </div>
              </div>
              <div style="font-size:var(--fs-xs);color:var(--cc-text-muted);">{{ $appliedCount }} of {{ $totalActiveCount }} active tips pinned</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters Row -->
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <!-- Status Tab Pills -->
        <div class="d-flex gap-2 flex-wrap">
          <a href="{{ route('saving-tips', ['month' => $currentMonthKey, 'category' => $categoryFilter, 'status' => 'all']) }}" 
             class="btn-cc {{ $statusFilter === 'all' ? 'btn-cc-primary' : 'btn-cc-secondary' }} btn-cc-sm">
            All Active ({{ $activeTips->count() }})
          </a>
          <a href="{{ route('saving-tips', ['month' => $currentMonthKey, 'category' => $categoryFilter, 'status' => 'recommended']) }}" 
             class="btn-cc {{ $statusFilter === 'recommended' ? 'btn-cc-primary' : 'btn-cc-secondary' }} btn-cc-sm">
            Recommended ({{ $recommendedTips->count() }})
          </a>
          <a href="{{ route('saving-tips', ['month' => $currentMonthKey, 'category' => $categoryFilter, 'status' => 'pinned']) }}" 
             class="btn-cc {{ $statusFilter === 'pinned' ? 'btn-cc-primary' : 'btn-cc-secondary' }} btn-cc-sm">
            <i class="bi bi-pin-fill text-warning"></i> Pinned ({{ $pinnedTips->count() }})
          </a>
          @if($dismissedTips->isNotEmpty())
            <a href="{{ route('saving-tips', ['month' => $currentMonthKey, 'category' => $categoryFilter, 'status' => 'dismissed']) }}" 
               class="btn-cc {{ $statusFilter === 'dismissed' ? 'btn-cc-primary' : 'btn-cc-secondary' }} btn-cc-sm">
              <i class="bi bi-eye-slash"></i> Dismissed ({{ $dismissedTips->count() }})
            </a>
          @endif
        </div>

        <!-- Category Dropdown Filter -->
        <div class="d-flex align-items-center gap-2">
          <label for="categorySelectFilter" class="text-secondary" style="font-size:var(--fs-xs);white-space:nowrap;">Category:</label>
          <select class="cc-select" style="width:auto;min-width:160px;" id="categorySelectFilter" onchange="window.location.href=this.value;">
            <option value="{{ route('saving-tips', ['month' => $currentMonthKey, 'status' => $statusFilter, 'category' => 'all']) }}" {{ $categoryFilter === 'all' ? 'selected' : '' }}>
              All Categories
            </option>
            @foreach($userCategories as $cat)
              <option value="{{ route('saving-tips', ['month' => $currentMonthKey, 'status' => $statusFilter, 'category' => $cat->id]) }}" {{ (string)$categoryFilter === (string)$cat->id ? 'selected' : '' }}>
                {{ $cat->name }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      <!-- TIP CARDS SECTION -->
      @php
        $displayTips = match($statusFilter) {
          'pinned' => $pinnedTips,
          'recommended' => $recommendedTips,
          'dismissed' => $dismissedTips,
          default => $activeTips,
        };
      @endphp

      @if($displayTips->isEmpty())
        <div class="cc-card py-5 text-center">
          <div style="width:56px;height:56px;border-radius:50%;background:var(--cc-surface-tint);color:var(--cc-primary);display:inline-flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:1rem;">
            <i class="bi bi-lightbulb"></i>
          </div>
          @if(!$hasSufficientHistory && $totalTransactionsCount < 5)
            <h3 class="font-bold mb-1" style="font-size:var(--fs-md);">Keep tracking your expenses</h3>
            <p class="text-secondary mb-3" style="font-size:var(--fs-xs);max-width:420px;margin-left:auto;margin-right:auto;">
              Keep tracking your expenses to unlock more personalized saving tips. As your transaction history grows, Campus Coin compares spending against historical averages and budgets.
            </p>
            <div class="d-flex justify-content-center gap-2">
              <a href="{{ route('transactions.create') }}" class="btn-cc btn-cc-primary btn-cc-sm">
                <i class="bi bi-plus-lg"></i> Add Transaction
              </a>
              <a href="{{ route('budgets') }}" class="btn-cc btn-cc-secondary btn-cc-sm">
                <i class="bi bi-bullseye"></i> Set Budgets
              </a>
            </div>
          @elseif($statusFilter === 'pinned')
            <h3 class="font-bold mb-1" style="font-size:var(--fs-md);">No pinned tips yet</h3>
            <p class="text-secondary mb-3" style="font-size:var(--fs-xs);max-width:380px;margin-left:auto;margin-right:auto;">
              Pin useful recommendations to keep them easily accessible at the top of your tips and dashboard.
            </p>
            <a href="{{ route('saving-tips', ['month' => $currentMonthKey, 'status' => 'all']) }}" class="btn-cc btn-cc-primary btn-cc-sm">
              View Active Tips
            </a>
          @elseif($statusFilter === 'dismissed')
            <h3 class="font-bold mb-1" style="font-size:var(--fs-md);">No dismissed tips</h3>
            <p class="text-secondary mb-0" style="font-size:var(--fs-xs);">
              Tips you dismiss will appear here if you ever wish to restore them.
            </p>
          @else
            <h3 class="font-bold mb-1" style="font-size:var(--fs-md);">No saving tips found</h3>
            <p class="text-secondary mb-3" style="font-size:var(--fs-xs);max-width:380px;margin-left:auto;margin-right:auto;">
              All your spending in {{ $monthLabel }} is well-balanced! Continue maintaining your current habits.
            </p>
            <a href="{{ route('transactions.create') }}" class="btn-cc btn-cc-secondary btn-cc-sm">
              <i class="bi bi-plus-lg"></i> Record Activity
            </a>
          @endif
        </div>
      @else
        <!-- Tips Grid -->
        <div class="row g-4 mb-4">
          @foreach($displayTips as $tip)
            <div class="col-md-6 col-lg-4" id="tip-card-{{ $tip->id }}">
              <div class="cc-card h-100 d-flex flex-column" style="border-color:{{ $tip->is_pinned ? 'rgba(245,158,11,0.5)' : 'var(--cc-border-subtle)' }};box-shadow:{{ $tip->is_pinned ? '0 4px 14px rgba(245,158,11,0.1)' : 'none' }};">
                <div class="cc-card-body flex-1 p-3">
                  <!-- Badges Header -->
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="cc-badge {{ $tip->type_badge_class }}">
                      <i class="bi {{ $tip->type_icon }}"></i> {{ $tip->category ? $tip->category->name : $tip->type_label }}
                    </span>
                    @if($tip->potential_savings > 0)
                      <span class="cc-badge cc-badge-income">
                        Save Rs. {{ number_format($tip->potential_savings, 2) }}
                      </span>
                    @elseif($tip->is_pinned)
                      <span class="cc-badge cc-badge-warning"><i class="bi bi-pin-fill"></i> Pinned</span>
                    @endif
                  </div>

                  <!-- Title & Message -->
                  <h3 class="h6 font-bold mb-2" style="color:var(--cc-text-main);">
                    {{ $tip->title }}
                  </h3>
                  <p class="text-secondary mb-3" style="font-size:var(--fs-sm);line-height:1.5;">
                    {{ $tip->message }}
                  </p>

                  <!-- Dynamic Context Pill (Metadata) -->
                  @if(!empty($tip->metadata))
                    <div class="p-2" style="background:var(--cc-surface-subtle);border-radius:var(--radius-sm);font-size:var(--fs-xs);margin-bottom:0.75rem;border:1px solid var(--cc-border-subtle);">
                      @if(isset($tip->metadata['historical_average']) && isset($tip->metadata['current_spent']))
                        <strong>Current:</strong> Rs. {{ number_format($tip->metadata['current_spent'], 2) }} &bull; 
                        <strong>Avg:</strong> Rs. {{ number_format($tip->metadata['historical_average'], 2) }}
                      @elseif(isset($tip->metadata['limit_amount']) && isset($tip->metadata['spent_amount']))
                        <strong>Spent:</strong> Rs. {{ number_format($tip->metadata['spent_amount'], 2) }} &bull; 
                        <strong>Limit:</strong> Rs. {{ number_format($tip->metadata['limit_amount'], 2) }}
                      @elseif(isset($tip->metadata['percentage']))
                        <strong>Share:</strong> {{ $tip->metadata['percentage'] }}% of monthly expenses
                      @elseif(isset($tip->metadata['spike_amount']))
                        <strong>Spike:</strong> Rs. {{ number_format($tip->metadata['spike_amount'], 2) }}
                      @elseif(isset($tip->metadata['net_savings']))
                        <strong>Surplus:</strong> Rs. {{ number_format($tip->metadata['net_savings'], 2) }}
                      @endif
                    </div>
                  @endif
                </div>

                <!-- Action Footer -->
                <div class="p-3 border-top d-flex justify-content-between align-items-center gap-2" style="border-color:var(--cc-border-subtle)!important;background:var(--cc-surface);">
                  @if(is_null($tip->dismissed_at))
                    <!-- Pin / Unpin Button -->
                    @if($tip->is_pinned)
                      <button type="button" class="btn-cc btn-cc-warning btn-cc-sm" style="background:var(--cc-warning-bg);color:var(--cc-warning);border-color:rgba(245,158,11,.3);" onclick="toggleTipPin({{ $tip->id }}, false)">
                        <i class="bi bi-pin-fill"></i> Pinned ✓
                      </button>
                    @else
                      <button type="button" class="btn-cc btn-cc-primary btn-cc-sm" onclick="toggleTipPin({{ $tip->id }}, true)">
                        <i class="bi bi-pin-angle"></i> Pin Tip
                      </button>
                    @endif

                    <!-- Dismiss Button -->
                    <button type="button" class="btn-cc btn-cc-ghost btn-cc-sm text-secondary" onclick="dismissTipAction({{ $tip->id }})" title="Dismiss this tip">
                      <i class="bi bi-x-lg"></i> Dismiss
                    </button>
                  @else
                    <!-- Restore Button (for dismissed tips) -->
                    <button type="button" class="btn-cc btn-cc-secondary btn-cc-sm w-100" onclick="restoreTipAction({{ $tip->id }})">
                      <i class="bi bi-arrow-counterclockwise"></i> Restore to Active Tips
                    </button>
                  @endif
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif

    </div><!-- /.cc-content -->
  </main>
</div>

<!-- LOGOUT MODAL -->
<div class="cc-modal-overlay" id="logoutModal">
  <div class="cc-modal" style="max-width:380px;">
    <div class="cc-modal-header">
      <h3 class="cc-card-title">Confirm Logout</h3>
      <button class="btn-cc btn-cc-ghost btn-cc-sm" data-modal-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="cc-card-body text-center">
      <div style="width:52px;height:52px;border-radius:50%;background:var(--cc-warning-bg);color:var(--cc-warning);display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin:0 auto 1rem;">
        <i class="bi bi-box-arrow-left"></i>
      </div>
      <p class="text-secondary mb-0" style="font-size:var(--fs-sm);">Are you sure you want to end your Campus Coin session?</p>
    </div>
    <div class="cc-card-footer d-flex justify-content-end gap-2">
      <button class="btn-cc btn-cc-secondary btn-cc-sm" data-modal-close>Cancel</button>
      <form action="{{ route('logout') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn-cc btn-cc-danger btn-cc-sm">Logout</button>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script>
// Notification dropdown toggle
document.addEventListener('DOMContentLoaded', function() {
  const bell = document.getElementById('notifBellBtn');
  const dropdown = document.getElementById('notifDropdown');
  if (bell && dropdown) {
    bell.addEventListener('click', function(e) {
      e.stopPropagation();
      dropdown.classList.toggle('open');
    });
    document.addEventListener('click', function(e) {
      if (!dropdown.contains(e.target) && e.target !== bell) {
        dropdown.classList.remove('open');
      }
    });
  }
});

// AJAX Pin/Unpin
function toggleTipPin(tipId, shouldPin) {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const url = shouldPin ? `/saving-tips/${tipId}/pin` : `/saving-tips/${tipId}/unpin`;

  fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': token,
      'Accept': 'application/json'
    }
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      if (typeof Toast !== 'undefined') {
        Toast.success(shouldPin ? 'Tip Pinned' : 'Tip Unpinned', data.message);
      }
      setTimeout(() => window.location.reload(), 250);
    }
  })
  .catch(err => {
    console.error(err);
    if (typeof Toast !== 'undefined') Toast.error('Error', 'Failed to update tip status.');
  });
}

// AJAX Dismiss
function dismissTipAction(tipId) {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  fetch(`/saving-tips/${tipId}/dismiss`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': token,
      'Accept': 'application/json'
    }
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      const card = document.getElementById(`tip-card-${tipId}`);
      if (card) {
        card.style.transition = 'all 0.3s ease';
        card.style.opacity = '0';
        card.style.transform = 'scale(0.95)';
        setTimeout(() => {
          card.remove();
          if (typeof Toast !== 'undefined') Toast.info('Dismissed', 'Tip dismissed.');
          // If no cards left, reload to show empty state
          if (document.querySelectorAll('[id^="tip-card-"]').length === 0) {
            window.location.reload();
          }
        }, 300);
      }
    }
  })
  .catch(err => {
    console.error(err);
    if (typeof Toast !== 'undefined') Toast.error('Error', 'Failed to dismiss tip.');
  });
}

// AJAX Restore
function restoreTipAction(tipId) {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  fetch(`/saving-tips/${tipId}/restore`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': token,
      'Accept': 'application/json'
    }
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      if (typeof Toast !== 'undefined') {
        Toast.success('Restored', 'Tip restored to active list.');
      }
      setTimeout(() => window.location.reload(), 250);
    }
  })
  .catch(err => {
    console.error(err);
    if (typeof Toast !== 'undefined') Toast.error('Error', 'Failed to restore tip.');
  });
}
</script>
</body>
</html>
