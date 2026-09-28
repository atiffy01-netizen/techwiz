<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Financial Reports &amp; Analytics — Campus Coin. Understand where your money goes and how your spending changes over time.">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Financial Reports — Campus Coin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
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
            <span class="current">Reports</span>
          </div>
          <div class="cc-topbar-title">Financial Reports</div>
        </div>
      </div>
      <div class="cc-topbar-right">
        <button class="cc-theme-toggle" aria-label="Toggle theme" onclick="ThemeManager.toggle()"><i class="bi bi-moon-fill"></i></button>
        <a href="{{ route('profile') }}" class="cc-avatar" style="font-size:var(--fs-xs);">{{ $user->initials }}</a>
      </div>
    </header>

    <div class="cc-content">
      
      <!-- PAGE HEADER WITH ACTIONS -->
      <div class="cc-page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
          <h1 class="cc-page-title mb-1">Financial Reports</h1>
          <p class="cc-page-subtitle mb-0">Understand where your money goes and how your spending changes over time.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 cc-actions-no-print">
          <a href="{{ route('reports.export.pdf', request()->query()) }}" class="btn-cc btn-cc-primary" id="btnExportPdf" onclick="showExportToast('PDF')">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i> Export PDF
          </a>
          <button type="button" class="btn-cc btn-cc-secondary" id="btnExportImage" onclick="exportReportImage()">
            <i class="bi bi-image me-1"></i> Export Image
          </button>
          <a href="{{ route('reports.print', request()->query()) }}" target="_blank" class="btn-cc btn-cc-ghost" title="Print this report">
            <i class="bi bi-printer me-1"></i> Print
          </a>
        </div>
      </div>

      <!-- FILTER CONTROLS AREA -->
      <div class="cc-card cc-filter-card mb-4">
        <div class="cc-card-body">
          <form action="{{ route('reports') }}" method="GET" id="reportFilterForm">
            <div class="row g-3 align-items-end">
              
              <!-- Period Mode Switcher -->
              <div class="col-lg-2 col-md-4 col-sm-6">
                <label class="cc-label" for="period_type_select"><i class="bi bi-calendar3 me-1"></i> Period View</label>
                <select name="period_type" id="period_type_select" class="cc-select" onchange="togglePeriodInputs(this.value)">
                  <option value="month" {{ $periodType === 'month' ? 'selected' : '' }}>Monthly</option>
                  <option value="custom" {{ $periodType === 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                </select>
              </div>

              <!-- Month Selector -->
              <div class="col-lg-2 col-md-4 col-sm-6" id="monthSelectorWrapper" style="{{ $periodType === 'custom' ? 'display:none;' : '' }}">
                <label class="cc-label" for="month_select"><i class="bi bi-calendar-month me-1"></i> Month</label>
                <select name="month" id="month_select" class="cc-select">
                  @foreach($availableMonths as $m)
                    <option value="{{ $m['value'] }}" {{ $selectedMonth === $m['value'] ? 'selected' : '' }}>
                      {{ $m['label'] }}
                    </option>
                  @endforeach
                </select>
              </div>

              <!-- Custom Date Pickers -->
              <div class="col-lg-2 col-md-4 col-sm-6 custom-date-field" style="{{ $periodType !== 'custom' ? 'display:none;' : '' }}">
                <label class="cc-label" for="start_date_input">Start Date</label>
                <input type="date" name="start_date" id="start_date_input" class="cc-input" value="{{ $startDate }}">
              </div>

              <div class="col-lg-2 col-md-4 col-sm-6 custom-date-field" style="{{ $periodType !== 'custom' ? 'display:none;' : '' }}">
                <label class="cc-label" for="end_date_input">End Date</label>
                <input type="date" name="end_date" id="end_date_input" class="cc-input" value="{{ $endDate }}">
              </div>

              <!-- Transaction Type -->
              <div class="col-lg-2 col-md-4 col-sm-6">
                <label class="cc-label" for="type_select"><i class="bi bi-funnel me-1"></i> Type</label>
                <select name="type" id="type_select" class="cc-select" onchange="toggleCategoryDropdowns(this.value)">
                  <option value="all" {{ $typeFilter === 'all' ? 'selected' : '' }}>All Transactions</option>
                  <option value="income" {{ $typeFilter === 'income' ? 'selected' : '' }}>Income Only</option>
                  <option value="expense" {{ $typeFilter === 'expense' ? 'selected' : '' }}>Expense Only</option>
                </select>
              </div>

              <!-- Category Filter -->
              <div class="col-lg-2 col-md-4 col-sm-6" id="categorySelectWrapper" style="{{ $typeFilter === 'income' ? 'display:none;' : '' }}">
                <label class="cc-label" for="category_id_select"><i class="bi bi-tag me-1"></i> Category</label>
                <select name="category_id" id="category_id_select" class="cc-select">
                  <option value="all">All Categories</option>
                  @foreach($expenseCategories as $cat)
                    <option value="{{ $cat->id }}" {{ (string)$categoryId === (string)$cat->id ? 'selected' : '' }}>
                      {{ $cat->name }}
                    </option>
                  @endforeach
                </select>
              </div>

              <!-- Income Source Filter -->
              <div class="col-lg-2 col-md-4 col-sm-6" id="incomeSourceSelectWrapper" style="{{ $typeFilter === 'expense' ? 'display:none;' : '' }}">
                <label class="cc-label" for="income_source_select"><i class="bi bi-cash-stack me-1"></i> Income Source</label>
                <select name="income_source_id" id="income_source_select" class="cc-select">
                  <option value="all">All Income Sources</option>
                  @foreach($incomeCategories as $icat)
                    <option value="{{ $icat->id }}" {{ (string)$incomeSourceId === (string)$icat->id ? 'selected' : '' }}>
                      {{ $icat->name }}
                    </option>
                  @endforeach
                </select>
              </div>

              <!-- Filter Action Buttons -->
              <div class="col-lg-2 col-md-4 col-sm-12 d-flex gap-2">
                <button type="submit" class="btn-cc btn-cc-primary flex-fill">
                  <i class="bi bi-filter"></i> Apply
                </button>
                <a href="{{ route('reports') }}" class="btn-cc btn-cc-secondary" title="Reset Filters">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </a>
              </div>

            </div>
          </form>

          <!-- Active Filter Badges -->
          <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-3" style="border-top:1px solid var(--cc-border-subtle); font-size:var(--fs-xs);">
            <span class="text-secondary font-semibold"><i class="bi bi-info-circle me-1"></i> Active Filter:</span>
            @foreach($activeFilters as $af)
              <span class="cc-report-filter-tag">
                <span>{{ $af['label'] }}</span>
              </span>
            @endforeach
            @if($isFiltered)
              <a href="{{ route('reports') }}" class="text-decoration-none ms-2" style="color:var(--cc-expense); font-size:var(--fs-2xs); font-weight:var(--fw-bold);">
                <i class="bi bi-x-circle me-1"></i> Clear All
              </a>
            @endif
          </div>
        </div>
      </div>

      <!-- MAIN EXPORTABLE REPORT CARD -->
      <div id="reportExportContainer">

        <!-- SUMMARY STATS KPI CARDS -->
        <div class="row g-3 mb-4">
          <!-- Total Income -->
          <div class="col-xl-3 col-md-6">
            <div class="cc-stat-card income">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="cc-stat-label">Total Income</div>
                  <div class="cc-stat-value" style="color:var(--cc-income);">
                    Rs. {{ number_format($totalIncome, 2) }}
                  </div>
                  <div class="cc-stat-meta">
                    <span class="cc-badge cc-badge-income"><i class="bi bi-arrow-down-left"></i> {{ $incomeCount }} inflows</span>
                  </div>
                </div>
                <div class="cc-stat-icon" style="background:var(--cc-income-bg);color:var(--cc-income);">
                  <i class="bi bi-wallet2"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Total Expenses -->
          <div class="col-xl-3 col-md-6">
            <div class="cc-stat-card expense">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="cc-stat-label">Total Expenses</div>
                  <div class="cc-stat-value" style="color:var(--cc-expense);">
                    Rs. {{ number_format($totalExpense, 2) }}
                  </div>
                  <div class="cc-stat-meta">
                    <span class="cc-badge cc-badge-expense"><i class="bi bi-arrow-up-right"></i> {{ $expenseCount }} outflows</span>
                  </div>
                </div>
                <div class="cc-stat-icon" style="background:var(--cc-expense-bg);color:var(--cc-expense);">
                  <i class="bi bi-credit-card-2-back"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Net Balance / Savings -->
          <div class="col-xl-3 col-md-6">
            <div class="cc-stat-card balance">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="cc-stat-label">Net Balance</div>
                  <div class="cc-stat-value" style="color:{{ $netBalance >= 0 ? 'var(--cc-primary)' : 'var(--cc-expense)' }};">
                    Rs. {{ number_format($netBalance, 2) }}
                  </div>
                  <div class="cc-stat-meta">
                    <span class="cc-badge {{ $netBalance >= 0 ? 'cc-badge-income' : 'cc-badge-expense' }}">
                      <i class="bi {{ $netBalance >= 0 ? 'bi-piggy-bank' : 'bi-exclamation-triangle' }}"></i>
                      Savings Rate: {{ $savingsRate }}%
                    </span>
                  </div>
                </div>
                <div class="cc-stat-icon" style="background:var(--cc-primary-light);color:var(--cc-primary);">
                  <i class="bi bi-piggy-bank-fill"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Total Transactions Count -->
          <div class="col-xl-3 col-md-6">
            <div class="cc-stat-card savings">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="cc-stat-label">Transactions</div>
                  <div class="cc-stat-value" style="color:var(--cc-cyan);">
                    {{ $totalTransactionsCount }}
                  </div>
                  <div class="cc-stat-meta">
                    <span class="text-secondary" style="font-size:var(--fs-2xs);">{{ $incomeCount }} income &bull; {{ $expenseCount }} expense</span>
                  </div>
                </div>
                <div class="cc-stat-icon" style="background:var(--cc-cyan-light);color:var(--cc-cyan);">
                  <i class="bi bi-receipt"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ANALYTICS HIGHLIGHTS & SUMMARY METRICS -->
        <div class="row g-3 mb-4">
          <!-- Top Spending Category -->
          <div class="col-lg-3 col-md-6">
            <div class="cc-highlight-card h-100">
              <div class="cc-highlight-icon" style="background:rgba(239, 68, 68, 0.12);color:var(--cc-expense);">
                <i class="bi {{ $topCategory['icon'] ?? 'bi-fire' }}"></i>
              </div>
              <div>
                <div class="text-secondary" style="font-size:var(--fs-2xs);font-weight:var(--fw-bold);text-transform:uppercase;">Top Spending Category</div>
                @if($topCategory && $topCategory['amount'] > 0)
                  <div class="font-bold" style="font-size:var(--fs-sm);color:var(--cc-text-main);">{{ $topCategory['name'] }}</div>
                  <div style="font-size:var(--fs-xs);color:var(--cc-expense);font-weight:var(--fw-semibold);">
                    Rs. {{ number_format($topCategory['amount'], 2) }} <span class="text-secondary">({{ $topCategory['percentage'] }}%)</span>
                  </div>
                @else
                  <div class="text-secondary" style="font-size:var(--fs-xs);font-style:italic;">No expense data</div>
                @endif
              </div>
            </div>
          </div>

          <!-- Highest Spending Day -->
          <div class="col-lg-3 col-md-6">
            <div class="cc-highlight-card h-100">
              <div class="cc-highlight-icon" style="background:rgba(245, 158, 11, 0.12);color:var(--cc-warning);">
                <i class="bi bi-calendar-check"></i>
              </div>
              <div>
                <div class="text-secondary" style="font-size:var(--fs-2xs);font-weight:var(--fw-bold);text-transform:uppercase;">Highest Spending Day</div>
                @if($highestSpendingDay)
                  <div class="font-bold" style="font-size:var(--fs-sm);color:var(--cc-text-main);">{{ $highestSpendingDay['short_date'] }}</div>
                  <div style="font-size:var(--fs-xs);color:var(--cc-warning);font-weight:var(--fw-semibold);">
                    Rs. {{ number_format($highestSpendingDay['amount'], 2) }}
                  </div>
                @else
                  <div class="text-secondary" style="font-size:var(--fs-xs);font-style:italic;">No spending recorded</div>
                @endif
              </div>
            </div>
          </div>

          <!-- Average Daily Spending -->
          <div class="col-lg-3 col-md-6">
            <div class="cc-highlight-card h-100">
              <div class="cc-highlight-icon" style="background:rgba(99, 102, 241, 0.12);color:var(--cc-primary);">
                <i class="bi bi-speedometer"></i>
              </div>
              <div>
                <div class="text-secondary" style="font-size:var(--fs-2xs);font-weight:var(--fw-bold);text-transform:uppercase;">Average Daily Spending</div>
                <div class="font-bold" style="font-size:var(--fs-sm);color:var(--cc-text-main);">
                  Rs. {{ number_format($averageDailySpending, 2) }} / day
                </div>
                <div style="font-size:var(--fs-2xs);color:var(--cc-text-muted);">
                  Across {{ $totalDays }} calendar days
                </div>
              </div>
            </div>
          </div>

          <!-- Month-over-Month Comparison -->
          <div class="col-lg-3 col-md-6">
            <div class="cc-highlight-card h-100">
              <div class="cc-highlight-icon" style="background:rgba(16, 185, 129, 0.12);color:var(--cc-income);">
                <i class="bi bi-arrow-left-right"></i>
              </div>
              <div>
                <div class="text-secondary" style="font-size:var(--fs-2xs);font-weight:var(--fw-bold);text-transform:uppercase;">MoM Comparison</div>
                @if($monthOverMonth && $monthOverMonth['has_data'])
                  <div class="font-bold" style="font-size:var(--fs-sm);color:{{ $monthOverMonth['is_lower'] ? 'var(--cc-income)' : 'var(--cc-expense)' }};">
                    {{ $monthOverMonth['diff_expense_percent'] > 0 ? '+' : '' }}{{ $monthOverMonth['diff_expense_percent'] }}%
                  </div>
                  <div style="font-size:var(--fs-2xs);color:var(--cc-text-muted);">
                    {{ $monthOverMonth['is_lower'] ? 'Saved' : 'Spent' }} Rs. {{ number_format($monthOverMonth['diff_expense_abs'], 2) }} vs {{ $monthOverMonth['prev_month_short'] }}
                  </div>
                @else
                  <div class="text-secondary" style="font-size:var(--fs-xs);font-style:italic;">No previous-period data</div>
                @endif
              </div>
            </div>
          </div>
        </div>

        <!-- DETERMINISTIC REPORT HIGHLIGHTS -->
        @if(!empty($highlights))
        <div class="cc-card mb-4">
          <div class="cc-card-header py-2">
            <h2 class="cc-card-title mb-0" style="font-size:var(--fs-sm);"><i class="bi bi-stars me-2" style="color:var(--cc-primary);"></i>Report Highlights &amp; Insights</h2>
          </div>
          <div class="cc-card-body py-3">
            <div class="row g-2">
              @foreach($highlights as $hl)
                <div class="col-md-6">
                  <div class="d-flex align-items-start gap-2 p-2 rounded" style="background:var(--cc-surface-tint); border:1px solid var(--cc-border-subtle);">
                    <i class="bi {{ $hl['icon'] }} mt-1" style="color:{{ $hl['color'] }}; font-size:1rem;"></i>
                    <div>
                      <div class="font-bold" style="font-size:var(--fs-xs); color:var(--cc-text-main);">{{ $hl['title'] }}</div>
                      <div style="font-size:var(--fs-xs); color:var(--cc-text-secondary);">{{ $hl['text'] }}</div>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
        @endif

        @if($totalTransactionsCount === 0)
        <!-- EMPTY REPORT STATE -->
        <div class="cc-card p-5 text-center my-4">
          <div style="font-size:3rem; color:var(--cc-text-muted);" class="mb-3">
            <i class="bi bi-folder-x"></i>
          </div>
          <h3 class="font-bold mb-2">No financial activity for this period</h3>
          <p class="text-secondary mb-4" style="max-width:480px; margin:0 auto;">
            Try selecting another date range or add your first transaction to view visual analytics and financial reports.
          </p>
          <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('transactions.create') }}?type=income" class="btn-cc btn-cc-primary">
              <i class="bi bi-plus-circle me-1"></i> Add Income
            </a>
            <a href="{{ route('transactions.create') }}?type=expense" class="btn-cc btn-cc-secondary">
              <i class="bi bi-dash-circle me-1"></i> Add Expense
            </a>
          </div>
        </div>
        @else

        <!-- CHARTS SECTION ROW 1: CATEGORY BREAKDOWN & INCOME VS EXPENSE -->
        <div class="row g-4 mb-4">
          
          <!-- Category Breakdown Chart -->
          <div class="col-lg-6">
            <div class="cc-card h-100">
              <div class="cc-card-header">
                <h2 class="cc-card-title"><i class="bi bi-pie-chart-fill me-2" style="color:var(--cc-warning);"></i>Monthly Spending by Category</h2>
                <span class="cc-badge cc-badge-primary">{{ $categoryBreakdown->count() }} Categories</span>
              </div>
              <div class="cc-card-body">
                @if($categoryBreakdown->isEmpty())
                  <div class="text-center py-5 text-secondary fst-italic">No expense data recorded in this period.</div>
                @else
                  <div class="row align-items-center g-3">
                    <div class="col-sm-6 text-center">
                      <div style="position:relative; height:210px; width:100%; max-width:210px; margin:0 auto;">
                        <canvas id="categoryDonutChart"></canvas>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="d-flex flex-column gap-2" style="max-height:220px; overflow-y:auto; padding-right:4px;">
                        @foreach($categoryBreakdown as $cat)
                          <div>
                            <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:var(--fs-xs);">
                              <span class="d-flex align-items-center gap-1 text-truncate">
                                <span style="width:8px; height:8px; border-radius:50%; background:{{ $cat['color'] }}; display:inline-block; flex-shrink:0;"></span>
                                <span class="font-semibold text-truncate">{{ $cat['name'] }}</span>
                              </span>
                              <span class="font-bold ms-2" style="white-space:nowrap;">
                                Rs. {{ number_format($cat['amount'], 2) }}
                                <small class="text-muted">({{ $cat['percentage'] }}%)</small>
                              </span>
                            </div>
                            <div class="cc-progress-bar" style="height:5px;">
                              <div class="cc-progress-fill" style="width:{{ $cat['percentage'] }}%; background:{{ $cat['color'] }};"></div>
                            </div>
                          </div>
                        @endforeach
                      </div>
                    </div>
                  </div>
                @endif
              </div>
            </div>
          </div>

          <!-- Income vs Expense Comparison -->
          <div class="col-lg-6">
            <div class="cc-card h-100">
              <div class="cc-card-header">
                <h2 class="cc-card-title"><i class="bi bi-bar-chart-steps me-2" style="color:var(--cc-primary);"></i>Income vs Expense Comparison</h2>
                <div class="d-flex gap-2" style="font-size:var(--fs-2xs);">
                  <span class="d-flex align-items-center gap-1"><span style="width:8px;height:8px;border-radius:2px;background:var(--cc-income);"></span>Income</span>
                  <span class="d-flex align-items-center gap-1"><span style="width:8px;height:8px;border-radius:2px;background:var(--cc-expense);"></span>Expense</span>
                </div>
              </div>
              <div class="cc-card-body d-flex flex-column justify-content-between">
                <div style="height:190px; width:100%; position:relative;">
                  <canvas id="incomeExpenseBarChart"></canvas>
                </div>
                <div class="row g-2 mt-3 pt-3" style="border-top:1px solid var(--cc-border-subtle); text-align:center;">
                  <div class="col-4">
                    <div style="font-size:var(--fs-2xs); color:var(--cc-text-muted); text-transform:uppercase;">Inflow</div>
                    <div class="font-bold" style="font-size:var(--fs-xs); color:var(--cc-income);">Rs. {{ number_format($totalIncome, 2) }}</div>
                  </div>
                  <div class="col-4">
                    <div style="font-size:var(--fs-2xs); color:var(--cc-text-muted); text-transform:uppercase;">Outflow</div>
                    <div class="font-bold" style="font-size:var(--fs-xs); color:var(--cc-expense);">Rs. {{ number_format($totalExpense, 2) }}</div>
                  </div>
                  <div class="col-4">
                    <div style="font-size:var(--fs-2xs); color:var(--cc-text-muted); text-transform:uppercase;">Balance</div>
                    <div class="font-bold" style="font-size:var(--fs-xs); color:{{ $netBalance >= 0 ? 'var(--cc-primary)' : 'var(--cc-expense)' }};">
                      Rs. {{ number_format($netBalance, 2) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- CHARTS SECTION ROW 2: 6-MONTH TREND & DAILY SPENDING -->
        <div class="row g-4 mb-4">
          
          <!-- 6-Month Consecutive Financial Trend -->
          <div class="col-lg-8">
            <div class="cc-card h-100">
              <div class="cc-card-header">
                <h2 class="cc-card-title"><i class="bi bi-graph-up me-2" style="color:var(--cc-primary);"></i>6-Month Financial Trend</h2>
                <span class="text-secondary" style="font-size:var(--fs-2xs);">Consecutive 6 Calendar Months</span>
              </div>
              <div class="cc-card-body d-flex flex-column justify-content-between">
                <div style="height:230px; width:100%; position:relative;">
                  <canvas id="sixMonthTrendChart"></canvas>
                </div>
                <div class="row g-3 mt-2 pt-3" style="border-top:1px solid var(--cc-border-subtle); text-align:center;">
                  <div class="col-4">
                    <div style="font-size:var(--fs-2xs); color:var(--cc-text-muted);">Avg Monthly Income</div>
                    <div class="font-bold" style="font-size:var(--fs-xs); color:var(--cc-income);">Rs. {{ number_format($sixMonthTrend['avg_income'], 2) }}</div>
                  </div>
                  <div class="col-4">
                    <div style="font-size:var(--fs-2xs); color:var(--cc-text-muted);">Avg Monthly Expense</div>
                    <div class="font-bold" style="font-size:var(--fs-xs); color:var(--cc-expense);">Rs. {{ number_format($sixMonthTrend['avg_expense'], 2) }}</div>
                  </div>
                  <div class="col-4">
                    <div style="font-size:var(--fs-2xs); color:var(--cc-text-muted);">Avg Net Savings</div>
                    <div class="font-bold" style="font-size:var(--fs-xs); color:var(--cc-primary);">Rs. {{ number_format($sixMonthTrend['avg_savings'], 2) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Weekly Spending Summary -->
          <div class="col-lg-4">
            <div class="cc-card h-100">
              <div class="cc-card-header">
                <h2 class="cc-card-title"><i class="bi bi-calendar-week me-2" style="color:var(--cc-cyan);"></i>Weekly Spending</h2>
              </div>
              <div class="cc-card-body d-flex flex-column justify-content-between">
                <div style="height:170px; width:100%; position:relative;">
                  <canvas id="weeklySpendingChart"></canvas>
                </div>
                <div class="d-flex flex-column gap-2 mt-2 pt-2" style="border-top:1px solid var(--cc-border-subtle);">
                  @foreach($weeklySpending['detailed'] as $w)
                    <div class="d-flex justify-content-between align-items-center" style="font-size:var(--fs-xs);">
                      <span class="text-secondary">{{ $w['label'] }}</span>
                      <span class="font-bold">Rs. {{ number_format($w['amount'], 2) }}</span>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- DAILY SPENDING TIMELINE CHART -->
        <div class="cc-card mb-4">
          <div class="cc-card-header">
            <h2 class="cc-card-title"><i class="bi bi-activity me-2" style="color:var(--cc-primary);"></i>Daily Spending Trend ({{ $periodLabel }})</h2>
            <span class="text-secondary" style="font-size:var(--fs-2xs);">Hover over points for daily totals</span>
          </div>
          <div class="cc-card-body">
            <div style="height:210px; width:100%; position:relative;">
              <canvas id="dailySpendingChart"></canvas>
            </div>
          </div>
        </div>

        <!-- BUDGET PERFORMANCE INTEGRATION (PHASE 4 CONTEXT) -->
        @if($budgetComparison['has_budgets'])
        <div class="cc-card mb-4">
          <div class="cc-card-header">
            <h2 class="cc-card-title"><i class="bi bi-bullseye me-2" style="color:var(--cc-primary);"></i>Budget Performance Context ({{ $selectedMonth }})</h2>
            <a href="{{ route('budgets') }}" class="btn-cc btn-cc-ghost btn-cc-xs">
              Manage Budgets <i class="bi bi-arrow-right ms-1"></i>
            </a>
          </div>
          <div class="cc-card-body">
            <div class="row g-3">
              @foreach($budgetComparison['items'] as $b)
                <div class="col-md-6 col-lg-4">
                  <div class="p-3 rounded" style="background:var(--cc-surface-tint); border:1px solid var(--cc-border-subtle);">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <div class="d-flex align-items-center gap-2">
                        <i class="bi {{ $b['category_icon'] }}" style="color:var(--cc-primary);"></i>
                        <span class="font-bold" style="font-size:var(--fs-sm);">{{ $b['category_name'] }}</span>
                      </div>
                      <span class="cc-badge {{ $b['status'] === 'over_budget' ? 'cc-badge-expense' : ($b['status'] === 'near_limit' ? 'cc-badge-warning' : 'cc-badge-income') }}">
                        {{ $b['status'] === 'over_budget' ? 'Over Budget' : ($b['status'] === 'near_limit' ? 'Near Limit' : 'On Track') }}
                      </span>
                    </div>
                    <div class="d-flex justify-content-between mb-1" style="font-size:var(--fs-xs);">
                      <span class="text-secondary">Spent: <strong>Rs. {{ number_format($b['spent_amount'], 2) }}</strong></span>
                      <span class="text-secondary">Limit: <strong>Rs. {{ number_format($b['limit_amount'], 2) }}</strong></span>
                    </div>
                    <div class="cc-progress-bar" style="height:6px;">
                      <div class="cc-progress-fill" style="width:{{ min(100, $b['usage_percent']) }}%; background:{{ $b['status'] === 'over_budget' ? 'var(--cc-expense)' : ($b['status'] === 'near_limit' ? 'var(--cc-warning)' : 'var(--cc-income)') }};"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2" style="font-size:var(--fs-2xs);">
                      <span class="text-muted">{{ $b['usage_percent'] }}% utilized</span>
                      <span class="text-muted">Remaining: Rs. {{ number_format($b['remaining'], 2) }}</span>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
        @endif

        <!-- ITEMIZED TRANSACTION RECORD TABLE -->
        <div class="cc-card">
          <div class="cc-card-header">
            <h2 class="cc-card-title"><i class="bi bi-list-check me-2" style="color:var(--cc-primary);"></i>Filtered Transaction Ledger</h2>
            <span class="cc-badge cc-badge-primary">{{ $transactions->total() }} Records Found</span>
          </div>
          <div class="cc-table-wrapper" style="border:none; border-radius:0;">
            <table class="cc-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Description</th>
                  <th>Category</th>
                  <th class="text-center">Type</th>
                  <th class="text-end">Amount</th>
                </tr>
              </thead>
              <tbody>
                @forelse($transactions as $tx)
                <tr>
                  <td>
                    <div class="font-bold" style="font-size:var(--fs-xs);">{{ \Carbon\Carbon::parse($tx->transaction_date)->format('M d, Y') }}</div>
                  </td>
                  <td>
                    <div class="font-semibold" style="font-size:var(--fs-sm);">{{ $tx->description }}</div>
                    @if($tx->note)
                      <div style="font-size:var(--fs-2xs); color:var(--cc-text-muted);">{{ Str::limit($tx->note, 50) }}</div>
                    @endif
                  </td>
                  <td>
                    <span class="d-inline-flex align-items-center gap-1" style="font-size:var(--fs-xs);">
                      <i class="bi {{ $tx->category->icon ?? 'bi-tag' }}" style="color:{{ $tx->category->color ?? 'var(--cc-primary)' }};"></i>
                      <span>{{ $tx->category->name ?? 'Uncategorized' }}</span>
                    </span>
                  </td>
                  <td class="text-center">
                    <span class="cc-badge {{ $tx->type === 'income' ? 'cc-badge-income' : 'cc-badge-expense' }}">
                      {{ ucfirst($tx->type) }}
                    </span>
                  </td>
                  <td class="text-end font-bold cc-amount" style="color:{{ $tx->type === 'income' ? 'var(--cc-income)' : 'var(--cc-expense)' }};">
                    {{ $tx->type === 'income' ? '+' : '-' }}Rs. {{ number_format($tx->amount, 2) }}
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="5" class="text-center py-4 text-secondary fst-italic">
                    No transactions match the selected filter criteria.
                  </td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @if($transactions->hasPages())
          <div class="cc-card-footer d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
            <div style="font-size:var(--fs-xs); color:var(--cc-text-muted);">
              Showing <strong>{{ $transactions->firstItem() }}</strong> to <strong>{{ $transactions->lastItem() }}</strong> of <strong>{{ $transactions->total() }}</strong> entries
            </div>
            <div>
              {{ $transactions->links() }}
            </div>
          </div>
          @endif
        </div>

        @endif

      </div> <!-- /#reportExportContainer -->

    </div>
  </main>
</div>

<!-- CONFIRM LOGOUT MODAL -->
<div class="cc-modal-overlay" id="logoutModal">
  <div class="cc-modal" style="max-width:380px;">
    <div class="cc-modal-header">
      <h3 class="cc-card-title">Confirm Logout</h3>
      <button class="btn-cc btn-cc-ghost btn-cc-sm" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="cc-card-body text-center">
      <p class="text-secondary mb-0" style="font-size:var(--fs-sm);">End your current session?</p>
    </div>
    <div class="cc-card-footer d-flex justify-content-end gap-2">
      <button class="btn-cc btn-cc-secondary btn-cc-sm" data-modal-close>Cancel</button>
      <form action="{{ route('logout') }}" method="POST" style="display:inline;">
        @csrf
        <button type="submit" class="btn-cc btn-cc-danger btn-cc-sm">Logout</button>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>

<script>
  'use strict';

  function togglePeriodInputs(val) {
    const monthWrap = document.getElementById('monthSelectorWrapper');
    const customFields = document.querySelectorAll('.custom-date-field');
    if (val === 'custom') {
      if (monthWrap) monthWrap.style.display = 'none';
      customFields.forEach(el => el.style.display = 'block');
    } else {
      if (monthWrap) monthWrap.style.display = 'block';
      customFields.forEach(el => el.style.display = 'none');
    }
  }

  function toggleCategoryDropdowns(val) {
    const catWrap = document.getElementById('categorySelectWrapper');
    const srcWrap = document.getElementById('incomeSourceSelectWrapper');
    if (val === 'income') {
      if (catWrap) catWrap.style.display = 'none';
      if (srcWrap) srcWrap.style.display = 'block';
    } else if (val === 'expense') {
      if (catWrap) catWrap.style.display = 'block';
      if (srcWrap) srcWrap.style.display = 'none';
    } else {
      if (catWrap) catWrap.style.display = 'block';
      if (srcWrap) srcWrap.style.display = 'block';
    }
  }

  function showExportToast(type) {
    if (window.Toast) {
      Toast.success('Generating ' + type, 'Your ' + type + ' report is being prepared for download.');
    }
  }

  // Export report as high-resolution PNG image
  function exportReportImage() {
    const container = document.getElementById('reportExportContainer');
    if (!container) return;

    if (window.Toast) {
      Toast.show('info', 'Generating Image', 'Preparing high-resolution report snapshot...');
    }

    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const bgCol = currentTheme === 'dark' ? '#0f172a' : '#f8fafc';

    html2canvas(container, {
      scale: 2,
      backgroundColor: bgCol,
      useCORS: true,
      logging: false
    }).then(canvas => {
      const link = document.createElement('a');
      link.download = 'CampusCoin_Financial_Report_{{ preg_replace("/[^A-Za-z0-9_\-]/", "_", $periodLabel) }}.png';
      link.href = canvas.toDataURL('image/png');
      link.click();
      if (window.Toast) {
        Toast.success('Image Downloaded', 'Financial report image saved successfully.');
      }
    }).catch(err => {
      console.error('Image export failed:', err);
      if (window.Toast) {
        Toast.error('Export Error', 'Unable to capture image. Try using PDF export instead.');
      }
    });
  }

  // Initialize interactive charts when DOM loads
  document.addEventListener('DOMContentLoaded', () => {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.05)';

    // 1. Category Donut Chart
    const catCanvas = document.getElementById('categoryDonutChart');
    if (catCanvas) {
      const catLabels = {!! json_encode($categoryBreakdown->pluck('name')) !!};
      const catData = {!! json_encode($categoryBreakdown->pluck('amount')) !!};
      const catColors = {!! json_encode($categoryBreakdown->pluck('color')) !!};

      if (catData.length > 0) {
        new Chart(catCanvas, {
          type: 'doughnut',
          data: {
            labels: catLabels,
            datasets: [{
              data: catData,
              backgroundColor: catColors,
              borderWidth: 2,
              borderColor: isDark ? '#1e293b' : '#ffffff',
              hoverOffset: 6
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  label: function(ctx) {
                    const val = ctx.parsed || 0;
                    return ' Rs. ' + Number(val).toLocaleString();
                  }
                }
              }
            },
            cutout: '70%'
          }
        });
      }
    }

    // 2. Income vs Expense Bar Chart
    const incExpCanvas = document.getElementById('incomeExpenseBarChart');
    if (incExpCanvas) {
      new Chart(incExpCanvas, {
        type: 'bar',
        data: {
          labels: ['Inflow (Income)', 'Outflow (Expenses)', 'Net Savings'],
          datasets: [{
            data: [{{ $totalIncome }}, {{ $totalExpense }}, {{ max(0, $netBalance) }}],
            backgroundColor: ['#10b981', '#ef4444', '#6366f1'],
            borderRadius: 6,
            maxBarThickness: 45
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: (ctx) => ' Rs. ' + Number(ctx.parsed.y).toLocaleString()
              }
            }
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { color: textColor, font: { size: 11 } }
            },
            y: {
              grid: { color: gridColor },
              ticks: {
                color: textColor,
                font: { size: 10 },
                callback: (v) => 'Rs. ' + Number(v).toLocaleString()
              }
            }
          }
        }
      });
    }

    // 3. Consecutive 6-Month Trend Chart
    const sixTrendCanvas = document.getElementById('sixMonthTrendChart');
    if (sixTrendCanvas) {
      new Chart(sixTrendCanvas, {
        type: 'bar',
        data: {
          labels: {!! json_encode($sixMonthTrend['labels']) !!},
          datasets: [
            {
              label: 'Income',
              data: {!! json_encode($sixMonthTrend['income']) !!},
              backgroundColor: 'rgba(16, 185, 129, 0.85)',
              borderRadius: 4,
              categoryPercentage: 0.7,
              barPercentage: 0.8
            },
            {
              label: 'Expenses',
              data: {!! json_encode($sixMonthTrend['expense']) !!},
              backgroundColor: 'rgba(239, 68, 68, 0.85)',
              borderRadius: 4,
              categoryPercentage: 0.7,
              barPercentage: 0.8
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top',
              labels: { color: textColor, boxWidth: 12, font: { size: 11 } }
            },
            tooltip: {
              callbacks: {
                label: (ctx) => ' ' + ctx.dataset.label + ': Rs. ' + Number(ctx.parsed.y).toLocaleString()
              }
            }
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { color: textColor, font: { size: 11 } }
            },
            y: {
              grid: { color: gridColor },
              ticks: {
                color: textColor,
                font: { size: 10 },
                callback: (v) => 'Rs. ' + Number(v).toLocaleString()
              }
            }
          }
        }
      });
    }

    // 4. Weekly Spending Bar Chart
    const weeklyCanvas = document.getElementById('weeklySpendingChart');
    if (weeklyCanvas) {
      new Chart(weeklyCanvas, {
        type: 'bar',
        data: {
          labels: {!! json_encode($weeklySpending['labels']) !!},
          datasets: [{
            label: 'Spending',
            data: {!! json_encode($weeklySpending['data']) !!},
            backgroundColor: '#14b8a6',
            borderRadius: 4,
            maxBarThickness: 30
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: (ctx) => ' Rs. ' + Number(ctx.parsed.y).toLocaleString()
              }
            }
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { color: textColor, font: { size: 10 } }
            },
            y: {
              grid: { color: gridColor },
              ticks: {
                color: textColor,
                font: { size: 9 },
                callback: (v) => 'Rs. ' + Number(v).toLocaleString()
              }
            }
          }
        }
      });
    }

    // 5. Daily Spending Timeline Chart
    const dailyCanvas = document.getElementById('dailySpendingChart');
    if (dailyCanvas) {
      new Chart(dailyCanvas, {
        type: 'line',
        data: {
          labels: {!! json_encode($dailySpending['labels']) !!},
          datasets: [{
            label: 'Daily Expense',
            data: {!! json_encode($dailySpending['data']) !!},
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            borderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 6,
            pointBackgroundColor: '#6366f1',
            fill: true,
            tension: 0.3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                title: (ctx) => ctx[0].label,
                label: (ctx) => ' Expense: Rs. ' + Number(ctx.parsed.y).toLocaleString()
              }
            }
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: {
                color: textColor,
                font: { size: 10 },
                maxTicksLimit: 15
              }
            },
            y: {
              grid: { color: gridColor },
              ticks: {
                color: textColor,
                font: { size: 10 },
                callback: (v) => 'Rs. ' + Number(v).toLocaleString()
              }
            }
          }
        }
      });
    }

  });
</script>
</body>
</html>
