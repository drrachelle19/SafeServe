<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
  
    public function run(): void
    {
        $restaurantId = DB::table('restaurants')
            ->where('slug', 'safeserve-demo')
            ->value('id');

        if (!$restaurantId) {
            throw new \RuntimeException('Demo restaurant not found. Run SafeServeSeeder first.');
        }

        $categories = DB::table('menu_categories')
            ->where('restaurant_id', $restaurantId)
            ->pluck('id', 'name');

        // Demo ingredients
        $ingredients = [
            'Chicken',
            'Rice',
            'Onion',
            'Tomato',
            'Cooking Oil',
            'Salt',
            'Wheat Flour',
            'Milk',
            'Eggs',
            'Banana',
            'Mango',
            'Fish',
            'Garlic',
            'Butter',
        ];

        foreach ($ingredients as $ingredient) {
            DB::table('ingredients')->updateOrInsert(
                [
                    'restaurant_id' => $restaurantId,
                    'name' => $ingredient,
                ],
                [
                    'description' => 'Demo ingredient - confirm with restaurant kitchen',
                    'is_verified' => false,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $ingredientIds = DB::table('ingredients')
            ->where('restaurant_id', $restaurantId)
            ->pluck('id', 'name');

        $allergenIds = DB::table('allergens')->pluck('id', 'name');

        // Link ingredients to allergens
        $ingredientAllergens = [
            'Wheat Flour' => ['Wheat'],
            'Milk' => ['Milk'],
            'Eggs' => ['Eggs'],
            'Butter' => ['Milk'],
            'Fish' => ['Fish'],
        ];

        foreach ($ingredientAllergens as $ingredient => $allergens) {
            foreach ($allergens as $allergen) {
                DB::table('ingredient_allergens')->insertOrIgnore([
                    'ingredient_id' => $ingredientIds[$ingredient],
                    'allergen_id' => $allergenIds[$allergen],
                ]);
            }
        }

        // Demo food menu
        $menu = [
            [
                'name' => 'Grilled Chicken & Rice',
                'category' => 'Main Course',
                'description' => 'Grilled chicken served with seasoned rice and vegetables.',
                'price' => 12000,
                'ingredients' => ['Chicken', 'Rice', 'Onion', 'Tomato', 'Cooking Oil', 'Salt'],
            ],
            [
                'name' => 'Vegetable Pasta',
                'category' => 'Main Course',
                'description' => 'Pasta served with fresh vegetables and garlic sauce.',
                'price' => 10000,
                'ingredients' => ['Wheat Flour', 'Tomato', 'Onion', 'Garlic', 'Cooking Oil', 'Salt'],
            ],
            [
                'name' => 'Classic Pancakes',
                'category' => 'Breakfast',
                'description' => 'Soft pancakes served with a light topping.',
                'price' => 8000,
                'ingredients' => ['Wheat Flour', 'Milk', 'Eggs', 'Butter'],
            ],
            [
                'name' => 'Tropical Smoothie',
                'category' => 'Drinks',
                'description' => 'A refreshing banana and mango smoothie.',
                'price' => 6000,
                'ingredients' => ['Banana', 'Mango', 'Milk'],
            ],
            [
                'name' => 'Grilled Fish',
                'category' => 'Main Course',
                'description' => 'Fresh grilled fish served with vegetables.',
                'price' => 15000,
                'ingredients' => ['Fish', 'Garlic', 'Onion', 'Cooking Oil', 'Salt'],
            ],
        ];

        foreach ($menu as $item) {
            $categoryId = $categories[$item['category']] ?? null;

            if (!$categoryId) {
                throw new \RuntimeException('Menu category missing: ' . $item['category']);
            }

            DB::table('menu_items')->updateOrInsert(
                [
                    'restaurant_id' => $restaurantId,
                    'name' => $item['name'],
                ],
                [
                    'menu_category_id' => $categoryId,
                    'description' => $item['description'],
                    'price' => $item['price'],
                    'is_available' => true,
                    'ingredients_verified' => false,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $menuItemId = DB::table('menu_items')
                ->where('restaurant_id', $restaurantId)
                ->where('name', $item['name'])
                ->value('id');

            foreach ($item['ingredients'] as $ingredient) {
                DB::table('menu_item_ingredients')->insertOrIgnore([
                    'menu_item_id' => $menuItemId,
                    'ingredient_id' => $ingredientIds[$ingredient],
                ]);
            }
        }
    }
}

    
