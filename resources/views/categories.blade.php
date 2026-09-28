<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Categories — Campus Coin. Manage and customize your spending categories.">
  <title>Categories — Campus Coin</title>
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
          <div class="cc-breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span class="sep"><i class="bi bi-chevron-right"></i></span><span class="current">Categories</span></div>
          <div class="cc-topbar-title">Categories</div>
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
          <h1 class="cc-page-title">Categories</h1>
          <p class="cc-page-subtitle">Organize your transactions with standard and custom spending categories.</p>
        </div>
        <button class="btn-cc btn-cc-primary" onclick="Modal.open('addCategoryModal')">
          <i class="bi bi-plus-lg"></i> Add Category
        </button>
      </div>

      <!-- Stats -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="cc-stat-card balance">
            <div class="cc-stat-label">Total Categories</div>
            <div class="cc-stat-value" style="font-size:var(--fs-3xl);">{{ $totalCategoriesCount }}</div>
            <div class="cc-stat-meta">{{ $defaultCount }} default · {{ $customCount }} personal</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="cc-stat-card expense">
            <div class="cc-stat-label">Most Spent Category</div>
            <div class="cc-stat-value" style="font-size:var(--fs-xl);color:var(--cc-warning);">{{ $mostSpentName }}</div>
            <div class="cc-stat-meta">Rs. {{ number_format($mostSpentAmount, 2) }} this month</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="cc-stat-card income">
            <div class="cc-stat-label">Personal Custom Categories</div>
            <div class="cc-stat-value" style="font-size:var(--fs-3xl);color:var(--cc-income);">{{ $customCount }}</div>
            <div class="cc-stat-meta">Created by you</div>
          </div>
        </div>
      </div>

      <!-- Expense Categories -->
      <div class="cc-card mb-4">
        <div class="cc-card-header">
          <h2 class="cc-card-title"><i class="bi bi-arrow-up-right me-2" style="color:var(--cc-expense);"></i>Expense Categories</h2>
          <span class="cc-badge cc-badge-expense">{{ $expenseCategories->count() }} categories</span>
        </div>
        <div class="cc-table-wrapper" style="border:none;border-radius:0;">
          <table class="cc-table">
            <thead>
              <tr>
                <th>Category</th>
                <th>Icon</th>
                <th>Type</th>
                <th>This Month</th>
                <th>Transactions</th>
                <th>Scope</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($expenseCategories as $cat)
              <tr @if(!$cat->is_default) style="background:var(--cc-surface-tint);" @endif>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="txn-icon" style="background:var(--cc-warning-bg);color:var(--cc-warning);">
                      <i class="bi {{ $cat->icon ?: 'bi-cup-hot' }}"></i>
                    </div>
                    <div>
                      <div class="font-bold">{{ $cat->name }}</div>
                      <div style="font-size:var(--fs-2xs);color:var(--cc-text-muted);">{{ $cat->is_default ? 'System default' : 'Personal category' }}</div>
                    </div>
                  </div>
                </td>
                <td><code style="font-size:var(--fs-xs);background:var(--cc-surface-subtle);padding:2px 6px;border-radius:4px;">{{ $cat->icon ?: 'bi-tag' }}</code></td>
                <td><span class="cc-badge cc-badge-expense">Expense</span></td>
                <td class="cc-amount font-bold" style="color:var(--cc-expense);">Rs. {{ number_format($cat->month_amount, 2) }}</td>
                <td>{{ $cat->total_count }} entries</td>
                <td>
                  @if($cat->is_default)
                    <span class="cc-badge cc-badge-neutral"><i class="bi bi-shield-lock me-1"></i>Default</span>
                  @else
                    <span class="cc-badge cc-badge-teal"><i class="bi bi-person me-1"></i>Personal</span>
                  @endif
                </td>
                <td>
                  <div class="d-flex gap-1">
                    @if(!$cat->is_default)
                      <button class="btn-cc btn-cc-ghost btn-cc-xs" onclick="openEditCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $cat->type }}', '{{ $cat->icon }}')" title="Edit Category">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="btn-cc btn-cc-ghost btn-cc-xs" style="color:var(--cc-expense);" onclick="openDeleteCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}')" title="Delete Category">
                        <i class="bi bi-trash3"></i>
                      </button>
                    @else
                      <button class="btn-cc btn-cc-ghost btn-cc-xs" style="color:var(--cc-text-muted);" disabled title="Default category (protected)"><i class="bi bi-lock"></i></button>
                    @endif
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">No expense categories found.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <!-- Income Categories -->
      <div class="cc-card">
        <div class="cc-card-header">
          <h2 class="cc-card-title"><i class="bi bi-arrow-down-left me-2" style="color:var(--cc-income);"></i>Income Categories</h2>
          <span class="cc-badge cc-badge-income">{{ $incomeCategories->count() }} categories</span>
        </div>
        <div class="cc-table-wrapper" style="border:none;border-radius:0;">
          <table class="cc-table">
            <thead>
              <tr>
                <th>Category</th>
                <th>Icon</th>
                <th>Type</th>
                <th>This Month</th>
                <th>Transactions</th>
                <th>Scope</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($incomeCategories as $cat)
              <tr @if(!$cat->is_default) style="background:var(--cc-surface-tint);" @endif>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="txn-icon" style="background:var(--cc-income-bg);color:var(--cc-income);">
                      <i class="bi {{ $cat->icon ?: 'bi-cash-coin' }}"></i>
                    </div>
                    <div>
                      <div class="font-bold">{{ $cat->name }}</div>
                      <div style="font-size:var(--fs-2xs);color:var(--cc-text-muted);">{{ $cat->is_default ? 'System default' : 'Personal category' }}</div>
                    </div>
                  </div>
                </td>
                <td><code style="font-size:var(--fs-xs);background:var(--cc-surface-subtle);padding:2px 6px;border-radius:4px;">{{ $cat->icon ?: 'bi-tag' }}</code></td>
                <td><span class="cc-badge cc-badge-income">Income</span></td>
                <td class="cc-amount font-bold" style="color:var(--cc-income);">Rs. {{ number_format($cat->month_amount, 2) }}</td>
                <td>{{ $cat->total_count }} entries</td>
                <td>
                  @if($cat->is_default)
                    <span class="cc-badge cc-badge-neutral"><i class="bi bi-shield-lock me-1"></i>Default</span>
                  @else
                    <span class="cc-badge cc-badge-teal"><i class="bi bi-person me-1"></i>Personal</span>
                  @endif
                </td>
                <td>
                  <div class="d-flex gap-1">
                    @if(!$cat->is_default)
                      <button class="btn-cc btn-cc-ghost btn-cc-xs" onclick="openEditCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $cat->type }}', '{{ $cat->icon }}')" title="Edit Category">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="btn-cc btn-cc-ghost btn-cc-xs" style="color:var(--cc-expense);" onclick="openDeleteCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}')" title="Delete Category">
                        <i class="bi bi-trash3"></i>
                      </button>
                    @else
                      <button class="btn-cc btn-cc-ghost btn-cc-xs" style="color:var(--cc-text-muted);" disabled title="Default category (protected)"><i class="bi bi-lock"></i></button>
                    @endif
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">No income categories found.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- Add Category Modal -->
<div class="cc-modal-overlay" id="addCategoryModal">
  <div class="cc-modal" style="max-width:440px;">
    <div class="cc-modal-header">
      <h3 class="cc-card-title"><i class="bi bi-tag me-2" style="color:var(--cc-primary);"></i>New Category</h3>
      <button class="btn-cc btn-cc-ghost btn-cc-sm" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <form action="{{ route('categories.store') }}" method="POST">
      @csrf
      <div class="cc-card-body">
        <div class="cc-form-group">
          <label class="cc-label" for="addCatName">Category Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="cc-input" id="addCatName" placeholder="e.g., Gym, Freelance, Rent" required maxlength="100">
        </div>
        <div class="cc-form-group">
          <label class="cc-label" for="addCatType">Type <span class="text-danger">*</span></label>
          <select name="type" class="cc-select" id="addCatType" required>
            <option value="expense">Expense</option>
            <option value="income">Income</option>
          </select>
        </div>
        <div class="cc-form-group mb-0">
          <label class="cc-label" for="addCatIcon">Icon (Bootstrap Icons class)</label>
          <div class="cc-input-group">
            <span class="cc-input-icon"><i class="bi bi-tag"></i></span>
            <input type="text" name="icon" class="cc-input has-icon-left" id="addCatIcon" placeholder="e.g., bi-laptop, bi-heart-pulse" maxlength="50">
          </div>
          <div class="cc-form-text">Browse icons at <a href="https://icons.getbootstrap.com" target="_blank" rel="noopener">icons.getbootstrap.com</a></div>
        </div>
      </div>
      <div class="cc-card-footer d-flex justify-content-end gap-2">
        <button type="button" class="btn-cc btn-cc-secondary btn-cc-sm" data-modal-close>Cancel</button>
        <button type="submit" class="btn-cc btn-cc-primary btn-cc-sm">
          <i class="bi bi-check-lg"></i> Create Category
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Category Modal -->
<div class="cc-modal-overlay" id="editCategoryModal">
  <div class="cc-modal" style="max-width:440px;">
    <div class="cc-modal-header">
      <h3 class="cc-card-title"><i class="bi bi-pencil-square me-2" style="color:var(--cc-primary);"></i>Edit Category</h3>
      <button class="btn-cc btn-cc-ghost btn-cc-sm" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <form id="editCategoryForm" method="POST">
      @csrf
      @method('PUT')
      <div class="cc-card-body">
        <div class="cc-form-group">
          <label class="cc-label" for="editCatName">Category Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="cc-input" id="editCatName" required maxlength="100">
        </div>
        <div class="cc-form-group">
          <label class="cc-label" for="editCatType">Type <span class="text-danger">*</span></label>
          <select name="type" class="cc-select" id="editCatType" required>
            <option value="expense">Expense</option>
            <option value="income">Income</option>
          </select>
        </div>
        <div class="cc-form-group mb-0">
          <label class="cc-label" for="editCatIcon">Icon (Bootstrap Icons class)</label>
          <div class="cc-input-group">
            <span class="cc-input-icon"><i class="bi bi-tag"></i></span>
            <input type="text" name="icon" class="cc-input has-icon-left" id="editCatIcon" maxlength="50">
          </div>
        </div>
      </div>
      <div class="cc-card-footer d-flex justify-content-end gap-2">
        <button type="button" class="btn-cc btn-cc-secondary btn-cc-sm" data-modal-close>Cancel</button>
        <button type="submit" class="btn-cc btn-cc-primary btn-cc-sm">
          <i class="bi bi-check-lg"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Category Modal -->
