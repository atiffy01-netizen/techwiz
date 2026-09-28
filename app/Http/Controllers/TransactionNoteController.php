<?php

namespace App\Http\Controllers;

use App\Services\TransactionNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionNoteController extends Controller
{
    /**
     * Retrieve note for a transaction.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $note = TransactionNoteService::getNote($user, $id);

        return response()->json([
            'success' => true,
            'note' => $note ? $note->note : '',
            'updated_at' => $note ? $note->updated_at->format('M d, Y H:i') : null,
        ]);
    }

    /**
     * Save or update a note for a transaction.
     */
    public function store(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ], [
            'note.required' => 'Please enter note text.',
            'note.max' => 'Note must not exceed 2,000 characters.',
        ]);

        $note = TransactionNoteService::saveNote($user, $id, $validated['note']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'note' => $note->note,
                'message' => 'Personal note saved successfully.',
            ]);
        }

        return back()->with('success', 'Personal note saved.');
    }

    /**
     * Delete a note for a transaction.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        TransactionNoteService::deleteNote($user, $id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Note removed successfully.',
            ]);
        }

        return back()->with('success', 'Note removed.');
    }
}
