<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Forgot Password — Campus Coin. Reset your student account password.">
  <title>Forgot Password — Campus Coin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

  <div class="cc-auth-split">

    <!-- LEFT: Hero -->
    <div class="cc-auth-hero">
      <a href="{{ route('home') }}" class="d-flex align-items-center text-white text-decoration-none mb-4">
        <img src="{{ asset('images/campus-coin-logo-light.png') }}" data-light-src="{{ asset('images/campus-coin-logo-light.png') }}" data-dark-src="{{ asset('images/campus-coin-logo-dark.png') }}" class="cc-brand-logo-auth" alt="Campus Coin">
      </a>

      <div class="my-auto py-5" style="max-width:400px;position:relative;z-index:1;">
        <div style="width:64px;height:64px;border-radius:50%;background:rgba(99,102,241,.15);border:2px solid rgba(139,92,246,.3);display:flex;align-items:center;justify-content:center;font-size:1.75rem;margin-bottom:1.5rem;color:#818CF8;box-shadow:0 0 20px rgba(99,102,241,0.25);">
          <i class="bi bi-key-fill"></i>
        </div>
        <h1 style="font-size:clamp(1.75rem,3.5vw,2.5rem);font-weight:var(--fw-extrabold);color:#fff;letter-spacing:-.04em;line-height:1.1;margin-bottom:1.25rem;">
          Locked out? <span class="gradient-text">We've got you.</span>
        </h1>
        <p style="color:rgba(255,255,255,.65);font-size:var(--fs-base);line-height:1.65;margin-bottom:2rem;">
          Enter your student email and we'll send you a secure link to reset your Campus Coin password in seconds.
        </p>
        <div class="d-flex flex-column gap-2" style="font-size:var(--fs-sm);">
          <div class="d-flex align-items-center gap-2" style="color:rgba(255,255,255,.75);">
            <i class="bi bi-envelope-check" style="color:#06B6D4;"></i> Reset link sent instantly
          </div>
          <div class="d-flex align-items-center gap-2" style="color:rgba(255,255,255,.75);">
            <i class="bi bi-shield-lock" style="color:#06B6D4;"></i> Secure, time-limited link (60 mins)
          </div>
          <div class="d-flex align-items-center gap-2" style="color:rgba(255,255,255,.75);">
            <i class="bi bi-arrow-counterclockwise" style="color:#06B6D4;"></i> Back to normal in under 2 minutes
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center" style="color:rgba(255,255,255,.4);font-size:var(--fs-xs);position:relative;z-index:1;">
        <span>&copy; {{ date('Y') }} Campus Coin</span>
        <span>Student Finance Platform</span>
      </div>
    </div>

    <!-- RIGHT: Form -->
    <div class="cc-auth-form-panel">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('login') }}" class="btn-cc btn-cc-ghost btn-cc-sm" style="color:var(--cc-text-muted);">
          <i class="bi bi-arrow-left"></i> Back to Sign In
        </a>
        <button class="cc-theme-toggle" aria-label="Toggle theme"><i class="bi bi-moon-fill"></i></button>
      </div>

      @if (session('status'))
        <!-- Step 2: Success State -->
        <div id="step2" style="text-align:center;">
          <div style="width:72px;height:72px;border-radius:50%;background:var(--cc-income-bg);color:var(--cc-income);display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 1.5rem;border:2px solid rgba(16,185,129,.25);">
            <i class="bi bi-envelope-check-fill"></i>
          </div>
          <h2 style="font-size:var(--fs-2xl);font-weight:var(--fw-extrabold);letter-spacing:-.03em;margin-bottom:.75rem;">Check your inbox!</h2>
          <p class="text-secondary mb-4" style="font-size:var(--fs-base);">
            We've sent a password reset link to <strong>{{ session('sent_email') ?? 'your email' }}</strong>.
            The link expires in <strong>60 minutes</strong>.
          </p>
          <div class="cc-alert cc-alert-info mb-4">
            <i class="bi bi-info-circle cc-alert-icon"></i>
            <span style="font-size:var(--fs-xs);">
              {{ session('status') }}
            </span>
          </div>
          <a href="{{ route('password.request') }}" class="btn-cc btn-cc-secondary w-100 mb-3">
            <i class="bi bi-arrow-clockwise"></i> Try Another Email
          </a>
          <a href="{{ route('login') }}" class="btn-cc btn-cc-primary w-100">
            <i class="bi bi-arrow-left"></i> Back to Sign In
          </a>
        </div>
      @else
        <!-- Step 1: Enter Email Form -->
        <div id="step1">
          <div class="mb-4">
            <h2 style="font-size:var(--fs-2xl);font-weight:var(--fw-extrabold);letter-spacing:-.03em;margin-bottom:.5rem;">Forgot Password?</h2>
            <p class="text-secondary mb-0" style="font-size:var(--fs-sm);">No worries — enter your student email and we'll send a reset link.</p>
          </div>

          @include('partials.alerts')

          <form id="forgotForm" method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="cc-form-group">
              <label class="cc-label" for="forgotEmail">Student Email</label>
              <div class="cc-input-group">
                <span class="cc-input-icon"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="cc-input has-icon-left @error('email') is-invalid @enderror" id="forgotEmail" placeholder="student@university.edu" value="{{ old('email') }}" required autofocus>
              </div>
              @error('email')
                <div class="cc-invalid-feedback">
                  <i class="bi bi-exclamation-circle"></i> {{ $message }}
                </div>
              @enderror
              <div class="cc-form-text">We'll send a password reset link to this email address.</div>
            </div>

            <button type="submit" class="btn-cc btn-cc-primary btn-cc-lg w-100 mb-4">
              <i class="bi bi-send"></i> Send Reset Link
            </button>

            <p class="text-center text-secondary mb-0" style="font-size:var(--fs-sm);">
              Remembered it? <a href="{{ route('login') }}" class="font-bold">Sign In</a>
            </p>
          </form>
        </div>
      @endif

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
