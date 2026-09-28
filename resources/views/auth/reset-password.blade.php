<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Reset Password — Campus Coin. Set a new password for your account.">
  <title>Reset Password — Campus Coin</title>
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
        <div style="width:64px;height:64px;border-radius:50%;background:rgba(16,185,129,.15);border:2px solid rgba(52,211,153,.3);display:flex;align-items:center;justify-content:center;font-size:1.75rem;margin-bottom:1.5rem;color:#34d399;">
          <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h1 style="font-size:clamp(1.75rem,3.5vw,2.5rem);font-weight:var(--fw-extrabold);color:#fff;letter-spacing:-.04em;line-height:1.1;margin-bottom:1.25rem;">
          Secure your account with a new password.
        </h1>
        <p style="color:rgba(255,255,255,.6);font-size:var(--fs-base);line-height:1.65;margin-bottom:2rem;">
          Choose a strong password with at least 8 characters to keep your student financial records safe.
        </p>
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

      <div class="mb-4">
        <h2 style="font-size:var(--fs-2xl);font-weight:var(--fw-extrabold);letter-spacing:-.03em;margin-bottom:.5rem;">Set New Password</h2>
        <p class="text-secondary mb-0" style="font-size:var(--fs-sm);">Create a fresh, secure password for your account.</p>
      </div>

      @include('partials.alerts')

      <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="cc-form-group">
          <label class="cc-label" for="resetEmail">Student Email</label>
          <div class="cc-input-group">
            <span class="cc-input-icon"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="cc-input has-icon-left @error('email') is-invalid @enderror" id="resetEmail" placeholder="student@university.edu" value="{{ old('email', $email) }}" required>
          </div>
          @error('email')
            <div class="cc-invalid-feedback">
              <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </div>
          @enderror
        </div>

        <div class="cc-form-group">
          <label class="cc-label" for="newPassword">New Password</label>
          <div class="cc-password-wrapper">
            <input type="password" name="password" class="cc-input @error('password') is-invalid @enderror" id="newPassword" placeholder="Min. 8 characters" required autofocus>
            <button type="button" class="cc-password-toggle" aria-label="Toggle password"><i class="bi bi-eye"></i></button>
          </div>
          @error('password')
            <div class="cc-invalid-feedback">
              <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </div>
          @enderror
        </div>

        <div class="cc-form-group">
          <label class="cc-label" for="confirmPassword">Confirm New Password</label>
          <div class="cc-password-wrapper">
            <input type="password" name="password_confirmation" class="cc-input" id="confirmPassword" placeholder="••••••••" required>
            <button type="button" class="cc-password-toggle" aria-label="Toggle password"><i class="bi bi-eye"></i></button>
          </div>
        </div>

        <button type="submit" class="btn-cc btn-cc-primary btn-cc-lg w-100 mb-4">
          <i class="bi bi-shield-check"></i> Reset Password
        </button>

        <p class="text-center text-secondary mb-0" style="font-size:var(--fs-sm);">
          Remembered your password? <a href="{{ route('login') }}" class="font-bold">Sign In</a>
        </p>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
