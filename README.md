# Campus Coin — Smart Spending, Student Style

> **NextGen BudgetBee / Campus Coin** is a comprehensive, production-grade personal finance management and budget tracking platform engineered specifically for university and college students.

---

## 🚀 Key Platform Features

- **Personalized Financial Dashboard**: Live balance calculation, monthly cash inflow/outflow metrics, month-over-month trend analysis, and visual ring gauges.
- **Transaction Hub**: Income and expense tracking, recurring transactions, personal & system category tagging, pagination, multi-criteria filtering, and CSV bulk import/export.
- **Smart Category Budgets & Proactive Alerts**: Monthly spending caps per category, real-time threshold detection (On Track, Near Limit 75–99%, Over Budget 100%+), and automatic in-app notifications.
- **Financial Analytics & Reporting**: Interactive multi-month spending trajectories, category breakdown charts, date-range filtering, and high-fidelity PDF report exports.
- **Personalized Saving Tips Engine**: Algorithmic financial guidance engine generating personalized actionable saving recommendations based on actual user transaction history.
- **AI Intelligence & Advisory Features**:
  - *Advisory AI Expense Categorization* (Google Gemini & OpenAI integration with local deterministic fallbacks).
  - *Monthly Spending Insights* with automated anomaly detection.
  - *Deterministic Upcoming-Month Spending Forecast*.
- **Advanced Productivity (Phase 9)**:
  - Transaction Bookmarks & quick filtering (`/bookmarks`).
  - Private Transaction Notes & annotations.
  - Tokenized Secure Public Transaction Sharing with instant revocation.
  - Live Duplicate Transaction Detection.
  - Transaction Audit Activity Tracking.
- **Role-Based Administration Panel**:
  - Secure `/admin` portal guarded by `AdminMiddleware`.
  - Student user account management & status toggling (preserves financial records).
  - System default category CRUD with transactional safeguards.
  - Platform-wide announcement and tip template broadcaster.
  - Multi-dimensional platform usage statistics and financial charts.

---

## 📋 System Requirements

| Requirement | Minimum Version | Recommended Version |
| :--- | :--- | :--- |
| **PHP** | `^8.2.0` | `8.2` or `8.3` |
| **Laravel Framework** | `12.x` | `12.0` |
| **MySQL / MariaDB** | `MySQL 8.0+` or `MariaDB 10.4+` | `MySQL 8.0` |
| **Composer** | `2.x` | Latest |
| **Node.js & NPM** | `Node 18.x` / `NPM 9.x` | `Node 20.x` |
| **Web Server** | Built-in CLI / Nginx / Apache | Apache / Nginx |

---

## 🛠️ Step-by-Step Installation & Setup

### 1. Clone / Copy Repository
```bash
git clone <repository_url> campus_coin
cd campus_coin
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Install Frontend Dependencies & Build Assets
```bash
npm install
npm run build
```

### 4. Environment Configuration
Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```
Generate the application key:
```bash
php artisan key:generate
```

### 5. Configure Database
Update your database credentials inside `.env`:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=campus_coin
DB_USERNAME=root
DB_PASSWORD=
```
Create the database in your MySQL server:
```sql
CREATE DATABASE IF NOT EXISTS campus_coin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 6. Run Database Migrations & Seeders
Execute the migrations to build all tables:
```bash
php artisan migrate
```
Run seeders to populate system categories, administrators, demo student accounts, budgets, and sample transactions:
```bash
php artisan db:seed
```

*(Alternatively, import the pre-generated SQL DDL located at `database/schema/campus_coin_schema.sql` and `database/schema/campus_coin_sample_data.sql`)*.

### 7. AI Provider Configuration (Optional)
By default, Campus Coin runs with `AI_PROVIDER=fallback` which provides 100% offline, deterministic categorization and insights without external API keys.

To enable Google Gemini or OpenAI:
```ini
AI_ENABLED=true
AI_PROVIDER=gemini # Options: gemini, openai, fallback
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=gemini-1.5-flash
```

### 8. Launch Development Server
```bash
php artisan serve
```
Access the application in your browser at `http://127.0.0.1:8000`.

