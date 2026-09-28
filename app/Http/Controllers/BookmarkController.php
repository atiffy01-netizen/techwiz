<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\BookmarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BookmarkController extends Controller
{
    /**
     * Display a paginated list of all bookmarked transactions for the authenticated student.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $search = $request->query('search');
        $type = $request->query('type');

        $bookmarks = BookmarkService::getBookmarkedTransactions($user, 15, $search, $type);

        return view('bookmarks', compact('bookmarks', 'search', 'type', 'user'));
    }

    /**
     * Toggle bookmark state for a transaction.
     */
    public function toggle(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $result = BookmarkService::toggle($user, $id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'bookmarked' => $result['bookmarked'],
                'message' => $result['message'],
            ]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Explicitly add a bookmark.
     */
    public function store(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        BookmarkService::bookmark($user, $id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'bookmarked' => true,
                'message' => 'Transaction bookmarked successfully.',
            ]);
        }

        return back()->with('success', 'Transaction bookmarked.');
    }

    /**
     * Explicitly remove a bookmark.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        BookmarkService::remove($user, $id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'bookmarked' => false,
                'message' => 'Transaction removed from bookmarks.',
            ]);
        }

        return back()->with('success', 'Transaction unbookmarked.');
    }
}
