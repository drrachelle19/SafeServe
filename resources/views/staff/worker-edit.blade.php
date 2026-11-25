@extends('staff.layout')

@section('title', 'Edit worker · SafeServe')
@section('heading', 'Edit worker')

@section('content')
    <section class="max-w-2xl rounded-3xl border border-forest-900/10 bg-white p-6 shadow-sm" aria-labelledby="edit-worker-heading">
        <h2 id="edit-worker-heading" class="text-lg font-semibold text-forest-950">Edit worker details</h2>
        <p class="mt-1 text-sm text-charcoal-700/75">Update the name, email, or phone number for {{ $workerRow->name }}.</p>

        <form method="POST" action="{{ route('staff.workers.update', $workerRow->id) }}" class="mt-6 space-y-5">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Worker name</label>
                <input id="name" name="name" type="text" maxlength="100" autocomplete="name" required
                    value="{{ old('name', $workerRow->name) }}"
                    class="mt-2 w-full rounded-xl border border-forest-900/15 bg-ivory-50 px-4 py-2.5 text-sm outline-none focus:border-forest-700 focus:ring-2 focus:ring-forest-700/20">
            </div>
            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Email</label>
                <input id="email" name="email" type="email" maxlength="255" autocomplete="email" required
                    value="{{ old('email', $workerRow->email) }}"
                    class="mt-2 w-full rounded-xl border border-forest-900/15 bg-ivory-50 px-4 py-2.5 text-sm outline-none focus:border-forest-700 focus:ring-2 focus:ring-forest-700/20">
            </div>
            <div>
                <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Phone number <span class="normal-case tracking-normal text-charcoal-700/50">(optional)</span></label>
                <input id="phone" name="phone" type="tel" maxlength="30" autocomplete="tel"
                    value="{{ old('phone', $workerRow->phone) }}"
                    class="mt-2 w-full rounded-xl border border-forest-900/15 bg-ivory-50 px-4 py-2.5 text-sm outline-none focus:border-forest-700 focus:ring-2 focus:ring-forest-700/20">
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-xl bg-forest-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-800">
                    Save changes
                </button>
                <a href="{{ route('staff.workers.index') }}" class="rounded-xl border border-forest-900/15 px-5 py-2.5 text-sm font-semibold text-forest-900 transition hover:bg-forest-900/5">
                    Cancel
                </a>
            </div>
        </form>
    </section>
@endsection
