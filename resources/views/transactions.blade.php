<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="All Transactions — Campus Coin. Search, filter, and manage every transaction.">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Transactions — Campus Coin</title>
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
          <div class="cc-breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span class="sep"><i class="bi bi-chevron-right"></i></span><span class="current">Transactions</span></div>
          <div class="cc-topbar-title">All Transactions</div>
        </div>
      </div>
      <div class="cc-topbar-right">
        <form action="{{ route('transactions') }}" method="GET" class="cc-topbar-search">
          <i class="bi bi-search"></i>
          <input type="text" name="search" value="{{ request('search') }}" placeholder="Search transactions..." aria-label="Search">
        </form>
        <button class="cc-theme-toggle" aria-label="Toggle theme" onclick="ThemeManager.toggle()"><i class="bi bi-moon-fill"></i></button>
        <a href="{{ route('profile') }}" class="cc-avatar" style="font-size:var(--fs-xs);">{{ $user->initials }}</a>
      </div>
    </header>

    <div class="cc-content">

      @include('partials.alerts')

      <div class="cc-page-header">
        <div>
          <h1 class="cc-page-title">Transactions</h1>
          <p class="cc-page-subtitle">Real-time ledger of all income and expense entries for your account.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <button class="btn-cc btn-cc-secondary" onclick="Modal.open('csvImportModal')">
            <i class="bi bi-upload"></i> Import CSV
          </button>
          <a href="{{ route('transactions.export') }}" class="btn-cc btn-cc-secondary">
            <i class="bi bi-download"></i> Export CSV
          </a>
          <a href="{{ route('transactions.create') }}" class="btn-cc btn-cc-primary">
            <i class="bi bi-plus-lg"></i> New Transaction
          </a>
        </div>
      </div>

      <!-- Summary Row -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="cc-stat-card income">
            <div class="cc-stat-label">Total Inflow ({{ date('M') }})</div>
            <div class="cc-stat-value" style="color:var(--cc-income);">Rs. {{ number_format($totalIncome, 2) }}</div>
            <div class="cc-stat-meta"><i class="bi bi-arrow-down-left me-1" style="color:var(--cc-income);"></i>{{ $incomeCount }} income entries</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="cc-stat-card expense">
            <div class="cc-stat-label">Total Outflow ({{ date('M') }})</div>
            <div class="cc-stat-value" style="color:var(--cc-expense);">Rs. {{ number_format($totalExpense, 2) }}</div>
            <div class="cc-stat-meta"><i class="bi bi-arrow-up-right me-1" style="color:var(--cc-expense);"></i>{{ $expenseCount }} expense entries</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="cc-stat-card balance">
            <div class="cc-stat-label">Net Monthly Balance</div>
            <div class="cc-stat-value" style="color:{{ $netBalance >= 0 ? 'var(--cc-text-main)' : 'var(--cc-expense)' }};">
              Rs. {{ number_format($netBalance, 2) }}
            </div>
            <div class="cc-stat-meta">
              @if($netBalance >= 0)
                <span class="cc-badge cc-badge-income"><i class="bi bi-shield-check"></i> Healthy Surplus</span>
              @else
                <span class="cc-badge cc-badge-expense"><i class="bi bi-exclamation-triangle"></i> Deficit</span>
              @endif
            </div>
          </div>
        </div>
      </div>

      <!-- Filters Form -->
      <div class="cc-card mb-4">
        <div class="cc-card-body-sm">
          <form action="{{ route('transactions') }}" method="GET" id="filterForm">
            <div class="row g-2 align-items-center">
              <div class="col-lg-3 col-md-6">
                <div class="cc-input-group">
                  <span class="cc-input-icon"><i class="bi bi-search"></i></span>
                  <input type="text" name="search" value="{{ request('search') }}" class="cc-input has-icon-left" placeholder="Search description..." id="txnSearch">
                </div>
              </div>
              <div class="col-lg-2 col-md-3 col-6">
                <select name="type" class="cc-select" id="txnType">
                  <option value="">All Types</option>
                  <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Income</option>
                  <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Expense</option>
                </select>
              </div>
              <div class="col-lg-2 col-md-3 col-6">
                <select name="category_id" class="cc-select" id="txnCategory">
                  <option value="">All Categories</option>
                  @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-lg-2 col-md-4 col-6">
                <select name="month" class="cc-select" id="txnMonth">
                  <option value="">All Months</option>
                  @foreach($distinctMonths as $ym)
                    <option value="{{ $ym }}" {{ request('month') === $ym ? 'selected' : '' }}>
                      {{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('F Y') }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="col-lg-1 col-md-4 col-6">
                <select name="sort" class="cc-select" id="txnSort">
                  <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest</option>
                  <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest</option>
                  <option value="highest" {{ request('sort') === 'highest' ? 'selected' : '' }}>Highest</option>
                  <option value="lowest" {{ request('sort') === 'lowest' ? 'selected' : '' }}>Lowest</option>
                </select>
              </div>
              <div class="col-lg-2 col-md-4 col-12 d-flex gap-2">
                <button type="submit" class="btn-cc btn-cc-primary flex-1">
                  <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="{{ route('transactions') }}" class="btn-cc btn-cc-secondary" title="Clear Filters">
                  <i class="bi bi-x-lg"></i>
                </a>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Transactions Table / Empty State -->
      <div class="cc-card">
        <div class="cc-card-header">
          <h2 class="cc-card-title">
            <i class="bi bi-list-check me-2" style="color:var(--cc-primary);"></i>
            Transaction Ledger ({{ $transactions->total() }} total)
          </h2>
          <div class="d-flex gap-2">
            <span class="cc-badge cc-badge-neutral">Page {{ $transactions->currentPage() }} of {{ max(1, $transactions->lastPage()) }}</span>
          </div>
        </div>

        @if($transactions->isEmpty())
          <!-- Empty State -->
          <div class="cc-card-body text-center py-5">
            <div style="width:64px;height:64px;border-radius:50%;background:var(--cc-surface-tint);color:var(--cc-primary);display:inline-flex;align-items:center;justify-content:center;font-size:2rem;margin-bottom:1rem;">
              <i class="bi bi-wallet2"></i>
            </div>
            <h3 class="font-bold mb-1" style="font-size:var(--fs-lg);">No transactions found</h3>
            <p class="text-secondary mb-4" style="font-size:var(--fs-sm);max-width:400px;margin-left:auto;margin-right:auto;">
              @if(request()->hasAny(['search', 'type', 'category_id', 'month', 'sort']))
                No transaction records match your active search and filter criteria. Try resetting your filters.
              @else
                Start your money story by logging your first transaction or importing records via CSV.
              @endif
            </p>
            <div class="d-flex gap-2 justify-content-center">
              @if(request()->hasAny(['search', 'type', 'category_id', 'month', 'sort']))
                <a href="{{ route('transactions') }}" class="btn-cc btn-cc-secondary">
                  <i class="bi bi-x-circle"></i> Clear Filters
                </a>
              @endif
              <button class="btn-cc btn-cc-secondary" onclick="Modal.open('csvImportModal')">
                <i class="bi bi-upload"></i> Import CSV
              </button>
              <a href="{{ route('transactions.create') }}" class="btn-cc btn-cc-primary">
                <i class="bi bi-plus-lg"></i> Add Transaction
              </a>
            </div>
          </div>
        @else
          <div class="cc-table-wrapper" style="border:none;border-radius:0;">
            <table class="cc-table">
              <thead>
                <tr>
                  <th>Description</th>
                  <th>Category</th>
                  <th>Payment Method</th>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Amount</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($transactions as $txn)
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="txn-icon" style="background:{{ $txn->type === 'income' ? 'var(--cc-income-bg)' : 'var(--cc-warning-bg)' }};color:{{ $txn->type === 'income' ? 'var(--cc-income)' : 'var(--cc-warning)' }};">
                        <i class="bi {{ $txn->category ? $txn->category->icon : ($txn->type === 'income' ? 'bi-cash-coin' : 'bi-cup-hot') }}"></i>
                      </div>
                      <div>
                        <div class="font-bold">{{ $txn->description }}</div>
                        @if($txn->is_recurring)
                          <span class="cc-badge cc-badge-teal" style="font-size:9px;padding:1px 5px;">
                            <i class="bi bi-arrow-repeat me-1"></i>Recurring ({{ ucfirst($txn->recurring_frequency) }})
                          </span>
                        @endif
                        @if($txn->note)
                          <div style="font-size:var(--fs-2xs);color:var(--cc-text-muted);">{{ Str::limit($txn->note, 35) }}</div>
                        @endif
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="cc-badge {{ $txn->type === 'income' ? 'cc-badge-income' : 'cc-badge-warning' }}">
                      {{ $txn->category ? $txn->category->name : 'Uncategorized' }}
                    </span>
                  </td>
                  <td><span class="cc-badge cc-badge-neutral">{{ $txn->payment_method ?? 'Cash' }}</span></td>
                  <td style="font-size:var(--fs-xs);color:var(--cc-text-muted);">
                    {{ $txn->transaction_date ? $txn->transaction_date->format('M d, Y') : 'N/A' }}
                  </td>
                  <td>
                    <span class="cc-badge {{ $txn->type === 'income' ? 'cc-badge-income' : 'cc-badge-expense' }}">
                      {{ ucfirst($txn->type) }}
                    </span>
                  </td>
                  <td class="cc-amount font-bold" style="color:{{ $txn->type === 'income' ? 'var(--cc-income)' : 'var(--cc-expense)' }};">
                    {{ $txn->type === 'income' ? '+' : '-' }}Rs. {{ number_format($txn->amount, 2) }}
                  </td>
                  <td>
                    <div class="d-flex gap-1 align-items-center flex-wrap">
                      <button class="btn-cc btn-cc-ghost btn-cc-xs" 
                        onclick="openEditTxn({{ $txn->id }}, '{{ addslashes($txn->description) }}', '{{ $txn->amount }}', '{{ $txn->type }}', {{ $txn->category_id }}, '{{ $txn->transaction_date ? $txn->transaction_date->format('Y-m-d') : '' }}', '{{ addslashes($txn->payment_method ?? 'Cash') }}', '{{ addslashes($txn->note ?? '') }}', {{ $txn->is_recurring ? 'true' : 'false' }}, '{{ $txn->recurring_frequency ?? 'monthly' }}')"
                        title="Edit Transaction">
                        <i class="bi bi-pencil"></i>
                      </button>
                      {{-- Phase 9: Bookmark Toggle --}}
                      <button class="btn-cc btn-cc-ghost btn-cc-xs txn-bm-btn" id="bm-btn-{{ $txn->id }}"
                        onclick="toggleBookmark({{ $txn->id }}, this)"
                        title="Bookmark Transaction"
                        style="color: {{ $txn->isBookmarkedBy(Auth::user()) ? 'var(--cc-warning)' : 'var(--cc-text-muted)' }};">
                        <i class="bi {{ $txn->isBookmarkedBy(Auth::user()) ? 'bi-bookmark-star-fill' : 'bi-bookmark' }}"></i>
                      </button>
                      {{-- Phase 9: Note Button --}}
                      <button class="btn-cc btn-cc-ghost btn-cc-xs"
                        onclick="openNoteModal({{ $txn->id }}, '{{ addslashes($txn->description) }}')"
                        title="{{ $txn->noteRecord ? 'View/Edit Note' : 'Add Personal Note' }}"
                        style="color: {{ $txn->noteRecord ? 'var(--cc-primary)' : 'var(--cc-text-muted)' }};">
                        <i class="bi {{ $txn->noteRecord ? 'bi-sticky-fill' : 'bi-sticky' }}"></i>
                      </button>
                      {{-- Phase 9: Share Button --}}
                      <button class="btn-cc btn-cc-ghost btn-cc-xs"
                        onclick="openShareModal({{ $txn->id }})"
                        title="Share Transaction"
                        style="color:var(--cc-text-muted);">
                        <i class="bi bi-share"></i>
                      </button>
                      <button class="btn-cc btn-cc-ghost btn-cc-xs" style="color:var(--cc-expense);" 
                        onclick="openDeleteTxn({{ $txn->id }}, '{{ addslashes($txn->description) }}', '{{ number_format($txn->amount, 2) }}')"
                        title="Delete Transaction">
                        <i class="bi bi-trash3"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <!-- Pagination Footer -->
          <div class="cc-card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span style="font-size:var(--fs-xs);color:var(--cc-text-muted);">
              Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} transactions
            </span>
            <div>
              {{ $transactions->links('pagination::bootstrap-4') }}
            </div>
          </div>
        @endif

      </div>

      {{-- Phase 9: Recent Activity Feed --}}
      @if(!empty($recentActivities) && $recentActivities->count())
      <div class="cc-card mt-4">
        <div class="cc-card-header">
          <h2 class="cc-card-title"><i class="bi bi-activity me-2" style="color:var(--cc-primary);"></i>Recently Viewed / Edited</h2>
          <a href="{{ route('bookmarks') }}" class="btn-cc btn-cc-ghost btn-cc-sm">Bookmarks <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="cc-card-body">
          <div class="d-flex flex-column gap-2">
            @foreach($recentActivities as $act)
              <div class="d-flex align-items-center gap-3 p-2 rounded" style="background:var(--cc-surface-subtle);font-size:var(--fs-xs);">
                <span class="cc-badge {{ $act->action === 'edited' ? 'cc-badge-warning' : 'cc-badge-neutral' }}">
                  <i class="bi {{ $act->action === 'edited' ? 'bi-pencil' : 'bi-eye' }}"></i>
                  {{ ucfirst($act->action) }}
                </span>
                <span class="font-bold text-truncate" style="max-width:200px;">{{ $act->transaction->description ?? 'Transaction #'.$act->transaction_id }}</span>
                <span class="text-secondary ms-auto">{{ $act->created_at->diffForHumans() }}</span>
              </div>
            @endforeach
          </div>
        </div>
      </div>
      @endif

    </div>
  </main>
