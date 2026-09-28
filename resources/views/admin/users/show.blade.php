@extends('layouts.admin')

@section('title', $user->name . ' — User Details')
@section('page_title', 'User Details')

@section('content')
<div class="d-flex flex-column gap-4">

  <!-- TOP BREADCRUMB & ACTIONS -->
  <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
      <i class="bi bi-arrow-left"></i> Back to User List
    </a>

    <div class="d-flex align-items-center gap-2">
      <!-- Status Badge -->
      @if ($user->is_active !== false)
        <span class="badge bg-success-subtle text-success px-3 py-2"><i class="bi bi-check-circle-fill"></i> Account Active</span>
      @else
        <span class="badge bg-danger-subtle text-danger px-3 py-2"><i class="bi bi-slash-circle-fill"></i> Account Deactivated</span>
      @endif

      <!-- Role Badge -->
      @if ($user->role === 'admin')
        <span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-shield-fill"></i> Admin</span>
      @else
        <span class="badge bg-secondary-subtle text-secondary px-3 py-2">Student Account</span>
      @endif
    </div>
  </div>

  <!-- PROFILE HEADER CARD -->
  <div class="admin-stat-card p-4">
    <div class="row g-4 align-items-center">
      <div class="col-12 col-md-auto text-center text-md-start">
        <div class="cc-avatar mx-auto" style="width:72px;height:72px;font-size:1.75rem;{{ $user->role === 'admin' ? 'background:linear-gradient(135deg, #d97706, #b45309);color:#fff;' : '' }}">
          {{ $user->initials }}
        </div>
      </div>
      <div class="col-12 col-md">
        <h2 class="h4 fw-bold mb-1 text-dark">{{ $user->name }}</h2>
        <div class="text-muted mb-2">{{ $user->email }}</div>
        <div class="d-flex flex-wrap gap-2 small">
          @if ($user->university)
            <span class="badge bg-light text-dark border"><i class="bi bi-mortarboard me-1"></i>{{ $user->university }}</span>
          @endif
          @if ($user->program)
            <span class="badge bg-light text-dark border"><i class="bi bi-book me-1"></i>{{ $user->program }}</span>
          @endif
          @if ($user->academic_year)
            <span class="badge bg-light text-dark border"><i class="bi bi-calendar me-1"></i>Year {{ $user->academic_year }}</span>
          @endif
          @if ($user->student_id)
            <span class="badge bg-light text-dark border"><i class="bi bi-card-text me-1"></i>ID: {{ $user->student_id }}</span>
          @endif
          <span class="badge bg-light text-muted border"><i class="bi bi-clock-history me-1"></i>Joined {{ $user->created_at ? $user->created_at->format('M d, Y') : 'N/A' }}</span>
        </div>
      </div>

      <!-- ADMIN CONTROLS IN PROFILE HEADER -->
      <div class="col-12 col-md-auto d-flex flex-column flex-sm-row gap-2">
        @if ($user->id !== auth()->id())
          <!-- Activate / Deactivate Toggle -->
          <form action="{{ route('admin.users.status', $user->id) }}" method="POST" onsubmit="return confirm('Confirm {{ $user->is_active !== false ? 'deactivating' : 'activating' }} {{ $user->name }}?');">
            @csrf
            @method('PATCH')
            @if ($user->is_active !== false)
              <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                <i class="bi bi-person-x me-1"></i> Deactivate Account
              </button>
            @else
              <button type="submit" class="btn btn-outline-success btn-sm w-100">
                <i class="bi bi-person-check me-1"></i> Activate Account
              </button>
            @endif
          </form>

          <!-- Toggle Role -->
          <form action="{{ route('admin.users.role', $user->id) }}" method="POST">
            @csrf
            @method('PATCH')
            <input type="hidden" name="role" value="{{ $user->role === 'admin' ? 'user' : 'admin' }}">
            <button type="submit" class="btn btn-outline-warning btn-sm w-100 text-dark" onclick="return confirm('Change role to {{ $user->role === 'admin' ? 'Student' : 'Admin' }}?');">
              <i class="bi bi-shield-lock me-1"></i> Make {{ $user->role === 'admin' ? 'Student' : 'Admin' }}
            </button>
          </form>
        @else
          <span class="badge bg-info-subtle text-info p-2">Current Logged-in Admin</span>
        @endif
      </div>
    </div>
  </div>

  <!-- FINANCIAL AGGREGATE METRICS -->
  <div class="row g-3">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="admin-stat-card">
        <div class="text-secondary small fw-semibold">Total Recorded Income</div>
        <div class="h3 fw-bold mb-0 text-success">Rs. {{ number_format($totalIncome, 2) }}</div>
        <div class="small text-muted mt-1">{{ $incomeCount }} income transactions</div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="admin-stat-card">
        <div class="text-secondary small fw-semibold">Total Recorded Expenses</div>
        <div class="h3 fw-bold mb-0 text-danger">Rs. {{ number_format($totalExpense, 2) }}</div>
        <div class="small text-muted mt-1">{{ $expenseCount }} expense transactions</div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="admin-stat-card">
        <div class="text-secondary small fw-semibold">Net Student Balance</div>
        <div class="h3 fw-bold mb-0 {{ $netBalance >= 0 ? 'text-success' : 'text-danger' }}">
          Rs. {{ number_format($netBalance, 2) }}
        </div>
        <div class="small text-muted mt-1">{{ $user->transactions_count }} total entries</div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="admin-stat-card">
        <div class="text-secondary small fw-semibold">Platform Usage</div>
        <div class="h3 fw-bold mb-0">{{ $user->budgets_count }} Budgets</div>
        <div class="small text-muted mt-1">{{ $user->saving_tips_count }} Tips • {{ $user->ai_monthly_insights_count }} AI Insights</div>
      </div>
    </div>
  </div>

  <!-- RECENT TRANSACTIONS & BUDGETS GRID -->
  <div class="row g-4">
    <!-- RECENT TRANSACTIONS -->
    <div class="col-12 col-lg-7">
      <div class="admin-stat-card h-100">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
          <h3 class="h6 fw-bold mb-0"><i class="bi bi-clock-history me-1 text-primary"></i> Recent Transactions</h3>
          <span class="badge bg-secondary-subtle text-secondary">{{ $user->transactions_count }} Total</span>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
            <thead class="table-light">
              <tr>
                <th>Category</th>
                <th>Type</th>
                <th>Date</th>
                <th class="text-end">Amount</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($recentTransactions as $tx)
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <i class="bi {{ $tx->category->icon ?? 'bi-tag' }} text-muted"></i>
                      <span class="fw-semibold">{{ $tx->category->name ?? 'Uncategorized' }}</span>
                    </div>
                  </td>
                  <td>
                    <span class="badge {{ $tx->type === 'income' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                      {{ ucfirst($tx->type) }}
                    </span>
                  </td>
                  <td class="text-muted small">
                    {{ \Carbon\Carbon::parse($tx->transaction_date)->format('M d, Y') }}
                  </td>
                  <td class="text-end fw-bold {{ $tx->type === 'income' ? 'text-success' : 'text-danger' }}">
                    {{ $tx->type === 'income' ? '+' : '-' }} Rs. {{ number_format($tx->amount, 2) }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center py-4 text-muted">No transactions recorded for this user.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- BUDGETS & AI INSIGHTS -->
    <div class="col-12 col-lg-5 d-flex flex-column gap-4">
      <!-- BUDGETS -->
      <div class="admin-stat-card">
        <div class="pb-2 mb-2 border-bottom">
          <h3 class="h6 fw-bold mb-0"><i class="bi bi-bullseye me-1 text-warning"></i> Configured Budgets</h3>
        </div>
        <div class="d-flex flex-column gap-2 mt-2">
          @forelse ($budgets as $b)
            <div class="p-2 rounded bg-light border d-flex align-items-center justify-content-between">
              <div>
                <strong class="text-dark small">{{ $b->category->name ?? 'General' }}</strong>
                <div class="text-muted small" style="font-size:0.75rem;">Month: {{ $b->month }}</div>
              </div>
              <span class="fw-bold text-dark small">Limit: Rs. {{ number_format($b->limit_amount, 2) }}</span>
            </div>
          @empty
            <div class="text-center py-3 text-muted small">No active budgets set by this user.</div>
          @endforelse
        </div>
      </div>

      <!-- AI MONTHLY INSIGHTS -->
      <div class="admin-stat-card">
        <div class="pb-2 mb-2 border-bottom">
          <h3 class="h6 fw-bold mb-0"><i class="bi bi-robot me-1 text-purple" style="color:#7c3aed;"></i> AI Monthly Insights</h3>
        </div>
        <div class="d-flex flex-column gap-2 mt-2">
          @forelse ($aiInsights as $ins)
            <div class="p-2 rounded bg-light border d-flex align-items-center justify-content-between">
              <div>
                <strong class="text-dark small">{{ $ins->formatted_month }}</strong>
                <div class="text-muted small" style="font-size:0.75rem;">Status: {{ $ins->status }}</div>
              </div>
              <span class="badge {{ $ins->isAiGenerated() ? 'bg-success-subtle text-success' : 'bg-info-subtle text-info' }}" style="font-size:0.65rem;">
                {{ $ins->status }}
              </span>
            </div>
          @empty
            <div class="text-center py-3 text-muted small">No AI insights generated for this user.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
