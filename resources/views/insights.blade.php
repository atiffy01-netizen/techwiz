<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="AI Monthly Spending Insights — Campus Coin. Advisory pattern analysis and recommendations.">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Insights — Campus Coin</title>
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
            <span class="current">AI Insights</span>
          </div>
          <div class="cc-topbar-title">Monthly Spending Insights</div>
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

      <!-- Page Header with Month Selector & Regenerate Button -->
      <div class="cc-page-header">
        <div>
          <h1 class="cc-page-title">AI Monthly Insights</h1>
          <p class="cc-page-subtitle">Pattern analysis and tailored spending feedback for {{ $monthLabel }}.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <div class="cc-month-selector">
            <a href="{{ route('insights', ['month' => $prevMonthKey]) }}" class="cc-month-btn" title="Previous Month"><i class="bi bi-chevron-left"></i></a>
            <span class="cc-month-label">{{ $monthLabel }}</span>
            <a href="{{ route('insights', ['month' => $nextMonthKey]) }}" class="cc-month-btn" title="Next Month"><i class="bi bi-chevron-right"></i></a>
          </div>

          <form action="{{ route('insights.generate') }}" method="POST" class="d-inline" id="regenerateInsightForm">
            @csrf
            <input type="hidden" name="month" value="{{ $currentMonthKey }}">
            <button type="submit" class="btn-cc btn-cc-primary" id="btnRegenerateInsight">
              <i class="bi bi-stars"></i> Regenerate Insight
            </button>
          </form>
        </div>
      </div>

      <!-- Quick Snapshot Stat Cards -->
      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
          <div class="cc-stat-card expense">
            <div class="cc-stat-label">Monthly Expenses</div>
            <div class="cc-stat-value" style="color:var(--cc-expense);font-size:var(--fs-2xl);">
              Rs. {{ number_format($monthlyExpense, 2) }}
            </div>
            <div class="cc-stat-meta">{{ $totalTxnsCount }} transactions in {{ $monthLabel }}</div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="cc-stat-card income">
            <div class="cc-stat-label">Net Savings</div>
            <div class="cc-stat-value" style="color:{{ $monthlyNetSavings >= 0 ? 'var(--cc-income)' : 'var(--cc-expense)' }};font-size:var(--fs-2xl);">
              Rs. {{ number_format(abs($monthlyNetSavings), 2) }}
            </div>
            <div class="cc-stat-meta">
              <span class="cc-badge {{ $monthlyNetSavings >= 0 ? 'cc-badge-income' : 'cc-badge-expense' }}">
                {{ $monthlyNetSavings >= 0 ? 'Surplus' : 'Deficit' }}
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="cc-stat-card balance">
            <div class="cc-stat-label">Savings Rate</div>
            <div class="cc-stat-value" style="font-size:var(--fs-2xl);color:var(--cc-primary);">
              {{ $savingsRate }}%
            </div>
            <div class="cc-stat-meta">Of total monthly income</div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="cc-stat-card">
            <div class="cc-stat-label">Insight Engine</div>
            <div class="cc-stat-value" style="font-size:var(--fs-lg);color:var(--cc-text-main);">
              @if($currentInsight->isAiGenerated())
                <span class="text-primary"><i class="bi bi-stars"></i> {{ ucfirst($currentInsight->provider ?? 'AI') }}</span>
              @else
                <span class="text-secondary"><i class="bi bi-calculator"></i> Smart Heuristics</span>
              @endif
            </div>
            <div class="cc-stat-meta">Generated {{ $currentInsight->generated_at ? $currentInsight->generated_at->diffForHumans() : 'recently' }}</div>
          </div>
        </div>
      </div>

      <!-- MAIN AI INSIGHT CARD -->
      <div class="cc-card mb-4" style="border-top:4px solid var(--cc-primary);">
        <div class="cc-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <div class="txn-icon" style="background:var(--cc-primary-light);color:var(--cc-primary);font-size:1.1rem;">
              <i class="bi bi-robot"></i>
            </div>
            <div>
              <h2 class="cc-card-title mb-0">Executive Monthly Summary</h2>
              <div style="font-size:var(--fs-2xs);color:var(--cc-text-muted);">
                Advisory analysis for {{ $monthLabel }}
              </div>
            </div>
          </div>
          <span class="cc-badge {{ $currentInsight->isAiGenerated() ? 'cc-badge-primary' : 'cc-badge-secondary' }}">
            <i class="bi {{ $currentInsight->isAiGenerated() ? 'bi-stars' : 'bi-shield-check' }}"></i>
            {{ $currentInsight->isAiGenerated() ? 'AI-Generated' : 'Automated Summary' }}
          </span>
        </div>
        <div class="cc-card-body">
          <!-- Executive Summary Paragraph -->
          <div class="p-3 mb-4 rounded" style="background:var(--cc-surface-subtle);border-left:4px solid var(--cc-primary);font-size:var(--fs-md);line-height:1.6;color:var(--cc-text-main);">
            {{ $currentInsight->summary }}
          </div>

          <div class="row g-4">
            <!-- Key Highlights Column -->
            <div class="col-lg-6">
              <h3 class="h6 font-bold mb-3 d-flex align-items-center gap-2" style="color:var(--cc-text-main);">
                <i class="bi bi-search text-primary"></i> Key Spending Highlights
              </h3>
              @if(!empty($currentInsight->highlights) && count($currentInsight->highlights) > 0)
                <div class="d-flex flex-column gap-2">
                  @foreach($currentInsight->highlights as $highlight)
                    <div class="p-3 rounded d-flex align-items-start gap-2" style="background:var(--cc-surface-subtle);border:1px solid var(--cc-border-subtle);font-size:var(--fs-sm);">
                      <i class="bi bi-check-circle-fill text-primary mt-1" style="font-size:0.9rem;"></i>
                      <span style="color:var(--cc-text-main);">{{ $highlight }}</span>
                    </div>
                  @endforeach
                </div>
              @else
                <p class="text-secondary" style="font-size:var(--fs-sm);">Log more transactions to reveal pattern highlights.</p>
              @endif
            </div>

            <!-- Actionable Suggestions Column -->
            <div class="col-lg-6">
              <h3 class="h6 font-bold mb-3 d-flex align-items-center gap-2" style="color:var(--cc-text-main);">
                <i class="bi bi-lightbulb-fill text-warning"></i> Actionable Suggestions
              </h3>
              @if(!empty($currentInsight->actions) && count($currentInsight->actions) > 0)
                <div class="d-flex flex-column gap-2">
                  @foreach($currentInsight->actions as $action)
                    <div class="p-3 rounded d-flex align-items-start gap-2" style="background:var(--cc-surface-subtle);border:1px solid var(--cc-border-subtle);font-size:var(--fs-sm);">
                      <i class="bi bi-arrow-right-circle-fill text-warning mt-1" style="font-size:0.9rem;"></i>
                      <span style="color:var(--cc-text-main);">{{ $action }}</span>
                    </div>
                  @endforeach
                </div>
              @else
                <p class="text-secondary" style="font-size:var(--fs-sm);">Continue logging spending to receive tailored suggestions.</p>
              @endif
            </div>
          </div>

          <!-- Advisory Notice Footer -->
          <div class="mt-4 pt-3 text-center border-top" style="border-color:var(--cc-border-subtle)!important;">
            <p class="text-secondary mb-0" style="font-size:var(--fs-xs);">
              <i class="bi bi-info-circle me-1"></i>
              <strong>Advisory Notice:</strong> This AI-generated spending insight is provided for educational and personal planning purposes only and does not constitute certified financial advice.
            </p>
          </div>
        </div>
      </div>

      <!-- PAST INSIGHTS HISTORY -->
      @if($pastInsights->isNotEmpty())
        <div class="cc-card">
          <div class="cc-card-header">
            <h2 class="cc-card-title"><i class="bi bi-clock-history me-2" style="color:var(--cc-text-muted);"></i>Past Monthly Insights</h2>
          </div>
          <div class="cc-card-body">
            <div class="row g-3">
              @foreach($pastInsights as $past)
                <div class="col-md-6 col-lg-4">
                  <div class="cc-card h-100 p-3" style="background:var(--cc-surface-subtle);border-color:var(--cc-border-subtle);">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <strong style="color:var(--cc-text-main);font-size:var(--fs-sm);">{{ $past->formatted_month }}</strong>
                      <span class="cc-badge {{ $past->isAiGenerated() ? 'cc-badge-primary' : 'cc-badge-secondary' }}" style="font-size:10px;">
                        {{ $past->isAiGenerated() ? 'AI' : 'Automated' }}
                      </span>
                    </div>
                    <p class="text-secondary mb-3 text-truncate-2" style="font-size:var(--fs-xs);line-height:1.45;">
                      {{ Str::limit($past->summary, 120) }}
                    </p>
                    <a href="{{ route('insights', ['month' => $past->month]) }}" class="btn-cc btn-cc-ghost btn-cc-xs w-100 justify-content-center">
                      View Full {{ $past->formatted_month }} Insight &rarr;
                    </a>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
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
// Notification dropdown
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

  // Regenerate Button Loading State
  const regenForm = document.getElementById('regenerateInsightForm');
  const regenBtn = document.getElementById('btnRegenerateInsight');
  if (regenForm && regenBtn) {
    regenForm.addEventListener('submit', function() {
      regenBtn.disabled = true;
      regenBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Generating with AI...';
    });
  }
});
</script>
</body>
</html>
