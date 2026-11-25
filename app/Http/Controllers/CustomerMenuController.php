<?php

namespace App\Http\Controllers;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerMenuController extends Controller
{
    private const CANCELLABLE_STATUSES = ['pending', 'confirmed', 'preparing', 'ready'];

    private const FINAL_STATUSES = ['completed', 'cancelled'];

    private const PROGRESS_STEPS = [
        ['status' => 'pending', 'label' => 'Received'],
        ['status' => 'confirmed', 'label' => 'Confirmed'],
        ['status' => 'preparing', 'label' => 'Preparing'],
        ['status' => 'ready', 'label' => 'Ready'],
        ['status' => 'completed', 'label' => 'Served'],
    ];

    private const STATUS_LABELS = [
        'pending' => 'Received',
        'confirmed' => 'Confirmed',
        'preparing' => 'Preparing',
        'ready' => 'Ready for your table',
        'completed' => 'Served',
        'cancelled' => 'Cancelled',
    ];

    private const STATUS_MESSAGES = [
        'pending' => 'Order received. The kitchen has been notified.',
        'confirmed' => 'Order confirmed. The kitchen is getting it ready.',
        'preparing' => 'The kitchen is preparing your food.',
        'ready' => 'Your food is ready and on its way to your table.',
        'completed' => 'Enjoy your meal — your order has been served.',
        'cancelled' => 'This order was cancelled. Please ask staff if you need help.',
    ];

