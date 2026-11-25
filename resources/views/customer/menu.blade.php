<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#062a20">
    <title>{{ $restaurant->name }} | SafeServe</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ivory-50 font-sans text-charcoal-900 antialiased">
    @php
        $welcomeEyebrowWords = explode(' ', 'Karibu · Welcome to');
        $restaurantNameWords = explode(' ', $restaurant->name);
        $welcomeMessageWords = explode(' ', 'Settle in, take your time, and choose what you would love today. We will follow your plate from the kitchen all the way to your table.');
        $welcomeBadgeWords = explode(' ', 'Karibu, served with care');
        $menuHeadingWords = explode(' ', 'What sounds good today?');
        $menuIntroWords = explode(' ', 'Pick the dishes you love, tell us what your body needs to avoid, and we will send everything straight to the kitchen. Your note travels with your plate.');
    @endphp

    <header class="bg-forest-950 text-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-6 sm:px-8">
            <div class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-white/10 text-sm font-bold tracking-widest ring-1 ring-white/15">SS</span>
                <span>
                    <span class="block text-xs font-bold uppercase tracking-[0.3em] text-emerald-300">SafeServe</span>
                    <span class="block text-xs text-white/60">Dine Smart. Dine Safe.</span>
                </span>
            </div>
            <div class="flex flex-col items-end gap-2">
                <span class="rounded-full border border-white/20 bg-white/5 px-4 py-2 text-xs font-semibold text-white/90">
                    Table {{ $table->table_number }}
                </span>
                <a href="{{ route('customer.table-code') }}" class="text-xs text-white/70 underline decoration-white/30 underline-offset-4 transition hover:text-white">
                    Enter a different table code
                </a>
            </div>
        </div>

        <div class="relative border-t border-white/10 bg-forest-900/40">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-8 sm:py-10 lg:grid-cols-[1fr_auto] lg:items-end">
                <div class="animate-welcome-rise">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-emerald-300">
                        @foreach ($welcomeEyebrowWords as $index => $word)
                            <span class="inline-block animate-word-fade" style="animation-delay: {{ 60 + $index * 70 }}ms">{{ $word }}</span>
                        @endforeach
                    </p>
                    <h1 class="mt-2 text-3xl font-semibold text-balance sm:text-5xl">
                        @foreach ($restaurantNameWords as $index => $word)
                            <span class="inline-block animate-word-fade" style="animation-delay: {{ 180 + $index * 90 }}ms">{{ $word }}</span>
                        @endforeach
                    </h1>
                    <p class="mt-3 max-w-xl text-sm leading-7 text-white/70">
                        @foreach ($welcomeMessageWords as $index => $word)
                            <span class="inline-block animate-word-fade" style="animation-delay: {{ 380 + $index * 45 }}ms">{{ $word }}</span>
                        @endforeach
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2 animate-welcome-rise [animation-delay:200ms]">
                    <span class="rounded-full border border-emerald-300/30 bg-emerald-300/10 px-4 py-2 text-xs font-semibold text-emerald-200">
                        @foreach ($welcomeBadgeWords as $index => $word)
                            <span class="inline-block animate-word-fade" style="animation-delay: {{ 900 + $index * 80 }}ms">{{ $word }}</span>
                        @endforeach
                    </span>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-8">
        @if (session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-900" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-900" role="alert">
                <p class="font-semibold">Please check your order:</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($orderProgress)
            <section id="orderProgress"
                class="relative mb-8 animate-welcome-rise overflow-hidden rounded-3xl border border-forest-900/10 bg-white p-6 shadow-sm"
                aria-labelledby="order-progress-heading"
                data-order-status-url="{{ route('customer.order.status', $table->qr_code) }}"
                data-order-progress='@json($orderProgress)'>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-emerald-700">Order progress</p>
                        <h2 id="order-progress-heading" class="mt-2 text-xl font-semibold text-forest-950">
                            Order <span data-progress-order-number>{{ $orderProgress['order_number'] }}</span>
                        </h2>
                        <p data-progress-message class="mt-1 text-sm leading-6 text-charcoal-700/75">{{ $orderProgress['message'] }}</p>
                    </div>
                    <span class="flex items-center gap-2 rounded-full bg-forest-900 px-4 py-2 text-xs font-semibold text-white shadow-sm">
                        @unless ($orderProgress['is_final'])
                            <span data-progress-dot class="size-2 rounded-full bg-emerald-300 animate-progress-glow" aria-hidden="true"></span>
                        @endunless
                        <span data-progress-status>{{ $orderProgress['status_label'] }}</span>
                    </span>
                </div>

                <ol class="mt-6 grid gap-3 sm:grid-cols-5" aria-label="Order progress steps">
                    @foreach ($progressSteps as $stepIndex => $step)
                        <li data-progress-step="{{ $stepIndex }}"
                            class="rounded-2xl border px-3 py-3 text-center text-xs font-semibold transition
                                {{ $stepIndex <= $orderProgress['step'] ? 'border-forest-700 bg-forest-900/5 text-forest-900' : 'border-forest-900/10 bg-ivory-50 text-charcoal-700/50' }}">
                            <span class="block text-[11px] font-bold uppercase tracking-wider">Step {{ $stepIndex + 1 }}</span>
                            <span class="mt-1 block">{{ $step['label'] }}</span>
                        </li>
                    @endforeach
                </ol>

                @if ($orderProgress['is_final'])
                    <p data-progress-live-note
                        class="mt-4 rounded-xl px-4 py-3 text-xs font-semibold
                            {{ $orderProgress['status'] === 'cancelled' ? 'bg-red-50 text-red-800' : 'bg-emerald-50 text-emerald-900' }}">
                        {{ $orderProgress['message'] }}
                    </p>
                @else
                    <p data-progress-live-note class="mt-4 text-[11px] text-charcoal-700/60">
                        Live updates are on. Your cart and order form will stay as they are.
                    </p>
                @endif
                <p data-progress-updated hidden class="mt-2 text-[11px] text-charcoal-700/50"></p>
            </section>
        @endif

        <div class="mb-8 flex flex-wrap items-end justify-between gap-4 animate-welcome-rise [animation-delay:80ms]">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-emerald-700">Explore our menu</p>
                <h2 class="mt-2 text-3xl font-semibold text-balance text-forest-950 sm:text-4xl">
                    @foreach ($menuHeadingWords as $index => $word)
                        <span class="inline-block animate-word-fade" style="animation-delay: {{ 120 + $index * 80 }}ms">{{ $word }}</span>
                    @endforeach
                </h2>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-charcoal-700/75">
                    @foreach ($menuIntroWords as $index => $word)
                        <span class="inline-block animate-word-fade" style="animation-delay: {{ 360 + $index * 35 }}ms">{{ $word }}</span>
                    @endforeach
                </p>
            </div>
            <span class="rounded-full bg-forest-900/5 px-4 py-2 text-xs font-medium text-forest-800 ring-1 ring-forest-900/5">
                {{ $menuItems->count() }} item(s) available
            </span>
        </div>

        <div class="grid gap-8 lg:grid-cols-3">
            <section class="lg:col-span-2" aria-label="Restaurant menu">
                <div class="mb-3 flex items-center gap-3">
                    <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-forest-800">Choose your lane</span>
                    <span class="h-px flex-1 bg-forest-900/10"></span>
                </div>

                <div class="categories flex gap-2 overflow-x-auto pb-4" role="group" aria-label="Menu categories">
                    <button type="button"
                        class="category-btn shrink-0 rounded-full border px-4 py-2 text-sm font-semibold transition duration-200 hover:-translate-y-0.5 active:scale-95 bg-forest-900 text-white border-forest-900 shadow-sm"
                        data-category="all">
                        All Items
                    </button>

                    @foreach ($categories as $category)
                        <button type="button"
                            class="category-btn shrink-0 rounded-full border px-4 py-2 text-sm font-semibold transition duration-200 hover:-translate-y-0.5 active:scale-95 bg-white text-forest-900 border-forest-900/15 hover:bg-forest-900/5"
                            data-category="{{ $category->id }}">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>

                <div class="menu-grid grid gap-5 sm:grid-cols-2">
                    @forelse ($menuItems as $item)
                        <article class="food-card group flex animate-card-rise flex-col overflow-hidden rounded-3xl border border-forest-900/10 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-forest-700/30 hover:shadow-xl focus-within:ring-2 focus-within:ring-forest-700/30" style="animation-delay: {{ $loop->index * 45 }}ms" data-category="{{ $item->menu_category_id }}">
                            <div class="relative grid h-32 place-items-center bg-forest-900">
                                <span class="text-4xl font-semibold text-white/90 transition-transform duration-300 group-hover:scale-110">{{ mb_substr($item->name, 0, 1) }}</span>
                                <span class="absolute bottom-3 left-4 text-[10px] font-bold uppercase tracking-[0.3em] text-white/60">SafeServe pick</span>
                            </div>
                            <div class="flex flex-1 flex-col p-5">
                                <h3 class="text-lg font-semibold text-forest-950 transition-colors group-hover:text-forest-700">{{ $item->name }}</h3>
                                <p class="mt-2 flex-1 text-sm leading-6 text-charcoal-700/75">{{ $item->description }}</p>
                                <p class="mt-4 inline-flex self-start rounded-full bg-forest-900/5 px-3 py-1 text-lg font-bold text-forest-900">
                                    {{ $restaurant->currency }} {{ number_format($item->price, 0) }}
                                </p>

                                <div class="mt-4 rounded-xl bg-ivory-100 px-4 py-3 text-xs leading-5 text-charcoal-700/80">
                                    <p>
                                        <span class="font-semibold text-forest-950">Ingredients:</span>
                                        @forelse ($item->ingredients as $ingredient)
                                            {{ $ingredient->name }}@if (! $loop->last), @endif
                                        @empty
                                            Information unavailable
                                        @endforelse
                                    </p>
                                    <p class="mt-2">
                                        <span class="font-semibold text-forest-950">Recorded allergens:</span>
                                        @forelse ($item->allergens as $allergen)
                                            {{ $allergen->name }}@if (! $loop->last), @endif
                                        @empty
                                            No known match recorded
                                        @endforelse
                                    </p>
                                </div>

                                @if (! $item->ingredients_verified)
                                    <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-[11px] leading-5 text-amber-900">
                                        Ingredient information is unverified. Please confirm allergy requirements with staff. Cross-contact is also possible.
                                    </div>
                                @endif

                                <button type="button"
                                    class="add-btn mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-forest-900 px-4 py-3 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-forest-800 hover:shadow-md active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-forest-700 focus:ring-offset-2"
                                    data-id="{{ $item->id }}"
                                    data-name="{{ $item->name }}"
                                    data-price="{{ $item->price }}">
                                    + Add to order
                                </button>
                            </div>
                        </article>
                    @empty
                        <p class="rounded-2xl border border-forest-900/10 bg-white px-6 py-12 text-center text-sm text-charcoal-700/70 sm:col-span-2">
                            No menu items available yet.
                        </p>
                    @endforelse
                </div>
            </section>

            <aside class="relative h-fit animate-welcome-rise overflow-hidden rounded-3xl border border-forest-900/10 bg-white p-6 shadow-sm lg:sticky lg:top-6 [animation-delay:140ms]">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-400 via-forest-700 to-forest-950" aria-hidden="true"></div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-emerald-700">Almost there</p>
                        <h2 class="mt-1 text-xl font-semibold text-forest-950">Your order</h2>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span id="cartCount" class="rounded-full bg-forest-900 px-3 py-1 text-xs font-semibold text-white">0 items</span>
                        <span class="rounded-full bg-forest-900/5 px-3 py-1 text-xs font-medium text-forest-800">Table {{ $table->table_number }}</span>
                    </div>
                </div>
                <p class="mt-3 text-xs leading-5 text-charcoal-700/70">
                    Build your plate, then tell us how to make it safe for you.
                </p>

                <form method="POST" action="{{ route('customer.order', ['qrCode' => $table->qr_code]) }}" id="orderForm" class="mt-6">
                    @csrf
                    <input type="hidden" name="cart_json" id="cartJson">

                    <div id="cartItems" class="max-h-72 overflow-y-auto">
                        <div class="cart-empty py-8 text-center">
                            <p class="text-sm font-semibold text-forest-950">Nothing on the tray yet</p>
                            <p class="mt-1 text-xs text-charcoal-700/70">Add something delicious from the menu.</p>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-forest-900/10 pt-4">
                        <span class="text-sm font-semibold text-charcoal-700/70">Total</span>
                        <span id="cartTotal" class="text-xl font-bold text-forest-950">{{ $restaurant->currency }} 0</span>
                    </div>

                    <div class="mt-6 rounded-2xl border border-emerald-900/10 bg-emerald-50/60 p-4">
                        <p class="text-sm font-semibold text-forest-950">Food allergy declaration</p>
                        <p class="mt-1 text-xs leading-5 text-charcoal-700/75">Select every allergen you need the kitchen to know about.</p>
                        <div class="mt-4 grid gap-2">
                            @foreach ($allergyOptions as $allergen)
                                <label class="flex items-center gap-3 text-sm text-charcoal-900">
                                    <input type="checkbox" name="allergies[]" value="{{ $allergen->id }}" class="size-4 rounded border-forest-900/30 text-forest-800 focus:ring-forest-700">
                                    {{ $allergen->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-5">
                        <label for="customer_name" class="block text-sm font-semibold text-forest-950">Your name (optional)</label>
                        <input type="text" name="customer_name" id="customer_name" maxlength="100" placeholder="Enter your name"
                            class="mt-2 w-full rounded-xl border border-forest-900/15 bg-ivory-50 px-4 py-3 text-sm outline-none transition focus:border-forest-700 focus:ring-2 focus:ring-forest-700/20">
                    </div>

                    <div class="mt-5">
                        <label for="customer_notes" class="block text-sm font-semibold text-forest-950">Special instructions</label>
                        <textarea name="customer_notes" id="customer_notes" maxlength="1000" placeholder="Any additional requests?"
                            class="mt-2 min-h-20 w-full resize-y rounded-xl border border-forest-900/15 bg-ivory-50 px-4 py-3 text-sm outline-none transition focus:border-forest-700 focus:ring-2 focus:ring-forest-700/20"></textarea>
                    </div>

                    <button type="submit" class="mt-6 w-full rounded-xl bg-forest-900 px-5 py-3 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-forest-800 hover:shadow-md active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-forest-700 focus:ring-offset-2">
                        Send my order to the kitchen
                    </button>

                    <p class="mt-4 text-[11px] leading-5 text-charcoal-700/70">
                        Allergy information will be sent to restaurant staff for review. SafeServe does not guarantee that food is free from allergens or cross-contact.
                    </p>
                </form>
            </aside>
        </div>

        <section class="mt-10 animate-welcome-rise rounded-3xl border border-forest-900/10 bg-white p-6 shadow-sm [animation-delay:200ms]" aria-labelledby="cancel-order-heading">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-emerald-700">Need a hand?</p>
                    <h2 id="cancel-order-heading" class="mt-1 text-xl font-semibold text-forest-950">Emergency? Cancel an order</h2>
                </div>
                <span class="rounded-full bg-red-50 px-3 py-1 text-[11px] font-semibold text-red-800">The kitchen will be told</span>
            </div>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-charcoal-700/75">
                Plans changed? No stress. If you need to leave or made a mistake, enter your order number and a short reason. Only orders that are still pending, confirmed, preparing, or ready can be cancelled.
            </p>

            @if ($recentOrder)
                <div class="mt-4 rounded-2xl border border-forest-900/10 bg-ivory-100 px-4 py-3">
                    <p class="text-sm font-semibold text-forest-950">
                        Most recent order on this table: {{ $recentOrder->order_number }} · {{ $restaurant->currency }} {{ number_format($recentOrder->total, 0) }}
                    </p>
                    <p class="mt-1 text-xs text-charcoal-700/75">
                        Status: <span data-cancel-status-label>{{ $orderProgress['status_label'] }}</span>
                        <span data-cancel-non-cancellable @if ($orderProgress['is_cancellable']) hidden @endif>· No longer cancellable — please ask staff for help.</span>
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ route('customer.order.cancel', ['qrCode' => $table->qr_code]) }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                @csrf
                <div>
                    <label for="cancel_order_number" class="block text-sm font-semibold text-forest-950">Order number</label>
                    <input type="text" id="cancel_order_number" name="order_number" maxlength="50" required
                        value="{{ old('order_number', $recentOrder?->order_number) }}"
                        placeholder="SS-XXXXXXXX"
                        class="mt-2 w-full rounded-xl border border-forest-900/15 bg-ivory-50 px-4 py-3 text-sm outline-none transition focus:border-forest-700 focus:ring-2 focus:ring-forest-700/20">
                </div>
                <div>
                    <label for="cancellation_reason" class="block text-sm font-semibold text-forest-950">Reason for cancellation</label>
                    <input type="text" id="cancellation_reason" name="cancellation_reason" maxlength="500" required
                        value="{{ old('cancellation_reason') }}"
                        placeholder="Tell the kitchen what happened"
                        class="mt-2 w-full rounded-xl border border-forest-900/15 bg-ivory-50 px-4 py-3 text-sm outline-none transition focus:border-forest-700 focus:ring-2 focus:ring-forest-700/20">
                </div>
                <button type="submit" data-cancel-submit class="sm:col-span-2 rounded-xl border border-red-300 bg-red-50 px-5 py-3 text-sm font-semibold text-red-800 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                    Cancel this order
                </button>
            </form>
        </section>
    </main>

    <footer class="border-t border-forest-900/10 py-8 text-center text-xs text-charcoal-700/70">
        <p class="font-semibold text-forest-900">Asante for dining with us.</p>
        <p class="mt-1">Powered by SafeServe · Dine Smart. Dine Safe.</p>
    </footer>

    <div id="cartToast" role="status" aria-live="polite" class="pointer-events-none fixed inset-x-4 bottom-4 z-50 mx-auto hidden max-w-sm rounded-2xl bg-forest-950 px-5 py-3 text-center text-sm font-semibold text-white shadow-xl ring-1 ring-white/10 sm:bottom-8"></div>

    <script>
        const currency = @json($restaurant->currency);
        const cart = {};

        const cartItems = document.getElementById('cartItems');
        const cartTotal = document.getElementById('cartTotal');
        const cartJson = document.getElementById('cartJson');
        const cartCount = document.getElementById('cartCount');
        const cartToast = document.getElementById('cartToast');

        let cartToastTimer;

        const activeCategoryClasses = ['bg-forest-900', 'text-white', 'border-forest-900', 'shadow-sm'];
        const inactiveCategoryClasses = ['bg-white', 'text-forest-900', 'border-forest-900/15', 'hover:bg-forest-900/5'];

        function formatMoney(amount) {
            return currency + ' ' + Number(amount).toLocaleString();
        }

        function showCartToast(message) {
            cartToast.textContent = message;
            cartToast.classList.remove('hidden', 'animate-toast-in');
            void cartToast.offsetWidth;
            cartToast.classList.add('animate-toast-in');

            window.clearTimeout(cartToastTimer);
            cartToastTimer = window.setTimeout(() => cartToast.classList.add('hidden'), 1800);
        }

        function renderCart(animateCount = false) {
            const entries = Object.values(cart);
            let total = 0;
            let count = 0;

            if (entries.length === 0) {
                cartItems.innerHTML = '<div class="cart-empty py-8 text-center"><p class="text-sm font-semibold text-forest-950">Nothing on the tray yet</p><p class="mt-1 text-xs text-charcoal-700/70">Add something delicious from the menu.</p></div>';
            } else {
                cartItems.replaceChildren();

                entries.forEach(item => {
                    const lineTotal = item.price * item.quantity;
                    total += lineTotal;
                    count += item.quantity;

                    const row = document.createElement('div');
                    row.className = 'flex items-start justify-between gap-3 border-b border-forest-900/10 py-3 text-sm';

                    const details = document.createElement('div');
                    const name = document.createElement('strong');
                    name.className = 'text-forest-950';
                    name.textContent = item.name;

                    const controls = document.createElement('div');
                    controls.className = 'mt-2 flex flex-wrap items-center gap-2';

                    const minus = document.createElement('button');
                    minus.type = 'button';
                    minus.className = 'qty-btn grid size-6 place-items-center rounded-md border border-forest-900/15 bg-ivory-50 text-sm font-bold text-forest-900';
                    minus.textContent = '−';
                    minus.addEventListener('click', () => changeQuantity(item.id, -1));

                    const quantity = document.createElement('span');
                    quantity.className = 'min-w-5 text-center text-xs font-semibold';
                    quantity.textContent = item.quantity;

                    const plus = document.createElement('button');
                    plus.type = 'button';
                    plus.className = 'qty-btn grid size-6 place-items-center rounded-md border border-forest-900/15 bg-ivory-50 text-sm font-bold text-forest-900';
                    plus.textContent = '+';
                    plus.addEventListener('click', () => changeQuantity(item.id, 1));

                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'remove-btn text-xs font-semibold text-red-700 hover:underline';
                    remove.textContent = 'Remove';
                    remove.addEventListener('click', () => {
                        delete cart[item.id];
                        renderCart(true);
                    });

                    controls.append(minus, quantity, plus, remove);
                    details.append(name, controls);

                    const price = document.createElement('strong');
                    price.className = 'whitespace-nowrap text-forest-900';
                    price.textContent = formatMoney(lineTotal);

                    row.append(details, price);
                    cartItems.append(row);
                });
            }

            cartTotal.textContent = formatMoney(total);
            cartCount.textContent = count === 1 ? '1 item' : count + ' items';

            if (animateCount) {
                cartCount.classList.remove('animate-cart-pop');
                void cartCount.offsetWidth;
                cartCount.classList.add('animate-cart-pop');
            }

            cartJson.value = JSON.stringify(
                entries.map(item => ({
                    id: item.id,
                    quantity: item.quantity
                }))
            );
        }

        function changeQuantity(id, change) {
            if (! cart[id]) return;

            cart[id].quantity += change;

            if (cart[id].quantity <= 0) {
                delete cart[id];
            }

            if (cart[id] && cart[id].quantity > 20) {
                cart[id].quantity = 20;
            }

            renderCart(true);
        }

        document.querySelectorAll('.add-btn').forEach(button => {
            const originalText = button.textContent;

            button.addEventListener('click', () => {
                const id = button.dataset.id;

                if (! cart[id]) {
                    cart[id] = {
                        id: Number(id),
                        name: button.dataset.name,
                        price: Number(button.dataset.price),
                        quantity: 0
                    };
                }

                if (cart[id].quantity < 20) {
                    cart[id].quantity++;
                }

                renderCart(true);

                button.textContent = 'Added ✓';
                window.setTimeout(() => {
                    button.textContent = originalText;
                }, 900);

                showCartToast(button.dataset.name + ' added to your order');
            });
        });

        document.querySelectorAll('.category-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.category-btn').forEach(btn => {
                    btn.classList.remove(...activeCategoryClasses);
                    btn.classList.add(...inactiveCategoryClasses);
                });

                button.classList.remove(...inactiveCategoryClasses);
                button.classList.add(...activeCategoryClasses);

                const selected = button.dataset.category;

                document.querySelectorAll('.food-card').forEach(card => {
                    card.style.display = selected === 'all' || card.dataset.category === selected ? '' : 'none';
                });
            });
        });

        document.getElementById('orderForm').addEventListener('submit', function (event) {
            if (Object.keys(cart).length === 0) {
                event.preventDefault();
                alert('Please add at least one food item to your order.');
                return;
            }

            cartJson.value = JSON.stringify(
                Object.values(cart).map(item => ({
                    id: item.id,
                    quantity: item.quantity
                }))
            );
        });

        (function () {
            const progressPanel = document.getElementById('orderProgress');

            if (! progressPanel) {
                return;
            }

            const statusUrl = progressPanel.dataset.orderStatusUrl;
            let currentProgress = JSON.parse(progressPanel.dataset.orderProgress);
            const statusLabel = progressPanel.querySelector('[data-progress-status]');
            const progressDot = progressPanel.querySelector('[data-progress-dot]');
            const message = progressPanel.querySelector('[data-progress-message]');
            const orderNumber = progressPanel.querySelector('[data-progress-order-number]');
            const liveNote = progressPanel.querySelector('[data-progress-live-note]');
            const updatedAt = progressPanel.querySelector('[data-progress-updated]');
            const cancelStatusLabel = document.querySelector('[data-cancel-status-label]');
            const cancelNonCancellable = document.querySelector('[data-cancel-non-cancellable]');
            const cancelSubmit = document.querySelector('[data-cancel-submit]');
            const stepElements = Array.from(progressPanel.querySelectorAll('[data-progress-step]'));
            const activeStepClasses = ['border-forest-700', 'bg-forest-900/5', 'text-forest-900'];
            const inactiveStepClasses = ['border-forest-900/10', 'bg-ivory-50', 'text-charcoal-700/50'];
            const cancelledNoteClasses = ['bg-red-50', 'text-red-800'];
            const servedNoteClasses = ['bg-emerald-50', 'text-emerald-900'];
            const liveNoteClasses = ['text-charcoal-700/60'];
            const finalNoteShapeClasses = ['rounded-xl', 'px-4', 'py-3', 'text-xs', 'font-semibold'];

            function applyProgress(progress, checkedAt) {
                if (! progress) {
                    progressPanel.hidden = true;
                    currentProgress = null;
                    return;
                }

                currentProgress = progress;
                orderNumber.textContent = progress.order_number;
                statusLabel.textContent = progress.status_label;
                message.textContent = progress.message;

                if (progressDot) {
                    progressDot.hidden = progress.is_final;
                }

                stepElements.forEach((stepElement, index) => {
                    const isActive = index <= progress.step;
                    stepElement.classList.remove(...(isActive ? inactiveStepClasses : activeStepClasses));
                    stepElement.classList.add(...(isActive ? activeStepClasses : inactiveStepClasses));

                    if (isActive && index === progress.step) {
                        stepElement.setAttribute('aria-current', 'step');
                    } else {
                        stepElement.removeAttribute('aria-current');
                    }
                });

                if (liveNote) {
                    if (progress.is_final) {
                        liveNote.classList.remove(...liveNoteClasses, ...finalNoteShapeClasses);
                        liveNote.classList.add(...finalNoteShapeClasses, ...(progress.status === 'cancelled' ? cancelledNoteClasses : servedNoteClasses));
                    } else {
                        liveNote.classList.remove(...finalNoteShapeClasses, ...cancelledNoteClasses, ...servedNoteClasses);
                        liveNote.classList.add(...liveNoteClasses);
                    }
                    liveNote.textContent = progress.is_final
                        ? progress.message
                        : 'Live updates are on. Your cart and order form will stay as they are.';
                }

                if (updatedAt) {
                    updatedAt.hidden = false;
                    updatedAt.textContent = 'Last checked at ' + checkedAt;
                }

                if (cancelStatusLabel) {
                    cancelStatusLabel.textContent = progress.status_label;
                }
                if (cancelNonCancellable) {
                    cancelNonCancellable.hidden = progress.is_cancellable;
                }
                if (cancelSubmit) {
                    cancelSubmit.disabled = ! progress.is_cancellable;
                }
            }

            async function refreshProgress() {
                if (! currentProgress || currentProgress.is_final || document.hidden) {
                    return;
                }

                try {
                    const response = await fetch(statusUrl, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });

                    if (! response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    applyProgress(payload.order, new Date().toLocaleTimeString());
                } catch (error) {
                    return;
                }
            }

            setInterval(refreshProgress, 10000);
            document.addEventListener('visibilitychange', function () {
                if (! document.hidden) {
                    refreshProgress();
                }
            });
        })();

        renderCart();
    </script>
</body>
</html>
