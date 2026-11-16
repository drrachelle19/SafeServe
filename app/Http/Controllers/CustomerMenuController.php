<?php

namespace App\Http\Controllers;

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
            ->select('menu_item_ingredients.menu_item_id', 'ingredients.name')
            ->get()
            ->groupBy('menu_item_id');

        $allergens = DB::table('menu_item_ingredients')
            ->join('ingredient_allergens', 'ingredient_allergens.ingredient_id', '=', 'menu_item_ingredients.ingredient_id')
            ->join('allergens', 'allergens.id', '=', 'ingredient_allergens.allergen_id')
            ->whereIn('menu_item_ingredients.menu_item_id', $itemIds)
            ->select('menu_item_ingredients.menu_item_id', 'allergens.name')
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
}
