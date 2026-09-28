@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page_title', 'System Dashboard')

@section('content')
<div class="d-flex flex-column gap-4">
  <!-- TOP WELCOME BANNER -->
  <div class="card p-4 border-0 text-white position-relative overflow-hidden" 
       style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3);">
    <div class="position-relative z-1 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
      <div>
        <div class="d-inline-flex align-items-center gap-2 px-2 py-1 mb-2 rounded-pill bg-warning text-dark fw-bold" style="font-size:0.75rem;">
          <i class="bi bi-shield-check"></i> Administrator Control Center
        </div>
        <h2 class="h4 mb-1 fw-bold text-white">Welcome back, {{ auth()->user()->name }}</h2>
        <p class="text-white-50 mb-0 small">Real-time system overview, user accounts management, and platform analytics.</p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.statistics.index') }}" class="btn btn-warning fw-semibold btn-sm px-3 shadow-sm">
          <i class="bi bi-bar-chart-fill me-1"></i> View Analytics
        </a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-light btn-sm px-3">
          <i class="bi bi-people me-1"></i> Manage Users
        </a>
      </div>
    </div>
  </div>

  <!-- KPI SUMMARY CARDS (8-METRIC GRID) -->
  <div class="row g-3">
    <!-- 1. Total Users -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="admin-stat-card h-100 d-flex flex-column justify-content-between">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-secondary small fw-semibold">Total Accounts</span>
          <div class="admin-card-icon bg-primary-subtle text-primary">
            <i class="bi bi-people-fill"></i>
          </div>
        </div>
        <div>
          <div class="h3 fw-bold mb-1">{{ number_format($totalUsers) }}</div>
          <div class="d-flex align-items-center gap-2 small text-secondary">
            <span class="text-success"><i class="bi bi-check-circle-fill"></i> {{ $activeUsers }} Active</span>
            <span>•</span>
            <span class="text-muted">{{ $inactiveUsers }} Inactive</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Total Transactions -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="admin-stat-card h-100 d-flex flex-column justify-content-between">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-secondary small fw-semibold">Total Transactions</span>
          <div class="admin-card-icon bg-success-subtle text-success">
            <i class="bi bi-arrow-left-right"></i>
          </div>
        </div>
        <div>
          <div class="h3 fw-bold mb-1">{{ number_format($totalTransactions) }}</div>
          <div class="small text-secondary">
            Net: <strong class="{{ $netPlatformBalance >= 0 ? 'text-success' : 'text-danger' }}">Rs. {{ number_format($netPlatformBalance, 2) }}</strong>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Total Income Recorded -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="admin-stat-card h-100 d-flex flex-column justify-content-between">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-secondary small fw-semibold">Total Income Inflow</span>
          <div class="admin-card-icon bg-info-subtle text-info">
            <i class="bi bi-wallet2"></i>
          </div>
        </div>
        <div>
          <div class="h3 fw-bold mb-1 text-success">Rs. {{ number_format($totalIncome, 2) }}</div>
          <div class="small text-muted">Across all student wallets</div>
        </div>
      </div>
    </div>

    <!-- 4. Total Expenses Recorded -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="admin-stat-card h-100 d-flex flex-column justify-content-between">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-secondary small fw-semibold">Total Expenses Outflow</span>
          <div class="admin-card-icon bg-danger-subtle text-danger">
            <i class="bi bi-cart-dash-fill"></i>
          </div>
        </div>
        <div>
          <div class="h3 fw-bold mb-1 text-danger">Rs. {{ number_format($totalExpenses, 2) }}</div>
          <div class="small text-muted">Platform student expenditures</div>
        </div>
      </div>
    </div>

    <!-- 5. Active Budgets -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="admin-stat-card h-100 d-flex flex-column justify-content-between">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-secondary small fw-semibold">Student Budgets</span>
          <div class="admin-card-icon bg-warning-subtle text-warning">
            <i class="bi bi-bullseye"></i>
          </div>
        </div>
        <div>
          <div class="h3 fw-bold mb-1">{{ number_format($totalBudgets) }}</div>
          <div class="small text-muted">Active spending caps configured</div>
        </div>
      </div>
    </div>

    <!-- 6. Saving Tips Engine -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="admin-stat-card h-100 d-flex flex-column justify-content-between">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-secondary small fw-semibold">Saving Tips Generated</span>
          <div class="admin-card-icon" style="background:#fef3c7; color:#b45309;">
            <i class="bi bi-lightbulb-fill"></i>
          </div>
        </div>
        <div>
          <div class="h3 fw-bold mb-1">{{ number_format($totalSavingTips) }}</div>
          <div class="small text-muted">Algorithmic personalized tips</div>
        </div>
      </div>
    </div>

    <!-- 7. AI Monthly Insights -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="admin-stat-card h-100 d-flex flex-column justify-content-between">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-secondary small fw-semibold">AI Insights Reports</span>
          <div class="admin-card-icon" style="background:#ede9fe; color:#7c3aed;">
            <i class="bi bi-robot"></i>
          </div>
        </div>
        <div>
          <div class="h3 fw-bold mb-1">{{ number_format($totalAiInsights) }}</div>
          <div class="small text-muted">Generated monthly reviews</div>
        </div>
      </div>
    </div>

    <!-- 8. System Categories -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="admin-stat-card h-100 d-flex flex-column justify-content-between">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-secondary small fw-semibold">Default Categories</span>
          <div class="admin-card-icon bg-secondary-subtle text-secondary">
            <i class="bi bi-tags"></i>
          </div>
        </div>
        <div>
          <div class="h3 fw-bold mb-1">{{ number_format($totalSystemCategories) }}</div>
          <div class="small text-muted">{{ $totalAnnouncements }} Active Announcements</div>
        </div>
      </div>
    </div>
  </div>

  <!-- MAIN SECTIONS: RECENT ACTIVITY GRID -->
  <div class="row g-4">
    
    <!-- LEFT COLUMN: RECENTLY REGISTERED USERS -->
    <div class="col-12 col-lg-7">
      <div class="admin-stat-card h-100">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-lines-fill text-primary"></i>
            <h3 class="h6 fw-bold mb-0">Recently Registered Users</h3>
          </div>
          <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-link text-decoration-none">View All Users <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
            <thead class="table-light">
              <tr>
                <th>User</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($recentUsers as $u)
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="cc-avatar" style="width:32px;height:32px;font-size:0.75rem;">
                        {{ $u->initials }}
                      </div>
                      <div>
                        <div class="fw-semibold text-dark">{{ $u->name }}</div>
                        <div class="text-muted small" style="font-size:0.75rem;">{{ $u->email }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    @if ($u->role === 'admin')
                      <span class="badge bg-warning text-dark"><i class="bi bi-shield-fill"></i> Admin</span>
                    @else
                      <span class="badge bg-secondary-subtle text-secondary">Student</span>
                    @endif
                  </td>
                  <td>
                    @if ($u->is_active !== false)
                      <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle-fill"></i> Active</span>
                    @else
                      <span class="badge bg-danger-subtle text-danger"><i class="bi bi-slash-circle-fill"></i> Inactive</span>
                    @endif
                  </td>
                  <td class="text-muted small">
                    {{ $u->created_at ? $u->created_at->diffForHumans() : 'N/A' }}
                  </td>
                  <td class="text-end">
                    <a href="{{ route('admin.users.show', $u->id) }}" class="btn btn-sm btn-outline-secondary" title="View details">
                      <i class="bi bi-eye"></i>
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">No users found in database.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- RIGHT COLUMN: RECENT AI INSIGHTS & ANNOUNCEMENTS -->
    <div class="col-12 col-lg-5 d-flex flex-column gap-4">
      
      <!-- RECENT AI INSIGHTS -->
      <div class="admin-stat-card">
        <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-robot text-purple" style="color:#7c3aed;"></i>
            <h3 class="h6 fw-bold mb-0">Recent AI Insights Generated</h3>
          </div>
          <a href="{{ route('admin.statistics.index') }}" class="btn btn-sm btn-link text-decoration-none">Stats <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="d-flex flex-column gap-2 mt-2">
          @forelse ($recentInsights as $insight)
            <div class="p-2 rounded bg-light border d-flex align-items-center justify-content-between">
              <div>
                <div class="fw-semibold text-dark small">{{ $insight->user->name ?? 'Student' }}</div>
                <div class="text-muted" style="font-size:0.75rem;">
                  Month: <strong>{{ $insight->formatted_month }}</strong> • 
                  Status: <span class="badge {{ $insight->isAiGenerated() ? 'bg-success-subtle text-success' : 'bg-info-subtle text-info' }}" style="font-size:0.65rem;">{{ $insight->status }}</span>
                </div>
              </div>
              <span class="text-muted small" style="font-size:0.75rem;">
                {{ $insight->created_at ? $insight->created_at->diffForHumans() : '' }}
              </span>
            </div>
          @empty
            <div class="text-center py-3 text-muted small">No AI insights generated yet.</div>
          @endforelse
        </div>
      </div>

      <!-- ACTIVE TEMPLATES & ANNOUNCEMENTS -->
      <div class="admin-stat-card">
        <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-megaphone-fill text-warning"></i>
            <h3 class="h6 fw-bold mb-0">Announcements &amp; Tip Templates</h3>
          </div>
          <a href="{{ route('admin.tip-templates.index') }}" class="btn btn-sm btn-link text-decoration-none">Manage <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="d-flex flex-column gap-2 mt-2">
          @forelse ($recentTemplates as $tpl)
            <div class="p-2 rounded bg-light border d-flex align-items-start justify-content-between">
              <div style="min-width:0;flex:1;">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge {{ $tpl->type_badge_class }}" style="font-size:0.65rem;">{{ ucfirst(str_replace('_', ' ', $tpl->type)) }}</span>
                  <strong class="text-dark small text-truncate">{{ $tpl->title }}</strong>
                </div>
                <div class="text-muted text-truncate mt-1" style="font-size:0.75rem;">{{ $tpl->message }}</div>
              </div>
              <span class="badge {{ $tpl->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} ms-2" style="font-size:0.65rem;">
                {{ $tpl->status }}
              </span>
            </div>
          @empty
            <div class="text-center py-3 text-muted small">
              No tip templates created yet.
              <a href="{{ route('admin.tip-templates.index') }}" class="d-block mt-1">Create one now</a>
            </div>
          @endforelse
        </div>
      </div>

    </div>
  </div>

</div>
@endsection
