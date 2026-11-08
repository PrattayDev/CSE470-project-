<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniNexus — Consultation Slot Booking</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 md:flex">
    <div class="flex items-center justify-between bg-slate-900 px-4 py-3 text-white md:hidden">
        <span class="font-bold">UniNexus · Feature 7</span>
        <button type="button" aria-label="Toggle navigation menu"
                onclick="document.getElementById('sidebarNav').classList.toggle('hidden')"
                class="rounded p-1.5 text-slate-200 hover:text-white focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
    </div>

    <aside id="sidebarNav" class="hidden w-full flex-shrink-0 flex-col bg-slate-900 text-slate-100 md:flex md:h-screen md:w-72 md:sticky md:top-0">
        <div class="border-b border-slate-800 p-4">
            <h1 class="text-lg font-bold">UniNexus MVC</h1>
            <p class="text-xs text-slate-400">Consultation Slot Booking</p>
        </div>
        <nav class="flex-1 p-3">
            <a href="{{ route('consultations.index') }}"
               class="flex items-center space-x-3 rounded-lg bg-indigo-600 px-3 py-2 font-semibold text-white">
                <i class="fa-solid fa-calendar-check w-5 text-amber-300" aria-hidden="true"></i>
                <span>Feature 7: Consultations</span>
            </a>
        </nav>
        <div class="border-t border-slate-800 bg-slate-950/60 px-4 py-3 text-xs text-slate-400">
            @auth
                <div class="mb-2">
                    <div class="truncate font-semibold text-white">{{ auth()->user()->name }}</div>
                    <div class="uppercase text-[11px] text-indigo-400">
                        {{ auth()->user()->role }} · ID: {{ auth()->user()->student_or_faculty_id }}
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full rounded bg-rose-600 py-1.5 font-semibold text-white hover:bg-rose-700">
                        <i class="fa-solid fa-right-from-bracket mr-1" aria-hidden="true"></i> Logout
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block w-full rounded bg-indigo-600 py-1.5 text-center font-medium text-white hover:bg-indigo-700">
                    Login
                </a>
            @endauth
        </div>
    </aside>

    <main class="min-w-0 flex-1 p-4 sm:p-6 md:p-8" id="mainContent">
        @if(session('success'))
            <div data-flash-alert role="status" aria-live="polite" class="mb-6 flex items-center justify-between rounded border-l-4 border-emerald-500 bg-emerald-50 p-4 text-emerald-900">
                <span>{{ session('success') }}</span>
                <button type="button" aria-label="Dismiss success alert" onclick="this.parentElement.remove()">×</button>
            </div>
        @endif
        @if(session('error'))
            <div data-flash-alert role="alert" aria-live="assertive" class="mb-6 flex items-center justify-between rounded border-l-4 border-rose-500 bg-rose-50 p-4 text-rose-900">
                <span>{{ session('error') }}</span>
                <button type="button" aria-label="Dismiss error alert" onclick="this.parentElement.remove()">×</button>
            </div>
        @endif
        @if($errors->any())
            <div role="alert" aria-live="assertive" class="mb-6 rounded border-l-4 border-amber-500 bg-amber-50 p-4 text-amber-900">
                <p class="mb-1 font-bold">Please fix the following form errors:</p>
                <ul class="list-disc pl-5 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>

    <script>
        setTimeout(() => {
            document.querySelectorAll('[data-flash-alert]').forEach((alert) => {
                alert.style.transition = 'opacity 0.4s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 400);
            });
        }, 5000);
    </script>
</body>
</html>
