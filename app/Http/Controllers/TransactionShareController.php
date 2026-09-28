<?php

namespace App\Http\Controllers;

use App\Services\TransactionShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TransactionShareController extends Controller
{
    /**
     * Generate a new shareable public link for a transaction.
     */
    public function store(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $days = $request->input('expires_in_days', 30);
        $share = TransactionShareService::createShare($user, $id, (int) $days);
        $shareUrl = route('transactions.shared', ['token' => $share->token]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'token' => $share->token,
                'share_url' => $shareUrl,
                'expires_at' => $share->expires_at ? $share->expires_at->format('M d, Y') : null,
                'message' => 'Share link generated successfully.',
            ]);
        }

        return back()->with('success', 'Share link generated: ' . $shareUrl);
    }

    /**
     * Revoke an active share link.
     */
    public function revoke(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        TransactionShareService::revokeShare($user, $id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Share link has been revoked.',
            ]);
        }

        return back()->with('success', 'Share link revoked.');
    }

    /**
     * Display a public safe view for a shared transaction.
     * This endpoint does NOT require authentication.
     */
    public function showPublic(string $token): View
    {
        $sharedData = TransactionShareService::getValidSharedTransaction($token);

        return view('shared-transaction', compact('sharedData'));
    }
}
