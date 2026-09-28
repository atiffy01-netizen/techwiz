<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Campus Coin — Smart Spending, Student Style. Premium budget & expense tracking designed for university students.">
  <title>Campus Coin — Smart Spending, Student Style</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ time() }}">
</head>
<body>

  <!-- NAV -->
  <nav class="cc-landing-nav" id="mainNav">
    <div class="nav-container">
      <div class="d-flex align-items-center">
        <a href="{{ route('home') }}" class="nav-brand">
          <img src="{{ asset('images/campus-coin-logo-light.png') }}" data-light-src="{{ asset('images/campus-coin-logo-light.png') }}" data-dark-src="{{ asset('images/campus-coin-logo-dark.png') }}" class="cc-brand-logo-nav" alt="Campus Coin">
        </a>
        <ul class="nav-links d-none d-lg-flex mb-0 ps-4">
          <li><a href="#features">Features</a></li>
          <li><a href="#how-it-works">How It Works</a></li>
          <li><a href="#insights">Insights</a></li>
          <li><a href="#budgets">Budgets</a></li>
          <li><a href="#savings">Saving Hacks</a></li>
        </ul>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button class="cc-theme-toggle" aria-label="Toggle theme"><i class="bi bi-moon-fill"></i></button>
        <a href="{{ route('login') }}" class="btn-cc btn-cc-ghost btn-cc-sm">Sign In</a>
        <a href="{{ route('register') }}" class="btn-cc btn-cc-primary btn-cc-sm">Get Started</a>
        <button class="nav-mobile-toggle" aria-label="Open menu"><i class="bi bi-list"></i></button>
      </div>
    </div>
  </nav>

  <!-- MOBILE NAV -->
  <div class="cc-mobile-nav-overlay"></div>
  <div class="cc-mobile-nav">
    <button class="mobile-nav-close" aria-label="Close menu"><i class="bi bi-x-lg"></i></button>
    <a href="#features" class="mobile-nav-link">Features</a>
    <a href="#how-it-works" class="mobile-nav-link">How It Works</a>
    <a href="#insights" class="mobile-nav-link">Insights</a>
    <a href="#budgets" class="mobile-nav-link">Budgets</a>
    <a href="#savings" class="mobile-nav-link">Saving Hacks</a>
    <hr class="cc-divider">
    <a href="{{ route('login') }}" class="btn-cc btn-cc-outline w-100 justify-content-center mb-2">Sign In</a>
    <a href="{{ route('register') }}" class="btn-cc btn-cc-primary w-100 justify-content-center">Get Started Free</a>
  </div>

  <!-- HERO -->
  <section class="cc-hero-section">
    <!-- Ambient Glow Spheres -->
    <div class="cc-hero-glow-sphere sphere-1"></div>
    <div class="cc-hero-glow-sphere sphere-2"></div>

    <div class="container position-relative" style="z-index: 1;">
      <div class="row align-items-center g-5">
        
        <!-- Left Side: Concise Hero Copy & CTAs -->
        <div class="col-lg-6">
          <div class="cc-hero-eyebrow">
            <i class="bi bi-stars spark-icon"></i>
            <span>AI-Powered Student Finance</span>
          </div>

          <h1 class="cc-hero-heading">
            Your Money.<br>
            Your Future.<br>
            <span class="gradient-text">Under Control.</span>
          </h1>

          <p class="cc-hero-sub">
            Track spending, manage budgets and understand your money with intelligent financial tools built for students.
          </p>

          <div class="cc-hero-ctas">
            <a href="{{ route('register') }}" class="btn-cc btn-cc-primary btn-cc-lg">
              <span>Get Started</span>
              <i class="bi bi-arrow-right ms-1"></i>
            </a>
            <a href="#features" class="btn-cc-explore btn-cc-lg">
              <i class="bi bi-compass"></i>
              <span>Explore Campus Coin</span>
            </a>
          </div>

          <!-- Social Proof & Trust Strip -->
          <div class="cc-hero-trust">
            <div class="hero-avatars">
              <div class="hero-avatar" style="background:#4F46E5;">HK</div>
              <div class="hero-avatar" style="background:#2563EB;">AK</div>
              <div class="hero-avatar" style="background:#7C3AED;">FS</div>
              <div class="hero-avatar" style="background:#06B6D4;">MR</div>
            </div>
            <div class="hero-trust-info">
              <div class="hero-stars">
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
              </div>
              <div class="hero-trust-text">
                <strong>4.9 / 5</strong> by 5,000+ students across 120+ universities
              </div>
            </div>
          </div>
        </div>

        <!-- Right Side: High-End Multi-Layer Fintech Interface Mockup (No Charts) -->
        <div class="col-lg-6">
          <div class="hero-fintech-stage">

            <!-- Floating Card 1: AI Insight Panel (Top Right) -->
            <div class="hero-float-ai">
              <div class="float-ai-header">
                <div class="ai-spark-icon"><i class="bi bi-cpu-fill"></i></div>
                <div>
                  <span class="ai-badge">AI INSIGHT</span>
                  <div class="ai-title">Dining Velocity Alert</div>
                </div>
              </div>
              <p class="ai-body">You spent <strong>18% more</strong> on dining this month.</p>
              <div class="ai-action-box">
                <span>Reduce dining by $42 next week</span>
                <strong>Potential savings: +$168/mo</strong>
              </div>
            </div>

            <!-- Main Fintech Dashboard Window -->
            <div class="hero-fintech-window">
              <!-- Window Top Bar -->
              <div class="window-topbar">
                <div class="window-dots">
                  <span class="window-dot red"></span>
                  <span class="window-dot yellow"></span>
                  <span class="window-dot green"></span>
                </div>
                <div class="window-search-pill">
                  <i class="bi bi-search"></i>
                  <span>⌘K Search transactions...</span>
                </div>
                <div class="window-user-pill">
                  <div class="window-user-avatar">HK</div>
                  <span class="window-status-badge">Student Pro</span>
                </div>
              </div>

              <!-- Window Body -->
              <div class="window-body">
                <!-- Balance Showcase Card -->
                <div class="window-balance-card">
                  <div class="balance-card-header">
                    <span class="balance-card-label">Available Balance</span>
                    <span class="balance-card-badge">
                      <i class="bi bi-arrow-up-right"></i> +4.8% this month
                    </span>
                  </div>
                  <div class="balance-card-amount">$3,842.50</div>
                  <div class="balance-card-stats">
                    <div class="balance-stat-item">
                      <span class="stat-lbl">Monthly Inflow</span>
                      <span class="stat-val income">+$1,450.00</span>
                    </div>
                    <div class="balance-stat-item">
                      <span class="stat-lbl">Total Spent</span>
                      <span class="stat-val expense">-$684.20</span>
                    </div>
                  </div>
                </div>

                <!-- Recent Intelligent Activity Feed -->
                <div class="window-feed-title">
                  <span>Intelligent Activity Feed</span>
                  <i class="bi bi-sliders text-muted"></i>
                </div>
                <div class="window-feed-list">
                  <div class="window-feed-item">
                    <div class="feed-item-left">
                      <div class="feed-item-icon book">
                        <i class="bi bi-book"></i>
                      </div>
                      <div class="feed-item-info">
                        <div class="title">Campus Bookstore</div>
                        <div class="sub">Semester textbooks</div>
                      </div>
                    </div>
                    <div class="feed-item-right">
                      <div class="feed-item-amount expense">-$48.50</div>
                      <span class="feed-item-tag ai-tag">🤖 AI Categorized (98%)</span>
                    </div>
                  </div>

                  <div class="window-feed-item">
                    <div class="feed-item-left">
                      <div class="feed-item-icon income">
                        <i class="bi bi-arrow-down-left"></i>
                      </div>
                      <div class="feed-item-info">
                        <div class="title">Merit Scholarship</div>
                        <div class="sub">University Direct Deposit</div>
                      </div>
                    </div>
                    <div class="feed-item-right">
                      <div class="feed-item-amount income">+$650.00</div>
                      <span class="feed-item-tag safe-tag">✨ Deposited</span>
                    </div>
                  </div>

                  <div class="window-feed-item">
                    <div class="feed-item-left">
                      <div class="feed-item-icon food">
                        <i class="bi bi-cup-hot"></i>
                      </div>
                      <div class="feed-item-info">
                        <div class="title">Cafeteria &amp; Bistro</div>
                        <div class="sub">Campus Meal Plan</div>
                      </div>
                    </div>
                    <div class="feed-item-right">
                      <div class="feed-item-amount expense">-$12.80</div>
                      <span class="feed-item-tag safe-tag">🛡️ In Budget</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Floating Card 2: Budget Progress (Bottom Left) -->
            <div class="hero-float-budget">
              <div class="float-budget-top">
                <div class="budget-icon"><i class="bi bi-pie-chart-fill"></i></div>
                <div>
                  <div class="budget-label">Semester Budget</div>
                  <div class="budget-values"><strong>$684.20</strong> <span class="muted">/ $950</span></div>
                </div>
                <span class="budget-status-pill">72%</span>
              </div>
              <div class="budget-bar-track">
                <div class="budget-bar-fill" style="width: 72%;"></div>
              </div>
              <div class="budget-sub">
                <i class="bi bi-shield-check text-success"></i> Safe zone · $265.80 remaining
              </div>
            </div>

            <!-- Floating Card 3: Privacy & Security Badge -->
            <div class="hero-float-badge" style="bottom: 110px; right: -20px;">
              <i class="bi bi-patch-check-fill" style="color: #10B981; font-size: 1.1rem;"></i>
              <div>
                <div class="float-badge-title">Zero Guesswork</div>
                <div class="float-badge-sub">Bank-grade student privacy</div>
              </div>
            </div>

            <!-- Layered Smartphone Mockup (Foreground Depth) -->
            <div class="hero-phone-mockup">
              <div class="phone-dynamic-island"></div>
              <div class="phone-quick-card">
                <div class="card-top">
                  <span>CAMPUS VIRTUAL CARD</span>
                  <i class="bi bi-wifi"></i>
                </div>
                <div class="card-bal">$3,842.50</div>
                <div class="card-num">•••• 8824 · HUNZALA K.</div>
              </div>
              <div class="phone-actions">
                <div class="phone-action-btn">
                  <i class="bi bi-send-fill"></i>
                  <span>Send</span>
                </div>
                <div class="phone-action-btn">
                  <i class="bi bi-pie-chart-fill"></i>
                  <span>Split</span>
                </div>
                <div class="phone-action-btn">
                  <i class="bi bi-stars"></i>
                  <span>AI Tips</span>
                </div>
              </div>
              <div class="phone-mini-feed">
                <div class="phone-mini-txn">
                  <div class="txn-left">
                    <span class="txn-dot"></span>
                    <span>Direct Deposit</span>
                  </div>
                  <span class="txn-val text-success">+$650.00</span>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- TRUST STRIP -->
  <section class="cc-trust-strip">
    <div class="container">
      <div class="row g-4 text-center">
        <div class="col-6 col-md-3">
          <div class="p-2">
            <div style="font-size:1.75rem;color:var(--cc-primary);margin-bottom:.5rem;"><i class="bi bi-arrow-left-right"></i></div>
            <div style="font-weight:var(--fw-bold);font-size:var(--fs-sm);margin-bottom:.25rem;">Track</div>
            <p class="text-secondary mb-0" style="font-size:var(--fs-xs);">Log allowance, UPI transfers, and café receipts in seconds.</p>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="p-2">
            <div style="font-size:1.75rem;color:var(--cc-accent-teal);margin-bottom:.5rem;"><i class="bi bi-bullseye"></i></div>
            <div style="font-weight:var(--fw-bold);font-size:var(--fs-sm);margin-bottom:.25rem;">Plan</div>
            <p class="text-secondary mb-0" style="font-size:var(--fs-xs);">Category-based semester caps that alert before you overspend.</p>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="p-2">
            <div style="font-size:1.75rem;color:var(--cc-accent-indigo);margin-bottom:.5rem;"><i class="bi bi-robot"></i></div>
            <div style="font-weight:var(--fw-bold);font-size:var(--fs-sm);margin-bottom:.25rem;">Understand</div>
            <p class="text-secondary mb-0" style="font-size:var(--fs-xs);">Smart insights uncover hidden leaks in food and subscriptions.</p>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="p-2">
            <div style="font-size:1.75rem;color:var(--cc-income);margin-bottom:.5rem;"><i class="bi bi-piggy-bank"></i></div>
            <div style="font-weight:var(--fw-bold);font-size:var(--fs-sm);margin-bottom:.25rem;">Save</div>
            <p class="text-secondary mb-0" style="font-size:var(--fs-xs);">Build emergency funds and crush your student tech goals.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FEATURES — Bento Grid -->
  <section class="py-5" id="features">
    <div class="container py-4">
      <div class="text-center mb-5">
        <span class="cc-badge cc-badge-primary mb-3">Designed for Campus Life</span>
        <h2 class="h2 font-bold mb-2" style="letter-spacing:-.03em;">Everything You Need to Master Your Money</h2>
        <p class="text-secondary mx-auto" style="max-width:540px;font-size:var(--fs-sm);">
          No complex accounting terms. Just crisp data, actionable alerts, and student-focused financial tools.
        </p>
      </div>

      <div class="row g-4">
        <!-- Big: Visual Analytics -->
        <div class="col-lg-7">
          <div class="bento-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <span class="cc-badge cc-badge-primary"><i class="bi bi-pie-chart-fill"></i> Visual Analytics</span>
              <span class="text-secondary" style="font-size:var(--fs-xs);">September Breakdown</span>
            </div>
            <h3 class="h4 font-bold mb-2" style="letter-spacing:-.02em;">See Where Your Money Goes</h3>
            <p class="text-secondary mb-4" style="font-size:var(--fs-sm);">
              Real-time category breakdown of hostel rent, mess fees, study materials, and weekend outings.
            </p>
            <div class="p-3" style="background:var(--cc-surface-subtle);border-radius:var(--radius-md);border:1px solid var(--cc-border-subtle);">
              <div class="d-flex justify-content-between mb-1" style="font-size:var(--fs-xs);">
                <span>🍔 Food &amp; Mess</span><span class="font-bold">Rs. 7,200 &nbsp;·&nbsp; 43%</span>
              </div>
              <div class="cc-progress-bar mb-3" style="height:7px;">
                <div class="cc-progress-fill" style="width:43%;background:var(--cc-warning);"></div>
              </div>
              <div class="d-flex justify-content-between mb-1" style="font-size:var(--fs-xs);">
                <span>🚌 Transit &amp; Metro</span><span class="font-bold">Rs. 2,040 &nbsp;·&nbsp; 18%</span>
              </div>
              <div class="cc-progress-bar mb-3" style="height:7px;">
                <div class="cc-progress-fill" style="width:18%;background:var(--cc-primary);"></div>
              </div>
              <div class="d-flex justify-content-between mb-1" style="font-size:var(--fs-xs);">
                <span>📚 Books &amp; Academics</span><span class="font-bold">Rs. 1,850 &nbsp;·&nbsp; 14%</span>
              </div>
              <div class="cc-progress-bar" style="height:7px;">
                <div class="cc-progress-fill" style="width:14%;background:var(--cc-income);"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Small: Smart Budgets -->
        <div class="col-lg-5">
          <div class="bento-card">
            <span class="cc-badge cc-badge-warning mb-3"><i class="bi bi-shield-exclamation"></i> Smart Limits</span>
            <h3 class="h4 font-bold mb-2" style="letter-spacing:-.02em;">Set Better Budgets</h3>
            <p class="text-secondary mb-4" style="font-size:var(--fs-sm);">
              Set strict or relaxed spending caps with proactive warnings before you go broke.
            </p>
            <div class="p-3" style="background:var(--cc-surface-subtle);border-radius:var(--radius-md);border:1px solid var(--cc-border-subtle);">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="font-bold" style="font-size:var(--fs-sm);">FOOD &amp; DINING</span>
                <span class="cc-badge cc-badge-warning">82% Used</span>
              </div>
              <div class="d-flex justify-content-between mb-2" style="font-size:var(--fs-xs);color:var(--cc-text-secondary);">
                <span>Rs. 8,200 spent</span><span>Rs. 10,000 limit</span>
              </div>
              <div class="cc-progress-bar" style="height:8px;">
                <div class="cc-progress-fill" style="width:82%;background:var(--cc-warning);"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Small: Saving Habits -->
        <div class="col-lg-5">
          <div class="bento-card">
            <span class="cc-badge cc-badge-income mb-3"><i class="bi bi-graph-up-arrow"></i> Growth</span>
            <h3 class="h4 font-bold mb-2" style="letter-spacing:-.02em;">Build Saving Habits</h3>
            <p class="text-secondary mb-4" style="font-size:var(--fs-sm);">
              Save small amounts consistently for laptops, certifications, and graduation trips.
            </p>
            <div class="d-flex align-items-center justify-content-between p-3" style="background:var(--cc-income-bg);border-radius:var(--radius-md);border:1px solid rgba(16,185,129,.18);">
              <div>
                <div style="font-size:var(--fs-2xs);color:var(--cc-text-muted);">Semester Goal</div>
                <div class="font-bold" style="font-size:var(--fs-sm);color:var(--cc-income);">M3 Laptop Fund</div>
              </div>
              <div class="text-end">
                <div style="font-size:var(--fs-2xs);color:var(--cc-text-muted);">Progress</div>
                <div class="font-bold" style="font-size:var(--fs-sm);color:var(--cc-income);">Rs. 45,000 / Rs. 60,000</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Big: Spending Intelligence -->
        <div class="col-lg-7">
          <div class="bento-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <span class="cc-badge cc-badge-info"><i class="bi bi-stars"></i> Spending Intelligence</span>
              <span class="text-secondary" style="font-size:var(--fs-xs);">AI Pattern Detector</span>
            </div>
            <h3 class="h4 font-bold mb-2" style="letter-spacing:-.02em;">Understand Your Spending</h3>
            <p class="text-secondary mb-4" style="font-size:var(--fs-sm);">
              Automatic anomaly alerts spotlighting sudden surge charges and recurring subscription leaks.
            </p>
            <div class="p-3 d-flex align-items-center justify-content-between" style="background:var(--cc-surface-subtle);border-radius:var(--radius-md);border-left:4px solid var(--cc-warning);">
              <div>
                <div style="font-size:var(--fs-xs);font-weight:var(--fw-bold);">Food Spending ↑ 24% this month</div>
                <div class="text-secondary" style="font-size:var(--fs-2xs);">62% of this occurred during weekend delivery orders.</div>
              </div>
              <a href="{{ route('insights') }}" class="btn-cc btn-cc-outline-primary btn-cc-sm text-nowrap ms-3">View Insight</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section class="py-5" id="how-it-works" style="background:var(--cc-surface-subtle);border-top:1px solid var(--cc-border-subtle);border-bottom:1px solid var(--cc-border-subtle);">
    <div class="container py-4">
      <div class="text-center mb-5">
        <h2 class="h2 font-bold mb-2" style="letter-spacing:-.03em;">A Connected Journey to Financial Clarity</h2>
        <p class="text-secondary mx-auto" style="max-width:520px;font-size:var(--fs-sm);">
          From day one of freshman orientation to graduation, Campus Coin keeps you organized in four simple steps.
        </p>
      </div>
      <div class="row g-4">
        <div class="col-md-6 col-lg-3">
          <div class="cc-card p-4 text-center h-100">
            <div class="cc-stat-icon mx-auto mb-3" style="background:var(--cc-primary-light);color:var(--cc-primary);">
              <i class="bi bi-plus-circle-fill"></i>
            </div>
            <div style="font-size:var(--fs-2xs);font-weight:var(--fw-bold);text-transform:uppercase;letter-spacing:.08em;color:var(--cc-text-muted);">Step 01</div>
            <h3 class="h5 font-bold mb-2">ADD</h3>
            <p class="text-secondary mb-0" style="font-size:var(--fs-xs);">Log allowance, freelance gigs, canteen snacks, or stationery items in under 5 seconds.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="cc-card p-4 text-center h-100">
            <div class="cc-stat-icon mx-auto mb-3" style="background:var(--cc-info-bg);color:var(--cc-info);">
              <i class="bi bi-speedometer2"></i>
            </div>
            <div style="font-size:var(--fs-2xs);font-weight:var(--fw-bold);text-transform:uppercase;letter-spacing:.08em;color:var(--cc-text-muted);">Step 02</div>
            <h3 class="h5 font-bold mb-2">TRACK</h3>
            <p class="text-secondary mb-0" style="font-size:var(--fs-xs);">Watch your balance update live with category tags and payment method filters.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="cc-card p-4 text-center h-100">
            <div class="cc-stat-icon mx-auto mb-3" style="background:var(--cc-warning-bg);color:var(--cc-warning);">
              <i class="bi bi-bullseye"></i>
            </div>
            <div style="font-size:var(--fs-2xs);font-weight:var(--fw-bold);text-transform:uppercase;letter-spacing:.08em;color:var(--cc-text-muted);">Step 03</div>
            <h3 class="h5 font-bold mb-2">PLAN</h3>
            <p class="text-secondary mb-0" style="font-size:var(--fs-xs);">Set sensible limits per category and receive notifications before you exceed them.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="cc-card p-4 text-center h-100">
            <div class="cc-stat-icon mx-auto mb-3" style="background:var(--cc-income-bg);color:var(--cc-income);">
              <i class="bi bi-piggy-bank-fill"></i>
            </div>
            <div style="font-size:var(--fs-2xs);font-weight:var(--fw-bold);text-transform:uppercase;letter-spacing:.08em;color:var(--cc-text-muted);">Step 04</div>
            <h3 class="h5 font-bold mb-2">SAVE</h3>
            <p class="text-secondary mb-0" style="font-size:var(--fs-xs);">Accumulate savings, discover campus perks, and graduate financially stress-free.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- INSIGHTS -->
  <section class="py-5" id="insights">
    <div class="container py-4">
      <div class="row align-items-center g-5">
        <div class="col-lg-5">
          <span class="cc-badge cc-badge-primary mb-3"><i class="bi bi-stars"></i> Intelligent Analysis</span>
          <h2 class="display-6 font-bold mb-3" style="letter-spacing:-.03em;">"Your spending tells a story."</h2>
          <p class="text-secondary mb-4" style="font-size:var(--fs-base);line-height:1.65;">
            Campus Coin looks beyond raw transaction numbers. It detects recurring spending surges, compares your habits against student averages, and delivers practical, guilt-free advice.
          </p>
          <a href="{{ route('insights') }}" class="btn-cc btn-cc-primary">Explore Insights Engine <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="col-lg-7">
          <div class="cc-card p-4" style="border-color:var(--cc-border-medium);">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span style="font-size:var(--fs-xs);font-weight:var(--fw-semibold);text-transform:uppercase;letter-spacing:.06em;color:var(--cc-text-muted);">Monthly Insight</span>
              <span class="cc-badge cc-badge-warning"><i class="bi bi-arrow-up-right"></i> Trend Alert</span>
            </div>
            <div class="d-flex align-items-baseline gap-2 mb-2">
              <h3 class="h4 font-bold mb-0">Food Spending</h3>
              <span class="font-bold" style="font-size:var(--fs-sm);color:var(--cc-expense);">↑ 24%</span>
            </div>
            <p class="text-secondary mb-4" style="font-size:var(--fs-sm);">
              Compared with your recent 3-month average. 62% of this occurred during weekend delivery orders.
            </p>
            <div class="p-3 mb-4" style="background:var(--cc-surface-subtle);border-radius:var(--radius-md);border:1px dashed var(--cc-border-medium);">
              <div style="font-weight:var(--fw-bold);font-size:var(--fs-xs);color:var(--cc-primary);margin-bottom:4px;">SUGGESTION</div>
              <p class="mb-0 text-secondary" style="font-size:var(--fs-sm);">
                Set a weekly weekend food cap of Rs. 500 to keep your monthly budget under control.
              </p>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-secondary" style="font-size:var(--fs-xs);"><i class="bi bi-clock me-1"></i> Generated today</span>
              <button class="btn-cc btn-cc-outline-primary btn-cc-sm" onclick="Toast.success('Pinned!', 'Insight pinned to your dashboard.')">
                <i class="bi bi-pin-angle"></i> Pin Insight
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- BUDGET VISUALIZATION -->
  <section class="py-5" id="budgets" style="background:var(--cc-surface-subtle);border-top:1px solid var(--cc-border-subtle);border-bottom:1px solid var(--cc-border-subtle);">
    <div class="container py-4">
      <div class="row align-items-center g-5">
        <div class="col-lg-6">
          <div class="cc-card p-5 text-center">
            <div class="cc-ring-gauge mb-4" style="background:conic-gradient(var(--cc-primary) 0% 71%, var(--cc-surface-subtle) 71% 100%);">
              <div class="cc-ring-gauge-inner">
                <span class="cc-amount font-bold" style="font-size:var(--fs-xl);color:var(--cc-primary);">71%</span>
                <span class="text-secondary" style="font-size:10px;text-transform:uppercase;">Allocated</span>
              </div>
            </div>
            <h3 class="h4 font-bold mb-1">September Budget Status</h3>
            <p class="text-secondary mb-3" style="font-size:var(--fs-sm);">Rs. 28,400 spent of Rs. 40,000</p>
            <span class="cc-badge cc-badge-income"><i class="bi bi-check-circle-fill"></i> Rs. 11,600 Remaining</span>
          </div>
        </div>
        <div class="col-lg-6">
          <span class="cc-badge cc-badge-primary mb-3">Proactive Spending Safeguard</span>
          <h2 class="h2 font-bold mb-3" style="letter-spacing:-.03em;">Never Run Out of Money Mid-Semester</h2>
          <p class="text-secondary mb-4" style="font-size:var(--fs-sm);line-height:1.65;">
            Set realistic monthly allocations for dorm groceries, study supplies, mobile data, and socializing. Visual progress meters keep your spending aligned with your goals.
          </p>
          <a href="{{ route('budgets') }}" class="btn-cc btn-cc-primary">Explore Budgeting Tools</a>
        </div>
      </div>
    </div>
  </section>

  <!-- SAVING SCENARIOS -->
  <section class="py-5" id="savings">
    <div class="container py-4">
      <div class="text-center mb-5">
        <span class="cc-badge cc-badge-income mb-3">Student Realistic Scenarios</span>
        <h2 class="h2 font-bold mb-2" style="letter-spacing:-.03em;">Small changes. Bigger savings.</h2>
        <p class="text-secondary mx-auto" style="max-width:520px;font-size:var(--fs-sm);">
          Here is how real students adjust daily habits to unlock Rs. 5,000+ in extra semester savings.
        </p>
      </div>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="cc-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="cc-badge cc-badge-warning"><i class="bi bi-cup-hot"></i> Food &amp; Mess</span>
                <span class="font-bold" style="font-size:var(--fs-xs);color:var(--cc-income);">Save ~Rs. 2,400/mo</span>
              </div>
              <h3 class="h5 font-bold mb-2">Batch Breakfast &amp; Campus Canteen</h3>
              <p class="text-secondary mb-3" style="font-size:var(--fs-sm);">
                Replacing 3 delivery app breakfast orders a week with dorm staples (oats, eggs, fruits).
              </p>
            </div>
            <div class="pt-3 border-top text-secondary" style="font-size:var(--fs-xs);border-color:var(--cc-border-subtle)!important;">
              <strong>Current:</strong> Rs. 4,800/mo &bull; <strong>Adjusted:</strong> Rs. 2,400/mo
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="cc-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="cc-badge cc-badge-primary"><i class="bi bi-bus-front"></i> Transport</span>
                <span class="font-bold" style="font-size:var(--fs-xs);color:var(--cc-income);">Save ~Rs. 1,200/mo</span>
              </div>
              <h3 class="h5 font-bold mb-2">Student Metro &amp; Bus Pass</h3>
              <p class="text-secondary mb-3" style="font-size:var(--fs-sm);">
                Claiming the 50% municipal student concession pass over daily on-demand cab rides.
              </p>
            </div>
            <div class="pt-3 border-top text-secondary" style="font-size:var(--fs-xs);border-color:var(--cc-border-subtle)!important;">
              <strong>Current:</strong> Rs. 2,400/mo &bull; <strong>Adjusted:</strong> Rs. 1,200/mo
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="cc-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="cc-badge cc-badge-info"><i class="bi bi-play-circle"></i> Subscriptions</span>
                <span class="font-bold" style="font-size:var(--fs-xs);color:var(--cc-income);">Save ~Rs. 850/mo</span>
              </div>
              <h3 class="h5 font-bold mb-2">Family &amp; Student Plans</h3>
              <p class="text-secondary mb-3" style="font-size:var(--fs-sm);">
                Splitting Spotify and YouTube family packages with 4 roommates and using GitHub Student Pack.
              </p>
            </div>
            <div class="pt-3 border-top text-secondary" style="font-size:var(--fs-xs);border-color:var(--cc-border-subtle)!important;">
              <strong>Current:</strong> Rs. 1,150/mo &bull; <strong>Adjusted:</strong> Rs. 300/mo
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA BANNER (PREMIUM REDESIGN) -->
  <section class="cc-cta-banner">
    <div class="cc-cta-glow-1"></div>
    <div class="cc-cta-glow-2"></div>
    <div class="container position-relative" style="z-index: 1;">
      <div class="cc-cta-badge">
        <i class="bi bi-stars"></i>
        <span>Join 5,000+ Smart University Students</span>
      </div>
      <h2 class="cc-cta-heading">
        Ready to Take Control of Your<br>
        <span class="gradient-text">Student Finances?</span>
      </h2>
      <p class="cc-cta-sub">
        Start tracking smart spending, setting proactive budgets, and achieving your semester savings goals in minutes.
      </p>
      <div class="cc-cta-actions">
        <a href="{{ route('register') }}" class="btn-cc btn-cc-primary btn-cc-xl">
          <span>Create Free Account</span>
          <i class="bi bi-arrow-right ms-2"></i>
        </a>
        <a href="{{ route('login') }}" class="btn-cc-ghost-light">
          <span>Sign In to Dashboard</span>
        </a>
      </div>
      <div class="cc-cta-perks">
        <div class="cc-cta-perk-item">
          <i class="bi bi-shield-lock-fill"></i>
          <span>100% Student Free</span>
        </div>
        <div class="cc-cta-perk-item">
          <i class="bi bi-patch-check-fill"></i>
          <span>Zero Bank Credentials Needed</span>
        </div>
        <div class="cc-cta-perk-item">
          <i class="bi bi-cpu-fill"></i>
          <span>AI Categorization &amp; Insights</span>
        </div>
      </div>
    </div>
  </section>

  <!-- SITEMAP (SRS Requirement) -->
  <section class="py-5" id="sitemap" style="background:var(--cc-surface-subtle);border-top:1px solid var(--cc-border-subtle);">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
          <span class="cc-badge cc-badge-primary mb-2"><i class="bi bi-diagram-3-fill"></i> Platform Architecture</span>
          <h2 class="h4 font-bold mb-0">Campus Coin Platform Sitemap</h2>
        </div>
        <span class="text-secondary" style="font-size:var(--fs-xs);">Complete navigation tree across all 10 phases</span>
      </div>

      <div class="row g-4">
        <!-- Col 1: Public & Authentication -->
        <div class="col-6 col-md-3">
          <div class="p-3 cc-card h-100">
            <h6 class="font-bold text-primary mb-3" style="font-size:var(--fs-xs);text-transform:uppercase;letter-spacing:.06em;">
              <i class="bi bi-globe me-1"></i> Public &amp; Auth
            </h6>
            <ul class="list-unstyled mb-0 d-flex flex-column gap-2" style="font-size:var(--fs-xs);">
              <li><a href="{{ route('home') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Landing Page</a></li>
              <li><a href="{{ route('login') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Student Login</a></li>
              <li><a href="{{ route('register') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Create Account</a></li>
              <li><a href="{{ route('password.request') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Forgot Password</a></li>
            </ul>
          </div>
        </div>

        <!-- Col 2: Core Financial Tools -->
        <div class="col-6 col-md-3">
          <div class="p-3 cc-card h-100">
            <h6 class="font-bold text-primary mb-3" style="font-size:var(--fs-xs);text-transform:uppercase;letter-spacing:.06em;">
              <i class="bi bi-wallet2 me-1"></i> Student Finances
            </h6>
            <ul class="list-unstyled mb-0 d-flex flex-column gap-2" style="font-size:var(--fs-xs);">
              <li><a href="{{ route('dashboard') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Real-time Dashboard</a></li>
              <li><a href="{{ route('transactions') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Transactions Hub</a></li>
              <li><a href="{{ route('transactions.create') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Add Transaction</a></li>
              <li><a href="{{ route('categories') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Custom Categories</a></li>
              <li><a href="{{ route('budgets') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Category Budgets</a></li>
              <li><a href="{{ route('reports') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Financial Reports &amp; PDF</a></li>
            </ul>
          </div>
        </div>

        <!-- Col 3: Intelligence & Advanced -->
        <div class="col-6 col-md-3">
          <div class="p-3 cc-card h-100">
            <h6 class="font-bold text-primary mb-3" style="font-size:var(--fs-xs);text-transform:uppercase;letter-spacing:.06em;">
              <i class="bi bi-stars me-1"></i> AI &amp; Intelligence
            </h6>
            <ul class="list-unstyled mb-0 d-flex flex-column gap-2" style="font-size:var(--fs-xs);">
              <li><a href="{{ route('saving-tips') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Saving Tips Engine</a></li>
              <li><a href="{{ route('insights') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> AI Spending Insights</a></li>
              <li><a href="{{ route('forecast') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Upcoming Month Forecast</a></li>
              <li><a href="{{ route('bookmarks') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Bookmarked Txns</a></li>
              <li><a href="{{ route('profile') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Student Profile</a></li>
              <li><a href="{{ route('settings') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Security Settings</a></li>
            </ul>
          </div>
        </div>

        <!-- Col 4: Administration -->
        <div class="col-6 col-md-3">
          <div class="p-3 cc-card h-100">
            <h6 class="font-bold text-primary mb-3" style="font-size:var(--fs-xs);text-transform:uppercase;letter-spacing:.06em;">
              <i class="bi bi-shield-lock me-1"></i> Administration
            </h6>
            <ul class="list-unstyled mb-0 d-flex flex-column gap-2" style="font-size:var(--fs-xs);">
              <li><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Admin Dashboard</a></li>
              <li><a href="{{ route('admin.users.index') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> User Management</a></li>
              <li><a href="{{ route('admin.categories.index') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> System Categories</a></li>
              <li><a href="{{ route('admin.tip-templates.index') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Announcement Templates</a></li>
              <li><a href="{{ route('admin.statistics.index') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-right text-primary me-1"></i> Platform Statistics</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="py-4" style="background:var(--cc-surface-base);border-top:1px solid var(--cc-border-subtle);">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2">
          <img src="{{ asset('images/campus-coin-logo-light.png') }}" data-light-src="{{ asset('images/campus-coin-logo-light.png') }}" data-dark-src="{{ asset('images/campus-coin-logo-dark.png') }}" class="cc-brand-logo-footer" alt="Campus Coin">
          <span class="text-secondary" style="font-size:var(--fs-xs);">&mdash; Smart Spending, Student Style</span>
        </div>
        <div class="text-secondary" style="font-size:var(--fs-xs);">&copy; 2026 Campus Coin. Built for college &amp; university students.</div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>

