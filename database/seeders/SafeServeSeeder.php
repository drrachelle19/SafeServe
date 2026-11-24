<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SafeServeSeeder extends Seeder
{
    public function run(): void
    {
        $restaurantId = DB::table('restaurants')->where('slug', 'safeserve-demo')->value('id');

        if (! $restaurantId) {
            $restaurantId = DB::table('restaurants')->insertGetId([
                'name' => 'SafeServe Restaurant',
                'slug' => 'safeserve-demo',
                'address' => 'Dar es Salaam, Tanzania',
                'phone' => '+255700000000',
                'currency' => 'TZS',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $categories = ['Breakfast', 'Main Course', 'Drinks', 'Desserts'];

        foreach ($categories as $category) {
            DB::table('menu_categories')->updateOrInsert(
                ['restaurant_id' => $restaurantId, 'name' => $category],
                [
                    'description' => $category.' menu',
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $allergens = [
            'Peanuts',
            'Milk',
            'Eggs',
            'Wheat',
            'Soy',
            'Fish',
            'Shellfish',
            'Tree Nuts',
            'Sesame',
        ];

        foreach ($allergens as $allergen) {
            DB::table('allergens')->updateOrInsert(
                ['name' => $allergen],
                [
                    'description' => 'Common food allergen',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        for ($i = 1; $i <= 5; $i++) {
            $tableNumber = 'T'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);

            $qrCode = DB::table('restaurant_tables')
                ->where('restaurant_id', $restaurantId)
                ->where('table_number', $tableNumber)
                ->value('qr_code') ?? Str::uuid()->toString();

            DB::table('restaurant_tables')->updateOrInsert(
                [
                    'restaurant_id' => $restaurantId,
                    'table_number' => $tableNumber,
                ],
                [
                    'qr_code' => $qrCode,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