</div>

<!-- Phase 9: NOTE MODAL -->
<div class="cc-modal-overlay" id="noteModal">
  <div class="cc-modal" style="max-width:500px;">
    <div class="cc-modal-header">
      <h3 class="cc-card-title"><i class="bi bi-sticky-fill me-2" style="color:var(--cc-primary);"></i>Personal Transaction Note</h3>
      <button class="btn-cc btn-cc-ghost btn-cc-sm" onclick="Modal.close('noteModal')" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="cc-card-body">
      <p class="text-secondary mb-2" id="noteModalDesc" style="font-size:var(--fs-xs);"></p>
      <textarea id="txnNoteInput" class="cc-textarea" rows="4" placeholder="Add private notes about this transaction (e.g. shared with roommates, receipt details)..." maxlength="2000" style="height:120px;"></textarea>
    </div>
    <div class="cc-card-footer d-flex justify-content-between">
      <button class="btn-cc btn-cc-danger btn-cc-sm" id="deleteNoteBtn" onclick="deleteNote()" style="display:none;"><i class="bi bi-trash"></i> Delete Note</button>
      <div class="d-flex gap-2 ms-auto">
        <button class="btn-cc btn-cc-secondary btn-cc-sm" onclick="Modal.close('noteModal')">Cancel</button>
        <button class="btn-cc btn-cc-primary btn-cc-sm" onclick="saveNote()"><i class="bi bi-check-lg"></i> Save Note</button>
      </div>
    </div>
  </div>