    public function index(string $qrCode): View
    {
        $table = $this->findActiveTable($qrCode);

        $restaurant = DB::table('restaurants')
            ->where('id', $table->restaurant_id)
            ->first();

        if (! $restaurant) {
            abort(404, 'Restaurant not found.');
        }

        $categories = DB::table('menu_categories')
            ->where('restaurant_id', $table->restaurant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $menuItems = DB::table('menu_items')
            ->where('restaurant_id', $table->restaurant_id)
            ->where('is_available', true)
            ->orderBy('name')
            ->get();

        $itemIds = $menuItems->pluck('id');

        $ingredients = DB::table('menu_item_ingredients')
            ->join('ingredients', 'ingredients.id', '=', 'menu_item_ingredients.ingredient_id')
            ->whereIn('menu_item_ingredients.menu_item_id', $itemIds)
            ->select('menu_item_ingredients.menu_item_id', 'ingredients.name')
            ->orderBy('ingredients.name')
            ->get()
            ->groupBy('menu_item_id');

        $allergens = DB::table('menu_item_ingredients')
            ->join('ingredient_allergens', 'ingredient_allergens.ingredient_id', '=', 'menu_item_ingredients.ingredient_id')
            ->join('allergens', 'allergens.id', '=', 'ingredient_allergens.allergen_id')
            ->whereIn('menu_item_ingredients.menu_item_id', $itemIds)
            ->select('menu_item_ingredients.menu_item_id', 'allergens.name')
            ->distinct()
            ->orderBy('allergens.name')
            ->get()
            ->groupBy('menu_item_id');

        foreach ($menuItems as $item) {
            $item->ingredients = $ingredients->get($item->id, collect());
            $item->allergens = $allergens->get($item->id, collect());
        }

        $allergyOptions = DB::table('allergens')
            ->orderBy('name')
            ->get();

        $recentOrder = $this->recentOrderForTable($table);

        return view('customer.menu', array_merge(compact(
            'restaurant',
            'table',
            'categories',
            'menuItems',
            'allergyOptions',
            'recentOrder'
        ), [
            'orderProgress' => $this->orderProgress($recentOrder),
            'progressSteps' => self::PROGRESS_STEPS,
        ]));
    }

    public function showTableCodeForm(): View
    {
        return view('customer.table-code');
    }

    public function resolveTableCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'table_code' => ['required', 'string', 'max:100'],
        ], [
            'table_code.required' => 'Enter the table code shown on your table.',
            'table_code.string' => 'The table code must be text.',
            'table_code.max' => 'The table code is too long.',
        ]);

        $parts = explode(':', trim($validated['table_code']), 2);
        $restaurantSlug = count($parts) === 2 ? Str::lower(trim($parts[0])) : null;
        $tableNumber = Str::upper(trim($parts[1] ?? $parts[0]));

        $query = DB::table('restaurant_tables')
            ->join('restaurants', 'restaurants.id', '=', 'restaurant_tables.restaurant_id')
            ->where('restaurant_tables.is_active', true)
            ->where('restaurant_tables.table_number', $tableNumber)
            ->select('restaurant_tables.*');

        if ($restaurantSlug !== null) {
            $query->where('restaurants.slug', $restaurantSlug);
        }

        $tables = $query->get();

        if ($tables->count() > 1) {
            throw ValidationException::withMessages([
                'table_code' => 'This table number is used by more than one restaurant. Enter the full code shown on the table pad.',
            ]);
        }

        $table = $tables->first();

        if (! $table) {
            throw ValidationException::withMessages([
                'table_code' => 'No active table matches that code. Check the code on your table.',
            ]);
        }

        if (! $table->qr_code) {
            throw ValidationException::withMessages([
                'table_code' => 'This table is not ready for ordering yet.',
            ]);
        }

        return redirect()->route('customer.menu', $table->qr_code);
    }

    public function store(Request $request, string $qrCode): RedirectResponse
    {
        $table = $this->findActiveTable($qrCode);
        $decodedCart = json_decode((string) $request->input('cart_json', ''), true);

        $request->merge([
            'cart' => is_array($decodedCart) ? array_values($decodedCart) : null,
        ]);

        $validated = $request->validate([
            'cart' => ['required', 'array', 'min:1', 'max:20'],
            'cart.*' => ['array:id,quantity'],
            'cart.*.id' => [
                'required',
                'integer',
                'distinct',
                function (string $attribute, mixed $value, Closure $fail) use ($table): void {
                    $isAvailable = DB::table('menu_items')
                        ->where('id', $value)
                        ->where('restaurant_id', $table->restaurant_id)
                        ->where('is_available', true)
                        ->exists();

                    if (! $isAvailable) {
                        $fail('The selected menu item is unavailable.');
                    }
                },
            ],
            'cart.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['integer', 'distinct', 'exists:allergens,id'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'cart.required' => 'Add at least one item to your order.',
            'cart.min' => 'Add at least one item to your order.',
            'cart.*.id.distinct' => 'You cannot add the same item twice. Adjust its quantity instead.',
            'cart.*.quantity.min' => 'Each quantity must be at least 1.',
            'cart.*.quantity.max' => 'Each quantity cannot exceed 20.',
        ]);

        $selectedAllergyIds = array_values(array_unique(array_map(
            'intval',
            $validated['allergies'] ?? []
        )));

        $allergenNames = DB::table('allergens')
            ->whereIn('id', $selectedAllergyIds)
            ->pluck('name', 'id');

        $order = DB::transaction(function () use (
            $validated,
            $table,
            $selectedAllergyIds,
            $allergenNames
        ): array {
            $cartItemIds = array_map('intval', array_column($validated['cart'], 'id'));

            $menuItems = DB::table('menu_items')
                ->where('restaurant_id', $table->restaurant_id)
                ->whereIn('id', $cartItemIds)
                ->where('is_available', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($menuItems->count() !== count($cartItemIds)) {
                throw ValidationException::withMessages([
                    'cart' => 'One or more selected items are no longer available.',
                ]);
            }

            $lineItems = [];
            $subtotal = 0;

            foreach ($validated['cart'] as $cartItem) {
                $menuItem = $menuItems->get((int) $cartItem['id']);
                $quantity = (int) $cartItem['quantity'];
                $lineTotal = round((float) $menuItem->price * $quantity, 2);

                $subtotal += $lineTotal;
                $lineItems[] = [
                    'menuItem' => $menuItem,
                    'quantity' => $quantity,
                    'lineTotal' => $lineTotal,
                ];
            }

            $subtotal = round($subtotal, 2);
            $orderNumber = $this->generateOrderNumber();
            $requiresAllergyReview = $selectedAllergyIds !== [];

            $orderId = DB::table('orders')->insertGetId([
                'restaurant_id' => $table->restaurant_id,
                'restaurant_table_id' => $table->id,
                'order_number' => $orderNumber,
                'customer_name' => $validated['customer_name'] ?? null,
                'language' => 'en',
                'subtotal' => $subtotal,
                'tax' => 0,
                'total' => $subtotal,
                'status' => 'pending',
                'allergy_review_status' => $requiresAllergyReview ? 'requires_review' : 'not_required',
                'payment_status' => 'unpaid',
                'customer_notes' => $validated['customer_notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('order_status_events')->insert([
                'order_id' => $orderId,
                'from_status' => null,
                'to_status' => 'pending',
                'note' => 'Order submitted by customer.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($lineItems as $lineItem) {
                $orderItemId = DB::table('order_items')->insertGetId([
                    'order_id' => $orderId,
                    'menu_item_id' => $lineItem['menuItem']->id,
                    'item_name' => $lineItem['menuItem']->name,
                    'unit_price' => $lineItem['menuItem']->price,
                    'quantity' => $lineItem['quantity'],
                    'line_total' => $lineItem['lineTotal'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->recordAllergyAlerts(
                    $orderId,
                    $orderItemId,
                    $lineItem['menuItem'],
                    $selectedAllergyIds,
                    $allergenNames
                );
            }

            return ['id' => $orderId, 'number' => $orderNumber];
        });

        $recentOrders = session('customer_recent_orders', []);
        $recentOrders = is_array($recentOrders) ? $recentOrders : [];
        $recentOrders[$qrCode] = $order['number'];
        session(['customer_recent_orders' => $recentOrders]);

        return redirect()
            ->route('customer.menu', $qrCode)
            ->with('success', 'Your order '.$order['number'].' has been submitted. Staff will review it shortly.');
    }

    public function cancel(Request $request, string $qrCode): RedirectResponse
    {
        $table = $this->findActiveTable($qrCode);

        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:50'],
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ], [
            'order_number.required' => 'Enter the order number you want to cancel.',
            'cancellation_reason.required' => 'Tell us why you need to cancel this order.',
        ]);

        $orderNumber = Str::upper(trim($validated['order_number']));

        $order = DB::table('orders')
            ->where('order_number', $orderNumber)
            ->where('restaurant_id', $table->restaurant_id)
            ->where('restaurant_table_id', $table->id)
            ->first();

        if (! $order) {
            throw ValidationException::withMessages([
                'order_number' => 'No order with that number was found for this table.',
            ]);
        }

        DB::transaction(function () use ($order, $validated): void {
            $currentOrder = DB::table('orders')
                ->where('id', $order->id)
                ->lockForUpdate()
                ->first();

            if (! in_array($currentOrder->status, self::CANCELLABLE_STATUSES, true)) {
                throw ValidationException::withMessages([
                    'order_number' => 'This order can no longer be cancelled. Please ask staff for help.',
                ]);
            }

            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'status' => 'cancelled',
                    'updated_at' => now(),
                ]);

            DB::table('order_status_events')->insert([
                'order_id' => $order->id,
                'from_status' => $currentOrder->status,
                'to_status' => 'cancelled',
                'note' => 'Cancelled by customer: '.$validated['cancellation_reason'],
                'changed_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('customer.menu', $qrCode)
            ->with('success', 'Order '.$orderNumber.' was cancelled. Staff have been notified.');
    }

    public function orderStatus(string $qrCode): JsonResponse
    {
        $table = $this->findActiveTable($qrCode);

        return response()->json([
            'order' => $this->orderProgress($this->recentOrderForTable($table)),
        ])->header('Cache-Control', 'no-store');
    }

    private function orderProgress(?object $order): ?array
    {
        if ($order === null) {
            return null;
        }

        $status = (string) $order->status;

        return [
            'order_number' => (string) $order->order_number,
            'status' => $status,
            'status_label' => self::STATUS_LABELS[$status] ?? Str::headline($status),
            'message' => self::STATUS_MESSAGES[$status] ?? 'Your order status has been updated.',
            'step' => $this->progressStep($status),
            'is_cancellable' => in_array($status, self::CANCELLABLE_STATUSES, true),
            'is_final' => in_array($status, self::FINAL_STATUSES, true),
        ];
    }

    private function progressStep(string $status): int
    {
        return match ($status) {
            'pending' => 0,
            'confirmed' => 1,
            'preparing' => 2,
            'ready' => 3,
            'completed' => 4,
            default => -1,
        };
    }

    private function recentOrderForTable(object $table): ?object
    {
        $recentOrders = session('customer_recent_orders', []);
        $orderNumber = is_array($recentOrders) ? ($recentOrders[$table->qr_code] ?? null) : null;

        if (! is_string($orderNumber) || $orderNumber === '') {
            return null;
        }

        return DB::table('orders')
            ->where('order_number', $orderNumber)
            ->where('restaurant_id', $table->restaurant_id)
            ->where('restaurant_table_id', $table->id)
            ->select('id', 'order_number', 'status', 'total')
            ->first();
    }

    private function findActiveTable(string $qrCode): object
    {
        $table = DB::table('restaurant_tables')
            ->where('qr_code', $qrCode)
            ->where('is_active', true)
            ->first();

        if (! $table) {
            abort(404, 'Restaurant table not found.');
        }

        return $table;
    }

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'SS-'.Str::upper(Str::random(8));
        } while (DB::table('orders')->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    private function recordAllergyAlerts(
        int $orderId,
        int $orderItemId,
        object $menuItem,
        array $selectedAllergyIds,
        Collection $allergenNames
    ): void {
        if ($selectedAllergyIds === []) {
            return;
        }

        $ingredientIds = DB::table('menu_item_ingredients')
            ->where('menu_item_id', $menuItem->id)
            ->pluck('ingredient_id');

        $allIngredientsVerified = $ingredientIds->isNotEmpty()
            && ! DB::table('ingredients')
                ->whereIn('id', $ingredientIds)
                ->where('is_verified', false)
                ->exists();

        $matchedAllergenIds = DB::table('ingredient_allergens')
            ->whereIn('ingredient_id', $ingredientIds)
            ->whereIn('allergen_id', $selectedAllergyIds)
            ->pluck('allergen_id')
            ->map(fn (mixed $id): int => (int) $id);

        foreach ($selectedAllergyIds as $allergenId) {
            $riskStatus = match (true) {
                $matchedAllergenIds->contains($allergenId) => 'matched',
                ! $menuItem->ingredients_verified || ! $allIngredientsVerified => 'unknown',
                default => 'no_known_match',
            };

            DB::table('order_allergens')->insert([
                'order_id' => $orderId,
                'order_item_id' => $orderItemId,
                'allergen_id' => $allergenId,
                'allergen_name' => $allergenNames->get($allergenId),
                'source' => 'customer_declared',
                'risk_status' => $riskStatus,
                'staff_acknowledged_at' => null,
                'staff_note' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
