@extends('staff.layout')

@section('title', 'Table QR codes · SafeServe')
@section('heading', 'Table QR codes')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm text-charcoal-700">Printable codes for {{ $restaurant->name }}.</p>
            <p class="mt-1 text-xs text-charcoal-700/70">Each code opens the live menu for its table and carries that table number with submitted orders.</p>
        </div>
        <button type="button" onclick="window.print()" class="rounded-xl bg-forest-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-forest-800 focus:outline-none focus:ring-2 focus:ring-forest-700 focus:ring-offset-2 print:hidden">
            Print table codes
        </button>
    </div>

    <div class="mt-8 rounded-2xl border border-forest-900/10 bg-white p-5 text-sm leading-7 text-charcoal-700 print:hidden">
        <p class="font-semibold text-forest-950">Before placing these on tables</p>
        <p>Scan each printed code with a phone camera and confirm it opens the correct table menu. Replace any damaged or faded code.</p>
        <p>QR codes open <code class="rounded bg-ivory-100 px-1 py-0.5">{{ config('app.url') }}</code>. For phone testing, set <code class="rounded bg-ivory-100 px-1 py-0.5">APP_URL</code> to your computer's network address and run the server on <code class="rounded bg-ivory-100 px-1 py-0.5">0.0.0.0</code>.</p>
    </div>

    <section class="mt-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-3 print:grid-cols-2 print:gap-4" aria-label="Printable table QR codes">
        @forelse ($tables as $table)
            <article class="rounded-3xl border border-forest-900/10 bg-white p-6 text-center shadow-sm print:break-inside-avoid print:shadow-none">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-forest-700">Table</p>
                <h2 class="mt-2 text-3xl font-semibold text-forest-950">{{ $table->table_number }}</h2>

                @if ($table->menu_url)
                    <img src="{{ route('staff.tables.qr.image', $table->id) }}"
                        alt="QR code for Table {{ $table->table_number }}"
                        class="mx-auto mt-6 size-48 rounded-2xl border border-forest-900/10 bg-white p-3 print:size-40">
                    <a href="{{ $table->menu_url }}" target="_blank" rel="noopener noreferrer"
                        class="mt-5 block break-all text-xs font-semibold text-forest-700 underline decoration-forest-700/30 underline-offset-4 hover:text-forest-900 print:hidden">
                        Preview customer menu ↗
                    </a>
                    <p class="mt-2 break-all text-[10px] text-charcoal-700/60 print:mt-5 print:text-xs">{{ $table->menu_url }}</p>
                @else
                    <div class="mx-auto mt-6 grid size-48 place-items-center rounded-2xl border border-dashed border-charcoal-900/20 bg-ivory-100 p-6 text-xs text-charcoal-700/70">
                        No QR code assigned to this table.
                    </div>
                @endif

                <p class="mt-4 text-xs font-medium {{ $table->is_active ? 'text-emerald-800' : 'text-red-800' }}">
                    {{ $table->is_active ? 'Active' : 'Inactive' }}
                </p>
            </article>
        @empty
            <div class="rounded-3xl border border-forest-900/10 bg-white px-8 py-16 text-center shadow-sm sm:col-span-2 xl:col-span-3">
                <p class="text-lg font-semibold text-forest-950">No tables found.</p>
                <p class="mt-2 text-sm text-charcoal-700/70">Add restaurant tables before printing QR codes.</p>
            </div>
        @endforelse
    </section>
@endsection
