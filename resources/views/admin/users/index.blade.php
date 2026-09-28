@extends('layouts.admin')

@section('title', 'User Accounts Management')
@section('page_title', 'User Accounts')

@section('content')
<div class="d-flex flex-column gap-4">

  <!-- TOP STATS CHIPS & ACTIONS -->
  <div class="row g-3">
    <div class="col-6 col-md-3">
      <div class="admin-stat-card p-3 d-flex align-items-center justify-content-between">
        <div>
          <div class="text-secondary small fw-semibold">Total Accounts</div>
          <div class="h4 fw-bold mb-0">{{ number_format($totalCount) }}</div>
        </div>
        <div class="admin-card-icon bg-primary-subtle text-primary" style="width:38px;height:38px;">
          <i class="bi bi-people"></i>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="admin-stat-card p-3 d-flex align-items-center justify-content-between">
        <div>
          <div class="text-secondary small fw-semibold">Active Students</div>
          <div class="h4 fw-bold mb-0 text-success">{{ number_format($activeCount) }}</div>
        </div>
        <div class="admin-card-icon bg-success-subtle text-success" style="width:38px;height:38px;">
          <i class="bi bi-person-check-fill"></i>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="admin-stat-card p-3 d-flex align-items-center justify-content-between">
        <div>
          <div class="text-secondary small fw-semibold">Deactivated</div>
          <div class="h4 fw-bold mb-0 text-danger">{{ number_format($inactiveCount) }}</div>
        </div>
        <div class="admin-card-icon bg-danger-subtle text-danger" style="width:38px;height:38px;">
          <i class="bi bi-person-x-fill"></i>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="admin-stat-card p-3 d-flex align-items-center justify-content-between">
        <div>
          <div class="text-secondary small fw-semibold">Administrators</div>
          <div class="h4 fw-bold mb-0 text-warning">{{ number_format($adminCount) }}</div>
        </div>
        <div class="admin-card-icon bg-warning-subtle text-warning" style="width:38px;height:38px;">
          <i class="bi bi-shield-lock-fill"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- SEARCH & FILTERS BAR -->
  <div class="admin-stat-card">
    <form action="{{ route('admin.users.index') }}" method="GET" class="row g-2 align-items-center">
      <!-- Search Input -->
      <div class="col-12 col-md-4">
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, email, ID..." value="{{ $search }}">
        </div>
      </div>

      <!-- Role Filter -->
      <div class="col-6 col-md-2">
        <select name="role" class="form-select" onchange="this.form.submit()">
          <option value="all" {{ $role === 'all' ? 'selected' : '' }}>All Roles</option>
          <option value="user" {{ $role === 'user' ? 'selected' : '' }}>Students</option>
          <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Admins</option>
        </select>
      </div>

      <!-- Status Filter -->
      <div class="col-6 col-md-2">
        <select name="status" class="form-select" onchange="this.form.submit()">
          <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Status</option>
          <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active Only</option>
          <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
        </select>
      </div>

      <!-- Sort By -->
      <div class="col-6 col-md-2">
        <select name="sort" class="form-select" onchange="this.form.submit()">
          <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>Newest First</option>
          <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
          <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>Name (A-Z)</option>
          <option value="transactions" {{ $sort === 'transactions' ? 'selected' : '' }}>Most Active</option>
        </select>
      </div>

      <!-- Submit & Reset -->
      <div class="col-6 col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-warning flex-fill fw-semibold">Filter</button>
        @if (!empty($search) || $role !== 'all' || $status !== 'all' || $sort !== 'latest')
          <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
        @endif
      </div>
    </form>
  </div>

  <!-- USERS TABLE CARD -->
  <div class="admin-stat-card p-0 overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="padding-left:1.25rem;">User</th>
            <th>Program / Campus</th>
            <th>Role</th>
            <th>Status</th>
            <th>Activity</th>
            <th>Joined</th>
            <th class="text-end" style="padding-right:1.25rem;">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($users as $user)
            <tr>
              <td style="padding-left:1.25rem;">
                <div class="d-flex align-items-center gap-3">
                  <div class="cc-avatar" style="width:38px;height:38px;font-size:0.85rem;{{ $user->role === 'admin' ? 'background:linear-gradient(135deg, #d97706, #b45309);color:#fff;' : '' }}">
                    {{ $user->initials }}
                  </div>
                  <div>
                    <div class="fw-bold text-dark">{{ $user->name }}</div>
                    <div class="text-muted small">{{ $user->email }}</div>
                    @if ($user->student_id)
                      <span class="badge bg-light text-secondary border mt-1" style="font-size:0.65rem;">ID: {{ $user->student_id }}</span>
                    @endif
                  </div>
                </div>
              </td>
              <td>
                <div class="small fw-semibold text-dark">{{ $user->program ?? '—' }}</div>
                <div class="text-muted small" style="font-size:0.75rem;">{{ $user->university ?? 'University' }}</div>
              </td>
              <td>
                @if ($user->role === 'admin')
                  <span class="badge bg-warning text-dark"><i class="bi bi-shield-fill"></i> Admin</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary">Student</span>
                @endif
              </td>
              <td>
                @if ($user->is_active !== false)
                  <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle-fill"></i> Active</span>
                @else
                  <span class="badge bg-danger-subtle text-danger"><i class="bi bi-slash-circle-fill"></i> Deactivated</span>
                @endif
              </td>
              <td>
                <div class="small text-secondary">
                  <span class="fw-semibold text-dark">{{ $user->transactions_count }}</span> txs
                </div>
                <div class="text-muted small" style="font-size:0.75rem;">
                  {{ $user->budgets_count }} budgets • {{ $user->saving_tips_count }} tips
                </div>
              </td>
              <td class="small text-muted">
                {{ $user->created_at ? $user->created_at->format('M d, Y') : 'N/A' }}
              </td>
              <td class="text-end" style="padding-right:1.25rem;">
                <div class="d-inline-flex align-items-center gap-1">
                  <!-- View Details -->
                  <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-sm btn-outline-secondary" title="View details and metrics">
                    <i class="bi bi-eye"></i> Details
                  </a>

                  <!-- Status Toggle Form -->
                  @if ($user->id !== auth()->id())
                    <form action="{{ route('admin.users.status', $user->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to {{ $user->is_active !== false ? 'deactivate' : 'activate' }} {{ $user->name }}?');">
                      @csrf
                      @method('PATCH')
                      @if ($user->is_active !== false)
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Deactivate user">
                          <i class="bi bi-person-x"></i>
                        </button>
                      @else
                        <button type="submit" class="btn btn-sm btn-outline-success" title="Activate user">
                          <i class="bi bi-person-check"></i>
                        </button>
                      @endif
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-person-x d-block mb-2" style="font-size:2rem;"></i>
                No user accounts matching the selected criteria.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    @if ($users->hasPages())
      <div class="p-3 border-top d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} users
        </div>
        <div>
          {{ $users->links() }}
        </div>
      </div>
    @endif
  </div>

</div>
@endsection