</div>

<!-- Phase 9: SHARE MODAL -->
<div class="cc-modal-overlay" id="shareModal">
  <div class="cc-modal" style="max-width:480px;">
    <div class="cc-modal-header">
      <h3 class="cc-card-title"><i class="bi bi-share-fill me-2" style="color:var(--cc-primary);"></i>Share Transaction</h3>
      <button class="btn-cc btn-cc-ghost btn-cc-sm" onclick="Modal.close('shareModal')" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="cc-card-body">
      <p class="text-secondary mb-3" style="font-size:var(--fs-xs);">Generate a secure receipt link. This exposes only amount, category, and date — never your email or private notes.</p>
      <div class="cc-input-group mb-2">
        <input type="text" id="shareUrlInput" class="cc-input" readonly placeholder="Generating share link...">
      </div>
      <div id="shareStatusText" class="d-none" style="font-size:var(--fs-xs);color:var(--cc-income);"><i class="bi bi-check-circle-fill me-1"></i>Link copied to clipboard!</div>
    </div>
    <div class="cc-card-footer d-flex justify-content-end gap-2">
      <button class="btn-cc btn-cc-secondary btn-cc-sm" onclick="Modal.close('shareModal')">Close</button>
      <button class="btn-cc btn-cc-primary btn-cc-sm" onclick="copyShareUrl()"><i class="bi bi-clipboard"></i> Copy Link</button>
    </div>
  </div>
