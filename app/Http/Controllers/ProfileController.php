<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's profile.
     */
    public function show(): View
    {
        $user = Auth::user();

        return view('profile', [
            'user' => $user,
        ]);
    }

    /**
     * Update the authenticated user's personal information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Handle first_name / last_name inputs if present
        if (!$request->filled('name') && ($request->filled('first_name') || $request->filled('last_name'))) {
            $request->merge([
                'name' => trim($request->input('first_name', '') . ' ' . $request->input('last_name', ''))
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'academic_year' => ['nullable', 'string', 'max:100'],
            'monthly_allowance' => ['nullable', 'numeric', 'min:0'],
            'savings_goal' => ['nullable', 'numeric', 'min:0'],
            'university' => ['nullable', 'string', 'max:255'],
            'program' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'dob' => ['nullable', 'date'],
            'student_id' => ['nullable', 'string', 'max:50'],
            'campus' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Your name cannot be empty.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email is already taken by another account.',
            'monthly_allowance.numeric' => 'Monthly allowance must be a valid number.',
            'monthly_allowance.min' => 'Monthly allowance cannot be negative.',
            'savings_goal.numeric' => 'Savings goal must be a valid number.',
            'savings_goal.min' => 'Savings goal cannot be negative.',
        ]);

        $user->update($validated);

        return redirect()->route('profile')->with('success', 'Profile updated successfully!');
    }
}
