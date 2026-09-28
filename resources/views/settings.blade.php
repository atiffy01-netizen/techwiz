<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Settings — Campus Coin. Customize your app experience, notifications, and preferences.">
  <title>Settings — Campus Coin</title>
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
          <div class="cc-breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span class="sep"><i class="bi bi-chevron-right"></i></span><span class="current">Settings</span></div>
          <div class="cc-topbar-title">Settings</div>
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
          <h1 class="cc-page-title">Settings</h1>
          <p class="cc-page-subtitle">Customize your Campus Coin experience, security, and preferences.</p>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-lg-8">

          <!-- Appearance -->
          <div class="cc-card mb-4">
            <div class="cc-card-header">
              <h2 class="cc-card-title"><i class="bi bi-palette me-2" style="color:var(--cc-primary);"></i>Appearance</h2>
            </div>
            <div class="cc-card-body d-flex flex-column gap-0">

              <!-- Theme -->
              <div class="d-flex justify-content-between align-items-center py-3" style="border-bottom:1px solid var(--cc-border-subtle);">
                <div>
                  <div class="font-bold" style="font-size:var(--fs-sm);">Color Theme</div>
                  <div class="text-secondary" style="font-size:var(--fs-xs);">Toggle between light and dark mode.</div>
                </div>
                <div class="d-flex gap-2">
                  <button class="btn-cc btn-cc-secondary btn-cc-sm" id="btnLightTheme" onclick="setTheme('light')">
                    <i class="bi bi-sun-fill"></i> Light
                  </button>
                  <button class="btn-cc btn-cc-primary btn-cc-sm" id="btnDarkTheme" onclick="setTheme('dark')">
                    <i class="bi bi-moon-fill"></i> Dark
                  </button>
                </div>
              </div>

              <!-- Font Size -->
              <div class="d-flex justify-content-between align-items-center py-3" style="border-bottom:1px solid var(--cc-border-subtle);">
                <div>
                  <div class="font-bold" style="font-size:var(--fs-sm);">Font Size</div>
                  <div class="text-secondary" style="font-size:var(--fs-xs);">Adjust the text size for readability.</div>
                </div>
                <div class="d-flex gap-2">
                  <label class="d-flex align-items-center gap-1" style="font-size:var(--fs-xs);cursor:pointer;">
                    <input type="radio" name="font-size" value="small" onchange="FontSizeManager.apply('small')"> Small
                  </label>
                  <label class="d-flex align-items-center gap-1" style="font-size:var(--fs-xs);cursor:pointer;">
                    <input type="radio" name="font-size" value="medium" onchange="FontSizeManager.apply('medium')"> Medium
                  </label>
                  <label class="d-flex align-items-center gap-1" style="font-size:var(--fs-xs);cursor:pointer;">
                    <input type="radio" name="font-size" value="large" onchange="FontSizeManager.apply('large')"> Large
                  </label>
                </div>
              </div>

              <!-- Language -->
              <div class="d-flex justify-content-between align-items-center py-3">
                <div>
                  <div class="font-bold" style="font-size:var(--fs-sm);">Language &amp; Currency</div>
                  <div class="text-secondary" style="font-size:var(--fs-xs);">Display language and default currency.</div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                  <select class="cc-select" style="width:auto;padding:.45rem .875rem;font-size:var(--fs-xs);" onchange="Toast.info('Language','Language updated.')">
                    <option>English (EN)</option>
                    <option>Urdu (UR)</option>
                  </select>
                  <select class="cc-select" style="width:auto;padding:.45rem .875rem;font-size:var(--fs-xs);" onchange="Toast.info('Currency','Currency updated.')">
                    <option selected>PKR (Rs.)</option>
                    <option>USD ($)</option>
                    <option>EUR (€)</option>
                  </select>
                </div>
              </div>

            </div>
          </div>

          <!-- Security & Change Password -->
          <div class="cc-card mb-4">
            <div class="cc-card-header">
              <h2 class="cc-card-title"><i class="bi bi-shield-check me-2" style="color:var(--cc-primary);"></i>Security &amp; Password</h2>
            </div>
            <div class="cc-card-body">
              <form method="POST" action="{{ route('settings.password.update') }}">
                @csrf
                <div class="row g-3">
                  <div class="col-12">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="currentPassword">Current Password</label>
                      <div class="cc-password-wrapper">
                        <input type="password" name="current_password" class="cc-input @error('current_password') is-invalid @enderror" id="currentPassword" placeholder="••••••••" required>
                        <button type="button" class="cc-password-toggle"><i class="bi bi-eye"></i></button>
                      </div>
                      @error('current_password')
                        <div class="cc-invalid-feedback"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="newPassword">New Password</label>
                      <div class="cc-password-wrapper">
                        <input type="password" name="password" class="cc-input @error('password') is-invalid @enderror" id="newPassword" placeholder="Min. 8 characters" required>
                        <button type="button" class="cc-password-toggle"><i class="bi bi-eye"></i></button>
                      </div>
                      @error('password')
                        <div class="cc-invalid-feedback"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="cc-form-group mb-0">
                      <label class="cc-label" for="confirmPassword">Confirm New Password</label>
                      <div class="cc-password-wrapper">
                        <input type="password" name="password_confirmation" class="cc-input" id="confirmPassword" placeholder="••••••••" required>
                        <button type="button" class="cc-password-toggle"><i class="bi bi-eye"></i></button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                  <button type="submit" class="btn-cc btn-cc-primary"><i class="bi bi-key"></i> Update Password</button>
                </div>
              </form>
            </div>
          </div>

          <!-- Notifications -->
          <div class="cc-card mb-4">
            <div class="cc-card-header">
              <h2 class="cc-card-title"><i class="bi bi-bell me-2" style="color:var(--cc-warning);"></i>Notifications</h2>
            </div>
            <div class="cc-card-body d-flex flex-column gap-0">

              <div class="d-flex justify-content-between align-items-center py-3" style="border-bottom:1px solid var(--cc-border-subtle);">
                <div>
                  <div class="font-bold" style="font-size:var(--fs-sm);">Budget Limit Alerts</div>
                  <div class="text-secondary" style="font-size:var(--fs-xs);">Get notified when you approach a budget limit.</div>
                </div>
                <label class="cc-switch">
                  <input type="checkbox" checked onchange="Toast.info('Notification','Budget alert preference updated.')">
                  <span class="cc-switch-slider"></span>
                </label>
              </div>

              <div class="d-flex justify-content-between align-items-center py-3" style="border-bottom:1px solid var(--cc-border-subtle);">
                <div>
                  <div class="font-bold" style="font-size:var(--fs-sm);">Weekly Summary Report</div>
                  <div class="text-secondary" style="font-size:var(--fs-xs);">Receive a weekly digest of your spending activity.</div>
                </div>
                <label class="cc-switch">
                  <input type="checkbox" checked onchange="Toast.info('Notification','Weekly summary preference updated.')">
                  <span class="cc-switch-slider"></span>
                </label>
              </div>

              <div class="d-flex justify-content-between align-items-center py-3" style="border-bottom:1px solid var(--cc-border-subtle);">
                <div>
                  <div class="font-bold" style="font-size:var(--fs-sm);">Allowance Reminder</div>
                  <div class="text-secondary" style="font-size:var(--fs-xs);">Remind me to log my monthly allowance receipt.</div>
                </div>
                <label class="cc-switch">
                  <input type="checkbox" checked onchange="Toast.info('Notification','Allowance reminder preference updated.')">
                  <span class="cc-switch-slider"></span>
                </label>
              </div>

            </div>
          </div>

          <!-- Data & Privacy -->
          <div class="cc-card">
            <div class="cc-card-header">
              <h2 class="cc-card-title"><i class="bi bi-database me-2" style="color:var(--cc-accent-indigo);"></i>Data &amp; Privacy</h2>
            </div>
            <div class="cc-card-body d-flex flex-column gap-3">
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                  <div class="font-bold" style="font-size:var(--fs-sm);">Export All Data</div>
                  <div class="text-secondary" style="font-size:var(--fs-xs);">Download your complete transaction history as a CSV file.</div>
                </div>
                <button class="btn-cc btn-cc-secondary btn-cc-sm" onclick="Toast.success('Exporting','Your data is being prepared for download.')">
                  <i class="bi bi-download"></i> Export CSV
                </button>
              </div>
            </div>
          </div>

        </div>

        <!-- Settings Sidebar Info -->
        <div class="col-lg-4">
          <div class="cc-card mb-4">
            <div class="cc-card-header"><h2 class="cc-card-title">Account Info</h2></div>
            <div class="cc-card-body d-flex flex-column gap-3">
              <div style="font-size:var(--fs-xs);">
                <div class="text-secondary mb-1">Account Name</div>
                <div class="font-bold">{{ $user->name }}</div>
              </div>
              <div style="font-size:var(--fs-xs);">
                <div class="text-secondary mb-1">Account Email</div>
                <div class="font-bold">{{ $user->email }}</div>
              </div>
              <div style="font-size:var(--fs-xs);">
                <div class="text-secondary mb-1">Member Since</div>
                <div class="font-bold">{{ $user->created_at ? $user->created_at->format('F d, Y') : 'Recent' }}</div>
              </div>
              <div style="font-size:var(--fs-xs);">
                <div class="text-secondary mb-1">Account Type</div>
                <span class="cc-badge cc-badge-primary"><i class="bi bi-shield-check"></i> Student Pro</span>
              </div>
            </div>
          </div>

          <div class="cc-card">
            <div class="cc-card-body">
              <div style="font-size:var(--fs-xs);font-weight:var(--fw-bold);color:var(--cc-primary);margin-bottom:.5rem;"><i class="bi bi-shield-lock-fill me-1"></i> PHASE 2 AUTHENTICATED</div>
              <p class="text-secondary mb-0" style="font-size:var(--fs-xs);">
                Your session is secured with MySQL and Laravel authentication middleware. Password changes and profile changes take immediate effect in the database.
              </p>
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
function setTheme(theme) {
  ThemeManager.apply(theme);
  ThemeManager.updateToggleIcons(theme);
  localStorage.setItem(ThemeManager.STORAGE_KEY, theme);
  const btnLight = document.getElementById('btnLightTheme');
  const btnDark  = document.getElementById('btnDarkTheme');
  if (theme === 'dark') {
    btnDark.className  = 'btn-cc btn-cc-primary btn-cc-sm';
    btnLight.className = 'btn-cc btn-cc-secondary btn-cc-sm';
  } else {
    btnLight.className = 'btn-cc btn-cc-primary btn-cc-sm';
    btnDark.className  = 'btn-cc btn-cc-secondary btn-cc-sm';
  }
  Toast.success('Theme Updated', theme === 'dark' ? 'Dark mode enabled.' : 'Light mode enabled.');
}
// Sync button state on load
document.addEventListener('DOMContentLoaded', () => {
  const t = localStorage.getItem('cc-theme') || 'light';
  const btnLight = document.getElementById('btnLightTheme');
  const btnDark  = document.getElementById('btnDarkTheme');
  if (btnLight && btnDark) {
    if (t === 'dark') {
      btnDark.className  = 'btn-cc btn-cc-primary btn-cc-sm';
      btnLight.className = 'btn-cc btn-cc-secondary btn-cc-sm';
    } else {
      btnLight.className = 'btn-cc btn-cc-primary btn-cc-sm';
      btnDark.className  = 'btn-cc btn-cc-secondary btn-cc-sm';
    }
  }
});
</script>
</body>
</html>
