<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#062a20">
    <title>@yield('title', 'SafeServe Staff')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-ivory-50 font-sans text-charcoal-900 antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:shadow-lg">
        Skip to content
    </a>

    @auth
        <aside class="fixed inset-y-0 left-0 hidden w-64 flex-col bg-forest-950 px-6 py-8 text-white lg:flex">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-white/10 text-lg font-bold">S</span>
                <span>
                    <span class="block text-sm font-bold tracking-[0.2em]">SAFESERVE</span>
                    <span class="block text-xs text-white/60">Dine Smart. Dine Safe.</span>
                </span>
            </a>

            <nav class="mt-10 flex flex-1 flex-col gap-1" aria-label="Staff navigation">
                <a href="{{ route('dashboard') }}" @class([
                    'rounded-xl px-4 py-3 text-sm font-medium transition',
                    'bg-white/10 text-white' => request()->routeIs('dashboard'),
                    'text-white/70 hover:bg-white/5 hover:text-white' => ! request()->routeIs('dashboard'),
                ]) @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                    Overview
                </a>
                <a href="{{ route('kitchen') }}" @class([
                    'rounded-xl px-4 py-3 text-sm font-medium transition',
                    'bg-white/10 text-white' => request()->routeIs('kitchen*'),
                    'text-white/70 hover:bg-white/5 hover:text-white' => ! request()->routeIs('kitchen*'),
                ]) @if(request()->routeIs('kitchen*')) aria-current="page" @endif>
                    Kitchen board
                </a>
                @can('manage-restaurant')
                    <a href="{{ route('staff.reports.sales') }}" @class([
                        'rounded-xl px-4 py-3 text-sm font-medium transition',
                        'bg-white/10 text-white' => request()->routeIs('staff.reports.sales*'),
                        'text-white/70 hover:bg-white/5 hover:text-white' => ! request()->routeIs('staff.reports.sales*'),
                    ]) @if(request()->routeIs('staff.reports.sales*')) aria-current="page" @endif>
                        Sales report
                    </a>
                    <a href="{{ route('staff.tables.qr') }}" @class([
                        'rounded-xl px-4 py-3 text-sm font-medium transition',
                        'bg-white/10 text-white' => request()->routeIs('staff.tables.qr*'),
                        'text-white/70 hover:bg-white/5 hover:text-white' => ! request()->routeIs('staff.tables.qr*'),
                    ]) @if(request()->routeIs('staff.tables.qr*')) aria-current="page" @endif>
                        Table QR codes
                    </a>
                    <a href="{{ route('staff.workers.index') }}" @class([
                        'rounded-xl px-4 py-3 text-sm font-medium transition',
                        'bg-white/10 text-white' => request()->routeIs('staff.workers.*'),
                        'text-white/70 hover:bg-white/5 hover:text-white' => ! request()->routeIs('staff.workers.*'),
                    ]) @if(request()->routeIs('staff.workers.*')) aria-current="page" @endif>
                        Workers
                    </a>
                @endcan
            </nav>

            <div class="border-t border-white/10 pt-6">
                <p class="text-sm font-semibold">{{ Auth::user()->name }}</p>
                <p class="mt-1 text-xs capitalize text-white/60">{{ Auth::user()->role }}</p>
                <form method="POST" action="{{ route('staff.logout') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full rounded-xl border border-white/20 px-4 py-2 text-sm font-medium text-white/80 transition hover:bg-white/10 hover:text-white">
                        Sign out
                    </button>
                </form>
            </div>
        </aside>
    @endauth

    <div @class(['min-h-screen', 'lg:pl-64' => Auth::check()])>
        @auth
            <header class="sticky top-0 z-30 border-b border-forest-900/10 bg-ivory-50/95 backdrop-blur">
                <div class="flex flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-8">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-forest-700">SafeServe operations</p>
                        <h1 class="mt-1 text-xl font-semibold text-forest-950">@yield('heading', 'Restaurant dashboard')</h1>
                    </div>
                    <nav class="flex items-center gap-2 lg:hidden" aria-label="Mobile navigation">
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-forest-900 px-3 py-2 text-xs font-semibold text-white">Overview</a>
                        <a href="{{ route('kitchen') }}" class="rounded-lg border border-forest-900/15 px-3 py-2 text-xs font-semibold text-forest-900">Kitchen</a>
                        @can('manage-restaurant')
                            <a href="{{ route('staff.reports.sales') }}" class="rounded-lg border border-forest-900/15 px-3 py-2 text-xs font-semibold text-forest-900">Reports</a>
                            <a href="{{ route('staff.tables.qr') }}" class="rounded-lg border border-forest-900/15 px-3 py-2 text-xs font-semibold text-forest-900">QR</a>
                            <a href="{{ route('staff.workers.index') }}" class="rounded-lg border border-forest-900/15 px-3 py-2 text-xs font-semibold text-forest-900">Workers</a>
                        @endcan
                        <form method="POST" action="{{ route('staff.logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-forest-900/15 px-3 py-2 text-xs font-semibold text-forest-900">Exit</button>
                        </form>
                    </nav>
                    <div class="hidden items-center gap-3 lg:flex">
                        <span class="rounded-full bg-forest-900/5 px-3 py-1 text-xs font-medium text-forest-800">
                            {{ now()->format('D, M j · H:i') }}
                        </span>
                    </div>
                </div>
            </header>
        @endauth

        <main id="main-content" class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-900" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-900" role="alert">
                    <p class="font-semibold">Please check the form:</p>
                    <ul class="mt-2 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