<div class="cc-modal-overlay" id="deleteCategoryModal">
  <div class="cc-modal" style="max-width:400px;">
    <div class="cc-modal-header">
      <h3 class="cc-card-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Delete Category</h3>
      <button class="btn-cc btn-cc-ghost btn-cc-sm" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="cc-card-body text-center">
      <p class="mb-2">Are you sure you want to delete <strong id="deleteCatName"></strong>?</p>
      <div class="cc-alert cc-alert-warning text-start" style="font-size:var(--fs-xs);">
        <i class="bi bi-shield-exclamation cc-alert-icon"></i>
        <span>Categories with existing transactions cannot be deleted.</span>
      </div>
    </div>
    <div class="cc-card-footer d-flex justify-content-end gap-2">
      <button type="button" class="btn-cc btn-cc-secondary btn-cc-sm" data-modal-close>Cancel</button>
      <form id="deleteCategoryForm" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn-cc btn-cc-danger btn-cc-sm">Delete Category</button>
      </form>
    </div>
  </div>
</div>

<!-- Logout Modal -->
<div class="cc-modal-overlay" id="logoutModal">
  <div class="cc-modal" style="max-width:380px;">
    <div class="cc-modal-header"><h3 class="cc-card-title">Confirm Logout</h3><button class="btn-cc btn-cc-ghost btn-cc-sm" data-modal-close><i class="bi bi-x-lg"></i></button></div>
    <div class="cc-card-body text-center"><p class="text-secondary mb-0" style="font-size:var(--fs-sm);">End your session?</p></div>
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
function openEditCategory(id, name, type, icon) {
  document.getElementById('editCategoryForm').action = '/categories/' + id;
  document.getElementById('editCatName').value = name;
  document.getElementById('editCatType').value = type;
  document.getElementById('editCatIcon').value = icon || '';
  Modal.open('editCategoryModal');
}

function openDeleteCategory(id, name) {
  document.getElementById('deleteCategoryForm').action = '/categories/' + id;
  document.getElementById('deleteCatName').textContent = name;
  Modal.open('deleteCategoryModal');
}
</script>
</body>
</html>
