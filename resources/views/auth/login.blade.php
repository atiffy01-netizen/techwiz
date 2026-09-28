<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Sign in to Campus Coin — your student finance dashboard.">
  <title>Login — Campus Coin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

  <div class="cc-auth-split">

    <!-- LEFT: Hero Panel -->
    <div class="cc-auth-hero">
      <a href="{{ route('home') }}" class="d-flex align-items-center text-white text-decoration-none mb-4">
        <img src="{{ asset('images/campus-coin-logo-light.png') }}" data-light-src="{{ asset('images/campus-coin-logo-dark.png') }}" data-dark-src="{{ asset('images/campus-coin-logo-dark.png') }}" class="cc-brand-logo-auth" alt="Campus Coin">
      </a>

      <div class="my-auto py-5" style="max-width:440px;position:relative;z-index:1;">
        <span class="cc-badge mb-4" style="background:rgba(99,102,241,.18);color:#A5B4FC;border-color:rgba(139,92,246,.3);">
          <i class="bi bi-stars"></i> Intelligent Student Finance
        </span>
        <h1 style="font-size:clamp(2rem,4vw,2.75rem);font-weight:var(--fw-extrabold);color:#fff;letter-spacing:-.04em;line-height:1.1;margin-bottom:1.25rem;">
          Build better money habits <span class="gradient-text">from day one.</span>
        </h1>
        <p style="color:rgba(255,255,255,.65);font-size:var(--fs-base);line-height:1.65;margin-bottom:2rem;">
          Join thousands of university students mastering allowance management, budgeting semester bills, and hitting savings milestones.
        </p>

        <!-- Mini stat card -->
        <div class="p-4 mb-4" style="background:rgba(255,255,255,.06);backdrop-filter:blur(16px);border-radius:var(--radius-lg);border:1px solid rgba(255,255,255,.12);box-shadow:0 12px 30px rgba(0,0,0,0.3);">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span style="color:rgba(255,255,255,.55);font-size:var(--fs-xs);text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Student Monthly Surplus</span>
            <span style="font-size:var(--fs-xs);font-weight:var(--fw-bold);color:#10B981;"><i class="bi bi-arrow-up-right"></i> +45.3%</span>
          </div>
          <div class="cc-amount" style="font-size:var(--fs-2xl);font-weight:var(--fw-extrabold);color:#fff;letter-spacing:-.02em;">Rs. 13,580 Saved</div>
          <div class="cc-progress-bar mt-3" style="height:6px;background:rgba(255,255,255,.1);">
            <div class="cc-progress-fill" style="width:73%;background:linear-gradient(90deg,#6366F1,#06B6D4);"></div>
          </div>
        </div>

        <!-- Testimonial -->
        <div class="d-flex align-items-center gap-3">
          <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:var(--fs-xs);box-shadow:0 4px 12px rgba(99,102,241,0.4);">AK</div>
          <div>
            <div style="color:#fff;font-size:var(--fs-xs);font-weight:var(--fw-semibold);">Ayesha Khan</div>
            <div style="color:rgba(255,255,255,.5);font-size:var(--fs-2xs);">CS Student, NUST · Saved Rs. 8,200 this semester</div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center" style="color:rgba(255,255,255,.4);font-size:var(--fs-xs);position:relative;z-index:1;">
        <span>&copy; {{ date('Y') }} Campus Coin</span>
        <span>Student Finance Platform</span>
      </div>
    </div>

    <!-- RIGHT: Login Form -->
    <div class="cc-auth-form-panel">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 style="font-size:var(--fs-2xl);font-weight:var(--fw-extrabold);letter-spacing:-.03em;margin-bottom:.25rem;">Welcome back</h2>
          <p class="text-secondary mb-0" style="font-size:var(--fs-sm);">Sign in to your student dashboard</p>
        </div>
        <button class="cc-theme-toggle" aria-label="Toggle theme"><i class="bi bi-moon-fill"></i></button>
      </div>

      @include('partials.alerts')

      <form id="loginForm" method="POST" action="{{ route('login.submit') }}">
        @csrf

        <div class="cc-form-group">
          <label class="cc-label" for="loginEmail">Student Email</label>
          <div class="cc-input-group">
            <input type="email" name="email" class="cc-input has-icon-left @error('email') is-invalid @enderror" id="loginEmail" placeholder="student@university.edu" value="{{ old('email') }}" required autofocus>
          </div>
          @error('email')
            <div class="cc-invalid-feedback">
              <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </div>
          @enderror
        </div>

        <div class="cc-form-group">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="cc-label mb-0" for="loginPassword">Password</label>
            <a href="{{ route('password.request') }}" style="font-size:var(--fs-xs);font-weight:var(--fw-medium);">Forgot password?</a>
          </div>
          <div class="cc-password-wrapper">
            <input type="password" name="password" class="cc-input @error('password') is-invalid @enderror" id="loginPassword" placeholder="••••••••" required>
            <button type="button" class="cc-password-toggle" aria-label="Toggle password"><i class="bi bi-eye"></i></button>
          </div>
          @error('password')
            <div class="cc-invalid-feedback">
              <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </div>
          @enderror
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="rememberMe" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label text-secondary" for="rememberMe" style="font-size:var(--fs-xs);">
              Remember this device
            </label>
          </div>
        </div>

        <button type="submit" class="btn-cc btn-cc-primary btn-cc-lg w-100 mb-3">
          <span>Sign In</span>
          <i class="bi bi-arrow-right ms-1"></i>
        </button>     

        <p class="text-center text-secondary mb-0" style="font-size:var(--fs-sm);">
          New to Campus Coin? <a href="{{ route('register') }}" class="font-bold text-primary">Create an account</a>
        </p>
      </form>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
