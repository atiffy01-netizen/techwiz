<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Print Financial Report — Campus Coin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body {
      font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      color: #0f172a;
      background: #ffffff;
      padding: 20px;
      font-size: 13px;
    }
    .print-actions {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 12px 18px;
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .report-header {
      border-bottom: 2px solid #4f46e5;
      padding-bottom: 12px;
      margin-bottom: 20px;
    }
    .kpi-card {
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 12px 16px;
      text-align: center;
      background: #f8fafc;
    }
    .kpi-title {
      font-size: 11px;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .kpi-val {
      font-size: 18px;
      font-weight: 800;
      margin-top: 4px;
    }
    .section-title {
      font-size: 14px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 1px solid #cbd5e1;
      padding-bottom: 4px;
      margin-top: 24px;
      margin-bottom: 12px;
    }
    .badge-cc {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 600;
    }
    .badge-income { background: #dcfce7; color: #166534; }
    .badge-expense { background: #fee2e2; color: #991b1b; }
    .badge-info { background: #e0e7ff; color: #3730a3; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    
    @media print {
      .print-actions {
        display: none !important;
      }
      body {
        padding: 0;
      }
      .page-break {
        page-break-before: always;
      }
    }
  </style>
</head>
<body>

  <!-- Screen Actions -->
  <div class="print-actions">
    <div>
      <a href="{{ route('reports', request()->query()) }}" class="btn btn-outline-secondary btn-sm me-2">
        <i class="bi bi-arrow-left me-1"></i> Back to Reports
      </a>
      <span class="text-muted">Viewing print preview for <strong>{{ $periodLabel }}</strong></span>
    </div>
    <button onclick="window.print()" class="btn btn-primary btn-sm">
      <i class="bi bi-printer me-1"></i> Print / Save as PDF
    </button>
  </div>

  <!-- Report Header -->
  <div class="report-header d-flex justify-content-between align-items-start">
    <div>
      <img src="{{ asset('images/campus-coin-logo-light.png') }}" data-light-src="{{ asset('images/campus-coin-logo-light.png') }}" data-dark-src="{{ asset('images/campus-coin-logo-dark.png') }}" style="height:48px;width:auto;display:block;margin-bottom:0.25rem;" alt="Campus Coin">
      <div class="text-muted small">Student Financial Statement &amp; Analytics</div>
    </div>
    <div class="text-end small">
      <div class="fw-bold fs-6">Financial Statement: {{ $periodLabel }}</div>
      <div><strong>Student:</strong> {{ $user->name }} &bull; {{ $user->email }}</div>
      <div><strong>Generated:</strong> {{ $generatedAt }}</div>
    </div>
  </div>

  <!-- KPI Grid -->
  <div class="row g-3 mb-4">
    <div class="col-3">
      <div class="kpi-card">
        <div class="kpi-title">Total Income</div>
        <div class="kpi-val text-success">Rs. {{ number_format($totalIncome, 2) }}</div>
        <div class="small text-muted">{{ $incomeCount }} transactions</div>
      </div>
    </div>
    <div class="col-3">
      <div class="kpi-card">
        <div class="kpi-title">Total Expenses</div>
        <div class="kpi-val text-danger">Rs. {{ number_format($totalExpense, 2) }}</div>
        <div class="small text-muted">{{ $expenseCount }} transactions</div>
      </div>
    </div>
    <div class="col-3">
      <div class="kpi-card">
        <div class="kpi-title">Net Savings</div>
        <div class="kpi-val {{ $netBalance >= 0 ? 'text-primary' : 'text-danger' }}">
          Rs. {{ number_format($netBalance, 2) }}
        </div>
        <div class="small text-muted">Savings Rate: {{ $savingsRate }}%</div>
      </div>
    </div>
    <div class="col-3">
      <div class="kpi-card">
        <div class="kpi-title">Avg Daily Expense</div>
        <div class="kpi-val text-dark">Rs. {{ number_format($averageDailySpending, 2) }}</div>
        <div class="small text-muted">Across {{ $totalDays }} days</div>
      </div>
    </div>
  </div>

  <!-- Highlights -->
  @if(!empty($highlights))
  <div class="alert alert-primary py-2 px-3 mb-4">
    <div class="fw-bold small mb-1">Key Report Highlights:</div>
    <ul class="mb-0 ps-3 small">
      @foreach($highlights as $hl)
        <li><strong>{{ $hl['title'] }}:</strong> {{ $hl['text'] }}</li>
      @endforeach
    </ul>
  </div>
  @endif

  <!-- Category Breakdown -->
  <div class="section-title">Expense Breakdown by Category</div>
  @if($categoryBreakdown->isEmpty())
    <p class="text-muted fst-italic">No expense transactions recorded in this period.</p>
  @else
    <table class="table table-bordered table-sm mb-4">
      <thead class="table-light">
        <tr>
          <th>Category</th>
          <th class="text-center">Count</th>
          <th class="text-end">Amount</th>
          <th class="text-end">% of Expenses</th>
        </tr>
      </thead>
      <tbody>
        @foreach($categoryBreakdown as $cat)
        <tr>
          <td class="fw-bold">{{ $cat['name'] }}</td>
          <td class="text-center">{{ $cat['transaction_count'] }}</td>
          <td class="text-end fw-bold">Rs. {{ number_format($cat['amount'], 2) }}</td>
          <td class="text-end">{{ $cat['percentage'] }}%</td>
        </tr>
        @endforeach
      </tbody>
      <tfoot class="table-light fw-bold">
        <tr>
          <td>Total Expenses</td>
          <td class="text-center">{{ $expenseCount }}</td>
          <td class="text-end">Rs. {{ number_format($totalExpense, 2) }}</td>
          <td class="text-end">100.0%</td>
        </tr>
      </tfoot>
    </table>
  @endif

  <!-- 6-Month Trend -->
  @if(!empty($sixMonthTrend['detailed']))
  <div class="section-title">6-Month Consecutive Financial Trend</div>
  <table class="table table-bordered table-sm mb-4">
    <thead class="table-light">
      <tr>
        <th>Month</th>
        <th class="text-end">Income</th>
        <th class="text-end">Expense</th>
        <th class="text-end">Net Savings</th>
        <th class="text-center">Status</th>
      </tr>
    </thead>
    <tbody>
      @foreach($sixMonthTrend['detailed'] as $trend)
      <tr>
        <td class="fw-bold">{{ $trend['full_label'] }}</td>
        <td class="text-end text-success">Rs. {{ number_format($trend['income'], 2) }}</td>
        <td class="text-end text-danger">Rs. {{ number_format($trend['expense'], 2) }}</td>
        <td class="text-end fw-bold {{ $trend['balance'] >= 0 ? 'text-primary' : 'text-danger' }}">
          Rs. {{ number_format($trend['balance'], 2) }}
        </td>
        <td class="text-center">
          <span class="badge-cc {{ $trend['balance'] >= 0 ? 'badge-income' : 'badge-expense' }}">
            {{ $trend['balance'] >= 0 ? 'Surplus' : 'Deficit' }}
          </span>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  <!-- Budget Status -->
  @if($budgetComparison['has_budgets'])
  <div class="section-title">Budget Performance ({{ $selectedMonth }})</div>
  <table class="table table-bordered table-sm mb-4">
    <thead class="table-light">
      <tr>
        <th>Category</th>
        <th class="text-end">Limit</th>
        <th class="text-end">Spent</th>
        <th class="text-end">Remaining</th>
        <th class="text-end">Usage</th>
        <th class="text-center">Status</th>
      </tr>
    </thead>
    <tbody>
      @foreach($budgetComparison['items'] as $b)
      <tr>
        <td class="fw-bold">{{ $b['category_name'] }}</td>
        <td class="text-end">Rs. {{ number_format($b['limit_amount'], 2) }}</td>
        <td class="text-end fw-bold">Rs. {{ number_format($b['spent_amount'], 2) }}</td>
        <td class="text-end">Rs. {{ number_format($b['remaining'], 2) }}</td>
        <td class="text-end">{{ $b['usage_percent'] }}%</td>
        <td class="text-center">
          @if($b['status'] === 'over_budget')
            <span class="badge-cc badge-expense">Over Budget</span>
          @elseif($b['status'] === 'near_limit')
            <span class="badge-cc badge-warning">Near Limit</span>
          @else
            <span class="badge-cc badge-income">On Track</span>
          @endif
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  <!-- Itemized Transactions Record -->
  <div class="section-title">Itemized Transactions Record</div>
  @if($transactions->isEmpty())
    <p class="text-muted fst-italic">No transactions found for this period.</p>
  @else
    <table class="table table-bordered table-sm mb-4">
      <thead class="table-light">
        <tr>
          <th>Date</th>
          <th>Description</th>
          <th>Category</th>
          <th class="text-center">Type</th>
          <th class="text-end">Amount</th>
        </tr>
      </thead>
      <tbody>
        @foreach($transactions as $t)
        <tr>
          <td>{{ \Carbon\Carbon::parse($t->transaction_date)->format('M d, Y') }}</td>
          <td>{{ $t->description }}</td>
          <td>{{ $t->category->name ?? 'Uncategorized' }}</td>
          <td class="text-center">
            <span class="badge-cc {{ $t->type === 'income' ? 'badge-income' : 'badge-expense' }}">
              {{ ucfirst($t->type) }}
            </span>
          </td>
          <td class="text-end fw-bold {{ $t->type === 'income' ? 'text-success' : 'text-danger' }}">
            {{ $t->type === 'income' ? '+' : '-' }}Rs. {{ number_format($t->amount, 2) }}
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <div class="text-center text-muted small mt-4 pt-2 border-top">
    Campus Coin Student Fintech System &bull; Generated on {{ $generatedAt }} for {{ $user->name }}
  </div>

</body>
</html>
