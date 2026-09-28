<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Profile — Campus Coin. Manage your student profile and account information.">
  <title>Profile — Campus Coin</title>
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
          <div class="cc-breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span class="sep"><i class="bi bi-chevron-right"></i></span><span class="current">Profile</span></div>
          <div class="cc-topbar-title">My Profile</div>
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
          <h1 class="cc-page-title">My Profile</h1>
          <p class="cc-page-subtitle">Manage your personal information, academic details, and allowance baselines.</p>
        </div>
      </div>

      <div class="row g-4">
        <!-- Profile Card + Stats -->
        <div class="col-lg-4">
          <!-- Profile Hero Card -->
          <div class="cc-card mb-4 text-center" style="padding:2rem 1.5rem;">
            <!-- Avatar large -->
            <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--cc-primary),var(--cc-violet));color:#fff;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:var(--fw-bold);margin:0 auto 1rem;box-shadow:var(--cc-shadow-brand);">
              {{ $user->initials }}
            </div>
            <h2 class="h4 font-bold mb-1" style="color:var(--cc-text-main);letter-spacing:-.02em;">{{ $user->name }}</h2>
            <div style="color:var(--cc-text-secondary);font-size:var(--fs-sm);margin-bottom:.75rem;">{{ $user->program ?? 'Student' }} · {{ $user->academic_year ?? 'Year 1' }}</div>
            <span class="cc-badge cc-badge-primary mb-3"><i class="bi bi-shield-check"></i> Student Pro</span>
            <div class="cc-divider"></div>
            <div class="d-flex justify-content-center gap-4" style="font-size:var(--fs-xs);">
              <div class="text-center">
                <div class="font-bold" style="font-size:var(--fs-lg);color:var(--cc-text-main);">{{ $user->transactions()->count() }}</div>
                <div style="color:var(--cc-text-muted);">Transactions</div>
              </div>
              <div style="width:1px;background:var(--cc-border-subtle);"></div>
              <div class="text-center">
                <div class="font-bold" style="font-size:var(--fs-lg);color:var(--cc-text-main);">{{ $user->budgets()->count() }}</div>
                <div style="color:var(--cc-text-muted);">Budgets</div>
              </div>
              <div style="width:1px;background:var(--cc-border-subtle);"></div>
              <div class="text-center">
                <div class="font-bold" style="font-size:var(--fs-lg);color:var(--cc-text-main);">{{ $user->aiMonthlyInsights()->count() }}</div>
                <div style="color:var(--cc-text-muted);">Insights</div>
              </div>
            </div>
          </div>

          <!-- Financial Summary Card Loaded from DB -->
          <div class="cc-card">
            <div class="cc-card-header"><h2 class="cc-card-title">Account Baseline</h2></div>
            <div class="cc-card-body d-flex flex-column gap-3">
              <div class="d-flex justify-content-between align-items-center">
                <span style="color:var(--cc-text-secondary);font-size:var(--fs-sm);">Monthly Allowance</span>
                <span class="cc-amount font-bold" style="color:var(--cc-income);">Rs. {{ number_format($user->monthly_allowance, 2) }}</span>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <span style="color:var(--cc-text-secondary);font-size:var(--fs-sm);">Savings Goal</span>
                <span class="cc-amount font-bold" style="color:var(--cc-primary);">Rs. {{ number_format($user->savings_goal, 2) }}</span>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <span style="color:var(--cc-text-secondary);font-size:var(--fs-sm);">Academic Year</span>
                <span class="cc-badge cc-badge-primary">{{ $user->academic_year ?? 'Year 1' }}</span>
              </div>
              <div class="cc-divider" style="margin:.25rem 0;"></div>
              <div class="d-flex justify-content-between align-items-center">
                <span style="color:var(--cc-text-secondary);font-size:var(--fs-sm);">Member Since</span>
                <span class="font-bold" style="font-size:var(--fs-xs);color:var(--cc-text-main);">{{ $user->created_at ? $user->created_at->format('M d, Y') : 'Recent' }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Edit Form -->
        <div class="col-lg-8">
          <form method="POST" action="{{ route('profile.update') }}">
            @csrf

            <!-- Personal Information -->
            <div class="cc-card mb-4">
              <div class="cc-card-header"><h2 class="cc-card-title"><i class="bi bi-person me-2" style="color:var(--cc-primary);"></i>Personal Information</h2></div>
              <div class="cc-card-body">
                <div class="row g-3">
                  <div class="col-sm-12">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="profileName">Full Name</label>
                      <input type="text" name="name" class="cc-input @error('name') is-invalid @enderror" id="profileName" value="{{ old('name', $user->name) }}" required>
                      @error('name')
                        <div class="cc-invalid-feedback"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="profileEmail">Student Email</label>
                      <div class="cc-input-group">
                        <span class="cc-input-icon"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="cc-input has-icon-left @error('email') is-invalid @enderror" id="profileEmail" value="{{ old('email', $user->email) }}" required>
                      </div>
                      @error('email')
                        <div class="cc-invalid-feedback"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="profilePhone">Phone Number</label>
                      <div class="cc-input-group">
                        <span class="cc-input-icon"><i class="bi bi-phone"></i></span>
                        <input type="tel" name="phone" class="cc-input has-icon-left" id="profilePhone" value="{{ old('phone', $user->phone) }}" placeholder="+92 300 1234567">
                      </div>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="profileAllowance">Monthly Allowance (Rs.)</label>
                      <div class="cc-input-group">
                        <span class="cc-input-prefix">Rs.</span>
                        <input type="number" step="0.01" min="0" name="monthly_allowance" class="cc-input @error('monthly_allowance') is-invalid @enderror" id="profileAllowance" value="{{ old('monthly_allowance', $user->monthly_allowance) }}">
                      </div>
                      @error('monthly_allowance')
                        <div class="cc-invalid-feedback"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="profileSavingsGoal">Monthly Savings Goal (Rs.)</label>
                      <div class="cc-input-group">
                        <span class="cc-input-prefix">Rs.</span>
                        <input type="number" step="0.01" min="0" name="savings_goal" class="cc-input @error('savings_goal') is-invalid @enderror" id="profileSavingsGoal" value="{{ old('savings_goal', $user->savings_goal) }}">
                      </div>
                      @error('savings_goal')
                        <div class="cc-invalid-feedback"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="profileDOB">Date of Birth</label>
                      <input type="date" name="dob" class="cc-input" id="profileDOB" value="{{ old('dob', $user->dob ? $user->dob->format('Y-m-d') : '') }}">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Academic Details -->
            <div class="cc-card mb-4">
              <div class="cc-card-header"><h2 class="cc-card-title"><i class="bi bi-building me-2" style="color:var(--cc-primary);"></i>Academic Details</h2></div>
              <div class="cc-card-body">
                <div class="row g-3">
                  <div class="col-12">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="profileUniversity">University / College</label>
                      <div class="cc-input-group">
                        <span class="cc-input-icon"><i class="bi bi-building"></i></span>
                        <input type="text" name="university" class="cc-input has-icon-left" id="profileUniversity" value="{{ old('university', $user->university ?? 'National University of Sciences & Technology (NUST)') }}">
                      </div>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="profileProgram">Program / Major</label>
                      <select class="cc-select" name="program" id="profileProgram">
                        <option value="Computer Science" {{ old('program', $user->program) == 'Computer Science' ? 'selected' : '' }}>Computer Science</option>
                        <option value="Software Engineering" {{ old('program', $user->program) == 'Software Engineering' ? 'selected' : '' }}>Software Engineering</option>
                        <option value="Business Administration" {{ old('program', $user->program) == 'Business Administration' ? 'selected' : '' }}>Business Administration</option>
                        <option value="Engineering" {{ old('program', $user->program) == 'Engineering' ? 'selected' : '' }}>Engineering</option>
                        <option value="Medicine" {{ old('program', $user->program) == 'Medicine' ? 'selected' : '' }}>Medicine</option>
                        <option value="Law" {{ old('program', $user->program) == 'Law' ? 'selected' : '' }}>Law</option>
                        <option value="Social Sciences" {{ old('program', $user->program) == 'Social Sciences' ? 'selected' : '' }}>Social Sciences</option>
                        <option value="Other" {{ old('program', $user->program) == 'Other' ? 'selected' : '' }}>Other</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="profileYear">Academic Year</label>
                      <select class="cc-select @error('academic_year') is-invalid @enderror" name="academic_year" id="profileYear">
                        <option value="Year 1" {{ old('academic_year', $user->academic_year) == 'Year 1' ? 'selected' : '' }}>Year 1</option>
                        <option value="Year 2" {{ old('academic_year', $user->academic_year) == 'Year 2' ? 'selected' : '' }}>Year 2</option>
                        <option value="Year 3" {{ old('academic_year', $user->academic_year) == 'Year 3' ? 'selected' : '' }}>Year 3</option>
                        <option value="Year 4" {{ old('academic_year', $user->academic_year) == 'Year 4' ? 'selected' : '' }}>Year 4</option>
                        <option value="Postgraduate" {{ old('academic_year', $user->academic_year) == 'Postgraduate' ? 'selected' : '' }}>Postgraduate</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="profileStudentId">Student ID</label>
                      <input type="text" name="student_id" class="cc-input" id="profileStudentId" value="{{ old('student_id', $user->student_id ?? 'NUST-2021-CS-0847') }}">
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="profileCampus">Campus / City</label>
                      <input type="text" name="campus" class="cc-input" id="profileCampus" value="{{ old('campus', $user->campus ?? 'H-12, Islamabad') }}">
                    </div>
                  </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                  <button type="submit" class="btn-cc btn-cc-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
                </div>
              </div>
            </div>
          </form>

          <!-- Danger Zone -->
          <div class="cc-card" style="border-color:rgba(239,68,68,.3);">
            <div class="cc-card-header" style="border-color:rgba(239,68,68,.2);">
              <h2 class="cc-card-title" style="color:var(--cc-expense);"><i class="bi bi-exclamation-triangle me-2"></i>Danger Zone</h2>
            </div>
            <div class="cc-card-body">
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                  <div class="font-bold" style="font-size:var(--fs-sm);margin-bottom:.25rem;">Delete Account</div>
                  <p class="text-secondary mb-0" style="font-size:var(--fs-xs);">Permanently delete your account and all associated student records. This action cannot be undone.</p>
                </div>
                <button class="btn-cc btn-cc-danger btn-cc-sm" onclick="Toast.error('Confirmation Required','Please contact support to request account deletion.')">
                  <i class="bi bi-trash3"></i> Delete Account
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<div class="cc-modal-overlay" id="logoutModal">
  <div class="cc-modal" style="max-width:380px;">
    <div class="cc-modal-header"><h3 class="cc-card-title">Confirm Logout</h3><button class="btn-cc btn-cc-ghost btn-cc-sm" data-modal-close><i class="bi bi-x-lg"></i></button></div>
    <div class="cc-card-body text-center"><p class="text-secondary mb-0" style="font-size:var(--fs-sm);">End your Campus Coin session?</p></div>
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
</body>
</html>
