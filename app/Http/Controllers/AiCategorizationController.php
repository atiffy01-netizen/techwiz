<?php

namespace App\Http\Controllers;

use App\Services\Ai\AiCategorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiCategorizationController extends Controller
{
    /**
     * Provide an advisory AI category suggestion based on transaction description.
     */
    public function categorize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => 'required|string|min:2|max:255',
            'type' => 'nullable|string|in:expense,income',
        ]);

        $user = Auth::user();
        $type = $validated['type'] ?? 'expense';
        $description = $validated['description'];

        $result = AiCategorizationService::suggestCategory($user, $description, $type);

        return response()->json($result);
    }
}
