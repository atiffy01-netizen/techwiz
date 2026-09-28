<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Add Transaction — Campus Coin. Log your income or expense in seconds.">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Add Transaction — Campus Coin</title>
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
          <div class="cc-breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span class="sep"><i class="bi bi-chevron-right"></i></span><span class="current">Add Transaction</span></div>
          <div class="cc-topbar-title">Add Transaction</div>
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
          <h1 class="cc-page-title">Add Transaction</h1>
          <p class="cc-page-subtitle">Log an income or expense entry with real category tagging.</p>
        </div>
        <a href="{{ route('transactions') }}" class="btn-cc btn-cc-secondary"><i class="bi bi-arrow-left"></i> Back to Transactions</a>
      </div>

      <div class="row g-4">
        <!-- Form -->
        <div class="col-lg-8">
          <div class="cc-card">
            <div class="cc-card-header">
              <h2 class="cc-card-title">Transaction Details</h2>
            </div>
            <form action="{{ route('transactions.store') }}" method="POST" id="txnForm" onsubmit="handleTxnSubmit(this)">
              @csrf
              <div class="cc-card-body">

                <!-- Type Toggle -->
                <div class="cc-form-group">
                  <label class="cc-label">Transaction Type <span class="text-danger">*</span></label>
                  <div class="d-flex gap-2">
                    <button type="button" id="btnExpense" class="btn-cc {{ old('type', $prefillType) === 'expense' ? 'btn-cc-danger' : 'btn-cc-secondary' }} flex-1" onclick="setType('expense')">
                      <i class="bi bi-dash-circle"></i> Expense
                    </button>
                    <button type="button" id="btnIncome" class="btn-cc {{ old('type', $prefillType) === 'income' ? 'btn-cc-primary' : 'btn-cc-secondary' }} flex-1" onclick="setType('income')">
                      <i class="bi bi-plus-circle"></i> Income
                    </button>
                  </div>
                  <input type="hidden" name="type" id="txnType" value="{{ old('type', $prefillType) }}">
                </div>

                <div class="row g-3">
                  <div class="col-12">
                    <div class="cc-form-group mb-0">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="cc-label mb-0" for="txnDescription">Description <span class="text-danger">*</span></label>
                        <span id="aiTypingIndicator" style="display:none;font-size:11px;color:var(--cc-primary);">
                          <span class="spinner-border spinner-border-sm me-1" style="width:10px;height:10px;" role="status"></span> Analyzing with AI...
                        </span>
                      </div>
                      <div class="cc-input-group">
                        <span class="cc-input-icon"><i class="bi bi-card-text"></i></span>
                        <input type="text" name="description" class="cc-input has-icon-left" id="txnDescription" placeholder="e.g., Campus Cafeteria lunch, Freelance project" value="{{ old('description') }}" required maxlength="255" autocomplete="off">
                      </div>

                      <!-- AI Category Suggestion Pill -->
                      <div id="aiSuggestionBox" class="mt-2" style="display:none;">
                        <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.25);font-size:var(--fs-xs);">
                          <div class="d-flex align-items-center gap-2" style="min-width:0;">
                            <span class="badge" style="background:var(--cc-primary);color:#fff;font-size:10px;font-weight:var(--fw-semibold);"><i class="bi bi-stars"></i> AI</span>
                            <span class="text-truncate">
                              Suggested: <strong id="aiSuggestedCategoryName" style="color:var(--cc-primary);"></strong>
                              <span id="aiSuggestedConfidence" class="text-secondary ms-1" style="font-size:11px;"></span>
                            </span>
                          </div>
                          <div class="d-flex align-items-center gap-1 flex-shrink-0">
                            <button type="button" class="btn-cc btn-cc-primary btn-cc-xs" id="btnApplyAiSuggestion" onclick="applyAiSuggestion()">
                              <i class="bi bi-check2"></i> Apply
                            </button>
                            <button type="button" class="btn-cc btn-cc-ghost btn-cc-xs text-secondary" onclick="dismissAiSuggestion()" title="Dismiss">
                              <i class="bi bi-x-lg"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-sm-6">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="txnAmount">Amount (Rs.) <span class="text-danger">*</span></label>
                      <div class="cc-input-group">
                        <span class="cc-input-prefix">Rs.</span>
                        <input type="number" name="amount" class="cc-input" id="txnAmount" placeholder="0.00" min="0.01" step="0.01" value="{{ old('amount') }}" required style="border-radius:0 var(--radius-md) var(--radius-md) 0;">
                      </div>
                    </div>
                  </div>

                  <div class="col-sm-6">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="txnDate">Date <span class="text-danger">*</span></label>
                      <div class="cc-input-group">
                        <span class="cc-input-icon"><i class="bi bi-calendar3"></i></span>
                        <input type="date" name="transaction_date" class="cc-input has-icon-left" id="txnDate" value="{{ old('transaction_date', date('Y-m-d')) }}" required>
                      </div>
                    </div>
                  </div>

                  <div class="col-sm-6">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="txnCategory">Category <span class="text-danger">*</span></label>
                      <select name="category_id" class="cc-select" id="txnCategory" required>
                        <option value="" disabled {{ old('category_id') ? '' : 'selected' }}>Select category</option>
                        
                        <!-- Expense categories group -->
                        <optgroup label="Expense Categories" id="expenseOptGroup">
                          @foreach($expenseCategories as $cat)
                            <option value="{{ $cat->id }}" data-type="expense" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                              {{ $cat->name }} {{ $cat->is_default ? '' : '(Custom)' }}
                            </option>
                          @endforeach
                        </optgroup>

                        <!-- Income categories group -->
                        <optgroup label="Income Categories" id="incomeOptGroup">
                          @foreach($incomeCategories as $cat)
                            <option value="{{ $cat->id }}" data-type="income" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                              {{ $cat->name }} {{ $cat->is_default ? '' : '(Custom)' }}
                            </option>
                          @endforeach
                        </optgroup>
                      </select>
                    </div>
                  </div>

                  <div class="col-sm-6">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="txnPaymentMethod">Payment Method</label>
                      <select name="payment_method" class="cc-select" id="txnPaymentMethod">
                        <option value="Cash" {{ old('payment_method') === 'Cash' ? 'selected' : '' }}>Cash</option>
                        <option value="JazzCash" {{ old('payment_method') === 'JazzCash' ? 'selected' : '' }}>JazzCash</option>
                        <option value="EasyPaisa" {{ old('payment_method') === 'EasyPaisa' ? 'selected' : '' }}>EasyPaisa</option>
                        <option value="Bank Transfer" {{ old('payment_method') === 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="Card" {{ old('payment_method') === 'Card' ? 'selected' : '' }}>Credit / Debit Card</option>
                        <option value="Other" {{ old('payment_method') === 'Other' ? 'selected' : '' }}>Other</option>
                      </select>
                    </div>
                  </div>

                  <!-- Recurring settings -->
                  <div class="col-12">
                    <div class="p-3 rounded" style="background:var(--cc-surface-tint);border:1px solid var(--cc-border-subtle);">
                      <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_recurring" value="1" id="isRecurringCheck" onchange="toggleRecurringOptions(this.checked)" {{ old('is_recurring') ? 'checked' : '' }}>
                        <label class="form-check-label font-bold" for="isRecurringCheck" style="font-size:var(--fs-sm);">
                          <i class="bi bi-arrow-repeat me-1 text-primary"></i> Recurring Transaction
                        </label>
                      </div>
                      <div id="recurringOptions" style="display:{{ old('is_recurring') ? 'block' : 'none' }};">
                        <div class="row g-2 pt-2">
                          <div class="col-md-4">
                            <label class="cc-label" for="recFreq">Frequency</label>
                            <select name="recurring_frequency" class="cc-select" id="recFreq">
                              <option value="monthly" {{ old('recurring_frequency') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                              <option value="weekly" {{ old('recurring_frequency') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                            </select>
                          </div>
                          <div class="col-md-4">
                            <label class="cc-label" for="recStart">Start Date</label>
                            <input type="date" name="recurring_start_date" class="cc-input" id="recStart" value="{{ old('recurring_start_date', date('Y-m-d')) }}">
                          </div>
                          <div class="col-md-4">
                            <label class="cc-label" for="recEnd">End Date (Optional)</label>
                            <input type="date" name="recurring_end_date" class="cc-input" id="recEnd" value="{{ old('recurring_end_date') }}">
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-12">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="txnNote">Note (Optional)</label>
                      <textarea name="note" class="cc-textarea" id="txnNote" placeholder="Add a brief reference or tags..." style="height:80px;" maxlength="1000">{{ old('note') }}</textarea>
                    </div>
                  </div>

                  <!-- Duplicate Detection Live Warning (Phase 9) -->
                  <div class="col-12" id="duplicateWarningBox" style="display:none;">
                    <div class="p-3 rounded d-flex align-items-start gap-2" style="background:rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.4);">
                      <i class="bi bi-exclamation-triangle-fill text-warning fs-5 flex-shrink-0 mt-1"></i>
                      <div class="flex-1">
                        <div class="fw-bold text-dark small">Possible Duplicate Transaction Detected</div>
                        <div class="text-secondary small" id="duplicateWarningMsg" style="font-size:0.8rem;"></div>
                        <div class="mt-2 d-flex gap-2">
                          <a href="{{ route('transactions') }}" class="btn-cc btn-cc-secondary btn-cc-xs">Review Existing</a>
                          <button type="button" class="btn-cc btn-cc-ghost btn-cc-xs text-secondary" onclick="dismissDuplicateWarning()">Continue Anyway</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
              <div class="cc-card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('transactions') }}" class="btn-cc btn-cc-secondary">Cancel</a>
                <button type="submit" id="submitBtn" class="btn-cc btn-cc-primary btn-cc-lg">
                  <i class="bi bi-check-lg"></i> Save Transaction
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Sidebar info -->
        <div class="col-lg-4">
          <div class="cc-card mb-3">
            <div class="cc-card-header"><h2 class="cc-card-title"><i class="bi bi-wallet2 me-2" style="color:var(--cc-primary);"></i>Current Balance</h2></div>
            <div class="cc-card-body text-center">
              <div class="cc-amount font-bold" style="font-size:var(--fs-3xl);color:var(--cc-primary);">Rs. {{ number_format($currentBalance, 2) }}</div>
              <div class="text-secondary" style="font-size:var(--fs-xs);margin-top:.25rem;">Live Net Balance</div>
            </div>
          </div>

          <div class="cc-card mb-3">
            <div class="cc-card-header"><h2 class="cc-card-title"><i class="bi bi-tags me-2" style="color:var(--cc-warning);"></i>Category Management</h2></div>
            <div class="cc-card-body">
              <p class="text-secondary mb-3" style="font-size:var(--fs-xs);">
                Can't find the category you're looking for? Create a custom personal category anytime.
              </p>
              <a href="{{ route('categories') }}" class="btn-cc btn-cc-secondary btn-cc-sm w-100 justify-content-center">
                <i class="bi bi-plus-lg"></i> Manage Categories
              </a>
            </div>
          </div>

          <div class="cc-card">
            <div class="cc-card-body">
              <div style="font-size:var(--fs-xs);font-weight:var(--fw-bold);color:var(--cc-primary);margin-bottom:.5rem;">💡 QUICK TIP</div>
              <p class="text-secondary mb-0" style="font-size:var(--fs-xs);">
                Tag recurring items like monthly allowances, hostel rent, or Spotify subscriptions to track predictable cash flows.
              </p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
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
function setType(type) {
  const btnExpense = document.getElementById('btnExpense');
  const btnIncome  = document.getElementById('btnIncome');
  const typeInput  = document.getElementById('txnType');
  const expGroup   = document.getElementById('expenseOptGroup');
  const incGroup   = document.getElementById('incomeOptGroup');
  const catSelect  = document.getElementById('txnCategory');

  typeInput.value = type;

  if (type === 'expense') {
    btnExpense.className = 'btn-cc btn-cc-danger flex-1';
    btnIncome.className  = 'btn-cc btn-cc-secondary flex-1';
    expGroup.style.display = '';
    incGroup.style.display = 'none';
  } else {
    btnExpense.className = 'btn-cc btn-cc-secondary flex-1';
    btnIncome.className  = 'btn-cc btn-cc-primary flex-1';
    expGroup.style.display = 'none';
    incGroup.style.display = '';
  }

  // Deselect option if currently selected option doesn't match type
  const selectedOpt = catSelect.options[catSelect.selectedIndex];
  if (selectedOpt && selectedOpt.dataset.type && selectedOpt.dataset.type !== type) {
    catSelect.value = '';
  }
}

function toggleRecurringOptions(checked) {
  const box = document.getElementById('recurringOptions');
  box.style.display = checked ? 'block' : 'none';
}

function handleTxnSubmit(form) {
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Saving...';
}

// AI Expense Categorization Assistant (Phase 7)
let currentAiSuggestion = null;
let aiDebounceTimer = null;
const aiCache = {};
let hasUserManuallyChangedCategory = false;

document.getElementById('txnCategory')?.addEventListener('change', function() {
  hasUserManuallyChangedCategory = true;
});

const descInput = document.getElementById('txnDescription');
if (descInput) {
  descInput.addEventListener('input', function(e) {
    const text = e.target.value.trim();
    clearTimeout(aiDebounceTimer);

    if (text.length < 2) {
      dismissAiSuggestion();
      return;
    }

    aiDebounceTimer = setTimeout(() => {
      fetchAiCategorySuggestion(text);
    }, 450);
  });
}

function fetchAiCategorySuggestion(description) {
  const currentType = document.getElementById('txnType')?.value || 'expense';
  const cacheKey = `${currentType}_${description.toLowerCase()}`;

  if (aiCache[cacheKey]) {
    renderAiSuggestion(aiCache[cacheKey]);
    return;
  }

  const indicator = document.getElementById('aiTypingIndicator');
  if (indicator) indicator.style.display = 'inline-block';

  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  fetch('/api/ai/categorize-expense', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': token,
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      description: description,
      type: currentType
    })
  })
  .then(res => res.json())
  .then(data => {
    if (indicator) indicator.style.display = 'none';
    if (data.success && data.suggestion) {
      aiCache[cacheKey] = data.suggestion;
      renderAiSuggestion(data.suggestion);
    } else {
      dismissAiSuggestion();
    }
  })
  .catch(err => {
    if (indicator) indicator.style.display = 'none';
    console.debug('AI Categorization unavailable:', err);
  });
}

function renderAiSuggestion(suggestion) {
  currentAiSuggestion = suggestion;
  const box = document.getElementById('aiSuggestionBox');
  const nameEl = document.getElementById('aiSuggestedCategoryName');
  const confEl = document.getElementById('aiSuggestedConfidence');

  if (box && nameEl) {
    nameEl.textContent = suggestion.category_name;
    if (confEl && suggestion.confidence) {
      confEl.textContent = `(${Math.round(suggestion.confidence * 100)}% match)`;
    }
    box.style.display = 'block';

    // Auto-select ONLY if user hasn't manually chosen a category yet and current select is empty
    const catSelect = document.getElementById('txnCategory');
    if (catSelect && !hasUserManuallyChangedCategory && !catSelect.value) {
      catSelect.value = suggestion.category_id;
    }
  }
}

function applyAiSuggestion() {
  if (!currentAiSuggestion) return;
  const catSelect = document.getElementById('txnCategory');
  if (catSelect) {
    catSelect.value = currentAiSuggestion.category_id;
    hasUserManuallyChangedCategory = true;
    if (typeof Toast !== 'undefined') {
      Toast.success('Category Applied', `Tagged as ${currentAiSuggestion.category_name}`);
    }
  }
  dismissAiSuggestion();
}

function dismissAiSuggestion() {
  currentAiSuggestion = null;
  const box = document.getElementById('aiSuggestionBox');
  if (box) box.style.display = 'none';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
  const currentType = document.getElementById('txnType').value || 'expense';
  setType(currentType);

  // ===== Phase 9: Duplicate Detection Live Check =====
  let dupDebounceTimer = null;
  let dupDismissed = false;

  function checkDuplicate() {
    if (dupDismissed) return;
    const amount = parseFloat(document.getElementById('txnAmount')?.value || 0);
    const date = document.getElementById('txnDate')?.value;
    const categoryId = document.getElementById('txnCategory')?.value;
    const description = document.getElementById('txnDescription')?.value || '';
    const type = document.getElementById('txnType')?.value || 'expense';

    if (!amount || amount <= 0) { hideDupWarning(); return; }

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    fetch('/api/transactions/check-duplicate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
      body: JSON.stringify({ amount, transaction_date: date, category_id: categoryId || null, description, type })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success && data.is_duplicate) {
        document.getElementById('duplicateWarningMsg').textContent = data.warning_message;
        document.getElementById('duplicateWarningBox').style.display = 'block';
      } else {
        hideDupWarning();
      }
    })
    .catch(() => hideDupWarning());
  }

  function hideDupWarning() {
    document.getElementById('duplicateWarningBox').style.display = 'none';
  }

  function scheduleDupCheck() {
    clearTimeout(dupDebounceTimer);
    dupDebounceTimer = setTimeout(checkDuplicate, 600);
  }

  ['txnAmount', 'txnDate', 'txnCategory', 'txnDescription'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', () => { dupDismissed = false; scheduleDupCheck(); });
    if (el && el.tagName === 'INPUT') el.addEventListener('input', () => { dupDismissed = false; scheduleDupCheck(); });
  });

  // Allow user to dismiss the warning and continue
  window.dismissDuplicateWarning = function() {
    dupDismissed = true;
    hideDupWarning();
  };
});
</script>
</body>
</html>
