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
    <header class="bg-forest-950 text-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-6 sm:px-8">
            <div class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-white/10 text-sm font-bold tracking-widest">SS</span>
                <span>
                    <span class="block text-xs font-bold uppercase tracking-[0.3em] text-emerald-300">SafeServe</span>
                    <span class="block text-xs text-white/60">Dine Smart. Dine Safe.</span>
                </span>
            </div>
            <span class="rounded-full border border-white/20 bg-white/5 px-4 py-2 text-xs font-semibold text-white/90">
                Table {{ $table->table_number }}
            </span>
        </div>
        <div class="border-t border-white/10 bg-forest-900/40">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-8">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-emerald-300">Welcome to</p>
                <h1 class="mt-2 text-3xl font-semibold sm:text-4xl">{{ $restaurant->name }}</h1>
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

        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-forest-950">Explore our menu</h2>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-charcoal-700/75">
                    Choose your meals and tell us about any food allergies before ordering. Your declarations go directly to the kitchen for staff review.
                </p>
            </div>
            <span class="rounded-full bg-forest-900/5 px-4 py-2 text-xs font-medium text-forest-800">
                {{ $menuItems->count() }} item(s) available
            </span>
        </div>

        <div class="grid gap-8 lg:grid-cols-3">
            <section class="lg:col-span-2" aria-label="Restaurant menu">
                <div class="categories flex gap-2 overflow-x-auto pb-4" role="group" aria-label="Menu categories">
                    <button type="button"
                        class="category-btn shrink-0 rounded-full border px-4 py-2 text-sm font-semibold transition bg-forest-900 text-white border-forest-900 shadow-sm"
                        data-category="all">
                        All Items
                    </button>

                    @foreach ($categories as $category)
                        <button type="button"
                            class="category-btn shrink-0 rounded-full border px-4 py-2 text-sm font-semibold transition bg-white text-forest-900 border-forest-900/15 hover:bg-forest-900/5"
                            data-category="{{ $category->id }}">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>

                <div class="menu-grid grid gap-5 sm:grid-cols-2">
                    @forelse ($menuItems as $item)
                        <article class="food-card flex flex-col overflow-hidden rounded-3xl border border-forest-900/10 bg-white shadow-sm" data-category="{{ $item->menu_category_id }}">
                            <div class="grid h-28 place-items-center bg-gradient-to-br from-forest-900 to-forest-700">
                                <span class="text-xs font-bold uppercase tracking-[0.3em] text-white/70">SafeServe</span>
                            </div>
                            <div class="flex flex-1 flex-col p-5">
                                <h3 class="text-lg font-semibold text-forest-950">{{ $item->name }}</h3>
                                <p class="mt-2 flex-1 text-sm leading-6 text-charcoal-700/75">{{ $item->description }}</p>
                                <p class="mt-4 text-lg font-bold text-forest-900">
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
                                    class="add-btn mt-5 w-full rounded-xl bg-forest-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-forest-800 focus:outline-none focus:ring-2 focus:ring-forest-700 focus:ring-offset-2"
                                    data-id="{{ $item->id }}"
                                    data-name="{{ $item->name }}"
                                    data-price="{{ $item->price }}">
                                    Add to order
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

            <aside class="h-fit rounded-3xl border border-forest-900/10 bg-white p-6 shadow-sm lg:sticky lg:top-6">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-xl font-semibold text-forest-950">Your order</h2>
                    <div class="flex flex-wrap items-center gap-2">
                        <span id="cartCount" class="rounded-full bg-forest-900 px-3 py-1 text-xs font-semibold text-white">0 items</span>
                        <span class="rounded-full bg-forest-900/5 px-3 py-1 text-xs font-medium text-forest-800">Table {{ $table->table_number }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('customer.order', ['qrCode' => $table->qr_code]) }}" id="orderForm" class="mt-6">
                    @csrf
                    <input type="hidden" name="cart_json" id="cartJson">

                    <div id="cartItems" class="max-h-72 overflow-y-auto">
                        <div class="cart-empty py-8 text-center text-sm text-charcoal-700/70">Your cart is empty.</div>
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

                    <button type="submit" class="mt-6 w-full rounded-xl bg-forest-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-forest-800 focus:outline-none focus:ring-2 focus:ring-forest-700 focus:ring-offset-2">
                        Submit order
                    </button>

                    <p class="mt-4 text-[11px] leading-5 text-charcoal-700/70">
                        Allergy information will be sent to restaurant staff for review. SafeServe does not guarantee that food is free from allergens or cross-contact.
                    </p>
                </form>
            </aside>
        </div>
    </main>

    <footer class="border-t border-forest-900/10 py-8 text-center text-xs text-charcoal-700/70">
        Powered by SafeServe · Dine Smart. Dine Safe.
    </footer>

    <div id="cartToast" role="status" aria-live="polite" class="pointer-events-none fixed inset-x-4 bottom-4 z-50 mx-auto hidden max-w-sm rounded-2xl bg-forest-950 px-5 py-3 text-center text-sm font-semibold text-white shadow-lg sm:bottom-8"></div>

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
            cartToast.classList.remove('hidden');

            window.clearTimeout(cartToastTimer);
            cartToastTimer = window.setTimeout(() => cartToast.classList.add('hidden'), 1800);
        }

        function renderCart() {
            const entries = Object.values(cart);
            let total = 0;
            let count = 0;

            if (entries.length === 0) {
                cartItems.innerHTML = '<div class="cart-empty py-8 text-center text-sm text-charcoal-700/70">Your cart is empty.</div>';
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
                        renderCart();
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

            renderCart();
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

                renderCart();

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

        renderCart();
    </script>
</body>
</html>
