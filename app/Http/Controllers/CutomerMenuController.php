<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerMenuController extends Controller
{
    public function index(string $qrCode)
    {
        $table = DB::table('restaurant_tables')
            ->where('qr_code', $qrCode)
            ->where('is_active', true)
            ->first();

        if (!$table) {
            abort(404, 'Restaurant table not found.');
        }

        $restaurant = DB::table('restaurants')
            ->where('id', $table->restaurant_id)
            ->first();

        $categories = DB::table('menu_categories')
            ->where('restaurant_id', $table->restaurant_id)
            ->where('is_active', true)
            ->get();

        $menuItems = DB::table('menu_items')
            ->where('restaurant_id', $table->restaurant_id)
            ->where('is_available', true)
            ->get();

        $itemIds = $menuItems->pluck('id');

        $ingredients = DB::table('menu_item_ingredients')
            ->join('ingredients', 'ingredients.id', '=', 'menu_item_ingredients.ingredient_id')
            ->whereIn('menu_item_ingredients.menu_item_id', $itemIds)
            ->select(
                'menu_item_ingredients.menu_item_id',
                'ingredients.name'
            )
            ->get()
            ->groupBy('menu_item_id');

        $allergens = DB::table('menu_item_ingredients')
            ->join('ingredient_allergens', 'ingredient_allergens.ingredient_id', '=', 'menu_item_ingredients.ingredient_id')
            ->join('allergens', 'allergens.id', '=', 'ingredient_allergens.allergen_id')
            ->whereIn('menu_item_ingredients.menu_item_id', $itemIds)
            ->select(
                'menu_item_ingredients.menu_item_id',
                'allergens.name'
            )
            ->distinct()
            ->get()
            ->groupBy('menu_item_id');

        foreach ($menuItems as $item) {
            $item->ingredients = $ingredients->get($item->id, collect());
            $item->allergens = $allergens->get($item->id, collect());
        }

        $allergyOptions = DB::table('allergens')
            ->orderBy('name')
            ->get();

        return view('customer.menu', compact(
            'restaurant',
            'table',
            'categories',
            'menuItems',
            'allergyOptions'
        ));
    }

    public function store(Request $request, string $qrCode)
    {
        $table = DB::table('restaurant_tables')
            ->where('qr_code', $qrCode)
            ->where('is_active', true)
            ->first();

        if (!$table) {
            abort(404, 'Restaurant table not found.');
        }

        $cart = json_decode($request->input('cart_json', '[]'), true);

        $validator = Validator::make([
            'cart' => $cart,
            'allergies' => $request->input('allergies', []),
            'customer_name' => $request->input('customer_name'),
            'customer_notes' => $request->input('customer_notes'),
        ], [
            'cart' => 'required|array|min:1',
            'cart.*.id' => 'required|integer',
            'cart.*.quantity' => 'required|integer|min:1|max:20',
            'allergies' => 'nullable|array',
            'allergies.*' => 'integer|exists:allergens,id',
            'customer_name' => 'nullable|string|max:100',
            'customer_notes' => 'nullable|string|max:1000',
        ]);

        $validator->validate();

        $orderNumber = 'SS-' . strtoupper(Str::random(8));

        DB::transaction(function () use (
            $cart,
            $request,
            $table,
            $orderNumber
        ) {
            $items = [];
            $subtotal = 0;

            foreach ($cart as $cartItem) {
                $menuItem = DB::table('menu_items')
                    ->where('id', $cartItem['id'])
                    ->where('restaurant_id', $table->restaurant_id)
                    ->where('is_available', true)
                    ->first();

                if (!$menuItem) {
                    abort(422, 'One of the selected menu items is unavailable.');
                }

                $lineTotal = $menuItem->price * $cartItem['quantity'];
                $subtotal += $lineTotal;

                $items[] = [
                    'menu_item' => $menuItem,
                    'quantity' => $cartItem['quantity'],
                    'line_total' => $lineTotal,
                ];
            }

            $selectedAllergies = array_unique(
                $request->input('allergies', [])
            );

            $orderId = DB::table('orders')->insertGetId([
                'restaurant_id' => $table->restaurant_id,
                'restaurant_table_id' => $table->id,
                'order_number' => $orderNumber,
                'customer_name' => $request->input('customer_name'),
                'language' => 'en',
                'subtotal' => $subtotal,
                'tax' => 0,
                'total' => $subtotal,
                'status' => 'pending',
                'allergy_review_status' => count($selectedAllergies)
                    ? 'requires_review'
                    : 'not_required',
                'payment_status' => 'unpaid',
                'customer_notes' => $request->input('customer_notes'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($items as $item) {
                $menuItem = $item['menu_item'];

                $orderItemId = DB::table('order_items')->insertGetId([
                    'order_id' => $orderId,
                    'menu_item_id' => $menuItem->id,
                    'item_name' => $menuItem->name,
                    'unit_price' => $menuItem->price,
                    'quantity' => $item['quantity'],
                    'line_total' => $item['line_total'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $ingredientIds = DB::table('menu_item_ingredients')
                    ->where('menu_item_id', $menuItem->id)
                    ->pluck('ingredient_id');

                $hasUnknownIngredients = DB::table('ingredients')
                    ->whereIn('id', $ingredientIds)
                    ->where('is_verified', false)
                    ->exists();

                foreach ($selectedAllergies as $allergenId) {
                    $allergen = DB::table('allergens')
                        ->where('id', $allergenId)
                        ->first();

                    $hasMatch = DB::table('ingredient_allergens')
                        ->whereIn('ingredient_id', $ingredientIds)
                        ->where('allergen_id', $allergenId)
                        ->exists();

                    if ($hasMatch) {
                        $riskStatus = 'matched';
                    } elseif ($hasUnknownIngredients || !$ingredientIds->count()) {
                        $riskStatus = 'unknown';
                    } else {
                        $riskStatus = 'no_known_match';
                    }

                    DB::table('order_allergens')->insert([
                        'order_id' => $orderId,
                        'order_item_id' => $orderItemId,
                        'allergen_id' => $allergenId,
                        'allergen_name' => $allergen->name,
                        'source' => 'customer_declared',
                        'risk_status' => $riskStatus,
                        'staff_acknowledged_at' => null,
                        'staff_note' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('customer.menu', $qrCode)
            ->with('success', 'Your order ' . $orderNumber . ' has been submitted. Please wait for staff confirmation.');
    }
}