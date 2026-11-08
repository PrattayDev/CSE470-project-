@extends('layouts.app')
@section('content')
<div class="max-w-lg mx-auto bg-white p-8 rounded-xl shadow border border-slate-200">
    <h2 class="text-2xl font-bold text-slate-800 mb-2">Student Registration</h2>
    <form method="POST" action="{{ route('register.submit') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Student ID</label>
            <input type="text" name="student_or_faculty_id" required class="w-full border rounded-lg px-3 py-2">
            <x-input-error field="student_or_faculty_id" />
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Full Name</label>
            <input type="text" name="name" required class="w-full border rounded-lg px-3 py-2">
            <x-input-error field="name" />
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">University G-Suite Email</label>
            <input type="email" name="email" required class="w-full border rounded-lg px-3 py-2">
            <x-input-error field="email" />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Password</label>
                <input type="password" name="password" required class="w-full border rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" required class="w-full border rounded-lg px-3 py-2">
            </div>
        </div>
        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg">
            Complete Registration
        </button>
    </form>
</div>
@endsection
