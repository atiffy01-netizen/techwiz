<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Campus Coin — Financial Report</title>
  <style>
    @page {
      margin: 25px 30px;
    }
    body {
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      color: #1e293b;
      font-size: 11px;
      line-height: 1.4;
      margin: 0;
      padding: 0;
    }
    .header-table {
      width: 100%;
      border-bottom: 2px solid #4f46e5;
      padding-bottom: 12px;
      margin-bottom: 15px;
    }
    .brand-title {
      font-size: 20px;
      font-weight: bold;
      color: #4f46e5;
      margin: 0;
    }
    .brand-subtitle {
      font-size: 10px;
      color: #64748b;
      margin-top: 2px;
    }
    .report-meta {
      text-align: right;
      font-size: 10px;
      color: #475569;
    }
    .report-title {
      font-size: 14px;
      font-weight: bold;
      color: #0f172a;
      margin-bottom: 4px;
    }
    .badge {
      display: inline-block;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 9px;
      font-weight: bold;
    }
    .badge-income { background: #dcfce7; color: #15803d; }
    .badge-expense { background: #fee2e2; color: #b91c1c; }
    .badge-info { background: #e0e7ff; color: #4338ca; }
    .badge-warning { background: #fef3c7; color: #b45309; }

    /* KPI Grid */
    .kpi-table {
      width: 100%;
      margin-bottom: 18px;
      border-collapse: separate;
      border-spacing: 8px 0;
    }
    .kpi-box {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 10px 12px;
      text-align: center;
    }
    .kpi-label {
      font-size: 9px;
      text-transform: uppercase;
      color: #64748b;
      font-weight: bold;
      letter-spacing: 0.5px;
      margin-bottom: 4px;
    }
    .kpi-value {
      font-size: 15px;
      font-weight: bold;
    }
    .val-income { color: #16a34a; }
    .val-expense { color: #dc2626; }
    .val-balance { color: #4f46e5; }
    .val-neutral { color: #0f172a; }

    /* Section Styles */
    .section-title {
      font-size: 12px;
      font-weight: bold;
      color: #0f172a;
      border-bottom: 1px solid #cbd5e1;
      padding-bottom: 4px;
      margin-top: 15px;
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Tables */
    .data-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }
    .data-table th {
      background-color: #f1f5f9;
      color: #334155;
      font-size: 9px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 6px 8px;
      border: 1px solid #cbd5e1;
      text-align: left;
    }
    .data-table td {
      padding: 5px 8px;
      border: 1px solid #e2e8f0;
      font-size: 10px;
    }
    .data-table tr:nth-child(even) {
      background-color: #f8fafc;
    }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .font-bold { font-weight: bold; }

    /* Highlights list */
    .highlight-box {
      background: #eff6ff;
      border-left: 3px solid #3b82f6;
      padding: 8px 12px;
      margin-bottom: 15px;
      border-radius: 0 4px 4px 0;
    }
    .highlight-item {
      font-size: 10px;
      color: #1e3a8a;
      margin-bottom: 3px;
    }

    .footer-note {
      margin-top: 20px;
      padding-top: 8px;
      border-top: 1px solid #e2e8f0;
      font-size: 8px;
      color: #94a3b8;
      text-align: center;
    }
  </style>
</head>
<body>

  <!-- HEADER -->
  <table class="header-table">
    <tr>
      <td style="vertical-align: top;">
        <div class="brand-title">CAMPUS COIN</div>
        <div class="brand-subtitle">Smart Student Financial Management</div>
      </td>
      <td class="report-meta" style="vertical-align: top;">
        <div class="report-title">FINANCIAL REPORT STATEMENT</div>
        <div><strong>Period:</strong> {{ $periodLabel }}</div>
        <div><strong>User:</strong> {{ $user->name }} ({{ $user->email }})</div>
        <div><strong>Generated:</strong> {{ $generatedAt }}</div>
      </td>
    </tr>
  </table>

  <!-- KPI SUMMARY BOXES -->
  <table class="kpi-table">
    <tr>
      <td class="kpi-box" style="width: 25%;">
        <div class="kpi-label">Total Income</div>
        <div class="kpi-value val-income">Rs. {{ number_format($totalIncome, 2) }}</div>
        <div style="font-size: 8px; color: #64748b; margin-top: 2px;">{{ $incomeCount }} inflows</div>
      </td>
      <td class="kpi-box" style="width: 25%;">
        <div class="kpi-label">Total Expenses</div>
        <div class="kpi-value val-expense">Rs. {{ number_format($totalExpense, 2) }}</div>
        <div style="font-size: 8px; color: #64748b; margin-top: 2px;">{{ $expenseCount }} outflows</div>
      </td>
      <td class="kpi-box" style="width: 25%;">
        <div class="kpi-label">Net Savings / Balance</div>
        <div class="kpi-value {{ $netBalance >= 0 ? 'val-balance' : 'val-expense' }}">
          Rs. {{ number_format($netBalance, 2) }}
        </div>
        <div style="font-size: 8px; color: #64748b; margin-top: 2px;">Rate: {{ $savingsRate }}%</div>
      </td>
      <td class="kpi-box" style="width: 25%;">
        <div class="kpi-label">Avg Daily Spending</div>
        <div class="kpi-value val-neutral">Rs. {{ number_format($averageDailySpending, 2) }}</div>
        <div style="font-size: 8px; color: #64748b; margin-top: 2px;">Across {{ $totalDays }} days</div>
      </td>
    </tr>
  </table>

  <!-- REPORT HIGHLIGHTS -->
  @if(!empty($highlights))
  <div class="highlight-box">
    <div style="font-weight: bold; font-size: 10px; margin-bottom: 4px; color: #1d4ed8;">Report Highlights & Summary</div>
    @foreach($highlights as $hl)
      <div class="highlight-item">• <strong>{{ $hl['title'] }}:</strong> {{ $hl['text'] }}</div>
    @endforeach
  </div>
  @endif

  <!-- CATEGORY BREAKDOWN -->
  <div class="section-title">Expense Breakdown by Category</div>
  @if($categoryBreakdown->isEmpty())
    <p style="color: #64748b; font-style: italic;">No expense transactions recorded for this period.</p>
  @else
    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 40%;">Category</th>
          <th style="width: 20%;" class="text-center">Transactions</th>
          <th style="width: 20%;" class="text-right">Amount (Rs.)</th>
          <th style="width: 20%;" class="text-right">% of Total</th>
        </tr>
      </thead>
      <tbody>
        @foreach($categoryBreakdown as $cat)
        <tr>
          <td class="font-bold">{{ $cat['name'] }}</td>
          <td class="text-center">{{ $cat['transaction_count'] }}</td>
          <td class="text-right font-bold">{{ number_format($cat['amount'], 2) }}</td>
          <td class="text-right">{{ $cat['percentage'] }}%</td>
        </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr style="background: #f1f5f9; font-weight: bold;">
          <td>Total Spending</td>
          <td class="text-center">{{ $expenseCount }}</td>
          <td class="text-right">{{ number_format($totalExpense, 2) }}</td>
          <td class="text-right">100.0%</td>
        </tr>
      </tfoot>
    </table>
  @endif

  <!-- 6-MONTH FINANCIAL TREND -->
  @if(!empty($sixMonthTrend['detailed']))
  <div class="section-title">6-Month Financial Trend</div>
  <table class="data-table">
    <thead>
      <tr>
        <th>Month</th>
        <th class="text-right">Income (Rs.)</th>
        <th class="text-right">Expense (Rs.)</th>
        <th class="text-right">Net Savings (Rs.)</th>
        <th class="text-center">Status</th>
      </tr>
    </thead>
    <tbody>
      @foreach($sixMonthTrend['detailed'] as $trend)
      <tr>
        <td class="font-bold">{{ $trend['full_label'] }}</td>
        <td class="text-right val-income">{{ number_format($trend['income'], 2) }}</td>
        <td class="text-right val-expense">{{ number_format($trend['expense'], 2) }}</td>
        <td class="text-right font-bold {{ $trend['balance'] >= 0 ? 'val-balance' : 'val-expense' }}">
          {{ number_format($trend['balance'], 2) }}
        </td>
        <td class="text-center">
          <span class="badge {{ $trend['balance'] >= 0 ? 'badge-income' : 'badge-expense' }}">
            {{ $trend['balance'] >= 0 ? 'Surplus' : 'Deficit' }}
          </span>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  <!-- BUDGET STATUS (IF APPLICABLE) -->
  @if($budgetComparison['has_budgets'])
  <div class="section-title">Budget Health & Usage ({{ $selectedMonth }})</div>
  <table class="data-table">
    <thead>
      <tr>
        <th>Category</th>
        <th class="text-right">Budget Limit (Rs.)</th>
        <th class="text-right">Actual Spent (Rs.)</th>
        <th class="text-right">Remaining (Rs.)</th>
        <th class="text-right">Usage</th>
        <th class="text-center">Status</th>
      </tr>
    </thead>
    <tbody>
      @foreach($budgetComparison['items'] as $b)
      <tr>
        <td class="font-bold">{{ $b['category_name'] }}</td>
        <td class="text-right">{{ number_format($b['limit_amount'], 2) }}</td>
        <td class="text-right font-bold">{{ number_format($b['spent_amount'], 2) }}</td>
        <td class="text-right">{{ number_format($b['remaining'], 2) }}</td>
        <td class="text-right">{{ $b['usage_percent'] }}%</td>
        <td class="text-center">
          @if($b['status'] === 'over_budget')
            <span class="badge badge-expense">Over Budget</span>
          @elseif($b['status'] === 'near_limit')
            <span class="badge badge-warning">Near Limit</span>
          @else
            <span class="badge badge-income">On Track</span>
          @endif
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  <!-- ITEMIZED TRANSACTION RECORD -->
  <div class="section-title">Itemized Transactions Record (Up to 250 entries)</div>
  @if($transactions->isEmpty())
    <p style="color: #64748b; font-style: italic;">No transactions found matching the applied filter criteria.</p>
  @else
    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 15%;">Date</th>
          <th style="width: 35%;">Description</th>
          <th style="width: 20%;">Category</th>
          <th style="width: 10%;" class="text-center">Type</th>
          <th style="width: 20%;" class="text-right">Amount (Rs.)</th>
        </tr>
      </thead>
      <tbody>
        @foreach($transactions as $t)
        <tr>
          <td>{{ \Carbon\Carbon::parse($t->transaction_date)->format('M d, Y') }}</td>
          <td>{{ $t->description }}</td>
          <td>{{ $t->category->name ?? 'Uncategorized' }}</td>
          <td class="text-center">
            <span class="badge {{ $t->type === 'income' ? 'badge-income' : 'badge-expense' }}">
              {{ ucfirst($t->type) }}
            </span>
          </td>
          <td class="text-right font-bold {{ $t->type === 'income' ? 'val-income' : 'val-expense' }}">
            {{ $t->type === 'income' ? '+' : '-' }}Rs. {{ number_format($t->amount, 2) }}
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <!-- FOOTER -->
  <div class="footer-note">
    Campus Coin Student Fintech System — Confidential User Statement — Generated for {{ $user->name }}
  </div>

</body>
</html>
