
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $restaurant->name }} | SafeServe</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'DM Sans', sans-serif;
            background: #f5f7f4;
            color: #203329;
        }

        header {
            background: #123c2c;
            color: white;
            padding: 22px 5%;
        }

        .brand {
            color: #a9c6b5;
            letter-spacing: 3px;
            font-size: 12px;
            font-weight: 800;
        }

        header h1 { margin: 8px 0 3px; }
        header p { margin: 0; color: #c6d8ce; font-size: 13px; }

        .container {
            max-width: 1250px;
            margin: auto;
            padding: 30px 20px;
        }

        .welcome { margin-bottom: 25px; }
        .welcome h2 { margin: 0 0 6px; }
        .welcome p { color: #78857d; font-size: 14px; margin: 0; }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 350px;
            gap: 25px;
            align-items: start;
        }

        .categories {
            display: flex;
            gap: 9px;
            overflow-x: auto;
            padding: 5px 0 22px;
        }

        .category-btn {
            background: white;
            border: 1px solid #dce5dd;
            color: #456052;
            border-radius: 30px;
            padding: 10px 18px;
            cursor: pointer;
            white-space: nowrap;
            font-weight: 600;
        }

        .category-btn.active {
            background: #174c36;
            color: white;
            border-color: #174c36;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 17px;
        }

        .food-card {
            background: white;
            border: 1px solid #e6ece7;
            border-radius: 15px;
            overflow: hidden;
        }

        .food-image {
            height: 125px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #dce9df, #a8c7b1);
            font-size: 42px;
        }

        .food-info { padding: 16px; }
        .food-info h3 { margin: 0 0 7px; font-size: 16px; }
        .description { color: #77847c; font-size: 12px; line-height: 1.6; min-height: 38px; }
        .price { color: #174c36; font-weight: 800; font-size: 17px; margin: 12px 0; }

        .ingredient-text {
            font-size: 11px;
            color: #6e7c73;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .warning {
            background: #fff5df;
            border: 1px solid #f0d9a8;
            color: #80591c;
            padding: 9px;
            border-radius: 8px;
            font-size: 10px;
            line-height: 1.5;
            margin: 10px 0;
        }

        .add-btn, .submit-btn {
            width: 100%;
            background: #174c36;
            color: white;
            border: none;
            border-radius: 9px;
            padding: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .add-btn:hover, .submit-btn:hover { background: #216346; }

        .order-panel {
            background: white;
            border: 1px solid #e5ebe6;
            border-radius: 15px;
            padding: 20px;
            position: sticky;
            top: 15px;
        }

        .order-panel h2 { font-size: 19px; margin: 0 0 18px; }

        .cart-empty {
            text-align: center;
            color: #89958d;
            font-size: 13px;
            padding: 20px 0;
        }

        .cart-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 0;
            border-bottom: 1px solid #edf0ed;
            font-size: 12px;
        }

        .cart-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 7px;
        }

        .qty-btn {
            border: 1px solid #dce5dd;
            background: #f6f8f6;
            border-radius: 5px;
            width: 25px;
            height: 25px;
            cursor: pointer;
        }

        .remove-btn {
            color: #a34b42;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 11px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: 800;
            margin: 18px 0;
            font-size: 16px;
        }

        .field { margin: 15px 0; }

        .field label, .allergy-heading {
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .field input, .field textarea {
            width: 100%;
            border: 1px solid #dce5dd;
            border-radius: 8px;
            padding: 11px;
            font-family: inherit;
            font-size: 13px;
        }

        .field textarea { min-height: 65px; resize: vertical; }

        .allergy-section {
            background: #fff8e9;
            border: 1px solid #f0dfb8;
            border-radius: 10px;
            padding: 13px;
            margin: 18px 0;
        }

        .allergy-section p {
            color: #806a43;
            font-size: 11px;
            line-height: 1.5;
        }

        .allergy-option {
            display: flex;
            gap: 8px;
            align-items: center;
            font-size: 12px;
            margin: 10px 0;
        }

        .allergy-option input { accent-color: #174c36; }

        .success-message {
            background: #e8f5ec;
            color: #21613a;
            border: 1px solid #c6e3ce;
            padding: 13px;
            border-radius: 9px;
            margin-bottom: 18px;
            font-size: 13px;
        }

        .error-message {
            background: #fff0ee;
            color: #a33c32;
            padding: 12px;
            border-radius: 8px;
            font-size: 12px;
            margin-bottom: 15px;
        }

        footer {
            text-align: center;
            padding: 30px;
            color: #87938a;
            font-size: 12px;
        }

        @media(max-width: 850px) {
            .layout { grid-template-columns: 1fr; }
            .order-panel { position: static; }
        }

        @media(max-width: 550px) {
            .menu-grid { grid-template-columns: 1fr; }
            .container { padding: 22px 14px; }
        }
    </style>
</head>

<body>

<header>
    <div class="brand">SAFESERVE</div>
    <h1>{{ $restaurant->name }}</h1>
    <p>Dine Smart. Dine Safe. · Table {{ $table->table_number }}</p>
</header>

<main class="container">

    @if(session('success'))
        <div class="success-message">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="error-message">
            <strong>Please check your order:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="welcome">
        <h2>Explore our menu</h2>
        <p>Choose your meals and tell us about any food allergies before ordering.</p>
    </section>

    <div class="layout">

        <section>
            <div class="categories">
                <button type="button" class="category-btn active" data-category="all">All Items</button>

                @foreach($categories as $category)
                    <button type="button"
                        class="category-btn"
                        data-category="{{ $category->id }}">
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>

            <div class="menu-grid">
                @forelse($menuItems as $item)
                    <article class="food-card"
                        data-category="{{ $item->menu_category_id }}">

                        <div class="food-image">🍽️</div>

                        <div class="food-info">
                            <h3>{{ $item->name }}</h3>

                            <div class="description">
                                {{ $item->description }}
                            </div>

                            <div class="price">
                                {{ $restaurant->currency }}
                                {{ number_format($item->price, 0) }}
                            </div>

                            <div class="ingredient-text">
                                <strong>Ingredients:</strong>
                                @forelse($item->ingredients as $ingredient)
                                    {{ $ingredient->name }}@if(!$loop->last), @endif
                                @empty
                                    Information unavailable
                                @endforelse
                                <br>
                                <strong>Recorded allergens:</strong>
                                @forelse($item->allergens as $allergen)
                                    {{ $allergen->name }}@if(!$loop->last), @endif
                                @empty
                                    No known match recorded
                                @endforelse
                            </div>

                            @if(!$item->ingredients_verified)
                                <div class="warning">
                                    Ingredient information is unverified.
                                    Please confirm allergy requirements with staff.
                                    Cross-contact is also possible.
                                </div>
                            @endif

                            <button type="button"
                                class="add-btn"
                                data-id="{{ $item->id }}"
                                data-name="{{ $item->name }}"
                                data-price="{{ $item->price }}">
                                + Add to order
                            </button>
                        </div>
                    </article>
                @empty
                    <p>No menu items available yet.</p>
                @endforelse
            </div>
        </section>

        <aside class="order-panel">
            <h2>Your order</h2>

            <form method="POST"
                action="{{ route('customer.order', ['qrCode' => $table->qr_code]) }}"
                id="orderForm">

                @csrf

                <input type="hidden" name="cart_json" id="cartJson">

                <div id="cartItems">
                    <div class="cart-empty">Your cart is empty.</div>
                </div>

                <div class="total-row">
                    <span>Total</span>
                    <span id="cartTotal">{{ $restaurant->currency }} 0</span>
                </div>

                <div class="allergy-section">
                    <span class="allergy-heading">Food allergy declaration</span>
                    <p>Select every allergen you need the kitchen to know about.</p>

                    @foreach($allergyOptions as $allergen)
                        <label class="allergy-option">
                            <input type="checkbox"
                                name="allergies[]"
                                value="{{ $allergen->id }}">
                            {{ $allergen->name }}
                        </label>
                    @endforeach
                </div>

                <div class="field">
                    <label for="customer_name">Your name (optional)</label>
                    <input type="text" name="customer_name"
                        id="customer_name" maxlength="100"
                        placeholder="Enter your name">
                </div>

                <div class="field">
                    <label for="customer_notes">Special instructions</label>
                    <textarea name="customer_notes"
                        id="customer_notes"
                        maxlength="1000"
                        placeholder="Any additional requests?"></textarea>
                </div>

                <button type="submit" class="submit-btn">
                    Submit order
                </button>

                <p style="font-size:10px;color:#87938a;line-height:1.5">
                    Allergy information will be sent to restaurant staff for review.
                    SafeServe does not guarantee that food is free from allergens
                    or cross-contact.
                </p>

            </form>
        </aside>

    </div>
</main>

<footer>Powered by SafeServe · Dine Smart. Dine Safe.</footer>

<script>
    const currency = @json($restaurant->currency);
    const cart = {};

    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    const cartJson = document.getElementById('cartJson');

    function formatMoney(amount) {
        return currency + ' ' + Number(amount).toLocaleString();
    }

    function renderCart() {
        const entries = Object.values(cart);
        let total = 0;

        if (entries.length === 0) {
            cartItems.innerHTML = '<div class="cart-empty">Your cart is empty.</div>';
        } else {
            cartItems.innerHTML = '';

            entries.forEach(item => {
                const lineTotal = item.price * item.quantity;
                total += lineTotal;

                const row = document.createElement('div');
                row.className = 'cart-row';

                const details = document.createElement('div');
                const name = document.createElement('strong');
                name.textContent = item.name;

                const controls = document.createElement('div');
                controls.className = 'cart-controls';

                const minus = document.createElement('button');
                minus.type = 'button';
                minus.className = 'qty-btn';
                minus.textContent = '−';
                minus.addEventListener('click', () => changeQuantity(item.id, -1));

                const quantity = document.createElement('span');
                quantity.textContent = item.quantity;

                const plus = document.createElement('button');
                plus.type = 'button';
                plus.className = 'qty-btn';
                plus.textContent = '+';
                plus.addEventListener('click', () => changeQuantity(item.id, 1));

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'remove-btn';
                remove.textContent = 'Remove';
                remove.addEventListener('click', () => {
                    delete cart[item.id];
                    renderCart();
                });

                controls.append(minus, quantity, plus, remove);
                details.append(name, controls);

                const price = document.createElement('strong');
                price.textContent = formatMoney(lineTotal);

                row.append(details, price);
                cartItems.append(row);
            });
        }

        cartTotal.textContent = formatMoney(total);

        cartJson.value = JSON.stringify(
            entries.map(item => ({
                id: item.id,
                quantity: item.quantity
            }))
        );
    }

    function changeQuantity(id, change) {
        if (!cart[id]) return;

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
        button.addEventListener('click', () => {
            const id = button.dataset.id;

            if (!cart[id]) {
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
        });
    });

    document.querySelectorAll('.category-btn').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.category-btn')
                .forEach(btn => btn.classList.remove('active'));

            button.classList.add('active');

            const selected = button.dataset.category;

            document.querySelectorAll('.food-card').forEach(card => {
                card.style.display =
                    selected === 'all' || card.dataset.category === selected
                        ? ''
                        : 'none';
            });
        });
    });

    document.getElementById('orderForm').addEventListener('submit', function(event) {
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
