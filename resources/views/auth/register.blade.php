<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Create your free Campus Coin account — start tracking smarter today.">
  <title>Register — Campus Coin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

  <div class="cc-auth-split">

    <!-- LEFT: Hero Panel -->
    <div class="cc-auth-hero">
      <a href="{{ route('home') }}" class="d-flex align-items-center text-white text-decoration-none mb-4">
        <img src="{{ asset('images/campus-coin-logo-light.png') }}" data-light-src="{{ asset('images/campus-coin-logo-light.png') }}" data-dark-src="{{ asset('images/campus-coin-logo-dark.png') }}" class="cc-brand-logo-auth" alt="Campus Coin">
      </a>

      <div class="my-auto py-5" style="max-width:440px;position:relative;z-index:1;">
        <span class="cc-badge mb-4" style="background:rgba(99,102,241,.18);color:#A5B4FC;border-color:rgba(139,92,246,.3);">
          <i class="bi bi-mortarboard-fill"></i> Free for Students
        </span>
        <h1 style="font-size:clamp(2rem,4vw,2.75rem);font-weight:var(--fw-extrabold);color:#fff;letter-spacing:-.04em;line-height:1.1;margin-bottom:1.25rem;">
          Take control of your <span class="gradient-text">campus finances</span> today.
        </h1>
        <p style="color:rgba(255,255,255,.65);font-size:var(--fs-base);line-height:1.65;margin-bottom:2rem;">
          Set budgets, track every rupee, get smart insights, and never run out of money before the next allowance.
        </p>

        <!-- Feature checklist -->
        <div class="d-flex flex-column gap-2">
          <div class="d-flex align-items-center gap-2" style="color:rgba(255,255,255,.85);font-size:var(--fs-sm);">
            <i class="bi bi-check-circle-fill" style="color:#06B6D4;"></i>
            Track income &amp; expenses in seconds
          </div>
          <div class="d-flex align-items-center gap-2" style="color:rgba(255,255,255,.85);font-size:var(--fs-sm);">
            <i class="bi bi-check-circle-fill" style="color:#06B6D4;"></i>
            Category-based budget limits with alerts
          </div>
          <div class="d-flex align-items-center gap-2" style="color:rgba(255,255,255,.85);font-size:var(--fs-sm);">
            <i class="bi bi-check-circle-fill" style="color:#06B6D4;"></i>
            Smart insights &amp; spending pattern analysis
          </div>
          <div class="d-flex align-items-center gap-2" style="color:rgba(255,255,255,.85);font-size:var(--fs-sm);">
            <i class="bi bi-check-circle-fill" style="color:#06B6D4;"></i>
            Monthly reports &amp; student allowances
          </div>
          <div class="d-flex align-items-center gap-2" style="color:rgba(255,255,255,.85);font-size:var(--fs-sm);">
            <i class="bi bi-check-circle-fill" style="color:#06B6D4;"></i>
            100% free. No credit card required.
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center" style="color:rgba(255,255,255,.4);font-size:var(--fs-xs);position:relative;z-index:1;">
        <span>&copy; {{ date('Y') }} Campus Coin</span>
        <span>Student Finance Platform</span>
      </div>
    </div>

    <!-- RIGHT: Register Form -->
    <div class="cc-auth-form-panel" style="width:540px;">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 style="font-size:var(--fs-2xl);font-weight:var(--fw-extrabold);letter-spacing:-.03em;margin-bottom:.25rem;">Create account</h2>
          <p class="text-secondary mb-0" style="font-size:var(--fs-sm);">Start your smart spending journey — it's free</p>
        </div>
        <button class="cc-theme-toggle" aria-label="Toggle theme"><i class="bi bi-moon-fill"></i></button>
      </div>

      @include('partials.alerts')

      <form id="registerForm" method="POST" action="{{ route('register.submit') }}">
        @csrf

        <div class="row g-3">
          <div class="col-6">
            <div class="cc-form-group mb-0">
              <label class="cc-label" for="regFirstName">First Name</label>
              <div class="cc-input-group">
                <input type="text" name="first_name" class="cc-input has-icon-left @error('name') is-invalid @enderror" id="regFirstName" placeholder="Hunzala" value="{{ old('first_name') }}" required>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="cc-form-group mb-0">
              <label class="cc-label" for="regLastName">Last Name</label>
              <input type="text" name="last_name" class="cc-input @error('name') is-invalid @enderror" id="regLastName" placeholder="Khan" value="{{ old('last_name') }}" required>
            </div>
          </div>
        </div>
        @error('name')
          <div class="cc-invalid-feedback mt-1">
            <i class="bi bi-exclamation-circle"></i> {{ $message }}
          </div>
        @enderror

        <div class="cc-form-group mt-3">
          <label class="cc-label" for="regEmail">Student Email</label>
          <div class="cc-input-group">
            <input type="email" name="email" class="cc-input has-icon-left @error('email') is-invalid @enderror" id="regEmail" placeholder="student@university.edu" value="{{ old('email') }}" required>
          </div>
          @error('email')
            <div class="cc-invalid-feedback">
              <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </div>
          @enderror
        </div>

        <div class="row g-3">
          <div class="col-sm-6">
            <div class="cc-form-group mb-0">
              <label class="cc-label" for="regAcademicYear">Academic Year</label>
              <select class="cc-select @error('academic_year') is-invalid @enderror" name="academic_year" id="regAcademicYear" required>
                <option value="" disabled {{ old('academic_year') ? '' : 'selected' }}>Select Year</option>
                <option value="Year 1" {{ old('academic_year') == 'Year 1' ? 'selected' : '' }}>Year 1 (Freshman)</option>
                <option value="Year 2" {{ old('academic_year') == 'Year 2' ? 'selected' : '' }}>Year 2 (Sophomore)</option>
                <option value="Year 3" {{ old('academic_year') == 'Year 3' ? 'selected' : '' }}>Year 3 (Junior)</option>
                <option value="Year 4" {{ old('academic_year') == 'Year 4' ? 'selected' : '' }}>Year 4 (Senior)</option>
                <option value="Postgraduate" {{ old('academic_year') == 'Postgraduate' ? 'selected' : '' }}>Postgraduate / Masters</option>
              </select>
              @error('academic_year')
                <div class="cc-invalid-feedback">
                  <i class="bi bi-exclamation-circle"></i> {{ $message }}
                </div>
              @enderror
            </div>
          </div>
          <div class="col-sm-6">
            <div class="cc-form-group mb-0">
              <label class="cc-label" for="regProgram">Program / Major</label>
              <select class="cc-select" name="program" id="regProgram">
                <option value="" disabled {{ old('program') ? '' : 'selected' }}>Select Major</option>
                <option value="Computer Science" {{ old('program') == 'Computer Science' ? 'selected' : '' }}>Computer Science</option>
                <option value="Software Engineering" {{ old('program') == 'Software Engineering' ? 'selected' : '' }}>Software Engineering</option>
                <option value="Business Administration" {{ old('program') == 'Business Administration' ? 'selected' : '' }}>Business Administration</option>
                <option value="Engineering" {{ old('program') == 'Engineering' ? 'selected' : '' }}>Engineering</option>
                <option value="Medicine" {{ old('program') == 'Medicine' ? 'selected' : '' }}>Medicine</option>
                <option value="Law" {{ old('program') == 'Law' ? 'selected' : '' }}>Law</option>
                <option value="Social Sciences" {{ old('program') == 'Social Sciences' ? 'selected' : '' }}>Social Sciences</option>
                <option value="Other" {{ old('program') == 'Other' ? 'selected' : '' }}>Other</option>
              </select>
            </div>
          </div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-sm-6">
            <div class="cc-form-group mb-0">
              <label class="cc-label" for="regAllowance">Monthly Allowance (Rs.)</label>
              <div class="cc-input-group">
                <span class="cc-input-prefix">Rs.</span>
                <input type="number" step="0.01" min="0" name="monthly_allowance" class="cc-input @error('monthly_allowance') is-invalid @enderror" id="regAllowance" placeholder="25000" value="{{ old('monthly_allowance', '25000') }}">
              </div>
              @error('monthly_allowance')
                <div class="cc-invalid-feedback">
                  <i class="bi bi-exclamation-circle"></i> {{ $message }}
                </div>
              @enderror
            </div>
          </div>
          <div class="col-sm-6">
            <div class="cc-form-group mb-0">
              <label class="cc-label" for="regSavingsGoal">Savings Goal (Rs.)</label>
              <div class="cc-input-group">
                <span class="cc-input-prefix">Rs.</span>
                <input type="number" step="0.01" min="0" name="savings_goal" class="cc-input @error('savings_goal') is-invalid @enderror" id="regSavingsGoal" placeholder="5000" value="{{ old('savings_goal', '5000') }}">
              </div>
              @error('savings_goal')
                <div class="cc-invalid-feedback">
                  <i class="bi bi-exclamation-circle"></i> {{ $message }}
                </div>
              @enderror
            </div>
          </div>
        </div>

        <div class="cc-form-group mt-3">
          <label class="cc-label" for="regUniversity">University / College</label>
          <div class="cc-input-group">
            <input type="text" name="university" class="cc-input has-icon-left" id="regUniversity" placeholder="National University of Sciences & Technology" value="{{ old('university') }}">
          </div>
        </div>

        <div class="cc-form-group">
          <label class="cc-label" for="regPassword">Password</label>
          <div class="cc-password-wrapper">
            <input type="password" name="password" class="cc-input @error('password') is-invalid @enderror" id="regPassword" placeholder="Min. 8 characters" required>
            <button type="button" class="cc-password-toggle" aria-label="Toggle password"><i class="bi bi-eye"></i></button>
          </div>
          <div class="cc-form-text">Use at least 8 characters with letters &amp; numbers.</div>
          @error('password')
            <div class="cc-invalid-feedback">
              <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </div>
          @enderror
        </div>

        <div class="cc-form-group">
          <label class="cc-label" for="regPasswordConfirm">Confirm Password</label>
          <div class="cc-password-wrapper">
            <input type="password" name="password_confirmation" class="cc-input" id="regPasswordConfirm" placeholder="••••••••" required>
            <button type="button" class="cc-password-toggle" aria-label="Toggle password"><i class="bi bi-eye"></i></button>
          </div>
        </div>

        <div class="form-check mb-4">
          <input class="form-check-input" type="checkbox" id="agreeTerms" required checked>
          <label class="form-check-label text-secondary" for="agreeTerms" style="font-size:var(--fs-xs);">
            I agree to the <a href="#" style="color:var(--cc-primary);">Terms of Service</a> and <a href="#" style="color:var(--cc-primary);">Privacy Policy</a>
          </label>
        </div>

        <button type="submit" class="btn-cc btn-cc-primary btn-cc-lg w-100 mb-3">
          Create My Account <i class="bi bi-arrow-right"></i>
        </button>

        <p class="text-center text-secondary mb-0" style="font-size:var(--fs-sm);">
          Already have an account? <a href="{{ route('login') }}" class="font-bold">Sign in</a>
        </p>
      </form>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
