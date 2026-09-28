<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Shared Transaction Receipt — Campus Coin">
  <title>Shared Transaction — Campus Coin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/design-tokens.css') }}">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--cc-bg, #f8fafc);
      padding: 1.5rem;
    }
    .shared-receipt-card {
      max-width: 480px;
      width: 100%;
      background: var(--cc-card-bg, #ffffff);
      border: 1px solid var(--cc-border, #e2e8f0);
      border-radius: 20px;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      overflow: hidden;
    }
  </style>
</head>
<body>

<div class="shared-receipt-card">
  <!-- RECEIPT TOP HEADER -->
  <div class="p-4 text-center text-white position-relative" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
    <img src="{{ asset('images/campus-coin-logo-light.png') }}" data-light-src="{{ asset('images/campus-coin-logo-light.png') }}" data-dark-src="{{ asset('images/campus-coin-logo-dark.png') }}" class="cc-brand-logo-auth mx-auto mb-2" alt="Campus Coin">
    <span class="badge bg-warning text-dark mt-1" style="font-size:0.7rem;">Shared Transaction</span>
  </div>

  <!-- AMOUNT & TYPE BADGE -->
  <div class="p-4 text-center border-bottom bg-light">
    <span class="badge {{ $sharedData['type'] === 'income' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} px-3 py-1 mb-2">
      <i class="bi {{ $sharedData['type'] === 'income' ? 'bi-arrow-down-left' : 'bi-arrow-up-right' }} me-1"></i>
      {{ ucfirst($sharedData['type']) }}
    </span>
    <div class="display-6 fw-bold {{ $sharedData['type'] === 'income' ? 'text-success' : 'text-danger' }}">
      {{ $sharedData['type'] === 'income' ? '+' : '-' }} {{ $sharedData['formatted_amount'] }}
    </div>
    <div class="text-secondary small mt-1">Transaction Details</div>
  </div>

  <!-- DETAILS LIST -->
  <div class="p-4">
    <div class="d-flex flex-column gap-3" style="font-size:0.9rem;">
      <div class="d-flex justify-content-between align-items-center">
        <span class="text-secondary">Description</span>
        <strong class="text-dark">{{ $sharedData['description'] }}</strong>
      </div>

      <div class="d-flex justify-content-between align-items-center">
        <span class="text-secondary">Category</span>
        <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1">
          <i class="bi {{ $sharedData['category_icon'] }} text-warning"></i>
          {{ $sharedData['category_name'] }}
        </span>
      </div>

      <div class="d-flex justify-content-between align-items-center">
        <span class="text-secondary">Transaction Date</span>
        <span class="fw-semibold text-dark">{{ $sharedData['transaction_date'] }}</span>
      </div>

      <div class="d-flex justify-content-between align-items-center">
        <span class="text-secondary">Payment Method</span>
        <span class="fw-semibold text-dark">{{ $sharedData['payment_method'] }}</span>
      </div>

      @if($sharedData['shared_at'])
        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
          <span class="text-muted small">Shared on</span>
          <span class="text-muted small">{{ $sharedData['shared_at'] }}</span>
        </div>
      @endif
    </div>
  </div>

  <!-- RECEIPT FOOTER -->
  <div class="p-3 bg-light text-center border-top">
    <div class="text-muted small" style="font-size:0.75rem;">
      <i class="bi bi-shield-check text-success me-1"></i> Verified &amp; Shared via Campus Coin Student Fintech
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
