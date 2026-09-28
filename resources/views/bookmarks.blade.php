<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Bookmarked Transactions — Campus Coin">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Bookmarked Transactions — Campus Coin</title>
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
            <span class="active">Bookmarks</span>
          </div>
          <h1 class="cc-page-title"><i class="bi bi-bookmark-star-fill text-warning me-2"></i>Bookmarked Transactions</h1>
        </div>
      </div>

      <div class="cc-topbar-right">
        <button class="cc-theme-toggle" aria-label="Toggle theme" onclick="ThemeManager.toggle()"><i class="bi bi-moon-fill"></i></button>
        <a href="{{ route('profile') }}" class="cc-avatar" style="font-size:var(--fs-xs);">{{ $user->initials }}</a>
      </div>
    </header>

    <div class="cc-content p-4">
      @include('partials.alerts')

      <!-- TOP HEADER & FILTERS -->
      <div class="card p-3 mb-4 border-0 shadow-sm rounded-3" style="background:var(--cc-card-bg, #fff);">
        <form action="{{ route('bookmarks') }}" method="GET" class="row g-2 align-items-center">
          <div class="col-12 col-md-6">
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" name="search" class="form-control border-start-0" placeholder="Search bookmarked transactions..." value="{{ $search ?? '' }}">
            </div>
          </div>
          <div class="col-6 col-md-3">
            <select name="type" class="form-select" onchange="this.form.submit()">
              <option value="">All Types (Income &amp; Expense)</option>
              <option value="expense" {{ ($type ?? '') === 'expense' ? 'selected' : '' }}>Expenses Only</option>
              <option value="income" {{ ($type ?? '') === 'income' ? 'selected' : '' }}>Income Only</option>
            </select>
          </div>
          <div class="col-6 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-warning flex-fill fw-semibold">Search</button>
            @if(!empty($search) || !empty($type))
              <a href="{{ route('bookmarks') }}" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-x-lg"></i></a>
            @endif
          </div>
        </form>
      </div>

      <!-- BOOKMARKS LIST -->
      <div class="card border-0 shadow-sm rounded-3 overflow-hidden" style="background:var(--cc-card-bg, #fff);">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="padding-left:1.25rem;">Transaction</th>
                <th>Category</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Notes</th>
                <th class="text-end" style="padding-right:1.25rem;">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($bookmarks as $tx)
                <tr id="bm-row-{{ $tx->id }}">
                  <td style="padding-left:1.25rem;">
                    <div class="d-flex align-items-center gap-2">
                      <button class="btn btn-sm btn-link text-warning p-0" onclick="toggleBookmark({{ $tx->id }}, this)" title="Remove Bookmark">
                        <i class="bi bi-star-fill fs-5"></i>
                      </button>
                      <div>
                        <div class="fw-bold text-dark">{{ $tx->description }}</div>
                        <div class="text-muted small" style="font-size:0.75rem;">{{ $tx->payment_method ?? 'Cash' }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1">
                      <i class="bi {{ $tx->category->icon ?? 'bi-tag' }} text-warning"></i>
                      {{ $tx->category->name ?? 'Uncategorized' }}
                    </span>
                  </td>
                  <td class="text-muted small">
                    {{ \Carbon\Carbon::parse($tx->transaction_date)->format('M d, Y') }}
                  </td>
                  <td>
                    <span class="fw-bold {{ $tx->type === 'income' ? 'text-success' : 'text-danger' }}">
                      {{ $tx->type === 'income' ? '+' : '-' }} Rs. {{ number_format($tx->amount, 2) }}
                    </span>
                  </td>
                  <td>
                    <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" onclick="openNoteModal({{ $tx->id }}, '{{ addslashes($tx->description) }}')">
                      <i class="bi bi-sticky{{ $tx->noteRecord ? '-fill text-primary' : '' }}"></i>
                      <span class="small">{{ $tx->noteRecord ? 'View Note' : 'Add Note' }}</span>
                    </button>
                  </td>
                  <td class="text-end" style="padding-right:1.25rem;">
                    <div class="d-inline-flex align-items-center gap-1">
                      <!-- Share Link Trigger -->
                      <button class="btn btn-sm btn-outline-primary" onclick="openShareModal({{ $tx->id }})" title="Share Transaction">
                        <i class="bi bi-share"></i>
                      </button>
                      <!-- Unbookmark -->
                      <form action="{{ route('transactions.unbookmark', $tx->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Remove Bookmark">
                          <i class="bi bi-bookmark-dash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-bookmark-star d-block mb-2 text-warning" style="font-size:2.5rem;"></i>
                    <h5 class="fw-bold text-dark">No bookmarked transactions</h5>
                    <p class="small text-secondary mb-3">Star important transactions to access them quickly anytime.</p>
                    <a href="{{ route('transactions') }}" class="btn btn-warning btn-sm fw-semibold">
                      <i class="bi bi-arrow-left-right me-1"></i> Browse Transactions
                    </a>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if ($bookmarks->hasPages())
          <div class="p-3 border-top d-flex justify-content-between align-items-center">
            <span class="small text-muted">Showing {{ $bookmarks->firstItem() }} to {{ $bookmarks->lastItem() }} of {{ $bookmarks->total() }} bookmarks</span>
            <div>{{ $bookmarks->links() }}</div>
          </div>
        @endif
      </div>
    </div>
  </main>
</div>

<!-- NOTE MODAL -->
<div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="noteModalLabel"><i class="bi bi-sticky-fill text-warning me-2"></i>Personal Transaction Note</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-secondary small mb-2" id="noteModalDesc"></p>
        <textarea id="noteInput" class="form-control" rows="4" placeholder="Add private notes about this transaction (e.g. shared with roommates, receipt details)..." maxlength="2000"></textarea>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-outline-danger btn-sm" id="deleteNoteBtn" onclick="deleteNote()"><i class="bi bi-trash"></i> Delete Note</button>
        <div>
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-warning btn-sm fw-semibold" onclick="saveNote()">Save Note</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- SHARE MODAL -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="shareModalLabel"><i class="bi bi-share-fill text-primary me-2"></i>Share Transaction</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-secondary small mb-3">Generate a secure, private-safe receipt link. This exposes only amount, category, and date — never your password, email, or private notes.</p>
        <div class="input-group mb-3">
          <input type="text" id="shareUrlInput" class="form-control" readonly placeholder="Generating share link...">
          <button class="btn btn-primary fw-semibold" onclick="copyShareUrl()"><i class="bi bi-clipboard me-1"></i> Copy</button>
        </div>
        <div id="shareStatusText" class="text-success small d-none"><i class="bi bi-check-circle-fill me-1"></i> Link copied to clipboard!</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- LOGOUT MODAL -->
<div class="cc-modal-backdrop" id="logoutModal" onclick="if(event.target===this)Modal.close('logoutModal')">
  <div class="cc-modal cc-modal-sm" role="dialog" aria-modal="true">
    <div class="cc-modal-header">
      <h3 class="cc-modal-title"><i class="bi bi-box-arrow-left text-danger me-2"></i> Confirm Logout</h3>
      <button class="cc-modal-close" onclick="Modal.close('logoutModal')">&times;</button>
    </div>
    <div class="cc-modal-body">
      <p class="text-secondary mb-0">Are you sure you want to sign out of Campus Coin?</p>
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
let currentTxId = null;

function toggleBookmark(txId, btn) {
  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  fetch(`/transactions/${txId}/bookmark`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      if (!data.bookmarked) {
        document.getElementById(`bm-row-${txId}`)?.remove();
      }
      if (typeof Toast !== 'undefined') Toast.success('Bookmarks', data.message);
    }
  });
}

