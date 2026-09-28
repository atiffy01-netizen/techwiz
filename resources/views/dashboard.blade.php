<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Dashboard — Campus Coin. Your financial snapshot at a glance.">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Dashboard — Campus Coin</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <!-- Bootstrap Grid + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <!-- Campus Coin Design System -->
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<div class="cc-app-layout">
  @include('partials.sidebar')

  <!-- ===================== MAIN CONTENT ===================== -->
  <main class="cc-main-content">

    <!-- ===== TOPBAR ===== -->
    <header class="cc-topbar">
      <div class="cc-topbar-left">
        <button class="cc-mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle sidebar">
          <i class="bi bi-list"></i>
        </button>
        <div>
          <div class="cc-breadcrumb">
            <span class="current">Dashboard</span>
          </div>
          <div class="cc-topbar-title">Financial Dashboard</div>
        </div>
      </div>

      <div class="cc-topbar-right">
        <!-- Search -->
        <form action="{{ route('transactions') }}" method="GET" class="cc-topbar-search" role="search">
          <i class="bi bi-search"></i>
          <input type="text" name="search" placeholder="Search transactions..." aria-label="Search transactions">
        </form>

        <!-- Notification Bell -->
        <div class="cc-notif-wrapper">
          <button class="cc-notif-bell" id="notifBellBtn" aria-label="Notifications" type="button">
            <i class="bi bi-bell-fill"></i>
            @if($unreadNotificationsCount > 0)
              <span class="cc-notif-badge" id="notifBadge">{{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}</span>
            @endif
          </button>
          <div class="cc-notif-dropdown" id="notifDropdown" role="dialog" aria-label="Notifications">
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
                  <div class="cc-notif-text" style="flex:1;min-width:0;">
                    <div class="cc-notif-title">{{ $notif->title }}</div>
                    <div class="cc-notif-msg">{{ Str::limit($notif->message, 90) }}</div>
                    <div class="cc-notif-time">{{ $notif->created_at ? $notif->created_at->diffForHumans() : 'Just now' }}</div>
                  </div>
                  @if(is_null($notif->read_at))
                    <form action="{{ route('notifications.read', $notif->id) }}" method="POST" style="flex-shrink:0;">
                      @csrf
                      <button type="submit" class="cc-icon-btn" style="width:28px;height:28px;font-size:.75rem;" title="Mark as read"><i class="bi bi-check2"></i></button>
                    </form>
                  @endif
                </div>
              @empty
                <div class="cc-notif-empty">
                  <i class="bi bi-bell-slash" style="font-size:1.75rem;display:block;margin-bottom:.625rem;opacity:.4;"></i>
                  No notifications yet
                </div>
              @endforelse
            </div>
          </div>
        </div>

        <!-- Theme Toggle -->
        <button class="cc-theme-toggle" aria-label="Toggle theme" title="Toggle dark/light mode">
          <i class="bi bi-moon-fill"></i>
        </button>

        <!-- Avatar -->
        <a href="{{ route('profile') }}" class="cc-avatar" style="font-size:var(--fs-xs);" title="{{ $user->name }}">
          {{ $user->initials }}
        </a>
      </div>
    </header>

    <!-- ===== PAGE CONTENT ===== -->
    <div class="cc-content">

      @include('partials.alerts')

      {{-- Announcements --}}
      @if(isset($activeAnnouncements) && $activeAnnouncements->isNotEmpty())
        @foreach($activeAnnouncements as $announcement)
          <div class="cc-announcement">
            <div class="d-flex align-items-start gap-2">
              <i class="bi bi-megaphone-fill" style="color:var(--cc-primary);font-size:1rem;margin-top:2px;flex-shrink:0;"></i>
              <div>
                <div style="font-weight:var(--fw-bold);font-size:var(--fs-sm);color:var(--cc-text-main);">{{ $announcement->title }}</div>
                <div style="font-size:var(--fs-xs);color:var(--cc-text-secondary);">{{ $announcement->message }}</div>
              </div>
            </div>
            <button type="button" onclick="this.closest('.cc-announcement').remove()" style="background:none;border:none;color:var(--cc-text-muted);cursor:pointer;font-size:.875rem;padding:.25rem;flex-shrink:0;" aria-label="Dismiss">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
        @endforeach
      @endif

      {{-- ===== VISUAL DASHBOARD HERO BANNER ===== --}}
      <div class="cc-dash-hero">
        <div class="row align-items-center g-4">
          <div class="col-lg-7">
            <div class="cc-dash-hero-badge">
              <i class="bi bi-stars"></i>
              <span>Live Student Financial Hub</span>
            </div>
            <h1 class="cc-dash-hero-title">{{ $greeting }} </h1>
            <p class="cc-dash-hero-sub">
              Your real-time financial snapshot for <strong>{{ $monthLabel }}</strong>. Your lifetime balance is 
              <strong style="color:{{ $availableBalance >= 0 ? 'var(--cc-income)' : 'var(--cc-expense)' }};">
                Rs. {{ number_format($availableBalance, 2) }}
              </strong> ({{ $availableBalance >= 0 ? 'Surplus' : 'Deficit' }}).
            </p>
            <div class="cc-dash-hero-actions">
              <div class="cc-month-selector me-2">
                <a href="{{ route('dashboard', ['month' => $prevMonthKey]) }}" class="cc-month-btn" title="Previous Month"><i class="bi bi-chevron-left"></i></a>
                <span class="cc-month-label">{{ $monthLabel }}</span>
                <a href="{{ route('dashboard', ['month' => $nextMonthKey]) }}" class="cc-month-btn" title="Next Month"><i class="bi bi-chevron-right"></i></a>
              </div>
              <a href="{{ route('transactions.create') }}?type=income" class="btn-cc btn-cc-primary btn-cc-sm">
                <i class="bi bi-plus-lg"></i> Add Income
              </a>
              <a href="{{ route('transactions.create') }}?type=expense" class="btn-cc btn-cc-secondary btn-cc-sm">
                <i class="bi bi-dash-lg" style="color:var(--cc-expense);"></i> Add Expense
              </a>
              <a href="{{ route('reports', ['month' => $currentMonthKey]) }}" class="btn-cc btn-cc-ghost btn-cc-sm">
                <i class="bi bi-file-earmark-bar-graph"></i> Reports
              </a>
            </div>
          </div>

          {{-- Right Side Hero Visual Overlay Composition --}}
          <div class="col-lg-5">
            <div class="cc-dash-hero-visual">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                  <img src="{{ asset('images/campus-coin-logo-light.png') }}" data-light-src="{{ asset('images/campus-coin-logo-light.png') }}" data-dark-src="{{ asset('images/campus-coin-logo-dark.png') }}" class="cc-brand-logo-sm" alt="Campus Coin">
                  <span style="font-weight:var(--fw-bold);font-size:var(--fs-xs);letter-spacing:0.04em;color:var(--cc-text-muted);">SNAPSHOT</span>
                </div>
                <span class="cc-badge cc-badge-primary"><i class="bi bi-shield-check"></i> {{ $shortMonthName }}</span>
              </div>

              <div class="dash-hero-stat-pill">
                <div class="d-flex align-items-center gap-2">
                  <div class="txn-icon" style="background:var(--cc-income-bg);color:var(--cc-income);width:30px;height:30px;font-size:0.8rem;">
                    <i class="bi bi-wallet2"></i>
                  </div>
                  <div>
                    <div style="font-size:10px;color:var(--cc-text-muted);text-transform:uppercase;font-weight:700;">Available Net Balance</div>
                    <div style="font-size:var(--fs-sm);font-weight:var(--fw-extrabold);font-family:var(--cc-font-mono);">Rs. {{ number_format($availableBalance, 2) }}</div>
                  </div>
                </div>
                <span class="cc-badge {{ $availableBalance >= 0 ? 'cc-badge-income' : 'cc-badge-expense' }}">
                  {{ $availableBalance >= 0 ? '+ Active' : 'Overdraft' }}
                </span>
              </div>

              <div class="dash-hero-stat-pill">
                <div class="d-flex align-items-center gap-2">
                  <div class="txn-icon" style="background:var(--cc-primary-light);color:var(--cc-primary);width:30px;height:30px;font-size:0.8rem;">
                    <i class="bi bi-pie-chart-fill"></i>
                  </div>
                  <div>
                    <div style="font-size:10px;color:var(--cc-text-muted);text-transform:uppercase;font-weight:700;">{{ $shortMonthName }} Budget Usage</div>
                    <div style="font-size:var(--fs-sm);font-weight:var(--fw-bold);font-family:var(--cc-font-mono);">
                      Rs. {{ number_format($monthlyExpense, 2) }} <span style="font-size:11px;color:var(--cc-text-muted);font-weight:normal;">/ Rs. {{ number_format($baselineBudget, 2) }}</span>
                    </div>
                  </div>
                </div>
                <span class="cc-badge {{ $gaugePercent >= 100 ? 'cc-badge-expense' : ($gaugePercent >= 75 ? 'cc-badge-warning' : 'cc-badge-cyan') }}">
                  {{ $gaugePercent }}%
                </span>
              </div>

              @if($topCategoryName)
                <div class="dash-hero-stat-pill">
                  <div class="d-flex align-items-center gap-2">
                    <div class="txn-icon" style="background:var(--cc-warning-bg);color:var(--cc-warning);width:30px;height:30px;font-size:0.8rem;">
                      <i class="bi {{ $topCategoryIcon }}"></i>
                    </div>
                    <div>
                      <div style="font-size:10px;color:var(--cc-text-muted);text-transform:uppercase;font-weight:700;">Top Expense Driver</div>
                      <div style="font-size:var(--fs-xs);font-weight:var(--fw-bold);">{{ $topCategoryName }}</div>
                    </div>
                  </div>
                  <span style="font-size:var(--fs-xs);font-weight:var(--fw-bold);color:var(--cc-expense);font-family:var(--cc-font-mono);">
                    Rs. {{ number_format($topCategoryAmount, 2) }} ({{ $topCategoryPercent }}%)
                  </span>
                </div>
              @endif

            </div>
          </div>
        </div>
      </div>

      {{-- ===== KPI STAT CARDS ===== --}}
      <div class="row g-3 mb-4">

        {{-- Available Balance --}}
        <div class="col-xl-3 col-md-6">
          <div class="cc-stat-card balance">
            <div class="d-flex justify-content-between align-items-start">
              <div style="flex:1;min-width:0;">
                <div class="cc-stat-label">Available Balance</div>
                <div class="cc-stat-value" style="color:{{ $availableBalance >= 0 ? 'var(--cc-text-main)' : 'var(--cc-expense)' }};">
                  Rs.&nbsp;{{ number_format($availableBalance, 2) }}
                </div>
                <div class="cc-stat-meta">
                  <span class="cc-badge {{ $availableBalance >= 0 ? 'cc-badge-primary' : 'cc-badge-expense' }}">
                    <i class="bi {{ $availableBalance >= 0 ? 'bi-shield-check' : 'bi-exclamation-triangle' }}"></i>
                    {{ $availableBalance >= 0 ? 'Surplus' : 'Deficit' }}
                  </span>
                </div>
              </div>
              <div class="cc-stat-icon" style="background:var(--cc-primary-light);color:var(--cc-primary);">
                <i class="bi bi-wallet2"></i>
              </div>
            </div>
            <div class="mt-3 pt-3" style="border-top:1px solid var(--cc-border-subtle);">
              <div style="font-size:var(--fs-xs);color:var(--cc-text-muted);">
                Total Transactions: <strong style="color:var(--cc-text-main);">{{ $totalTransactionsCount }} recorded</strong>
              </div>
            </div>
          </div>
        </div>

        {{-- Monthly Income --}}
        <div class="col-xl-3 col-md-6">
          <div class="cc-stat-card income">
            <div class="d-flex justify-content-between align-items-start">
              <div style="flex:1;min-width:0;">
                <div class="cc-stat-label">Income ({{ $shortMonthName }})</div>
                <div class="cc-stat-value" style="color:var(--cc-income);">Rs.&nbsp;{{ number_format($monthlyIncome, 2) }}</div>
                <div class="cc-stat-meta">
                  @if($hasPrevIncomeData)
                    <span class="cc-badge {{ $incomeChangePercent >= 0 ? 'cc-badge-income' : 'cc-badge-expense' }}">
                      <i class="bi {{ $incomeChangePercent >= 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short' }}"></i>
                      {{ abs($incomeChangePercent) }}% vs {{ $prevMonthLabel }}
                    </span>
                  @else
                    <span style="font-size:var(--fs-2xs);color:var(--cc-text-muted);">No prior data</span>
                  @endif
                </div>
              </div>
              <div class="cc-stat-icon" style="background:var(--cc-income-bg);color:var(--cc-income);">
                <i class="bi bi-arrow-down-left"></i>
              </div>
            </div>
            <div class="mt-3 pt-3" style="border-top:1px solid var(--cc-border-subtle);">
              <div class="cc-progress-bar">
                <div class="cc-progress-fill" style="width:100%;background:linear-gradient(90deg,var(--cc-income),var(--cc-cyan));"></div>
              </div>
            </div>
          </div>
        </div>

        {{-- Monthly Expenses --}}
        <div class="col-xl-3 col-md-6">
          <div class="cc-stat-card expense">
            <div class="d-flex justify-content-between align-items-start">
              <div style="flex:1;min-width:0;">
                <div class="cc-stat-label">Expenses ({{ $shortMonthName }})</div>
                <div class="cc-stat-value" style="color:var(--cc-expense);">Rs.&nbsp;{{ number_format($monthlyExpense, 2) }}</div>
                <div class="cc-stat-meta">
                  @if($hasPrevExpenseData)
                    <span class="cc-badge {{ $expenseChangePercent <= 0 ? 'cc-badge-income' : 'cc-badge-expense' }}">
                      <i class="bi {{ $expenseChangePercent <= 0 ? 'bi-arrow-down-short' : 'bi-arrow-up-short' }}"></i>
                      {{ abs($expenseChangePercent) }}% vs {{ $prevMonthLabel }}
                    </span>
                  @else
                    <span style="font-size:var(--fs-2xs);color:var(--cc-text-muted);">No prior data</span>
                  @endif
                </div>
              </div>
              <div class="cc-stat-icon" style="background:var(--cc-expense-bg);color:var(--cc-expense);">
                <i class="bi bi-arrow-up-right"></i>
              </div>
            </div>
            <div class="mt-3 pt-3" style="border-top:1px solid var(--cc-border-subtle);">
              <div class="cc-progress-bar">
                <div class="cc-progress-fill" style="width:{{ min(100, $gaugePercent) }}%;background:{{ $gaugePercent > 80 ? 'linear-gradient(90deg,var(--cc-expense),var(--cc-warning))' : 'linear-gradient(90deg,var(--cc-warning),var(--cc-expense))' }};"></div>
              </div>
            </div>
          </div>
        </div>

        {{-- Savings --}}
        <div class="col-xl-3 col-md-6">
          <div class="cc-stat-card {{ $monthlyNetSavings >= 0 ? 'income' : 'expense' }}" style="{{ $monthlyNetSavings >= 0 ? '' : 'background:linear-gradient(135deg,rgba(244,63,94,.07) 0%,rgba(124,58,237,.04) 100%);border-color:rgba(244,63,94,.2);' }}">
            <div class="d-flex justify-content-between align-items-start">
              <div style="flex:1;min-width:0;">
                <div class="cc-stat-label">Savings ({{ $shortMonthName }})</div>
                <div class="cc-stat-value" style="color:{{ $monthlyNetSavings >= 0 ? 'var(--cc-income)' : 'var(--cc-expense)' }};">
                  Rs.&nbsp;{{ number_format(abs($monthlyNetSavings), 2) }}
                </div>
                <div class="cc-stat-meta">
                  <span class="cc-badge {{ $monthlyNetSavings >= 0 ? 'cc-badge-income' : 'cc-badge-expense' }}">
                    <i class="bi {{ $monthlyNetSavings >= 0 ? 'bi-piggy-bank' : 'bi-exclamation-triangle' }}"></i>
                    {{ $monthlyNetSavings >= 0 ? 'Net Positive' : 'Overspent' }}
                  </span>
                </div>
              </div>
              <div class="cc-stat-icon" style="background:{{ $monthlyNetSavings >= 0 ? 'var(--cc-income-bg)' : 'var(--cc-expense-bg)' }};color:{{ $monthlyNetSavings >= 0 ? 'var(--cc-income)' : 'var(--cc-expense)' }};">
                <i class="bi bi-piggy-bank"></i>
              </div>
            </div>
            <div class="mt-3 pt-3" style="border-top:1px solid var(--cc-border-subtle);">
              <div style="font-size:var(--fs-xs);color:var(--cc-text-muted);">Income − Expenses for {{ $shortMonthName }}</div>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== FORECAST BANNER + RECENT ACTIVITY ===== --}}
      <div class="row g-3 mb-4">
        {{-- Forecast Banner --}}
        <div class="col-12 col-lg-{{ isset($recentActivities) && $recentActivities->isNotEmpty() ? '6' : '12' }}">
          <div class="cc-card h-100" style="background:linear-gradient(135deg,rgba(79,70,229,.1),rgba(124,58,237,.07));border-color:rgba(79,70,229,.25);">
            <div class="cc-card-body d-flex align-items-center justify-content-between gap-3 flex-wrap">
              <div class="d-flex align-items-center gap-3">
                <div class="cc-stat-icon" style="background:linear-gradient(135deg,var(--cc-primary),var(--cc-violet));color:#fff;box-shadow:0 4px 16px var(--cc-primary-glow);">
                  <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div>
                  <div style="font-size:var(--fs-xs);font-weight:var(--fw-semibold);color:var(--cc-text-secondary);">Upcoming Forecast — {{ $forecast['upcoming_month_label'] ?? 'Next Month' }}</div>
                  @if(isset($forecast['has_sufficient_data']) && $forecast['has_sufficient_data'])
                    <div style="font-size:var(--fs-xl);font-weight:var(--fw-extrabold);color:var(--cc-text-main);font-family:var(--cc-font-mono);letter-spacing:-.02em;">
                      Rs. {{ number_format($forecast['estimated_total_spending'], 2) }}
                    </div>
                    <div style="font-size:var(--fs-2xs);color:var(--cc-text-muted);">Estimated spending next month</div>
                  @else
                    <div style="font-size:var(--fs-sm);color:var(--cc-text-secondary);">Building historical baseline...</div>
                  @endif
                </div>
              </div>
              <a href="{{ route('forecast') }}" class="btn-cc btn-cc-primary btn-cc-sm">
                View Forecast <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>
        </div>

        {{-- Recently Viewed --}}
        @if(isset($recentActivities) && $recentActivities->isNotEmpty())
          <div class="col-12 col-lg-6">
            <div class="cc-card h-100">
              <div class="cc-card-header">
                <h2 class="cc-card-title"><i class="bi bi-clock-history me-2" style="color:var(--cc-text-muted);"></i>Recently Viewed</h2>
                <a href="{{ route('bookmarks') }}" class="btn-cc btn-cc-ghost btn-cc-sm">
                  <i class="bi bi-bookmark-star-fill" style="color:var(--cc-warning);"></i> Bookmarks
                </a>
              </div>
              <div class="cc-card-body">
                <div class="d-flex flex-wrap gap-2">
                  @foreach($recentActivities as $act)
                    @if($act->transaction)
                      <span class="d-inline-flex align-items-center gap-1 px-2 py-1" style="border-radius:var(--radius-sm);background:var(--cc-surface-subtle);border:1px solid var(--cc-border-subtle);font-size:var(--fs-xs);">
                        <i class="bi {{ $act->activity_type === 'edited' ? 'bi-pencil' : 'bi-eye' }}" style="color:{{ $act->activity_type === 'edited' ? 'var(--cc-warning)' : 'var(--cc-primary)' }};"></i>
                        <strong>{{ Str::limit($act->transaction->description, 18) }}</strong>
                        <span style="color:var(--cc-text-muted);">(Rs. {{ number_format($act->transaction->amount, 0) }})</span>
                      </span>
                    @endif
                  @endforeach
                </div>
              </div>
            </div>
          </div>
        @endif
      </div>

      {{-- ===== CHARTS ROW ===== --}}
      <div class="row g-4 mb-4">

        {{-- Spending Trajectory --}}
        <div class="col-lg-7">
          <div class="cc-card h-100">
            <div class="cc-card-header">
              <h2 class="cc-card-title">
                <i class="bi bi-bar-chart-fill me-2" style="color:var(--cc-primary);"></i>Spending Trajectory
              </h2>
              <div class="d-flex gap-3" style="font-size:var(--fs-2xs);">
                <span class="d-flex align-items-center gap-1">
                  <span style="width:8px;height:8px;border-radius:3px;background:var(--cc-income);display:inline-block;"></span> Income
                </span>
                <span class="d-flex align-items-center gap-1">
                  <span style="width:8px;height:8px;border-radius:3px;background:var(--cc-expense);display:inline-block;"></span> Expense
                </span>
              </div>
            </div>
            <div class="cc-card-body d-flex flex-column justify-content-between">
              <div class="d-flex justify-content-around align-items-end" style="height:200px;padding-top:12px;border-bottom:1px solid var(--cc-border-subtle);">
                @foreach($trajectory as $month)
                <div class="d-flex flex-column align-items-center gap-1">
                  <div class="d-flex gap-1 align-items-end" style="height:160px;">
                    <div style="width:14px;height:{{ $month['income_height'] }}px;background:var(--cc-income);opacity:{{ $month['is_current'] ? '1' : '.5' }};border-radius:4px 4px 0 0;{{ $month['is_current'] ? 'box-shadow:0 0 10px rgba(16,185,129,.4);' : '' }}"
                      title="{{ $month['label'] }} Income: Rs. {{ number_format($month['income'], 2) }}"></div>
                    <div style="width:14px;height:{{ $month['expense_height'] }}px;background:var(--cc-expense);opacity:{{ $month['is_current'] ? '1' : '.5' }};border-radius:4px 4px 0 0;{{ $month['is_current'] ? 'box-shadow:0 0 10px rgba(244,63,94,.4);' : '' }}"
                      title="{{ $month['label'] }} Expense: Rs. {{ number_format($month['expense'], 2) }}"></div>
                  </div>
                  <span style="font-size:var(--fs-xs);{{ $month['is_current'] ? 'font-weight:var(--fw-bold);color:var(--cc-primary);' : 'color:var(--cc-text-muted);' }}">
                    {{ $month['label'] }}{{ $month['is_current'] ? ' ●' : '' }}
                  </span>
                </div>
                @endforeach
              </div>
              <div class="d-flex justify-content-between align-items-center pt-3" style="font-size:var(--fs-xs);">
                <span style="color:var(--cc-text-secondary);">Savings Goal: <strong style="color:var(--cc-text-main);font-family:var(--cc-font-mono);">Rs. {{ number_format($user->savings_goal > 0 ? $user->savings_goal : 5000, 2) }}</strong></span>
                <a href="{{ route('transactions') }}" class="btn-cc btn-cc-ghost btn-cc-xs">Full Ledger <i class="bi bi-arrow-right"></i></a>
              </div>
            </div>
          </div>
        </div>

        {{-- Budget Health Ring --}}
        <div class="col-lg-5">
          <div class="cc-card h-100">
            <div class="cc-card-header">
              <h2 class="cc-card-title">
                <i class="bi bi-pie-chart me-2" style="color:var(--cc-violet);"></i>{{ $shortMonthName }} Budget Health
              </h2>
              <a href="{{ route('budgets', ['month' => $currentMonthKey]) }}" class="btn-cc btn-cc-ghost btn-cc-sm">Budgets</a>
            </div>
            <div class="cc-card-body text-center">
              {{-- Gauge Ring --}}
              <div class="cc-ring-gauge mb-3"
                style="background: conic-gradient(
                  {{ $gaugePercent >= 100 ? 'var(--cc-expense)' : ($gaugePercent >= 75 ? 'var(--cc-warning)' : 'var(--cc-primary)') }} 0% {{ min(100,$gaugePercent) }}%,
                  var(--cc-border-subtle) {{ min(100,$gaugePercent) }}% 100%
                );">
                <div class="cc-ring-gauge-inner">
                  <span class="cc-amount font-bold" style="font-size:var(--fs-2xl);color:{{ $gaugePercent >= 100 ? 'var(--cc-expense)' : ($gaugePercent >= 75 ? 'var(--cc-warning)' : 'var(--cc-primary)') }};">
                    {{ $gaugePercent }}%
                  </span>
                  <span style="font-size:10px;text-transform:uppercase;color:var(--cc-text-muted);letter-spacing:.06em;">Used</span>
                </div>
              </div>
              <div class="mb-3">
                <div class="font-bold" style="font-size:var(--fs-md);font-family:var(--cc-font-mono);">
                  Rs. {{ number_format($monthlyExpense, 2) }}
                  <span style="color:var(--cc-text-secondary);font-size:var(--fs-xs);font-weight:normal;font-family:var(--cc-font-body);">of Rs. {{ number_format($baselineBudget, 2) }}</span>
                </div>
                <span class="cc-badge {{ $gaugeRemaining > 0 ? 'cc-badge-income' : 'cc-badge-expense' }} mt-1">
                  <i class="bi {{ $gaugeRemaining > 0 ? 'bi-check-circle-fill' : 'bi-exclamation-octagon' }}"></i>
                  Rs. {{ number_format($gaugeRemaining, 2) }} Remaining
                </span>
              </div>
              <div class="text-start pt-2" style="border-top:1px solid var(--cc-border-subtle);font-size:var(--fs-xs);">
                @if($topCategoryName)
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="color:var(--cc-text-secondary);">🏆 Top: <strong style="color:var(--cc-text-main);">{{ $topCategoryName }}</strong></span>
                    <span class="cc-badge cc-badge-warning">Rs. {{ number_format($topCategoryAmount, 2) }} ({{ $topCategoryPercent }}%)</span>
                  </div>
                  <div class="cc-progress-bar mb-1">
                    <div class="cc-progress-fill" style="width:{{ min(100, $topCategoryPercent) }}%;background:linear-gradient(90deg,var(--cc-warning),var(--cc-expense));"></div>
                  </div>
                @else
                  <div style="color:var(--cc-text-muted);text-align:center;padding:.5rem 0;">
                    <i class="bi bi-tag me-1"></i> No expenses recorded yet this month.
                  </div>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== CATEGORY SPENDING + BUDGET VS ACTUAL ===== --}}
      <div class="row g-4 mb-4">

        {{-- Category Spending --}}
        <div class="col-lg-6">
          <div class="cc-card h-100">
            <div class="cc-card-header">
              <h2 class="cc-card-title"><i class="bi bi-tags me-2" style="color:var(--cc-primary);"></i>Category Spending</h2>
            </div>
            <div class="cc-card-body">
              @if($categorySpendings->isEmpty())
                <div class="cc-empty py-3">
                  <div class="cc-empty-icon"><i class="bi bi-tags"></i></div>
                  <h3>No spending data</h3>
                  <p>No expenses recorded for {{ $monthLabel }}.</p>
                  <a href="{{ route('transactions.create') }}?type=expense" class="btn-cc btn-cc-primary btn-cc-sm"><i class="bi bi-plus-lg"></i> Add Expense</a>
                </div>
              @else
                <div class="cc-spend-list">
                  @foreach($categorySpendings->take(6) as $i => $cat)
                  <div class="cc-spend-row">
                    <div class="cc-spend-icon" style="background:rgba({{ $i % 4 === 0 ? '79,70,229' : ($i % 4 === 1 ? '124,58,237' : ($i % 4 === 2 ? '6,182,212' : '16,185,129')) }},.15);color:var(--cc-{{ $i % 4 === 0 ? 'primary' : ($i % 4 === 1 ? 'violet' : ($i % 4 === 2 ? 'cyan' : 'income')) }});">
                      <i class="bi {{ $cat->icon }}"></i>
                    </div>
                    <div class="cc-spend-info">
                      <div class="cc-spend-name">{{ $cat->name }}</div>
                      <div class="cc-spend-bar">
                        <div class="cc-spend-bar-fill" style="width:{{ min(100, $cat->percent) }}%;"></div>
                      </div>
                    </div>
                    <div class="text-end" style="flex-shrink:0;">
                      <div class="cc-spend-amount" style="color:var(--cc-expense);">Rs. {{ number_format($cat->amount, 2) }}</div>
                      <div class="cc-spend-pct">{{ $cat->percent }}%</div>
                    </div>
                  </div>
                  @endforeach
                </div>
              @endif
            </div>
          </div>
        </div>

        {{-- Budget vs Actual --}}
        <div class="col-lg-6">
          <div class="cc-card h-100">
            <div class="cc-card-header">
              <h2 class="cc-card-title"><i class="bi bi-bullseye me-2" style="color:var(--cc-violet);"></i>Budget vs Actual</h2>
              <a href="{{ route('budgets', ['month' => $currentMonthKey]) }}" class="btn-cc btn-cc-ghost btn-cc-sm">Manage</a>
            </div>
            <div class="cc-card-body">
              @if($budgetItems->isEmpty())
                <div class="cc-empty py-3">
                  <div class="cc-empty-icon" style="background:linear-gradient(135deg,var(--cc-violet-light),var(--cc-primary-light));color:var(--cc-violet);"><i class="bi bi-bullseye"></i></div>
                  <h3>Set your first budget</h3>
                  <p>Set spending limits per category and track your progress automatically.</p>
                  <a href="{{ route('budgets', ['month' => $currentMonthKey]) }}" class="btn-cc btn-cc-primary btn-cc-sm"><i class="bi bi-plus-lg"></i> Create Budget</a>
                </div>
              @else
                <div class="d-flex flex-column gap-3">
                  @foreach($budgetItems->take(4) as $b)
                  <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <div class="d-flex align-items-center gap-2">
                        <div class="txn-icon" style="background:rgba(79,70,229,.1);color:{{ $b->color }};width:30px;height:30px;font-size:.8rem;">
                          <i class="bi {{ $b->category_icon }}"></i>
                        </div>
                        <span style="font-size:var(--fs-xs);font-weight:var(--fw-semibold);">{{ $b->category_name }}</span>
                      </div>
                      <span class="cc-badge {{ $b->status_badge_class }}" style="font-size:10px;">{{ $b->status_label }}</span>
                    </div>
                    <div class="d-flex justify-content-between" style="font-size:11px;color:var(--cc-text-muted);margin-bottom:.25rem;">
                      <span>Rs. {{ number_format($b->spent_amount, 2) }} spent</span>
                      <span>Rs. {{ number_format($b->limit_amount, 2) }} limit</span>
                    </div>
                    <div class="cc-progress-bar" style="height:6px;">
                      <div class="cc-progress-fill" style="width:{{ $b->display_percentage }}%;background:{{ $b->color }};"></div>
                    </div>
                  </div>
                  @endforeach
                </div>
                @if($totalBudgetLimit > 0)
                <div class="mt-3 pt-3" style="border-top:1px solid var(--cc-border-subtle);">
                  <div class="d-flex justify-content-between align-items-center" style="font-size:var(--fs-xs);">
                    <span style="color:var(--cc-text-secondary);">Total Budget Health</span>
                    <span class="font-bold">Rs. {{ number_format($totalBudgetSpent, 2) }} / Rs. {{ number_format($totalBudgetLimit, 2) }} ({{ $overallBudgetUsagePercent }}%)</span>
                  </div>
                </div>
                @endif
              @endif
            </div>
          </div>
        </div>
      </div>

      {{-- ===== AI MONTHLY INSIGHT ===== --}}
      <div class="cc-card cc-ai-card mb-4">
        <div class="cc-card-body p-3 p-md-4">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div class="d-flex align-items-center gap-3">
              <div class="cc-stat-icon" style="background:linear-gradient(135deg,rgba(79,70,229,.2),rgba(124,58,237,.15));color:var(--cc-primary);box-shadow:0 4px 16px var(--cc-primary-glow);">
                <i class="bi bi-stars"></i>
              </div>
              <div>
                <div style="font-size:var(--fs-2xs);font-weight:var(--fw-bold);text-transform:uppercase;letter-spacing:.08em;color:var(--cc-text-muted);margin-bottom:2px;">AI Intelligence</div>
                <h3 style="font-size:var(--fs-md);font-weight:var(--fw-bold);color:var(--cc-text-main);margin:0;letter-spacing:-.01em;">
                  Monthly Insight &bull; <span style="color:var(--cc-text-secondary);font-weight:normal;">{{ $monthLabel }}</span>
                </h3>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="cc-ai-badge">
                <i class="bi {{ $monthlyInsight && $monthlyInsight->isAiGenerated() ? 'bi-stars' : 'bi-shield-check' }}"></i>
                {{ $monthlyInsight && $monthlyInsight->isAiGenerated() ? 'AI Analysis' : 'Advisory' }}
              </span>
              <a href="{{ route('insights', ['month' => $currentMonthKey]) }}" class="btn-cc btn-cc-ghost btn-cc-xs">
                View Full <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          @if($monthlyInsight)
            <p style="font-size:var(--fs-sm);line-height:1.65;color:var(--cc-text-main);margin-bottom:.875rem;">
              {{ Str::limit($monthlyInsight->summary, 220) }}
            </p>
            @if(!empty($monthlyInsight->highlights) && count($monthlyInsight->highlights) > 0)
              <div class="d-flex flex-wrap gap-2">
                @foreach(array_slice($monthlyInsight->highlights, 0, 2) as $hl)
                  <span class="d-inline-flex align-items-center gap-1 px-2 py-1" style="border-radius:var(--radius-sm);background:var(--cc-primary-light);border:1px solid rgba(79,70,229,.2);font-size:var(--fs-xs);color:var(--cc-text-main);">
                    <i class="bi bi-check-circle-fill" style="color:var(--cc-primary);font-size:11px;"></i>
                    {{ Str::limit($hl, 65) }}
                  </span>
                @endforeach
              </div>
            @endif
          @else
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <p style="color:var(--cc-text-secondary);font-size:var(--fs-sm);margin:0;">
                Generate tailored spending analysis and actionable recommendations for {{ $monthLabel }}.
              </p>
              <form action="{{ route('insights.generate') }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="month" value="{{ $currentMonthKey }}">
                <button type="submit" class="btn-cc btn-cc-primary btn-cc-sm">
                  <i class="bi bi-stars"></i> Generate {{ $shortMonthName }} Insight
                </button>
              </form>
            </div>
          @endif
        </div>
      </div>

      {{-- ===== SAVING TIPS ===== --}}
      <div class="cc-card mb-4" id="dashboardSavingTipsSection">
        <div class="cc-card-header">
          <div class="d-flex align-items-center gap-2">
            <h2 class="cc-card-title mb-0">
              <i class="bi bi-lightbulb-fill me-2" style="color:var(--cc-warning);"></i>Personalized Saving Tips
            </h2>
            @if($totalSavingsPotential > 0)
              <span class="cc-badge cc-badge-income">Save up to Rs. {{ number_format($totalSavingsPotential, 2) }}</span>
            @endif
          </div>
          <a href="{{ route('saving-tips', ['month' => $currentMonthKey]) }}" class="btn-cc btn-cc-ghost btn-cc-sm">
            View All <i class="bi bi-arrow-right"></i>
          </a>
        </div>
        <div class="cc-card-body">
          @if($topSavingTips->isEmpty())
            <div class="cc-empty py-2">
              <div class="cc-empty-icon" style="background:var(--cc-warning-bg);color:var(--cc-warning);"><i class="bi bi-lightbulb"></i></div>
              <h3>Keep tracking your spending</h3>
              <p>Log more transactions and set category budgets to unlock personalized, data-driven saving tips.</p>
              <a href="{{ route('transactions.create') }}?type=expense" class="btn-cc btn-cc-primary btn-cc-sm"><i class="bi bi-plus-lg"></i> Log Expense</a>
            </div>
          @else
            <div class="row g-3">
              @foreach($topSavingTips as $tip)
                <div class="col-md-4" id="dash-tip-card-{{ $tip->id }}">
                  <div class="cc-card h-100 d-flex flex-column" style="background:var(--cc-surface-subtle);border-color:{{ $tip->is_pinned ? 'var(--cc-warning)' : 'var(--cc-border-subtle)' }};">
                    <div class="cc-card-body flex-1 p-3">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="cc-badge {{ $tip->type_badge_class }}">
                          <i class="bi {{ $tip->type_icon }}"></i> {{ $tip->category ? $tip->category->name : $tip->type_label }}
                        </span>
                        @if($tip->potential_savings > 0)
                          <span class="cc-badge cc-badge-income" style="font-size:10px;">Save Rs. {{ number_format($tip->potential_savings, 2) }}</span>
                        @endif
                      </div>
                      <h4 style="font-size:var(--fs-sm);font-weight:var(--fw-bold);color:var(--cc-text-main);margin-bottom:.375rem;">{{ $tip->title }}</h4>
                      <p style="color:var(--cc-text-secondary);font-size:var(--fs-xs);line-height:1.5;margin:0;">{{ $tip->message }}</p>
                    </div>
                    <div class="p-2 px-3 d-flex justify-content-between align-items-center" style="border-top:1px solid var(--cc-border-subtle);">
                      @if($tip->is_pinned)
                        <button type="button" class="btn-cc btn-cc-ghost btn-cc-xs" style="color:var(--cc-warning);" onclick="togglePinTip({{ $tip->id }}, false, this)" title="Unpin">
                          <i class="bi bi-pin-fill"></i> Pinned
                        </button>
                      @else
                        <button type="button" class="btn-cc btn-cc-ghost btn-cc-xs" onclick="togglePinTip({{ $tip->id }}, true, this)" title="Pin">
                          <i class="bi bi-pin"></i> Pin
                        </button>
                      @endif
                      <button type="button" class="btn-cc btn-cc-ghost btn-cc-xs" style="color:var(--cc-text-muted);" onclick="dismissTip({{ $tip->id }}, this)" title="Dismiss">
                        <i class="bi bi-x-lg"></i>
                      </button>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>

      {{-- ===== QUICK ACTIONS ===== --}}
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <a href="{{ route('transactions.create') }}?type=income" class="cc-quick-action">
            <div class="cc-quick-action-icon" style="background:var(--cc-income-bg);color:var(--cc-income);">
              <i class="bi bi-plus-circle"></i>
            </div>
            <span class="cc-quick-action-label">Add Income</span>
          </a>
        </div>
        <div class="col-6 col-md-3">
          <a href="{{ route('transactions.create') }}?type=expense" class="cc-quick-action">
            <div class="cc-quick-action-icon" style="background:var(--cc-expense-bg);color:var(--cc-expense);">
              <i class="bi bi-dash-circle"></i>
            </div>
            <span class="cc-quick-action-label">Add Expense</span>
          </a>
        </div>
        <div class="col-6 col-md-3">
          <a href="{{ route('budgets', ['month' => $currentMonthKey]) }}" class="cc-quick-action">
            <div class="cc-quick-action-icon" style="background:var(--cc-primary-light);color:var(--cc-primary);">
              <i class="bi bi-bullseye"></i>
            </div>
            <span class="cc-quick-action-label">Budgets</span>
          </a>
        </div>
        <div class="col-6 col-md-3">
          <a href="{{ route('transactions') }}" class="cc-quick-action">
            <div class="cc-quick-action-icon" style="background:var(--cc-violet-light);color:var(--cc-violet);">
              <i class="bi bi-journal-text"></i>
            </div>
            <span class="cc-quick-action-label">All Transactions</span>
          </a>
        </div>
      </div>

      {{-- ===== RECENT TRANSACTIONS ===== --}}
      <div class="cc-card mb-4">
        <div class="cc-card-header">
          <h2 class="cc-card-title"><i class="bi bi-clock-history me-2" style="color:var(--cc-text-muted);"></i>Recent Transactions</h2>
          <a href="{{ route('transactions') }}" class="btn-cc btn-cc-ghost btn-cc-sm">View All ({{ $totalTransactionsCount }}) <i class="bi bi-arrow-right"></i></a>
        </div>

        @if($recentTransactions->isEmpty())
          <div class="cc-card-body">
            <div class="cc-empty py-4">
              <div class="cc-empty-icon"><i class="bi bi-wallet2"></i></div>
              <h3>No transactions yet</h3>
              <p>Start your money story by logging your first transaction.</p>
              <a href="{{ route('transactions.create') }}" class="btn-cc btn-cc-primary btn-cc-sm"><i class="bi bi-plus-lg"></i> Add Transaction</a>
            </div>
          </div>
        @else
          <div class="cc-table-wrapper" style="border:none;border-radius:0;">
            <table class="cc-table">
              <thead>
                <tr>
                  <th>Description</th>
                  <th>Category</th>
                  <th>Date</th>
                  <th>Type</th>
                  <th class="text-end">Amount</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($recentTransactions as $txn)
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="txn-icon" style="background:{{ $txn->type === 'income' ? 'var(--cc-income-bg)' : 'var(--cc-violet-light)' }};color:{{ $txn->type === 'income' ? 'var(--cc-income)' : 'var(--cc-violet)' }};">
                        <i class="bi {{ $txn->category ? $txn->category->icon : ($txn->type === 'income' ? 'bi-cash-coin' : 'bi-cup-hot') }}"></i>
                      </div>
                      <span class="font-semibold">{{ $txn->description }}</span>
                    </div>
                  </td>
                  <td>
                    <span class="cc-badge cc-badge-{{ $txn->type === 'income' ? 'income' : 'secondary' }}">
                      {{ $txn->category ? $txn->category->name : 'Uncategorized' }}
                    </span>
                  </td>
                  <td style="color:var(--cc-text-muted);font-size:var(--fs-xs);">
                    {{ $txn->transaction_date ? $txn->transaction_date->format('M d, Y') : 'N/A' }}
                  </td>
                  <td>
                    <span class="cc-badge cc-badge-{{ $txn->type === 'income' ? 'income' : 'expense' }}">{{ ucfirst($txn->type) }}</span>
                  </td>
                  <td class="cc-amount font-bold text-end" style="color:{{ $txn->type === 'income' ? 'var(--cc-income)' : 'var(--cc-expense)' }};">
                    {{ $txn->type === 'income' ? '+' : '−' }}Rs. {{ number_format($txn->amount, 2) }}
                  </td>
                  <td><span class="cc-badge cc-badge-income">Done</span></td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>

      {{-- ===== FORECAST + ACTIVITY ROW ===== --}}
      <div class="row g-3">
        {{-- Forecast Mini --}}
        <div class="col-lg-6">
          <div class="cc-card h-100">
            <div class="cc-card-header">
              <h2 class="cc-card-title"><i class="bi bi-graph-up-arrow me-2" style="color:var(--cc-primary);"></i>Next Month Forecast</h2>
              <a href="{{ route('forecast') }}" class="btn-cc btn-cc-ghost btn-cc-sm">Full Forecast <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="cc-card-body">
              @if(!$forecast['has_sufficient_data'])
                <div class="cc-empty py-2">
                  <div class="cc-empty-icon" style="background:var(--cc-primary-light);color:var(--cc-primary);"><i class="bi bi-hourglass-split"></i></div>
                  <p style="margin:0;">Add more monthly transactions to unlock your spending forecast.</p>
                  <a href="{{ route('transactions.create') }}" class="btn-cc btn-cc-primary btn-cc-sm"><i class="bi bi-plus-lg"></i> Add Transaction</a>
                </div>
              @else
                <div class="cc-forecast-banner">
                  <div class="cc-stat-icon" style="background:linear-gradient(135deg,var(--cc-primary),var(--cc-violet));color:#fff;flex-shrink:0;">
                    <i class="bi bi-graph-up-arrow"></i>
                  </div>
                  <div style="flex:1;min-width:0;">
                    <div class="cc-forecast-banner-label">{{ $forecast['upcoming_month_label'] }}</div>
                    <div class="cc-forecast-banner-val">Rs. {{ number_format($forecast['estimated_total_spending'], 2) }}</div>
                    <div style="font-size:var(--fs-2xs);color:var(--cc-text-muted);">{{ $forecast['basis_explanation'] }}</div>
                  </div>
                  <div class="text-end" style="flex-shrink:0;">
                    @if($forecast['difference_from_prev'] >= 0)
                      <span class="cc-badge cc-badge-expense"><i class="bi bi-arrow-up-right"></i> +{{ $forecast['percent_change'] }}%</span>
                    @else
                      <span class="cc-badge cc-badge-income"><i class="bi bi-arrow-down-right"></i> {{ $forecast['percent_change'] }}%</span>
                    @endif
                    <div style="font-size:var(--fs-2xs);color:var(--cc-text-muted);margin-top:.25rem;">vs last month</div>
                  </div>
                </div>
                @if(!empty($forecast['category_forecasts']))
                  <div class="d-flex flex-column gap-2">
                    @foreach(array_slice($forecast['category_forecasts'], 0, 4) as $cf)
                      <div>
                        <div class="d-flex justify-content-between mb-1" style="font-size:var(--fs-xs);">
                          <span><i class="bi {{ $cf['icon'] }} me-1" style="color:var(--cc-primary);"></i>{{ $cf['name'] }}</span>
                          <span class="font-bold">Rs. {{ number_format($cf['estimated_amount'], 2) }}</span>
                        </div>
                        <div class="cc-progress-bar" style="height:4px;">
                          <div class="cc-progress-fill" style="width:{{ $cf['percentage'] }}%;"></div>
                        </div>
                      </div>
                    @endforeach
                  </div>
                @endif
              @endif
            </div>
          </div>
        </div>

        {{-- Recent Activity Feed --}}
        <div class="col-lg-6">
          <div class="cc-card h-100">
            <div class="cc-card-header">
              <h2 class="cc-card-title"><i class="bi bi-activity me-2" style="color:var(--cc-primary);"></i>Recent Activity</h2>
              <a href="{{ route('bookmarks') }}" class="btn-cc btn-cc-ghost btn-cc-sm">Bookmarks <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="cc-card-body">
              @if(empty($recentActivities) || !$recentActivities->count())
                <div class="cc-empty py-2">
                  <div class="cc-empty-icon" style="background:var(--cc-primary-light);color:var(--cc-primary);"><i class="bi bi-clock-history"></i></div>
                  <p style="margin:0;">View or edit transactions to see your recent activity here.</p>
                </div>
              @else
                <div class="d-flex flex-column gap-2">
                  @foreach($recentActivities as $act)
                    <div class="d-flex align-items-center gap-3 p-2 rounded" style="background:var(--cc-surface-subtle);font-size:var(--fs-xs);">
                      <div style="width:34px;height:34px;border-radius:var(--radius-md);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;background:{{ $act->action === 'edited' ? 'var(--cc-warning-bg)' : 'var(--cc-primary-light)' }};color:{{ $act->action === 'edited' ? 'var(--cc-warning)' : 'var(--cc-primary)' }};">
                        <i class="bi {{ $act->action === 'edited' ? 'bi-pencil' : 'bi-eye' }}"></i>
                      </div>
                      <div style="min-width:0;flex:1;">
                        <div class="font-semibold" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $act->transaction->description ?? 'Transaction #'.$act->transaction_id }}</div>
                        <div style="color:var(--cc-text-muted);">{{ ucfirst($act->action) }} &mdash; {{ $act->created_at->diffForHumans() }}</div>
                      </div>
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>

    </div><!-- /.cc-content -->
  </main>
