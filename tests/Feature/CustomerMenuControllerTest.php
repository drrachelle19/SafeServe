<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerMenuControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_menu_page_renders_items_and_recorded_allergens(): void
    {
        $context = $this->createRestaurantContext();
        $allergenId = $this->createAllergen('Peanut');
        $ingredientId = $this->createIngredient($context['restaurant_id'], 'Peanut butter', true);
        $this->linkAllergenToIngredient($ingredientId, $allergenId);
        $itemId = $this->createMenuItem($context, 'Satay', 12000, true);
        $this->linkIngredientToItem($itemId, $ingredientId);

        $response = $this->get(route('customer.menu', $context['qr_code']));

        $response->assertOk();
        $response->assertSee('Satay');
        $response->assertSee('Peanut butter');
        $response->assertSee('Peanut');
    }

    public function test_menu_page_returns_404_for_unknown_qr_code(): void
    {
        $this->get(route('customer.menu', 'unknown-qr-code'))
            ->assertNotFound();
    }

    public function test_order_rejects_empty_cart_without_saving_an_order(): void
    {
        $context = $this->createRestaurantContext();

        $response = $this->from(route('customer.menu', $context['qr_code']))
            ->post(
                route('customer.order', $context['qr_code']),
                $this->orderPayload([])
            );

        $response->assertRedirect(route('customer.menu', $context['qr_code']));
        $response->assertSessionHasErrors([
            'cart' => 'Add at least one item to your order.',
        ]);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_rejects_menu_item_from_another_restaurant(): void
    {
        $context = $this->createRestaurantContext();
        $otherContext = $this->createRestaurantContext('other-safeserve', 'other-table-qr');
        $otherItemId = $this->createMenuItem($otherContext, 'Other restaurant meal', 9000);

        $response = $this->post(
            route('customer.order', $context['qr_code']),
            $this->orderPayload([['id' => $otherItemId, 'quantity' => 1]])
        );

        $response->assertSessionHasErrors([
            'cart.0.id' => 'The selected menu item is unavailable.',
        ]);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_rejects_duplicate_cart_items(): void
    {
        $context = $this->createRestaurantContext();
        $itemId = $this->createMenuItem($context, 'Coffee', 6000);

        $response = $this->post(
            route('customer.order', $context['qr_code']),
            $this->orderPayload([
                ['id' => $itemId, 'quantity' => 1],
                ['id' => $itemId, 'quantity' => 2],
            ])
        );

        $response->assertSessionHasErrors([
            'cart.1.id' => 'You cannot add the same item twice. Adjust its quantity instead.',
        ]);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_calculates_totals_from_database_prices_and_ignores_submitted_price(): void
    {
        $context = $this->createRestaurantContext();
        $itemId = $this->createMenuItem($context, 'Grilled fish', 12500, true);

        $response = $this->post(
            route('customer.order', $context['qr_code']),
            $this->orderPayload(
                [['id' => $itemId, 'quantity' => 2]],
                [],
                ['price' => 0.01, 'total' => 0.01]
            )
        );

        $response->assertRedirect(route('customer.menu', $context['qr_code']));
        $response->assertSessionHas('success');

        $orderId = DB::table('orders')->value('id');

        $this->assertSame(25000.0, (float) DB::table('orders')->where('id', $orderId)->value('total'));
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'restaurant_id' => $context['restaurant_id'],
            'restaurant_table_id' => $context['table_id'],
            'customer_name' => 'Amina',
            'customer_notes' => 'Please prepare it freshly.',
            'status' => 'pending',
            'allergy_review_status' => 'not_required',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'menu_item_id' => $itemId,
            'item_name' => 'Grilled fish',
            'quantity' => 2,
            'line_total' => 25000,
        ]);
        $this->assertDatabaseHas('order_status_events', [
            'order_id' => $orderId,
            'from_status' => null,
            'to_status' => 'pending',
        ]);
        $this->assertDatabaseCount('order_allergens', 0);
    }

    public function test_order_records_matched_allergen_alert_for_declared_allergy(): void
    {
        $context = $this->createRestaurantContext();
        $allergenId = $this->createAllergen('Peanut');
        $ingredientId = $this->createIngredient($context['restaurant_id'], 'Groundnuts', true);
        $this->linkAllergenToIngredient($ingredientId, $allergenId);
        $itemId = $this->createMenuItem($context, 'Groundnut soup', 10000, true);
        $this->linkIngredientToItem($itemId, $ingredientId);

        $this->post(
            route('customer.order', $context['qr_code']),
            $this->orderPayload([['id' => $itemId, 'quantity' => 1]], [$allergenId])
        )->assertRedirect(route('customer.menu', $context['qr_code']));

        $orderId = DB::table('orders')->value('id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'allergy_review_status' => 'requires_review',
        ]);
        $this->assertDatabaseHas('order_allergens', [
            'order_id' => $orderId,
            'allergen_id' => $allergenId,
            'allergen_name' => 'Peanut',
            'source' => 'customer_declared',
            'risk_status' => 'matched',
            'staff_acknowledged_at' => null,
        ]);
    }

    public function test_order_records_unknown_risk_when_ingredient_data_is_unverified(): void
    {
        $context = $this->createRestaurantContext();
        $allergenId = $this->createAllergen('Shellfish');
        $ingredientId = $this->createIngredient($context['restaurant_id'], 'Prawns', false);
        $itemId = $this->createMenuItem($context, 'Prawn pasta', 15000, true);
        $this->linkIngredientToItem($itemId, $ingredientId);

        $this->post(
            route('customer.order', $context['qr_code']),
            $this->orderPayload([['id' => $itemId, 'quantity' => 1]], [$allergenId])
        )->assertRedirect(route('customer.menu', $context['qr_code']));

        $orderId = DB::table('orders')->value('id');

        $this->assertDatabaseHas('order_allergens', [
            'order_id' => $orderId,
            'allergen_name' => 'Shellfish',
            'risk_status' => 'unknown',
        ]);
    }

    public function test_order_records_no_known_match_only_when_all_data_is_verified(): void
    {
        $context = $this->createRestaurantContext();
        $allergenId = $this->createAllergen('Dairy');
        $ingredientId = $this->createIngredient($context['restaurant_id'], 'Rice', true);
        $itemId = $this->createMenuItem($context, 'Plain rice', 5000, true);
        $this->linkIngredientToItem($itemId, $ingredientId);

        $this->post(
            route('customer.order', $context['qr_code']),
            $this->orderPayload([['id' => $itemId, 'quantity' => 1]], [$allergenId])
        )->assertRedirect(route('customer.menu', $context['qr_code']));

        $orderId = DB::table('orders')->value('id');

        $this->assertDatabaseHas('order_allergens', [
            'order_id' => $orderId,
            'allergen_name' => 'Dairy',
            'risk_status' => 'no_known_match',
        ]);
    }

    private function createRestaurantContext(
        string $slug = 'safeserve-test',
        string $qrCode = 'test-table-qr'
    ): array {
        $restaurantId = DB::table('restaurants')->insertGetId([
            'name' => 'SafeServe Test Restaurant',
            'slug' => $slug,
            'currency' => 'TZS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tableId = DB::table('restaurant_tables')->insertGetId([
            'restaurant_id' => $restaurantId,
            'table_number' => 'T01',
            'qr_code' => $qrCode,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryId = DB::table('menu_categories')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Mains',
            'description' => 'Test category',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'restaurant_id' => $restaurantId,
            'table_id' => $tableId,
            'category_id' => $categoryId,
            'qr_code' => $qrCode,
        ];
    }

    private function createAllergen(string $name): int
    {
        return DB::table('allergens')->insertGetId([
            'name' => $name,
            'description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createIngredient(int $restaurantId, string $name, bool $isVerified): int
    {
        return DB::table('ingredients')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => $name,
            'description' => null,
            'is_verified' => $isVerified,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createMenuItem(array $context, string $name, float $price, bool $verified = false): int
    {
        return DB::table('menu_items')->insertGetId([
            'restaurant_id' => $context['restaurant_id'],
            'menu_category_id' => $context['category_id'],
            'name' => $name,
            'description' => 'Test menu item',
            'price' => $price,
            'is_available' => true,
            'ingredients_verified' => $verified,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function linkIngredientToItem(int $itemId, int $ingredientId): void
    {
        DB::table('menu_item_ingredients')->insert([
            'menu_item_id' => $itemId,
            'ingredient_id' => $ingredientId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function linkAllergenToIngredient(int $ingredientId, int $allergenId): void
    {
        DB::table('ingredient_allergens')->insert([
            'ingredient_id' => $ingredientId,
            'allergen_id' => $allergenId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function orderPayload(array $cart, array $allergies = [], array $extra = []): array
    {
        return array_merge([
            'cart_json' => json_encode($cart, JSON_THROW_ON_ERROR),
            'allergies' => $allergies,
            'customer_name' => 'Amina',
            'customer_notes' => 'Please prepare it freshly.',
        ], $extra);
    }
}
