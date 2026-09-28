<?php

namespace App\Http\Controllers;

use App\Services\ReportAnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    protected ReportAnalyticsService $reportService;

    public function __construct(ReportAnalyticsService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display the financial reports and analytics page.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        $filters = [
            'period_type' => $request->query('period_type', 'month'),
            'month' => $request->query('month'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'type' => $request->query('type', 'all'),
            'category_id' => $request->query('category_id'),
            'income_source_id' => $request->query('income_source_id'),
        ];

        $reportData = $this->reportService->getReportData($user, $filters);

        // Paginate itemized transactions for the interactive table
        $transactions = $reportData['txQuery']
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('reports', array_merge($reportData, [
            'user' => $user,
            'transactions' => $transactions,
        ]));
    }

    /**
     * Export the filtered report as a professionally formatted PDF.
     */
    public function exportPdf(Request $request): Response
    {
        $user = Auth::user();

        $filters = [
            'period_type' => $request->query('period_type', 'month'),
            'month' => $request->query('month'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'type' => $request->query('type', 'all'),
            'category_id' => $request->query('category_id'),
            'income_source_id' => $request->query('income_source_id'),
        ];

        $reportData = $this->reportService->getReportData($user, $filters);

        // Get transactions for PDF (capped at 250 rows for performance)
        $transactions = $reportData['txQuery']
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(250)
            ->get();

        $viewData = array_merge($reportData, [
            'user' => $user,
            'transactions' => $transactions,
            'generatedAt' => now()->format('F d, Y - h:i A'),
        ]);

        $sanitizedLabel = preg_replace('/[^A-Za-z0-9_\-]/', '_', $reportData['periodLabel']);
        $filename = "CampusCoin_Financial_Report_{$sanitizedLabel}.pdf";

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = Pdf::loadView('reports.pdf', $viewData);
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Helvetica',
            ]);
            return $pdf->download($filename);
        }

        $dompdf = new \Dompdf\Dompdf([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Helvetica',
        ]);
        $html = view('reports.pdf', $viewData)->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Print-friendly dedicated report view.
     */
    public function printReport(Request $request): View
    {
        $user = Auth::user();

        $filters = [
            'period_type' => $request->query('period_type', 'month'),
            'month' => $request->query('month'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'type' => $request->query('type', 'all'),
            'category_id' => $request->query('category_id'),
            'income_source_id' => $request->query('income_source_id'),
        ];

        $reportData = $this->reportService->getReportData($user, $filters);

        $transactions = $reportData['txQuery']
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('reports.print', array_merge($reportData, [
            'user' => $user,
            'transactions' => $transactions,
            'generatedAt' => now()->format('F d, Y - h:i A'),
        ]));
    }
}
