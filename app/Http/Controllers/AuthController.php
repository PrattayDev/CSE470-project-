<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Mandatory Authentication Module (Login - Logout - Registration)
 * Note: Excluded from the 20 substantive features count per CSE 470 Rule Book.
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->route('dashboard')
                ->with('success', 'Welcome back, ' . Auth::user()->name . '!');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials provided.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'student_or_faculty_id' => 'required|string|max:30|unique:users,student_or_faculty_id',
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'section' => 'nullable|string|max:20',
            'cgpa' => 'nullable|numeric|between:0,4.00',
            'skills' => 'nullable|string',
        ]);

        $skillsArray = !empty($validated['skills'])
            ? array_values(array_filter(array_map('trim', explode(',', $validated['skills']))))
            : ['Laravel', 'MVC', 'MySQL'];

        // Self-registration is strictly restricted to students; faculty/admin roles are provisioned by administrators.
        $user = User::create([
            'student_or_faculty_id' => $validated['student_or_faculty_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'student',
            'section' => $validated['section'] ?? '09',
            'cgpa' => $validated['cgpa'] ?? null,
            'skills' => $skillsArray,
            'max_group_quota' => 0,
        ]);

        Auth::login($user);
        return redirect()->route('dashboard')
            ->with('success', 'Student account registered successfully!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', 'You have been logged out.');
    }
}
