@extends('layouts.admin')

@section('title', 'System Categories Management')
@section('page_title', 'System Categories')

@section('content')
<div class="d-flex flex-column gap-4">

  <!-- TOP STATS & ADD BUTTON -->
  <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
    <div class="d-flex flex-wrap gap-2">
      <span class="badge bg-light text-dark border px-3 py-2">
        <strong class="text-primary">{{ $categories->count() }}</strong> Total System Categories
      </span>
      <span class="badge bg-success-subtle text-success px-3 py-2">
        <strong>{{ $incomeCount }}</strong> Income
      </span>
      <span class="badge bg-danger-subtle text-danger px-3 py-2">
        <strong>{{ $expenseCount }}</strong> Expense
      </span>
      <span class="badge bg-info-subtle text-info px-3 py-2">
        <strong>{{ $activeCount }}</strong> Enabled
      </span>
    </div>

    <button type="button" class="btn btn-warning fw-semibold d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
      <i class="bi bi-plus-lg"></i> Add System Category
    </button>
  </div>

  <!-- SEARCH & TYPE FILTER -->
  <div class="admin-stat-card">
    <form action="{{ route('admin.categories.index') }}" method="GET" class="row g-2 align-items-center">
      <div class="col-12 col-md-5">
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="search" class="form-control border-start-0" placeholder="Search system categories..." value="{{ $search }}">
        </div>
      </div>
      <div class="col-6 col-md-3">
        <select name="type" class="form-select" onchange="this.form.submit()">
          <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All Types (Income &amp; Expense)</option>
          <option value="expense" {{ $type === 'expense' ? 'selected' : '' }}>Expense Categories</option>
          <option value="income" {{ $type === 'income' ? 'selected' : '' }}>Income Categories</option>
        </select>
      </div>
      <div class="col-6 col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-warning flex-fill fw-semibold">Filter</button>
        @if (!empty($search) || $type !== 'all')
          <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
        @endif
      </div>
    </form>
  </div>

  <!-- CATEGORIES TABLE CARD -->
  <div class="admin-stat-card p-0 overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="padding-left:1.25rem;">Category Name &amp; Icon</th>
            <th>Type</th>
            <th>Platform Usage</th>
            <th>Status</th>
            <th class="text-end" style="padding-right:1.25rem;">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($categories as $cat)
            <tr>
              <td style="padding-left:1.25rem;">
                <div class="d-flex align-items-center gap-2">
                  <div class="d-flex align-items-center justify-content-center rounded bg-light border" style="width:36px;height:36px;font-size:1.1rem;">
                    <i class="bi {{ $cat->icon ?: 'bi-tag' }} text-warning"></i>
                  </div>
                  <div>
                    <div class="fw-bold text-dark">{{ $cat->name }}</div>
                    <div class="text-muted small" style="font-size:0.75rem;">Icon: <code>{{ $cat->icon ?: 'bi-tag' }}</code></div>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge {{ $cat->type === 'income' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                  {{ ucfirst($cat->type) }}
                </span>
              </td>
              <td>
                <span class="badge bg-light text-dark border">
                  <strong>{{ $cat->transactions_count }}</strong> Transactions
                </span>
              </td>
              <td>
                @if ($cat->is_active !== false)
                  <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle-fill"></i> Enabled</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-slash-circle"></i> Disabled</span>
                @endif
              </td>
              <td class="text-end" style="padding-right:1.25rem;">
                <div class="d-inline-flex align-items-center gap-1">
                  <!-- Edit Button -->
                  <button type="button" class="btn btn-sm btn-outline-secondary" 
                          data-bs-toggle="modal" 
                          data-bs-target="#editCategoryModal{{ $cat->id }}"
                          title="Edit Category">
                    <i class="bi bi-pencil"></i>
                  </button>

                  <!-- Toggle Active/Inactive -->
                  <form action="{{ route('admin.categories.status', $cat->id) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('PATCH')
                    @if ($cat->is_active !== false)
                      <button type="submit" class="btn btn-sm btn-outline-warning" title="Disable Category">
                        <i class="bi bi-toggle-on"></i>
                      </button>
                    @else
                      <button type="submit" class="btn btn-sm btn-outline-success" title="Enable Category">
                        <i class="bi bi-toggle-off"></i>
                      </button>
                    @endif
                  </form>

                  <!-- Delete Button (Safe check) -->
                  <form action="{{ route('admin.categories.destroy', $cat->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete system category {{ $cat->name }}? Note: Deletion is rejected if student transactions exist.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete category">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>

                <!-- EDIT MODAL FOR THIS CATEGORY -->
                <div class="modal fade text-start" id="editCategoryModal{{ $cat->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $cat->id }}" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <form action="{{ route('admin.categories.update', $cat->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                          <h5 class="modal-title" id="editModalLabel{{ $cat->id }}"><i class="bi bi-pencil-square text-warning me-1"></i> Edit System Category</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body d-flex flex-column gap-3">
                          <div>
                            <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $cat->name }}" required maxlength="100">
                          </div>
                          <div>
                            <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                              <option value="expense" {{ $cat->type === 'expense' ? 'selected' : '' }}>Expense</option>
                              <option value="income" {{ $cat->type === 'income' ? 'selected' : '' }}>Income</option>
                            </select>
                          </div>
                          <div>
                            <label class="form-label fw-semibold">Bootstrap Icon Class</label>
                            <input type="text" name="icon" class="form-control" value="{{ $cat->icon }}" placeholder="e.g. bi-egg-fried, bi-bus-front, bi-book">
                            <small class="text-muted">Use standard Bootstrap Icons (e.g. <code>bi-tag</code>, <code>bi-cart</code>, <code>bi-cash</code>).</small>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-warning fw-semibold">Save Changes</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-5 text-muted">
                <i class="bi bi-tags d-block mb-2" style="font-size:2rem;"></i>
                No system categories found matching your query.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- CREATE SYSTEM CATEGORY MODAL -->
<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-labelledby="createCategoryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('admin.categories.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="createCategoryModalLabel"><i class="bi bi-plus-circle-fill text-warning me-1"></i> Add New System Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body d-flex flex-column gap-3">
          <div>
            <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Cafeteria, Transport, Lab Supplies" required maxlength="100">
          </div>
          <div>
            <label class="form-label fw-semibold">Category Type <span class="text-danger">*</span></label>
            <select name="type" class="form-select" required>
              <option value="expense" selected>Expense Category</option>
              <option value="income">Income Category</option>
            </select>
          </div>
          <div>
            <label class="form-label fw-semibold">Icon Class</label>
            <input type="text" name="icon" class="form-control" value="bi-tag" placeholder="bi-tag">
            <small class="text-muted">Choose any valid Bootstrap Icon class (e.g. <code>bi-cart</code>, <code>bi-book</code>, <code>bi-bus-front</code>).</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning fw-semibold">Create System Category</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
