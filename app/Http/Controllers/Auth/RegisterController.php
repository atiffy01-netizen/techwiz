<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Show the registration form.
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(Request $request): RedirectResponse
    {
        // Handle name if split into first/last name or provided as full name
        if (!$request->filled('name') && ($request->filled('first_name') || $request->filled('last_name'))) {
            $request->merge([
                'name' => trim($request->input('first_name', '') . ' ' . $request->input('last_name', ''))
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'academic_year' => ['nullable', 'string', 'max:100'],
            'monthly_allowance' => ['nullable', 'numeric', 'min:0'],
            'savings_goal' => ['nullable', 'numeric', 'min:0'],
            'university' => ['nullable', 'string', 'max:255'],
            'program' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ], [
            'name.required' => 'Please provide your full name.',
            'email.required' => 'A valid university or personal email is required.',
            'email.unique' => 'This email is already registered. Please sign in instead.',
            'password.required' => 'Please choose a secure password.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'monthly_allowance.numeric' => 'Monthly allowance must be a valid number.',
            'monthly_allowance.min' => 'Monthly allowance cannot be negative.',
            'savings_goal.numeric' => 'Savings goal must be a valid number.',
            'savings_goal.min' => 'Savings goal cannot be negative.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'academic_year' => $validated['academic_year'] ?? 'Year 1',
            'monthly_allowance' => $validated['monthly_allowance'] ?? 0.00,
            'savings_goal' => $validated['savings_goal'] ?? 0.00,
            'university' => $validated['university'] ?? null,
            'program' => $validated['program'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Welcome to Campus Coin! Your account has been created successfully.');
    }
}
