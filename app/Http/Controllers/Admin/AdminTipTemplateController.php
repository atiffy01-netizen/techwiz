<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminTipTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminTipTemplateController extends Controller
{
    /**
     * Display a paginated list of tip templates and announcements.
     */
    public function index(Request $request): View
    {
        $type = $request->query('type', 'all');
        $status = $request->query('status', 'all');
        $search = trim($request->query('search', ''));

        $query = AdminTipTemplate::with('creator:id,name,email');

        if ($type !== 'all' && in_array($type, ['announcement', 'saving_tip', 'general_tip'])) {
            $query->where('type', $type);
        }

        if ($status !== 'all' && in_array($status, ['active', 'inactive'])) {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $templates = $query->latest()->paginate(12)->withQueryString();

        $totalCount = AdminTipTemplate::count();
        $activeCount = AdminTipTemplate::where('status', 'active')->count();
        $announcementCount = AdminTipTemplate::where('type', 'announcement')->count();
        $savingTipCount = AdminTipTemplate::where('type', 'saving_tip')->count();

        return view('admin.tip-templates.index', compact(
            'templates',
            'type',
            'status',
            'search',
            'totalCount',
            'activeCount',
            'announcementCount',
            'savingTipCount'
        ));
    }

    /**
     * Store a newly created tip template or announcement.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
            'type' => ['required', 'string', 'in:announcement,saving_tip,general_tip'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ], [
            'title.required' => 'Please provide a title for the template.',
            'message.required' => 'Please enter the message or tip text.',
        ]);

        AdminTipTemplate::create([
            'title' => trim($validated['title']),
            'message' => trim($validated['message']),
            'type' => $validated['type'],
            'status' => $validated['status'],
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.tip-templates.index')->with('success', 'Template created successfully.');
    }

    /**
     * Update an existing tip template or announcement.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $template = AdminTipTemplate::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
            'type' => ['required', 'string', 'in:announcement,saving_tip,general_tip'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ]);

        $template->update([
            'title' => trim($validated['title']),
            'message' => trim($validated['message']),
            'type' => $validated['type'],
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.tip-templates.index')->with('success', 'Template updated successfully.');
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(Request $request, int $id): RedirectResponse
    {
        $template = AdminTipTemplate::findOrFail($id);
        $template->status = $template->status === 'active' ? 'inactive' : 'active';
        $template->save();

        $statusLabel = $template->status === 'active' ? 'activated' : 'deactivated';
        return back()->with('success', "Template '{$template->title}' has been {$statusLabel}.");
    }

    /**
     * Delete a template.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $template = AdminTipTemplate::findOrFail($id);
        $title = $template->title;
        $template->delete();

        return redirect()->route('admin.tip-templates.index')->with('success', "Template '{$title}' was deleted successfully.");
    }
}