</div>

<!-- EDIT TRANSACTION MODAL -->
<div class="cc-modal-overlay" id="editTxnModal">
  <div class="cc-modal" style="max-width:560px;">
    <div class="cc-modal-header">
      <h3 class="cc-card-title"><i class="bi bi-pencil-square me-2" style="color:var(--cc-primary);"></i>Edit Transaction</h3>
      <button class="btn-cc btn-cc-ghost btn-cc-sm" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <form id="editTxnForm" method="POST">
      @csrf
      @method('PUT')
      <div class="cc-card-body">
        <div class="row g-3">
          <div class="col-sm-6">
            <label class="cc-label" for="editTxnType">Type</label>
            <select name="type" class="cc-select" id="editTxnType" required onchange="filterEditCategories(this.value)">
              <option value="expense">Expense</option>
              <option value="income">Income</option>
            </select>
          </div>
          <div class="col-sm-6">
            <label class="cc-label" for="editTxnAmount">Amount (Rs.) <span class="text-danger">*</span></label>
            <input type="number" name="amount" class="cc-input" id="editTxnAmount" step="0.01" min="0.01" required>
          </div>
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="cc-label mb-0" for="editTxnDesc">Description <span class="text-danger">*</span></label>
              <span id="editAiTypingIndicator" style="display:none;font-size:11px;color:var(--cc-primary);">
                <span class="spinner-border spinner-border-sm me-1" style="width:10px;height:10px;" role="status"></span> AI analyzing...
              </span>
            </div>
            <input type="text" name="description" class="cc-input" id="editTxnDesc" required maxlength="255" autocomplete="off">
            <div id="editAiSuggestionBox" class="mt-2" style="display:none;">
              <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.25);font-size:var(--fs-xs);">
                <div class="d-flex align-items-center gap-2" style="min-width:0;">
                  <span class="badge" style="background:var(--cc-primary);color:#fff;font-size:10px;"><i class="bi bi-stars"></i> AI</span>
                  <span class="text-truncate">Suggested: <strong id="editAiSuggestedCategoryName" style="color:var(--cc-primary);"></strong></span>
                </div>
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                  <button type="button" class="btn-cc btn-cc-primary btn-cc-xs" onclick="applyEditAiSuggestion()">
                    <i class="bi bi-check2"></i> Apply
                  </button>
                  <button type="button" class="btn-cc btn-cc-ghost btn-cc-xs text-secondary" onclick="dismissEditAiSuggestion()" title="Dismiss">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-6">
            <label class="cc-label" for="editTxnCategory">Category <span class="text-danger">*</span></label>
            <select name="category_id" class="cc-select" id="editTxnCategory" required>
              @foreach($categories as $cat)
                <option value="{{ $cat->id }}" data-type="{{ $cat->type }}">{{ $cat->name }} ({{ ucfirst($cat->type) }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-sm-6">
            <label class="cc-label" for="editTxnDate">Date <span class="text-danger">*</span></label>
            <input type="date" name="transaction_date" class="cc-input" id="editTxnDate" required>
          </div>
          <div class="col-sm-6">
            <label class="cc-label" for="editTxnPayment">Payment Method</label>
            <select name="payment_method" class="cc-select" id="editTxnPayment">
              <option value="Cash">Cash</option>
              <option value="JazzCash">JazzCash</option>
              <option value="EasyPaisa">EasyPaisa</option>
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Card">Credit / Debit Card</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="col-12">
            <label class="cc-label" for="editTxnNote">Note</label>
            <textarea name="note" class="cc-textarea" id="editTxnNote" style="height:60px;"></textarea>
          </div>
        </div>
      </div>
      <div class="cc-card-footer d-flex justify-content-end gap-2">
        <button type="button" class="btn-cc btn-cc-secondary btn-cc-sm" data-modal-close>Cancel</button>
        <button type="submit" class="btn-cc btn-cc-primary btn-cc-sm">
          <i class="bi bi-check-lg"></i> Update Transaction
        </button>
      </div>
    </form>
  </div>
</div>

<!-- DELETE TRANSACTION MODAL -->
<div class="cc-modal-overlay" id="deleteTxnModal">
  <div class="cc-modal" style="max-width:400px;">
    <div class="cc-modal-header">
      <h3 class="cc-card-title text-danger"><i class="bi bi-trash3 me-2"></i>Delete Transaction</h3>
      <button class="btn-cc btn-cc-ghost btn-cc-sm" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="cc-card-body text-center">
      <p class="mb-2">Are you sure you want to delete this transaction record?</p>
      <div class="p-2 rounded mb-2 font-bold" style="background:var(--cc-surface-tint);">
        <span id="deleteTxnDesc"></span> — <span id="deleteTxnAmount" class="text-danger"></span>
      </div>
      <div style="font-size:var(--fs-xs);color:var(--cc-text-muted);">This action will immediately update your balance.</div>
    </div>
    <div class="cc-card-footer d-flex justify-content-end gap-2">
      <button type="button" class="btn-cc btn-cc-secondary btn-cc-sm" data-modal-close>Cancel</button>
      <form id="deleteTxnForm" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn-cc btn-cc-danger btn-cc-sm">Delete Transaction</button>
      </form>
    </div>
  </div>
</div>

<!-- CSV IMPORT MODAL -->
<div class="cc-modal-overlay" id="csvImportModal">
  <div class="cc-modal" style="max-width:720px;">
    <div class="cc-modal-header">
      <h3 class="cc-card-title"><i class="bi bi-filetype-csv me-2" style="color:var(--cc-primary);"></i>Import Transactions CSV</h3>
      <button class="btn-cc btn-cc-ghost btn-cc-sm" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="cc-card-body">
      <!-- Step 1: Upload Form -->
      <div id="csvUploadStep">
        <div class="cc-alert cc-alert-info mb-3" style="font-size:var(--fs-xs);">
          <i class="bi bi-info-circle cc-alert-icon"></i>
          <div>
            <strong>Required CSV format:</strong> <code>date,type,category,amount,description</code><br>
            Example: <code>2026-09-01,expense,Food,450,Campus Cafe lunch</code>
          </div>
        </div>
        <div class="cc-form-group">
          <label class="cc-label" for="csvFileInput">Choose CSV File (.csv)</label>
          <input type="file" id="csvFileInput" class="form-control cc-input" accept=".csv,text/csv">
        </div>
        <div class="d-flex justify-content-end">
          <button type="button" id="btnPreviewCsv" class="btn-cc btn-cc-primary" onclick="previewCsvFile()">
            <i class="bi bi-eye"></i> Validate &amp; Preview
          </button>
        </div>
      </div>

      <!-- Step 2: Preview & Validation Table -->
      <div id="csvPreviewStep" style="display:none;">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div id="csvSummaryStats" class="font-bold" style="font-size:var(--fs-sm);"></div>
          <button type="button" class="btn-cc btn-cc-ghost btn-cc-xs" onclick="resetCsvModal()"><i class="bi bi-arrow-counterclockwise"></i> Choose Another File</button>
        </div>
        <div class="cc-table-wrapper mb-3" style="max-height:260px;overflow-y:auto;">
          <table class="cc-table" style="font-size:var(--fs-xs);">
            <thead>
              <tr><th>Row</th><th>Date</th><th>Type</th><th>Category</th><th>Amount</th><th>Status</th></tr>
            </thead>
            <tbody id="csvPreviewBody"></tbody>
          </table>
        </div>
        <div class="d-flex justify-content-end gap-2">
          <button type="button" class="btn-cc btn-cc-secondary btn-cc-sm" data-modal-close>Cancel</button>
          <button type="button" id="btnConfirmImport" class="btn-cc btn-cc-primary btn-cc-sm" onclick="confirmCsvImport()">
            <i class="bi bi-check-circle"></i> Confirm Import Valid Rows
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Logout Modal -->
<div class="cc-modal-overlay" id="logoutModal">
  <div class="cc-modal" style="max-width:380px;">
    <div class="cc-modal-header"><h3 class="cc-card-title">Confirm Logout</h3><button class="btn-cc btn-cc-ghost btn-cc-sm" data-modal-close><i class="bi bi-x-lg"></i></button></div>
    <div class="cc-card-body text-center"><p class="text-secondary mb-0" style="font-size:var(--fs-sm);">Are you sure you want to end your session?</p></div>
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
let validImportRows = [];

function openEditTxn(id, desc, amount, type, categoryId, date, paymentMethod, note, isRecurring, recurringFrequency) {
  document.getElementById('editTxnForm').action = '/transactions/' + id;
  document.getElementById('editTxnDesc').value = desc;
  document.getElementById('editTxnAmount').value = amount;
  document.getElementById('editTxnType').value = type;
  document.getElementById('editTxnDate').value = date;
  document.getElementById('editTxnPayment').value = paymentMethod;
  document.getElementById('editTxnNote').value = note;
  filterEditCategories(type);
  document.getElementById('editTxnCategory').value = categoryId;
  Modal.open('editTxnModal');
}

function filterEditCategories(type) {
  const select = document.getElementById('editTxnCategory');
  for (let opt of select.options) {
    if (opt.dataset.type) {
      opt.style.display = (opt.dataset.type === type) ? '' : 'none';
    }
  }
}

function openDeleteTxn(id, desc, amount) {
  document.getElementById('deleteTxnForm').action = '/transactions/' + id;
  document.getElementById('deleteTxnDesc').textContent = desc;
  document.getElementById('deleteTxnAmount').textContent = 'Rs. ' + amount;
  Modal.open('deleteTxnModal');
}

// CSV Import Logic
async function previewCsvFile() {
  const fileInput = document.getElementById('csvFileInput');
  if (!fileInput.files.length) {
    Toast.warning('Select File', 'Please select a valid CSV file to upload.');
    return;
  }

  const btn = document.getElementById('btnPreviewCsv');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Parsing...';

  const formData = new FormData();
  formData.append('csv_file', fileInput.files[0]);
  formData.append('_token', '{{ csrf_token() }}');

  try {
    const res = await fetch('{{ route("transactions.import.preview") }}', {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    const data = await res.json();
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-eye"></i> Validate &amp; Preview';

    if (!res.ok) {
      Toast.error('Upload Error', data.error || 'Failed to parse CSV file.');
      return;
    }

    validImportRows = data.valid_rows || [];
    renderCsvPreview(data);
  } catch (err) {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-eye"></i> Validate &amp; Preview';
    Toast.error('Error', 'An unexpected error occurred while parsing the CSV.');
  }
}

function renderCsvPreview(data) {
  document.getElementById('csvUploadStep').style.display = 'none';
  document.getElementById('csvPreviewStep').style.display = 'block';

  document.getElementById('csvSummaryStats').innerHTML = 
    `<span>Found ${data.total_rows} rows:</span> ` +
    `<span class="text-success ms-2"><i class="bi bi-check-circle"></i> ${data.valid_count} valid</span> ` +
    (data.invalid_count > 0 ? `<span class="text-danger ms-2"><i class="bi bi-exclamation-triangle"></i> ${data.invalid_count} issues</span>` : '');

  const tbody = document.getElementById('csvPreviewBody');
  tbody.innerHTML = '';

  data.rows.forEach(r => {
    const tr = document.createElement('tr');
    tr.style.background = r.is_valid ? 'transparent' : 'rgba(239, 68, 68, 0.08)';
    
    let statusBadge = r.is_valid 
      ? '<span class="cc-badge cc-badge-income"><i class="bi bi-check"></i> Ready</span>'
      : `<span class="cc-badge cc-badge-expense" title="${r.errors.join(', ')}"><i class="bi bi-x"></i> ${r.errors[0]}</span>`;

    tr.innerHTML = `
      <td>#${r.row_number}</td>
      <td>${r.date}</td>
      <td><span class="cc-badge ${r.type === 'income' ? 'cc-badge-income' : 'cc-badge-expense'}">${r.type}</span></td>
      <td>${r.category_name}</td>
      <td class="font-bold">Rs. ${r.amount}</td>
      <td>${statusBadge}</td>
    `;
    tbody.appendChild(tr);
  });

  const confirmBtn = document.getElementById('btnConfirmImport');
  if (data.valid_count === 0) {
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'No Valid Rows to Import';
  } else {
    confirmBtn.disabled = false;
    confirmBtn.innerHTML = `<i class="bi bi-check-circle"></i> Import ${data.valid_count} Valid Records`;
  }
}

function resetCsvModal() {
  document.getElementById('csvFileInput').value = '';
  document.getElementById('csvUploadStep').style.display = 'block';
  document.getElementById('csvPreviewStep').style.display = 'none';
  validImportRows = [];
}

async function confirmCsvImport() {
  if (!validImportRows.length) return;

  const btn = document.getElementById('btnConfirmImport');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Importing...';

  try {
    const res = await fetch('{{ route("transactions.import.confirm") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ records: validImportRows })
    });

    const data = await res.json();
    if (res.ok && data.success) {
      Toast.success('Import Successful', data.message);
      Modal.close('csvImportModal');
      setTimeout(() => window.location.reload(), 1000);
    } else {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check-circle"></i> Retry Import';
      Toast.error('Import Failed', data.message || 'Unable to complete CSV import.');
    }
  } catch (err) {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-circle"></i> Retry Import';
    Toast.error('Error', 'Failed to connect to server for import.');
  }
}

// ===================== Phase 9: Bookmark / Note / Share =====================
let currentTxId = null;

function toggleBookmark(txId, btn) {
  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  fetch(`/transactions/${txId}/bookmark`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      const icon = btn.querySelector('i');
      if (data.bookmarked) {
        icon.className = 'bi bi-bookmark-star-fill';
        btn.style.color = 'var(--cc-warning)';
        btn.title = 'Remove Bookmark';
      } else {
        icon.className = 'bi bi-bookmark';
        btn.style.color = 'var(--cc-text-muted)';
        btn.title = 'Bookmark Transaction';
      }
      if (typeof Toast !== 'undefined') Toast.success('Bookmark', data.message);
    }
  });
}

function openNoteModal(txId, description) {
  currentTxId = txId;
  document.getElementById('noteModalDesc').innerText = `Transaction: ${description}`;
  document.getElementById('txnNoteInput').value = '';
  document.getElementById('deleteNoteBtn').style.display = 'none';

  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  fetch(`/transactions/${txId}/notes`, {
    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
  })
  .then(r => r.json())
  .then(data => {
    if (data.success && data.note) {
      document.getElementById('txnNoteInput').value = data.note;
      document.getElementById('deleteNoteBtn').style.display = 'inline-flex';
    }
  });
  Modal.open('noteModal');
}

function saveNote() {
  const note = document.getElementById('txnNoteInput').value.trim();
  if (!note) { alert('Please enter a note.'); return; }
  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  fetch(`/transactions/${currentTxId}/notes`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
    body: JSON.stringify({ note })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      Modal.close('noteModal');
      if (typeof Toast !== 'undefined') Toast.success('Note Saved', data.message);
      setTimeout(() => location.reload(), 400);
    }
  });
}

function deleteNote() {
  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  fetch(`/transactions/${currentTxId}/notes`, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      Modal.close('noteModal');
      if (typeof Toast !== 'undefined') Toast.info('Note Removed', 'Note has been deleted.');
      setTimeout(() => location.reload(), 400);
    }
  });
}

function openShareModal(txId) {
  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const input = document.getElementById('shareUrlInput');
  input.value = 'Generating...';
  document.getElementById('shareStatusText').classList.add('d-none');
  fetch(`/transactions/${txId}/share`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) input.value = data.share_url;
    else input.value = 'Error generating link';
  });
  Modal.open('shareModal');
}

function copyShareUrl() {
  const input = document.getElementById('shareUrlInput');
  input.select();
  navigator.clipboard.writeText(input.value).then(() => {
    document.getElementById('shareStatusText').classList.remove('d-none');
  });
}
</script>
</body>
</html>
