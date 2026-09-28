@extends('layouts.admin')

@section('title', 'System Usage & Analytics')
@section('page_title', 'System Usage & Analytics')

@section('content')
<div class="d-flex flex-column gap-4">

  <!-- TIMEFRAME FILTER BAR -->
  <div class="admin-stat-card">
    <form action="{{ route('admin.statistics.index') }}" method="GET" class="row g-2 align-items-center">
      <div class="col-12 col-md-3">
        <label class="form-label small fw-semibold text-secondary mb-1">Timeframe Preset</label>
        <select name="timeframe" class="form-select" onchange="if(this.value!=='custom') this.form.submit()">
          <option value="current_month" {{ $timeframe === 'current_month' ? 'selected' : '' }}>Current Month</option>
          <option value="last_3_months" {{ $timeframe === 'last_3_months' ? 'selected' : '' }}>Last 3 Months</option>
          <option value="last_6_months" {{ $timeframe === 'last_6_months' ? 'selected' : '' }}>Last 6 Months</option>
          <option value="year_to_date" {{ $timeframe === 'year_to_date' ? 'selected' : '' }}>Year-to-Date (YTD)</option>
          <option value="all_time" {{ $timeframe === 'all_time' ? 'selected' : '' }}>All Time</option>
          <option value="custom" {{ $timeframe === 'custom' ? 'selected' : '' }}>Custom Date Range...</option>
        </select>
      </div>

      <div class="col-6 col-md-3">
        <label class="form-label small fw-semibold text-secondary mb-1">Start Date</label>
        <input type="date" name="start_date" class="form-control" value="{{ $startDate->toDateString() }}">
      </div>

      <div class="col-6 col-md-3">
        <label class="form-label small fw-semibold text-secondary mb-1">End Date</label>
        <input type="date" name="end_date" class="form-control" value="{{ $endDate->toDateString() }}">
      </div>

      <div class="col-12 col-md-3 d-flex align-items-end gap-2 pt-md-4">
        <button type="submit" class="btn btn-warning flex-fill fw-semibold">
          <i class="bi bi-funnel-fill me-1"></i> Apply Filter
        </button>
        <a href="{{ route('admin.statistics.index') }}" class="btn btn-outline-secondary" title="Reset">
          <i class="bi bi-arrow-counterclockwise"></i>
        </a>
      </div>
    </form>
  </div>

  <!-- SUMMARY KPI ROW -->
  <div class="row g-3">
    <!-- 1. Platform Users -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="admin-stat-card h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-secondary small fw-semibold">Platform Users</span>
          <div class="admin-card-icon bg-primary-subtle text-primary"><i class="bi bi-people-fill"></i></div>
        </div>
        <div class="h3 fw-bold mb-1">{{ number_format($totalUsers) }}</div>
        <div class="small text-success"><i class="bi bi-plus-lg"></i> {{ $newUsersInPeriod }} registered in period</div>
      </div>
    </div>

    <!-- 2. Financial Volume -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="admin-stat-card h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-secondary small fw-semibold">Period Net Flow</span>
          <div class="admin-card-icon bg-success-subtle text-success"><i class="bi bi-cash-stack"></i></div>
        </div>
        <div class="h3 fw-bold mb-1 {{ $netBalance >= 0 ? 'text-success' : 'text-danger' }}">
          Rs. {{ number_format($netBalance, 2) }}
        </div>
        <div class="small text-muted">{{ $totalTransactionsCount }} transactions recorded</div>
      </div>
    </div>

    <!-- 3. AI Insights Metrics -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="admin-stat-card h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-secondary small fw-semibold">AI Insights Reports</span>
          <div class="admin-card-icon" style="background:#ede9fe; color:#7c3aed;"><i class="bi bi-robot"></i></div>
        </div>
        <div class="h3 fw-bold mb-1">{{ number_format($totalAiInsights) }}</div>
        <div class="small text-muted">{{ $aiGeneratedCount }} Cloud AI • {{ $aiFallbackCount }} Heuristics</div>
      </div>
    </div>

    <!-- 4. Saving Tips & Budgets -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="admin-stat-card h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-secondary small fw-semibold">Tips &amp; Budgets</span>
          <div class="admin-card-icon bg-warning-subtle text-warning"><i class="bi bi-lightbulb-fill"></i></div>
        </div>
        <div class="h3 fw-bold mb-1">{{ number_format($totalSavingTips) }} Tips</div>
        <div class="small text-muted">{{ $totalBudgets }} Budgets (Avg: Rs. {{ number_format($avgBudgetLimit, 0) }})</div>
      </div>
    </div>
  </div>

  <!-- CHARTS ROW 1: USER GROWTH & TRANSACTION VOLUME -->
  <div class="row g-4">
    <!-- USER GROWTH LINE CHART -->
    <div class="col-12 col-lg-6">
      <div class="admin-stat-card h-100">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
          <h3 class="h6 fw-bold mb-0"><i class="bi bi-graph-up-arrow text-primary me-2"></i> Student Registration Trend</h3>
          <span class="badge bg-light text-secondary border">6 Months</span>
        </div>
        <div style="height:260px; position:relative;">
          <canvas id="userGrowthChart"></canvas>
        </div>
      </div>
    </div>

    <!-- TRANSACTION INFLOW VS OUTFLOW BAR CHART -->
    <div class="col-12 col-lg-6">
      <div class="admin-stat-card h-100">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
          <h3 class="h6 fw-bold mb-0"><i class="bi bi-bar-chart-fill text-success me-2"></i> Monthly Cashflow Volume (PKR)</h3>
          <span class="badge bg-light text-secondary border">6 Months</span>
        </div>
        <div style="height:260px; position:relative;">
          <canvas id="cashflowChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- CHARTS ROW 2: CATEGORY BREAKDOWN & METRICS TABLE -->
  <div class="row g-4">
    <!-- CATEGORY DISTRIBUTION DOUGHNUT -->
    <div class="col-12 col-lg-5">
      <div class="admin-stat-card h-100">
        <div class="pb-3 mb-3 border-bottom">
          <h3 class="h6 fw-bold mb-0"><i class="bi bi-pie-chart-fill text-warning me-2"></i> Top Spending Categories in Period</h3>
        </div>
        <div style="height:240px; position:relative;">
          @if (!empty($categoryChartLabels) && count($categoryChartLabels) > 0)
            <canvas id="categoryDonutChart"></canvas>
          @else
            <div class="d-flex align-items-center justify-content-center h-100 text-muted small">
              No category expense transactions in this period.
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- FINANCIAL BREAKDOWN DATA TABLE -->
    <div class="col-12 col-lg-7">
      <div class="admin-stat-card h-100 p-0 overflow-hidden">
        <div class="p-3 border-bottom bg-light">
          <h3 class="h6 fw-bold mb-0"><i class="bi bi-table text-secondary me-2"></i> Period Financial Breakdown</h3>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
            <tbody>
              <tr>
                <td class="fw-semibold" style="padding-left:1.25rem;">Total Income Inflow</td>
                <td class="text-end fw-bold text-success">{{ $incomeCount }} entries</td>
                <td class="text-end fw-bold text-success" style="padding-right:1.25rem;">Rs. {{ number_format($totalIncome, 2) }}</td>
              </tr>
              <tr>
                <td class="fw-semibold" style="padding-left:1.25rem;">Total Expense Outflow</td>
                <td class="text-end fw-bold text-danger">{{ $expenseCount }} entries</td>
                <td class="text-end fw-bold text-danger" style="padding-right:1.25rem;">Rs. {{ number_format($totalExpense, 2) }}</td>
              </tr>
              <tr class="table-light">
                <td class="fw-bold" style="padding-left:1.25rem;">Net Platform Cashflow</td>
                <td class="text-end fw-bold">{{ $totalTransactionsCount }} total</td>
                <td class="text-end fw-bold {{ $netBalance >= 0 ? 'text-success' : 'text-danger' }}" style="padding-right:1.25rem;">
                  Rs. {{ number_format($netBalance, 2) }}
                </td>
              </tr>
              <tr>
                <td class="fw-semibold" style="padding-left:1.25rem;">Active Student Accounts</td>
                <td class="text-end text-muted">{{ $inactiveUsers }} inactive</td>
                <td class="text-end fw-bold text-primary" style="padding-right:1.25rem;">{{ $activeUsers }} Active</td>
              </tr>
              <tr>
                <td class="fw-semibold" style="padding-left:1.25rem;">Saving Tips Generated</td>
                <td class="text-end text-muted">{{ $pinnedSavingTips }} pinned</td>
                <td class="text-end fw-bold text-warning" style="padding-right:1.25rem;">{{ $totalSavingTips }} Total</td>
              </tr>
              <tr>
                <td class="fw-semibold" style="padding-left:1.25rem;">AI Monthly Insight Reports</td>
                <td class="text-end text-muted">{{ $aiFallbackCount }} heuristics</td>
                <td class="text-end fw-bold" style="color:#7c3aed; padding-right:1.25rem;">{{ $totalAiInsights }} Reports</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const trendMonths = @json($trendMonths);
  const trendIncome = @json($trendIncome);
  const trendExpense = @json($trendExpense);
  const trendUserGrowth = @json($trendUserGrowth);
  const categoryLabels = @json($categoryChartLabels);
  const categoryData = @json($categoryChartData);

  // 1. USER GROWTH CHART
  const ctxGrowth = document.getElementById('userGrowthChart');
  if (ctxGrowth) {
    new Chart(ctxGrowth, {
      type: 'line',
      data: {
        labels: trendMonths,
        datasets: [{
          label: 'New Registrations',
          data: trendUserGrowth,
          borderColor: '#f59e0b',
          backgroundColor: 'rgba(245, 158, 11, 0.15)',
          borderWidth: 2.5,
          fill: true,
          tension: 0.35,
          pointRadius: 4,
          pointBackgroundColor: '#f59e0b'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } },
          x: { grid: { display: false } }
        }
      }
    });
  }

  // 2. CASHFLOW BAR CHART
  const ctxCashflow = document.getElementById('cashflowChart');
  if (ctxCashflow) {
    new Chart(ctxCashflow, {
      type: 'bar',
      data: {
        labels: trendMonths,
        datasets: [
          {
            label: 'Income',
            data: trendIncome,
            backgroundColor: '#10b981',
            borderRadius: 6
          },
          {
            label: 'Expenses',
            data: trendExpense,
            backgroundColor: '#ef4444',
            borderRadius: 6
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top', labels: { boxWidth: 12 } }
        },
        scales: {
          y: { beginAtZero: true },
          x: { grid: { display: false } }
        }
      }
    });
  }

  // 3. CATEGORY DONUT CHART
  const ctxCategory = document.getElementById('categoryDonutChart');
  if (ctxCategory && categoryLabels.length > 0) {
    new Chart(ctxCategory, {
      type: 'doughnut',
      data: {
        labels: categoryLabels,
        datasets: [{
          data: categoryData,
          backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#ec4899', '#6b7280'],
          borderWidth: 2,
          borderColor: '#ffffff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } },
          tooltip: {
            callbacks: {
              label: (context) => ` ${context.label}: ${context.raw}%`
            }
          }
        },
        cutout: '65%'
      }
    });
  }
});
</script>
@endpush
