@extends('staff.layout')

@section('title', 'Staff sign in · SafeServe')
@section('heading', 'Staff sign in')

@section('content')
    <div class="mx-auto grid max-w-5xl overflow-hidden rounded-3xl border border-forest-900/10 bg-white shadow-2xl shadow-forest-950/10 md:grid-cols-2">
        <div class="bg-forest-950 p-10 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-emerald-300">SafeServe staff portal</p>
            <h2 class="mt-6 text-3xl font-semibold leading-tight">Every allergy alert, visible before the plate leaves the kitchen.</h2>
            <p class="mt-4 text-sm leading-7 text-white/70">
                Review declared allergies, acknowledge ingredient risks, and move orders from pending to ready in one focused workspace.
            </p>
            <p class="mt-10 rounded-2xl border border-white/10 bg-white/5 p-4 text-xs leading-6 text-white/70">
                SafeServe supports staff review. It does not guarantee that food is free from allergens or cross-contact.
            </p>
        </div>

        <div class="p-8 sm:p-10">
            <h3 class="text-2xl font-semibold text-forest-950">Welcome back</h3>
            <p class="mt-2 text-sm text-charcoal-700">Sign in to manage orders and allergy alerts.</p>

            <form method="POST" action="{{ route('staff.login.store') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-semibold text-forest-950">Email address</label>
                    <input id="email" name="email" type="email" autocomplete="username" required autofocus
                        value="{{ old('email') }}"
                        class="mt-2 w-full rounded-xl border border-forest-900/15 bg-ivory-50 px-4 py-3 text-sm outline-none transition focus:border-forest-700 focus:ring-2 focus:ring-forest-700/20">
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-forest-950">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                        class="mt-2 w-full rounded-xl border border-forest-900/15 bg-ivory-50 px-4 py-3 text-sm outline-none transition focus:border-forest-700 focus:ring-2 focus:ring-forest-700/20">
                </div>

                <label class="flex items-center gap-3 text-sm text-charcoal-700">
                    <input type="checkbox" name="remember" value="1" class="size-4 rounded border-forest-900/30 text-forest-800 focus:ring-forest-700">
                    Keep me signed in
                </label>

                <button type="submit" class="w-full rounded-xl bg-forest-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-forest-800 focus:outline-none focus:ring-2 focus:ring-forest-700 focus:ring-offset-2">
                    Sign in securely
                </button>
            </form>

            <div class="mt-8 rounded-2xl bg-ivory-100 p-4 text-xs leading-6 text-charcoal-700">
                <p class="font-semibold text-forest-950">Demo accounts</p>
                <p>Manager: manager@safeserve.test</p>
                <p>Kitchen: kitchen@safeserve.test</p>
                <p>Password: password</p>
            </div>
        </div>
    </div>
@endsection
