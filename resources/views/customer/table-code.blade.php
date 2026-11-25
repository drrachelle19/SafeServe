<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#062a20">
    <title>Enter table code · SafeServe</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ivory-50 font-sans text-charcoal-900 antialiased">
    <header class="bg-forest-950 text-white">
        <div class="mx-auto flex max-w-3xl items-center gap-3 px-4 py-6 sm:px-8">
            <span class="grid size-10 place-items-center rounded-xl bg-white/10 text-sm font-bold tracking-widest">SS</span>
            <span>
                <span class="block text-xs font-bold uppercase tracking-[0.3em] text-emerald-300">SafeServe</span>
                <span class="block text-xs text-white/60">Dine Smart. Dine Safe.</span>
            </span>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-10 sm:px-8">
        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-900" role="alert">
                <p class="font-semibold">Please check the table code:</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-3xl border border-forest-900/10 bg-white p-6 shadow-sm sm:p-10">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-emerald-700">Customer menu</p>
            <h1 class="mt-3 text-3xl font-semibold text-forest-950">Enter your table code</h1>
            <p class="mt-3 text-sm leading-7 text-charcoal-700/75">
                Type the code shown on the pad at your table, for example <code class="rounded bg-ivory-100 px-1 py-0.5 font-semibold text-forest-900">T01</code>.
                You can also scan the QR code on your table to open the same menu.
            </p>

            <form method="POST" action="{{ route('customer.table-code.store') }}" class="mt-8 space-y-5">
                @csrf
                <div>
                    <label for="table_code" class="block text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Table code</label>
                    <input id="table_code" name="table_code" type="text" maxlength="100" autocomplete="off" autocapitalize="characters" required
                        value="{{ old('table_code') }}"
                        placeholder="T01"
                        class="mt-2 w-full rounded-xl border border-forest-900/15 bg-ivory-50 px-4 py-3 text-sm outline-none focus:border-forest-700 focus:ring-2 focus:ring-forest-700/20">
                </div>
                <button type="submit" class="rounded-xl bg-forest-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-forest-800 focus:outline-none focus:ring-2 focus:ring-forest-700 focus:ring-offset-2">
                    Open menu
                </button>
            </form>

            <p class="mt-6 text-xs leading-6 text-charcoal-700/60">
                If two tables share the same number, use the full code printed on the pad, such as <code class="font-semibold text-forest-900">safeserve-demo:T01</code>.
            </p>
        </section>
    </main>
</body>
</html>
