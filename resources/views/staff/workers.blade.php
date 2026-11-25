@extends('staff.layout')

@section('title', 'Workers · SafeServe')
@section('heading', 'Workers')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm text-charcoal-700">Registered kitchen workers are chosen in rotation when an order becomes Completed.</p>
            <p class="mt-1 text-xs text-charcoal-700/70">Only active workers are included in the automatic assignment.</p>
        </div>
        <span class="rounded-full bg-forest-900 px-4 py-2 text-xs font-semibold text-white">
            {{ $workers->where('is_active', true)->count() }} active / {{ $workers->count() }} total
        </span>
    </div>

    @if ($lastAssignedWorker)
        <div class="mt-6 rounded-2xl border border-forest-900/10 bg-white px-5 py-4 text-sm text-charcoal-800 shadow-sm" role="status">
            Last automatically assigned worker: <span class="font-semibold text-forest-950">{{ $lastAssignedWorker }}</span>
        </div>
    @endif

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="rounded-3xl border border-forest-900/10 bg-white p-6 shadow-sm" aria-labelledby="add-worker-heading">
            <h2 id="add-worker-heading" class="text-lg font-semibold text-forest-950">Add one worker</h2>
            <p class="mt-1 text-sm text-charcoal-700/75">Creates a kitchen staff account for this restaurant.</p>

            <form method="POST" action="{{ route('staff.workers.store') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Worker name</label>
                    <input id="name" name="name" type="text" maxlength="100" autocomplete="name" required
                        value="{{ old('name') }}"
                        class="mt-2 w-full rounded-xl border border-forest-900/15 bg-ivory-50 px-4 py-2.5 text-sm outline-none focus:border-forest-700 focus:ring-2 focus:ring-forest-700/20">
                </div>
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Email</label>
                    <input id="email" name="email" type="email" maxlength="255" autocomplete="email" required
                        value="{{ old('email') }}"
                        class="mt-2 w-full rounded-xl border border-forest-900/15 bg-ivory-50 px-4 py-2.5 text-sm outline-none focus:border-forest-700 focus:ring-2 focus:ring-forest-700/20">
                </div>
                <div>
                    <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Phone number <span class="normal-case tracking-normal text-charcoal-700/50">(optional)</span></label>
                    <input id="phone" name="phone" type="tel" maxlength="30" autocomplete="tel"
                        value="{{ old('phone') }}"
                        class="mt-2 w-full rounded-xl border border-forest-900/15 bg-ivory-50 px-4 py-2.5 text-sm outline-none focus:border-forest-700 focus:ring-2 focus:ring-forest-700/20">
                </div>
                <button type="submit" class="rounded-xl bg-forest-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-800">
                    Add worker
                </button>
            </form>
        </section>

        <section class="rounded-3xl border border-forest-900/10 bg-white p-6 shadow-sm" aria-labelledby="upload-workers-heading">
            <h2 id="upload-workers-heading" class="text-lg font-semibold text-forest-950">Upload registered workers</h2>
            <p class="mt-1 text-sm text-charcoal-700/75">Bulk-add up to 100 workers from a CSV file.</p>

            <div class="mt-4 rounded-xl border border-forest-900/10 bg-forest-950/5 px-4 py-3 text-xs text-charcoal-700">
                CSV columns: <code class="font-semibold text-forest-950">name,email</code> with optional <code class="font-semibold text-forest-950">phone</code>
            </div>

            <form method="POST" action="{{ route('staff.workers.upload') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="workers_csv" class="block text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">CSV file</label>
                    <input id="workers_csv" name="workers_csv" type="file" accept=".csv,text/csv" required
                        class="mt-2 w-full rounded-xl border border-forest-900/15 bg-ivory-50 px-4 py-2.5 text-sm outline-none focus:border-forest-700 focus:ring-2 focus:ring-forest-700/20">
                </div>
                <button type="submit" class="rounded-xl bg-forest-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-800">
                    Upload workers
                </button>
            </form>
        </section>
    </div>

    <section class="mt-12" aria-labelledby="worker-list-heading">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 id="worker-list-heading" class="text-xl font-semibold text-forest-950">Registered workers</h2>
                <p class="mt-1 text-sm text-charcoal-700/75">Pause a worker to skip them in the rotation without deleting their record.</p>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto rounded-3xl border border-forest-900/10 bg-white shadow-sm">
            <table class="w-full min-w-[640px] divide-y divide-forest-900/10">
                <caption class="sr-only">Registered kitchen workers</caption>
                <thead class="bg-forest-950/5">
                    <tr>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Worker</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Email</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Phone</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Status</th>
                        <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-forest-900/10">
                    @forelse ($workers as $worker)
                        <tr>
                            <td class="px-5 py-4 text-sm font-semibold text-forest-950">{{ $worker->name }}</td>
                            <td class="px-5 py-4 text-sm text-charcoal-700">{{ $worker->email }}</td>
                            <td class="px-5 py-4 text-sm text-charcoal-700">{{ $worker->phone ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-medium {{ $worker->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-charcoal-900/10 text-charcoal-700' }}">
                                    {{ $worker->is_active ? 'Active' : 'Paused' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <a href="{{ route('staff.workers.edit', $worker->id) }}" class="rounded-lg border border-forest-900/15 px-4 py-2 text-xs font-semibold text-forest-900 transition hover:bg-forest-900/5">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('staff.workers.toggle', $worker->id) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg border border-forest-900/15 px-4 py-2 text-xs font-semibold text-forest-900 transition hover:bg-forest-900/5">
                                            {{ $worker->is_active ? 'Pause' : 'Resume' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('staff.workers.destroy', $worker->id) }}" onsubmit="return confirm('Delete this worker from the list?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-red-900/20 px-4 py-2 text-xs font-semibold text-red-800 transition hover:bg-red-50">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-charcoal-700/70">No workers registered yet. Add one above or upload a CSV.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