</div>

<!-- ===== LOGOUT MODAL ===== -->
<div class="cc-modal-overlay" id="logoutModal">
  <div class="cc-modal" style="max-width:380px;">
    <div class="cc-modal-header">
      <h3 class="cc-modal-title"><i class="bi bi-box-arrow-left" style="color:var(--cc-expense);"></i> Confirm Logout</h3>
      <button class="cc-modal-close" data-modal-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="cc-modal-body text-center py-4">
      <div style="width:56px;height:56px;border-radius:var(--radius-xl);background:var(--cc-expense-bg);color:var(--cc-expense);display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin:0 auto 1rem;">
        <i class="bi bi-box-arrow-left"></i>
      </div>
      <p style="color:var(--cc-text-secondary);font-size:var(--fs-sm);margin:0;">Are you sure you want to end your Campus Coin session?</p>
    </div>
    <div class="cc-modal-footer">
      <button class="btn-cc btn-cc-secondary btn-cc-sm" data-modal-close>Cancel</button>
      <form action="{{ route('logout') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn-cc btn-cc-danger btn-cc-sm">Sign Out</button>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Notification Bell Toggle
  const bell     = document.getElementById('notifBellBtn');
  const dropdown = document.getElementById('notifDropdown');
  if (bell && dropdown) {
    bell.addEventListener('click', function (e) {
      e.stopPropagation();
      const isOpen = dropdown.classList.contains('open');
      dropdown.style.display = isOpen ? 'none' : 'flex';
      dropdown.classList.toggle('open', !isOpen);
    });
    document.addEventListener('click', function (e) {
      if (!dropdown.contains(e.target) && e.target !== bell) {
        dropdown.classList.remove('open');
        dropdown.style.display = 'none';
      }
    });
  }
});

function togglePinTip(tipId, shouldPin, btnEl) {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const url   = shouldPin ? `/saving-tips/${tipId}/pin` : `/saving-tips/${tipId}/unpin`;
  fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      if (typeof Toast !== 'undefined') Toast.success('Tip', shouldPin ? 'Tip pinned.' : 'Tip unpinned.');
      setTimeout(() => window.location.reload(), 300);
    }
  })
  .catch(() => { if (typeof Toast !== 'undefined') Toast.error('Error', 'Could not update tip.'); });
}

function dismissTip(tipId, btnEl) {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  fetch(`/saving-tips/${tipId}/dismiss`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      const card = document.getElementById(`dash-tip-card-${tipId}`);
      if (card) {
        card.style.transition = 'all 0.3s ease';
        card.style.opacity = '0';
        card.style.transform = 'scale(0.95)';
        setTimeout(() => { card.remove(); if (typeof Toast !== 'undefined') Toast.info('Dismissed', 'Tip dismissed.'); }, 300);
      }
    }
  })
  .catch(() => { if (typeof Toast !== 'undefined') Toast.error('Error', 'Could not dismiss tip.'); });
}
</script>
</body>
</html>
