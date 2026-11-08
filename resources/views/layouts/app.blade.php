<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniNexus — CSE 470 MVC Thesis, Project & Lab Management</title>
    <!-- Locally compiled CSS asset bundle (Improvement #6) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col md:flex-row">
    @php
        $currentUser = auth()->user();
        $userRole = $currentUser?->role ?? 'guest';
        $unreadNotificationCount = $currentUser
            ? \App\Models\SystemNotification::where('user_id', $currentUser->id)->where('is_read', false)->count()
            : 0;
        $navClass = function (string $routePattern) {
            return request()->routeIs($routePattern)
                ? 'flex items-center space-x-3 px-3 py-2 rounded-lg bg-indigo-600 text-white font-semibold shadow-sm'
                : 'flex items-center space-x-3 px-3 py-2 rounded-lg text-slate-200 hover:bg-slate-800';
        };
    @endphp

    <!-- Mobile Top Header -->
    <div class="md:hidden bg-slate-900 text-white px-4 py-3 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span class="bg-indigo-600 text-white px-2 py-1 rounded font-bold text-sm">UN</span>
            <span class="font-bold text-sm">UniNexus MVC</span>
        </div>
        <button type="button" aria-label="Toggle navigation menu" onclick="document.getElementById('sidebarNav').classList.toggle('hidden')" class="text-slate-200 hover:text-white p-1.5 rounded focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <i class="fa-solid fa-bars text-lg" aria-hidden="true"></i>
        </button>
    </div>

    <!-- Sidebar Navigation (Role-Aware + All 20 Features Mapped by Member Module) -->
    <aside id="sidebarNav" class="hidden md:flex w-full md:w-72 bg-slate-900 text-slate-100 flex-shrink-0 flex-col md:sticky md:top-0 md:h-screen overflow-y-auto print:hidden">
        <div class="p-4 border-b border-slate-800 flex items-center space-x-3">
            <div class="bg-indigo-600 text-white p-2 rounded-lg font-bold text-xl">UN</div>
            <div>
                <h1 class="font-bold text-lg tracking-wide">UniNexus MVC</h1>
                <p class="text-xs text-slate-400">CSE 470 • Group 3 (Sec 09)</p>
            </div>
        </div>

        <nav class="p-3 space-y-1 text-sm">
            <a href="{{ route('dashboard') }}" class="{{ $navClass('dashboard') }}">
                <i class="fa-solid fa-gauge-high w-5 text-indigo-300"></i>
                <span>Overview Dashboard</span>
            </a>

            <div class="pt-2.5 pb-1 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Prattay (M1) • F1–F4, F20</div>
            <a href="{{ route('profiles.index') }}" class="{{ $navClass('profiles.*') }}">
                <i class="fa-solid fa-id-card w-5 text-emerald-400"></i>
                <span>F1: Profile & Skill Matrix</span>
            </a>
            <a href="{{ route('teams.index') }}" class="{{ $navClass('teams.*') }}">
                <i class="fa-solid fa-users-gear w-5 text-emerald-400"></i>
                <span>F2: Smart Team Finder</span>
            </a>
            <a href="{{ route('proposals.index') }}" class="{{ $navClass('proposals.*') }}">
                <i class="fa-solid fa-file-shield w-5 text-emerald-400"></i>
                <span>F3 & F4: Bidding & Similarity</span>
            </a>
            <a href="{{ route('analytics.index') }}" class="{{ $navClass('analytics.*') }} justify-between">
                <span class="flex items-center space-x-3">
                    <i class="fa-solid fa-chart-pie w-5 text-emerald-400"></i>
                    <span>F20: Cohort Analytics</span>
                </span>
                @if($userRole !== 'coordinator')
                    <span class="text-[10px] uppercase px-1.5 py-0.5 rounded bg-slate-800 text-slate-400">Coord</span>
                @endif
            </a>

            <div class="pt-2.5 pb-1 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Rownak (M2) • F9, F15–F18</div>
            <a href="{{ route('documents.index') }}" class="{{ $navClass('documents.*') }}">
                <i class="fa-solid fa-code-branch w-5 text-sky-400"></i>
                <span>F9: SRS & Doc Versioning</span>
            </a>
            <a href="{{ route('notifications.index') }}" class="{{ $navClass('notifications.*') }} justify-between">
                <span class="flex items-center space-x-3">
                    <i class="fa-solid fa-bell w-5 text-sky-400"></i>
                    <span>F15: Deadline Broadcasts</span>
                </span>
                @if($unreadNotificationCount > 0)
                    <span class="bg-rose-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $unreadNotificationCount }}</span>
                @endif
            </a>
            <a href="{{ route('archive.index') }}" class="{{ $navClass('archive.*') }}">
                <i class="fa-solid fa-box-archive w-5 text-sky-400"></i>
                <span>F16: Digital Showcase Archive</span>
            </a>
            <a href="{{ route('compliance.index') }}" class="{{ $navClass('compliance.*') }}">
                <i class="fa-solid fa-robot w-5 text-sky-400"></i>
                <span>F17: AI-Use Compliance</span>
            </a>
            <a href="{{ route('disputes.index') }}" class="{{ $navClass('disputes.*') }}">
                <i class="fa-solid fa-scale-balanced w-5 text-sky-400"></i>
                <span>F18: Scope Change & Disputes</span>
            </a>

            <div class="pt-2.5 pb-1 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Samara (M3) • F7, F8, F10, F11, F14</div>
            <a href="{{ route('consultations.index') }}" class="{{ $navClass('consultations.*') }}">
                <i class="fa-solid fa-calendar-check w-5 text-amber-400"></i>
                <span>F7 & F8: Slots & Minutes</span>
            </a>
            <a href="{{ route('evaluations.index') }}" class="{{ $navClass('evaluations.*') }}">
                <i class="fa-solid fa-award w-5 text-amber-400"></i>
                <span>F10 & F11: Rubric & Peer Eval</span>
            </a>
            <a href="{{ route('defense.index') }}" class="{{ $navClass('defense.*') }}">
                <i class="fa-solid fa-chalkboard-user w-5 text-amber-400"></i>
                <span>F14: Defense Panel Scheduler</span>
            </a>

            <div class="pt-2.5 pb-1 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Siddique (M4) • F5, F6, F12, F13, F19</div>
            <a href="{{ route('sprints.index') }}" class="{{ $navClass('sprints.*') }}">
                <i class="fa-solid fa-bars-progress w-5 text-rose-400"></i>
                <span>F5 & F6: Weekly Sprints</span>
            </a>
            <a href="{{ route('inventory.index') }}" class="{{ $navClass('inventory.*') }}">
                <i class="fa-solid fa-microchip w-5 text-rose-400"></i>
                <span>F12 & F13: Lab Inventory & Fine</span>
            </a>
            <a href="{{ route('clearance.index') }}" class="{{ $navClass('clearance.*') }}">
                <i class="fa-solid fa-certificate w-5 text-rose-400"></i>
                <span>F19: Clearance Sheet Generator</span>
            </a>
        </nav>

        <div class="px-4 py-3 border-t border-slate-800 bg-slate-950/60 text-xs text-slate-400">
            @auth
                <div class="flex items-center justify-between mb-2">
                    <div class="truncate pr-2">
                        <div class="font-semibold text-white truncate">{{ auth()->user()->name }}</div>
                        <div class="uppercase text-[11px] text-indigo-400">{{ auth()->user()->role }} • ID: {{ auth()->user()->student_or_faculty_id }}</div>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white py-1.5 rounded text-center font-semibold transition">
                        <i class="fa-solid fa-right-from-bracket mr-1"></i> Logout
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white py-1.5 rounded text-center font-medium">
                    Login / Register
                </a>
            @endauth
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Utility Bar with Role Badge & F15 Notification Bell -->
        @auth
        <header class="bg-white border-b border-slate-200 px-8 py-3 flex items-center justify-between print:hidden">
            <div class="flex items-center space-x-3 text-xs text-slate-500">
                <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 font-semibold uppercase tracking-wide">
                    <i class="fa-solid fa-user-shield mr-1 text-indigo-600"></i> Role: {{ auth()->user()->role }}
                </span>
                @if(auth()->user()->section)
                    <span class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 font-semibold">
                        Section {{ auth()->user()->section }}
                    </span>
                @endif
            </div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('notifications.index') }}" class="relative inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                    <i class="fa-solid fa-bell mr-1.5 text-indigo-600"></i>
                    <span>Notifications</span>
                    @if($unreadNotificationCount > 0)
                        <span class="ml-2 bg-rose-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ $unreadNotificationCount }}</span>
                    @endif
                </a>
                <a href="{{ route('profiles.index') }}" class="text-xs font-semibold text-slate-600 hover:text-indigo-600">
                    <i class="fa-solid fa-circle-user mr-1"></i> {{ auth()->user()->name }}
                </a>
            </div>
        </header>
        @endauth

        <main class="flex-1 p-4 sm:p-6 md:p-8 overflow-y-auto" id="mainContent">
            @if(session('success'))
                <div data-flash-alert role="status" aria-live="polite" class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-900 p-4 rounded shadow-sm flex items-center justify-between">
                    <div><i class="fa-solid fa-circle-check mr-2 text-emerald-600" aria-hidden="true"></i> {{ session('success') }}</div>
                    <button type="button" aria-label="Dismiss success alert" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 text-xs font-bold ml-4 p-1">✕</button>
                </div>
            @endif

            @if(session('error'))
                <div data-flash-alert role="alert" aria-live="assertive" class="mb-6 bg-rose-50 border-l-4 border-rose-500 text-rose-900 p-4 rounded shadow-sm flex items-center justify-between">
                    <div><i class="fa-solid fa-triangle-exclamation mr-2 text-rose-600" aria-hidden="true"></i> {{ session('error') }}</div>
                    <button type="button" aria-label="Dismiss error alert" onclick="this.parentElement.remove()" class="text-rose-700 hover:text-rose-900 text-xs font-bold ml-4 p-1">✕</button>
                </div>
            @endif

            @if($errors->any())
                <div role="alert" aria-live="assertive" class="mb-6 bg-amber-50 border-l-4 border-amber-500 text-amber-900 p-4 rounded shadow-sm">
                    <div class="font-bold text-xs uppercase tracking-wide mb-1">Please fix the following form errors:</div>
                    <ul class="list-disc pl-5 text-sm">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        setTimeout(() => {
            document.querySelectorAll('[data-flash-alert]').forEach(el => {
                el.style.transition = 'opacity 0.4s ease';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 400);
            });
        }, 5000);
    </script>
</body>
</html>
