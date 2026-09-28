<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Budgets — Campus Coin. Set and manage your monthly spending limits.">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Budgets — Campus Coin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<div class="cc-app-layout">
  @include('partials.sidebar')

  <main class="cc-main-content">
    <header class="cc-topbar">
      <div class="cc-topbar-left">
        <button class="cc-mobile-menu-btn" aria-label="Toggle sidebar" onclick="Sidebar.toggle()"><i class="bi bi-list"></i></button>
        <div>
          <div class="cc-breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span class="sep"><i class="bi bi-chevron-right"></i></span><span class="current">Budgets</span></div>
          <div class="cc-topbar-title">Budget Manager</div>
        </div>
      </div>
      <div class="cc-topbar-right">
        <button class="cc-theme-toggle" aria-label="Toggle theme" onclick="ThemeManager.toggle()"><i class="bi bi-moon-fill"></i></button>
        <a href="{{ route('profile') }}" class="cc-avatar" style="font-size:var(--fs-xs);">{{ $user->initials }}</a>
      </div>
    </header>

    <div class="cc-content">

      @include('partials.alerts')

      <div class="cc-page-header">
        <div>
          <h1 class="cc-page-title">Budgets</h1>
          <p class="cc-page-subtitle">Set monthly spending limits per category. {{ $monthLabel }}.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
          <div class="cc-month-selector">
            <a href="{{ route('budgets', ['month' => $prevMonthKey]) }}" class="cc-month-btn" title="Previous Month"><i class="bi bi-chevron-left"></i></a>
            <span class="cc-month-label">{{ $monthLabel }}</span>
            <a href="{{ route('budgets', ['month' => $nextMonthKey]) }}" class="cc-month-btn" title="Next Month"><i class="bi bi-chevron-right"></i></a>
          </div>
          <button class="btn-cc btn-cc-primary" onclick="Modal.open('addBudgetModal')">
            <i class="bi bi-plus-lg"></i> New Budget
          </button>
        </div>
      </div>

      @if($budgetItems->isEmpty())
        <!-- Empty State -->
        <div class="cc-card">
          <div class="cc-card-body text-center py-5">
            <div style="width:64px;height:64px;border-radius:50%;background:var(--cc-primary-light);color:var(--cc-primary);display:inline-flex;align-items:center;justify-content:center;font-size:1.75rem;margin-bottom:1.25rem;">
              <i class="bi bi-bullseye"></i>
            </div>
            <h3 class="font-bold mb-1" style="font-size:var(--fs-lg);">No budgets yet for {{ $monthLabel }}</h3>
            <p class="text-secondary mb-3" style="font-size:var(--fs-sm);max-width:420px;margin:0 auto;">
              Set spending limits for categories and stay in control of your finances.
            </p>
            <button class="btn-cc btn-cc-primary" onclick="Modal.open('addBudgetModal')">
              <i class="bi bi-plus-lg"></i> Create Budget
            </button>
          </div>
        </div>
      @else
        <!-- Overview Ring + Summary -->
        <div class="row g-4 mb-4">
          <div class="col-lg-4">
            <div class="cc-card p-4 text-center h-100 d-flex flex-column align-items-center justify-content-center">
              <div style="font-size:var(--fs-xs);font-weight:var(--fw-semibold);text-transform:uppercase;letter-spacing:.06em;color:var(--cc-text-muted);margin-bottom:1.25rem;">Overall Budget Health</div>
              <div class="cc-ring-gauge mb-3" style="background:conic-gradient({{ $rawOverallUsagePercent >= 100 ? 'var(--cc-expense)' : ($rawOverallUsagePercent >= 75 ? 'var(--cc-warning)' : 'var(--cc-primary)') }} 0% {{ $overallUsagePercent }}%, var(--cc-border-subtle) {{ $overallUsagePercent }}% 100%);">
                <div class="cc-ring-gauge-inner">
                  <span class="cc-amount font-bold" style="font-size:var(--fs-2xl);color:{{ $rawOverallUsagePercent >= 100 ? 'var(--cc-expense)' : ($rawOverallUsagePercent >= 75 ? 'var(--cc-warning)' : 'var(--cc-primary)') }};">{{ $overallUsagePercent }}%</span>
                  <span style="font-size:10px;text-transform:uppercase;color:var(--cc-text-muted);">Used</span>
                </div>
              </div>
              <div class="font-bold" style="font-size:var(--fs-md);margin-bottom:.25rem;">Rs. {{ number_format($totalBudgetSpent, 2) }} <span class="text-secondary" style="font-size:var(--fs-xs);font-weight:normal;">/ Rs. {{ number_format($totalBudgetLimit, 2) }}</span></div>
              <span class="cc-badge {{ $totalRemaining > 0 ? 'cc-badge-income' : 'cc-badge-expense' }}"><i class="bi {{ $totalRemaining > 0 ? 'bi-check-circle-fill' : 'bi-exclamation-octagon' }}"></i> Rs. {{ number_format($totalRemaining, 2) }} Remaining</span>
              <hr class="cc-divider w-100">
              <div class="row g-2 w-100 text-start">
                <div class="col-6" style="font-size:var(--fs-xs);">
                  <div class="text-secondary">Budgets Set</div>
                  <div class="font-bold">{{ $budgetItems->count() }} {{ $budgetItems->count() === 1 ? 'category' : 'categories' }}</div>
                </div>
                <div class="col-6" style="font-size:var(--fs-xs);">
                  <div class="text-secondary">Alerts</div>
                  <div class="font-bold">
                    @if($nearLimitCount > 0)
                      <span style="color:var(--cc-warning);">{{ $nearLimitCount }} near</span>
                    @endif
                    @if($overBudgetCount > 0)
                      @if($nearLimitCount > 0), @endif
                      <span style="color:var(--cc-expense);">{{ $overBudgetCount }} over</span>
                    @endif
                    @if($nearLimitCount == 0 && $overBudgetCount == 0)
                      <span style="color:var(--cc-income);">All clear</span>
                    @endif
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-8">
            <div class="cc-card h-100">
              <div class="cc-card-header"><h2 class="cc-card-title">Category Breakdown</h2></div>
              <div class="cc-card-body d-flex flex-column gap-3">
                @foreach($budgetItems as $b)
                <div class="cc-budget-item">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="d-flex align-items-center gap-2">
                      <div class="txn-icon" style="background:var(--cc-surface-tint);color:{{ $b->status === 'over_budget' ? 'var(--cc-expense)' : ($b->status === 'near_limit' ? 'var(--cc-warning)' : 'var(--cc-primary)') }};width:32px;height:32px;font-size:.875rem;">
                        <i class="bi {{ $b->category_icon }}"></i>
                      </div>
                      <span class="font-bold" style="font-size:var(--fs-sm);">{{ $b->category_name }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                      <span class="cc-badge {{ $b->status_badge_class }}">{{ $b->status_label }}</span>
                      <button class="btn-cc btn-cc-ghost btn-cc-xs" onclick="openEditBudget({{ $b->id }}, '{{ $b->category_name }}', {{ $b->limit_amount }})" title="Edit Budget"><i class="bi bi-pencil"></i></button>
                      <button class="btn-cc btn-cc-ghost btn-cc-xs" onclick="openDeleteBudget({{ $b->id }}, '{{ $b->category_name }}')" title="Delete Budget" style="color:var(--cc-expense);"><i class="bi bi-trash3"></i></button>
                    </div>
                  </div>
                  <div class="d-flex justify-content-between" style="font-size:var(--fs-xs);color:var(--cc-text-muted);margin-bottom:.375rem;">
                    <span>Rs. {{ number_format($b->spent_amount, 2) }} spent</span>
                    <span>Rs. {{ number_format($b->limit_amount, 2) }} limit</span>
                  </div>
                  <div class="cc-progress-bar" style="height:8px;">
                    <div class="cc-progress-fill" style="width:{{ $b->display_percentage }}%;background:{{ $b->status === 'over_budget' ? 'var(--cc-expense)' : ($b->status === 'near_limit' ? 'var(--cc-warning)' : 'var(--cc-primary)') }};"></div>
                  </div>
                  @if($b->status === 'over_budget')
                    <div style="font-size:11px;color:var(--cc-expense);margin-top:4px;">
                      <i class="bi bi-exclamation-triangle-fill me-1"></i> Over by Rs. {{ number_format($b->overage_amount, 2) }}
                    </div>
                  @endif
                </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      @endif

    </div>
  </main>
</div>

<!-- Add Budget Modal -->
<div class="cc-modal-overlay" id="addBudgetModal">
  <div class="cc-modal" style="max-width:460px;">
    <div class="cc-modal-header">
      <h3 class="cc-card-title"><i class="bi bi-bullseye me-2" style="color:var(--cc-primary);"></i>Set New Budget</h3>
      <button class="btn-cc btn-cc-ghost btn-cc-sm" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <form action="{{ route('budgets.store') }}" method="POST">
      @csrf
      <input type="hidden" name="month" value="{{ $currentMonthKey }}">
      <div class="cc-card-body">
        <div class="cc-form-group">
          <label class="cc-label" for="budgetCategory">Category (Expense)</label>
          <select class="cc-select" id="budgetCategory" name="category_id" required>
            <option value="">-- Select Category --</option>
            @foreach($availableCategories as $cat)
              @if(!in_array($cat->id, $existingCategoryIds))
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
              @endif
            @endforeach
            @if(count($availableCategories) === count($existingCategoryIds))
              <option value="" disabled>All categories have budgets</option>
            @endif
          </select>
        </div>
        <div class="cc-form-group mb-0">
          <label class="cc-label" for="budgetAmount">Monthly Limit (Rs.)</label>
          <div class="cc-input-group">
            <span class="cc-input-prefix">Rs.</span>
            <input type="number" class="cc-input" id="budgetAmount" name="limit_amount" placeholder="0.00" min="1" step="0.01" required style="border-radius:0 var(--radius-md) var(--radius-md) 0;">
          </div>
        </div>
      </div>
      <div class="cc-card-footer d-flex justify-content-end gap-2">
        <button type="button" class="btn-cc btn-cc-secondary btn-cc-sm" data-modal-close>Cancel</button>
        <button type="submit" class="btn-cc btn-cc-primary btn-cc-sm">
          <i class="bi bi-check-lg"></i> Save Budget
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Budget Modal -->
<div class="cc-modal-overlay" id="editBudgetModal">
  <div class="cc-modal" style="max-width:440px;">
    <div class="cc-modal-header">
      <h3 class="cc-card-title"><i class="bi bi-pencil me-2" style="color:var(--cc-primary);"></i>Edit Budget</h3>
      <button class="btn-cc btn-cc-ghost btn-cc-sm" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <form id="editBudgetForm" method="POST">
      @csrf
      @method('PUT')
      <div class="cc-card-body">
        <div class="cc-form-group">
          <label class="cc-label">Category</label>
          <div class="cc-input" id="editBudgetCategoryName" style="background:var(--cc-surface-tint);cursor:default;"></div>
        </div>
        <div class="cc-form-group mb-0">
          <label class="cc-label" for="editBudgetAmount">Monthly Limit (Rs.)</label>
          <div class="cc-input-group">
            <span class="cc-input-prefix">Rs.</span>
            <input type="number" class="cc-input" id="editBudgetAmount" name="limit_amount" placeholder="0.00" min="1" step="0.01" required style="border-radius:0 var(--radius-md) var(--radius-md) 0;">
          </div>
        </div>
      </div>
      <div class="cc-card-footer d-flex justify-content-end gap-2">
        <button type="button" class="btn-cc btn-cc-secondary btn-cc-sm" data-modal-close>Cancel</button>
        <button type="submit" class="btn-cc btn-cc-primary btn-cc-sm"><i class="bi bi-check-lg"></i> Update</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Budget Modal -->
<div class="cc-modal-overlay" id="deleteBudgetModal">
  <div class="cc-modal" style="max-width:400px;">
    <div class="cc-modal-header">
      <h3 class="cc-card-title">Delete Budget</h3>
      <button class="btn-cc btn-cc-ghost btn-cc-sm" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="cc-card-body text-center">
      <div style="width:52px;height:52px;border-radius:50%;background:var(--cc-expense-bg);color:var(--cc-expense);display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin:0 auto 1rem;">
        <i class="bi bi-trash3"></i>
      </div>
      <p class="text-secondary mb-1" style="font-size:var(--fs-sm);">Delete the <strong id="deleteBudgetCategoryName"></strong> budget?</p>
      <p class="text-secondary mb-0" style="font-size:var(--fs-xs);">Your transactions will not be affected.</p>
    </div>
    <div class="cc-card-footer d-flex justify-content-end gap-2">
      <button class="btn-cc btn-cc-secondary btn-cc-sm" data-modal-close>Cancel</button>
      <form id="deleteBudgetForm" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn-cc btn-cc-danger btn-cc-sm"><i class="bi bi-trash3"></i> Delete</button>
      </form>
    </div>
  </div>
</div>

<!-- LOGOUT MODAL -->
<div class="cc-modal-overlay" id="logoutModal">
  <div class="cc-modal" style="max-width:380px;">
    <div class="cc-modal-header"><h3 class="cc-card-title">Confirm Logout</h3><button class="btn-cc btn-cc-ghost btn-cc-sm" data-modal-close><i class="bi bi-x-lg"></i></button></div>
    <div class="cc-card-body text-center"><p class="text-secondary mb-0" style="font-size:var(--fs-sm);">End your session?</p></div>
    <div class="cc-card-footer d-flex justify-content-end gap-2">
      <button class="btn-cc btn-cc-secondary btn-cc-sm" data-modal-close>Cancel</button>
      <form action="{{ route('logout') }}" method="POST" class="d-inline">@csrf<button type="submit" class="btn-cc btn-cc-danger btn-cc-sm">Logout</button></form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script>
function openEditBudget(budgetId, categoryName, limitAmount) {
  document.getElementById('editBudgetForm').action = '/budgets/' + budgetId;
  document.getElementById('editBudgetCategoryName').textContent = categoryName;
  document.getElementById('editBudgetAmount').value = limitAmount;
  Modal.open('editBudgetModal');
}

function openDeleteBudget(budgetId, categoryName) {
  document.getElementById('deleteBudgetForm').action = '/budgets/' + budgetId;
  document.getElementById('deleteBudgetCategoryName').textContent = categoryName;
  Modal.open('deleteBudgetModal');
}
</script>
</body>
</html>
