<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Upcoming Month Spending Forecast — Campus Coin">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Spending Forecast — Campus Coin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/design-tokens.css') }}">
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
            <i class="bi bi-chevron-right cc-breadcrumb-sep"></i>
            <span class="active">Spending Forecast</span>
          </div>
          <h1 class="cc-page-title"><i class="bi bi-graph-up-arrow text-warning me-2"></i>Upcoming Month Forecast</h1>
        </div>
      </div>

      <div class="cc-topbar-right">
        <button class="cc-theme-toggle" aria-label="Toggle theme" onclick="ThemeManager.toggle()"><i class="bi bi-moon-fill"></i></button>
        <a href="{{ route('profile') }}" class="cc-avatar" style="font-size:var(--fs-xs);">{{ $user->initials }}</a>
      </div>
    </header>

    <div class="cc-content p-4">
      @include('partials.alerts')

      @if (!$forecast['has_sufficient_data'])
        <!-- INSUFFICIENT DATA STATE -->
        <div class="card p-5 border-0 shadow-sm rounded-3 text-center" style="background:var(--cc-card-bg, #fff);">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-warning mx-auto mb-3" style="width:72px;height:72px;font-size:2rem;">
            <i class="bi bi-hourglass-split"></i>
          </div>
          <h4 class="fw-bold text-dark mb-2">Unlocking Your Spending Forecast</h4>
          <p class="text-secondary mx-auto" style="max-width:550px;">
            {{ $forecast['message'] }}
          </p>
          <div class="d-flex justify-content-center gap-2 mt-3">
            <a href="{{ route('transactions.create') }}" class="btn btn-warning fw-semibold px-4">
              <i class="bi bi-plus-circle me-1"></i> Add Transaction
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary px-4">Return to Dashboard</a>
          </div>
        </div>
      @else
        <!-- TOP FORECAST BANNER & SUMMARY -->
        <div class="card p-4 border-0 text-white mb-4 rounded-3 overflow-hidden position-relative" 
             style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3);">
          <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
              <span class="badge bg-warning text-dark px-3 py-1 mb-2 fw-bold" style="font-size:0.75rem;">
                <i class="bi bi-calendar-check me-1"></i> Forecast for {{ $forecast['upcoming_month_label'] }}
              </span>
              <h2 class="h3 fw-bold mb-1 text-white">Estimated Spending: Rs. {{ number_format($forecast['estimated_total_spending'], 2) }}</h2>
              <p class="text-white-50 mb-0 small">{{ $forecast['basis_explanation'] }}</p>
            </div>
            
            <div class="d-flex align-items-center gap-3 bg-dark bg-opacity-50 p-3 rounded-3 border border-secondary border-opacity-25">
              <div>
                <div class="text-white-50 small" style="font-size:0.75rem;">Previous Month Actual</div>
                <div class="fw-bold text-white">Rs. {{ number_format($forecast['previous_month_expense'], 2) }}</div>
              </div>
              <div class="border-start ps-3">
                <div class="text-white-50 small" style="font-size:0.75rem;">Projected Trend</div>
                @if ($forecast['difference_from_prev'] >= 0)
                  <span class="badge bg-danger-subtle text-danger"><i class="bi bi-arrow-up-right"></i> +{{ $forecast['percent_change'] }}%</span>
                @else
                  <span class="badge bg-success-subtle text-success"><i class="bi bi-arrow-down-right"></i> {{ $forecast['percent_change'] }}%</span>
                @endif
              </div>
            </div>
          </div>
        </div>

        <!-- MAIN FORECAST CONTENT GRID -->
        <div class="row g-4">
          <!-- CATEGORY SPENDING FORECAST -->
          <div class="col-12 col-lg-7">
            <div class="card p-4 border-0 shadow-sm rounded-3 h-100" style="background:var(--cc-card-bg, #fff);">
              <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                <h3 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-pie-chart-fill text-warning me-2"></i>Projected Category Breakdown</h3>
                <span class="badge bg-light text-secondary border">Deterministic Average</span>
              </div>

              <div class="d-flex flex-column gap-3">
                @foreach ($forecast['category_forecasts'] as $cat)
                  <div>
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <div class="d-flex align-items-center gap-2">
                        <i class="bi {{ $cat['icon'] }} text-warning"></i>
                        <span class="fw-semibold text-dark">{{ $cat['name'] }}</span>
                      </div>
                      <div class="text-end">
                        <strong class="text-dark">Rs. {{ number_format($cat['estimated_amount'], 2) }}</strong>
                        <span class="text-muted small ms-1">({{ $cat['percentage'] }}%)</span>
                      </div>
                    </div>
                    <div class="progress" style="height: 6px;">
                      <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $cat['percentage'] }}%" aria-valuenow="{{ $cat['percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          </div>

          <!-- HISTORICAL DATA BASIS & CALCULATION TRANSPARENCY -->
          <div class="col-12 col-lg-5 d-flex flex-column gap-4">
            
            <!-- BASIS CARD -->
            <div class="card p-4 border-0 shadow-sm rounded-3" style="background:var(--cc-card-bg, #fff);">
              <h3 class="h6 fw-bold mb-3 text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Completed Months Basis</h3>
              <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.875rem;">
                  <thead class="table-light">
                    <tr>
                      <th>Month</th>
                      <th>Activity</th>
                      <th class="text-end">Expense</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($forecast['historical_months'] as $hm)
                      <tr>
                        <td class="fw-semibold text-dark">{{ $hm['month_label'] }}</td>
                        <td class="text-muted small">{{ $hm['transaction_count'] }} txs</td>
                        <td class="text-end fw-bold text-danger">Rs. {{ number_format($hm['total_expense'], 2) }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>

            <!-- EXPLANATION / TRANSPARENCY CARD -->
            <div class="card p-3 border-0 shadow-sm rounded-3 bg-light" style="border-left: 4px solid #f59e0b !important;">
              <div class="d-flex align-items-start gap-2">
                <i class="bi bi-info-circle-fill text-warning fs-5 mt-1"></i>
                <div>
                  <div class="fw-bold text-dark small">How is this calculated?</div>
                  <div class="text-secondary small" style="font-size:0.8rem;">
                    This forecast computes the deterministic mathematical average of your actual expenses across your {{ $forecast['completed_months_count'] }} recent completed month(s). It does not include assumptions, demo data, or opaque algorithms.
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      @endif

    </div>
  </main>
</div>

<!-- LOGOUT MODAL -->
<div class="cc-modal-backdrop" id="logoutModal" onclick="if(event.target===this)Modal.close('logoutModal')">
  <div class="cc-modal cc-modal-sm" role="dialog" aria-modal="true">
    <div class="cc-modal-header">
      <h3 class="cc-modal-title"><i class="bi bi-box-arrow-left text-danger me-2"></i> Confirm Logout</h3>
      <button class="cc-modal-close" onclick="Modal.close('logoutModal')">&times;</button>
    </div>
    <div class="cc-modal-body">
      <p class="text-secondary mb-0">Are you sure you want to sign out?</p>
    </div>
    <div class="cc-modal-footer">
      <button class="btn-cc btn-cc-secondary" onclick="Modal.close('logoutModal')">Cancel</button>
      <form action="{{ route('logout') }}" method="POST" style="display:inline;">
        @csrf
        <button type="submit" class="btn-cc btn-cc-danger">Sign Out</button>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  if (typeof ThemeManager !== 'undefined') ThemeManager.init();
});
</script>
</body>
</html>