function openNoteModal(txId, description) {
  currentTxId = txId;
  document.getElementById('noteModalDesc').innerText = `Transaction: ${description}`;
  document.getElementById('noteInput').value = '';
  
  fetch(`/transactions/${txId}/notes`)
    .then(res => res.json())
    .then(data => {
      if (data.success && data.note) {
        document.getElementById('noteInput').value = data.note;
      }
    });

  const modal = new bootstrap.Modal(document.getElementById('noteModal'));
  modal.show();
}

function saveNote() {
  const note = document.getElementById('noteInput').value;
  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  
  fetch(`/transactions/${currentTxId}/notes`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
    body: JSON.stringify({ note: note })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById('noteModal')).hide();
      if (typeof Toast !== 'undefined') Toast.success('Note Saved', data.message);
      setTimeout(() => location.reload(), 300);
    }
  });
}

function deleteNote() {
  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  fetch(`/transactions/${currentTxId}/notes`, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById('noteModal')).hide();
      if (typeof Toast !== 'undefined') Toast.info('Note Removed', 'Note has been deleted.');
      setTimeout(() => location.reload(), 300);
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
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      input.value = data.share_url;
    }
  });

  const modal = new bootstrap.Modal(document.getElementById('shareModal'));
  modal.show();
}

function copyShareUrl() {
  const input = document.getElementById('shareUrlInput');
  input.select();
  navigator.clipboard.writeText(input.value);
  document.getElementById('shareStatusText').classList.remove('d-none');
}

document.addEventListener('DOMContentLoaded', () => {
  if (typeof ThemeManager !== 'undefined') ThemeManager.init();
  if (typeof Toast !== 'undefined') Toast.init();
});
</script>
</body>
</html>
