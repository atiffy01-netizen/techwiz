<?php

namespace App\Http\Controllers;

use App\Services\DuplicateDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DuplicateCheckController extends Controller
{
    /**
     * Check if a proposed transaction is potentially a duplicate of an existing record.
     */
    public function check(Request $request): JsonResponse
    {
        $user = Auth::user();

        $amount = (float) $request->input('amount', 0);
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $type = $request->input('type', 'expense');
        $date = $request->input('transaction_date', now()->toDateString());
        $description = $request->input('description', '');
        $excludeId = $request->filled('exclude_id') ? (int) $request->input('exclude_id') : null;

        $result = DuplicateDetectionService::checkForDuplicates(
            $user,
            $amount,
            $categoryId,
            $type,
            $date,
            $description,
            $excludeId
        );

        return response()->json([
            'success' => true,
            'is_duplicate' => $result['is_duplicate'],
            'count' => $result['count'],
            'warning_message' => $result['warning_message'],
            'matches' => $result['matches'],
        ]);
    }
}