---

## 🔐 Demonstration & Test Credentials

The database seeder provisions the following safe demo accounts:

### 1. Student Account (Regular User)
- **Email**: `hunzala@campuscoin.edu`
- **Password**: `password123`
- **Role**: `user`
- **Access**: Full access to student dashboard, transactions, budgets, reports, saving tips, AI features, and bookmarks.

### 2. Secondary Student Account (Isolated Testing)
- **Email**: `ayesha@campuscoin.edu`
- **Password**: `password123`
- **Role**: `user`
- **Access**: User-isolated student environment for cross-account testing.

### 3. System Administrator Account
- **Email**: `admin@campuscoin.edu`
- **Password**: `Admin@123`
- **Role**: `admin`
- **Access**: Full access to `/admin` dashboard, user management, category CRUD, announcements, and statistics.

---

## 🗺️ Application Sitemap & Route Structure

```
Campus Coin
├── Public & Guest
│   ├── / (Landing Page & Feature Highlights)
│   ├── /login (Student & Staff Authentication)
│   ├── /register (New Student Onboarding)
│   ├── /forgot-password (Password Reset Request)
│   └── /shared/transaction/{token} (Tokenized Public Transaction Share)
│
├── Authenticated Student Portal
│   ├── /dashboard (Real-time Overview, Trajectory & Alerts)
│   ├── /transactions (Transaction History, Filters & Search)
│   ├── /add-transaction (Expense / Income Creation Form)
│   ├── /categories (Personal Custom Categories)
│   ├── /budgets (Monthly Spending Caps & Thresholds)
│   ├── /reports (Analytics, Breakdowns, Charts & PDF Export)
│   ├── /saving-tips (Algorithmic Saving Engine & Pinning)
│   ├── /insights (AI Spending Analysis & Anomaly Detection)
│   ├── /forecast (Upcoming Month Deterministic Spending Forecast)
│   ├── /bookmarks (Bookmarked High-Priority Transactions)
│   ├── /notifications (Notification Center)
│   ├── /profile (Student Academic & Financial Profile)
│   └── /settings (Account Password & Security)
│
└── Administrator Portal (/admin)
    ├── /admin (Overview Metrics & User Counts)
    ├── /admin/users (Platform User Listing & Status Toggle)
    ├── /admin/users/{id} (Individual Student Financial Audit)
    ├── /admin/categories (System Default Categories CRUD)
    ├── /admin/tip-templates (Broadcast Announcements & Tips)
    └── /admin/statistics (Platform Trends & Financial Aggregates)
```

---

## 🧪 Automated Testing Suite

Campus Coin includes comprehensive automated test suites covering all phases:

```bash
# Run all phase verification suites
php test_phase3_suite.php  # Core Transactions & Categories (16 tests)
php test_phase4_suite.php  # Budgets & Alerts Engine (28 tests)
php test_phase5_suite.php  # Reports, Analytics & PDF Exports (41 tests)
php test_phase6_suite.php  # Personalized Saving Tips Engine (23 tests)
php test_phase7_suite.php  # AI Expense Categorization & Insights (111 tests)
php test_phase8_suite.php  # Admin Panel & Role Enforcement (96 tests)
php test_phase9_suite.php  # Advanced Features & System Intelligence (15 tests)
php test_csv_suite.php     # CSV Import/Export Pipeline (7 tests)
```

---

## 🤖 AI Tools Acknowledgement

In compliance with SRS submission requirements, the following AI tools and development assistants were utilized during the design and construction of Campus Coin:

- **Antigravity (Google DeepMind)**: Used for architecture scaffolding, pair programming, automated test suite construction, CSS design tokens, and comprehensive verification.
- **Google Gemini 1.5 Flash**: Utilized within the application backend for runtime advisory expense categorization and monthly spending narrative insights.

*All AI outputs, code implementations, queries, security constraints, and database relationships were thoroughly reviewed, validated, and tested by the human engineering team.*

---

## 📄 License

Campus Coin is open-source software built for educational and student financial literacy purposes under the **MIT License**.
