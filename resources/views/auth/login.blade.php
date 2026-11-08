@extends('layouts.app')
@section('content')
<div class="max-w-md mx-auto bg-white p-8 rounded-xl shadow border border-slate-200 mt-10">
    <h2 class="text-2xl font-bold text-slate-800 mb-2">UniNexus Login</h2>
    <p class="text-sm text-slate-500 mb-6">Sign in to manage consultation slots and team bookings.</p>
    <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">University Email</label>
            <input type="email" name="email" value="student.f7@uninexus.test" required class="w-full border rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Password</label>
            <input type="password" name="password" value="password123" required class="w-full border rounded-lg px-3 py-2">
        </div>
        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg">
            Sign In
        </button>
    </form>
    <p class="text-xs text-slate-500 mt-4">Seed demo accounts use password <code>password123</code>:<br>
    • Student: <code>student.f7@uninexus.test</code><br>
    • Supervisor: <code>supervisor.f7@uninexus.test</code><br>
    • Coordinator: <code>coordinator.f7@uninexus.test</code></p>
    <div class="mt-4 text-center">
        <a href="{{ route('register') }}" class="text-sm text-indigo-600 hover:underline">Create a new account</a>
    </div>
</div>
@endsection
