<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerOrderCancellationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_cancels_active_order_and_records_the_reason(): void
    {
        $context = $this->createRestaurantContext();
        $orderId = $this->createOrder($context, 'SS-CANCEL-001', 'preparing');

        $this->from(route('customer.menu', $context['qr_code']))
            ->post(route('customer.order.cancel', $context['qr_code']), [
                'order_number' => 'ss-cancel-001',
                'cancellation_reason' => 'Emergency: guest had to leave.',
            ])
            ->assertRedirect(route('customer.menu', $context['qr_code']))
            ->assertSessionHas('success', 'Order SS-CANCEL-001 was cancelled. Staff have been notified.');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('order_status_events', [
            'order_id' => $orderId,
            'from_status' => 'preparing',
            'to_status' => 'cancelled',
            'note' => 'Cancelled by customer: Emergency: guest had to leave.',
            'changed_by' => null,
        ]);
    }

    public function test_kitchen_board_shows_customer_cancellation_reason(): void
    {
        $context = $this->createRestaurantContext();
        $this->createOrder($context, 'SS-CANCEL-003', 'pending');
        $staff = User::factory()->create([
            'name' => 'Kitchen Staff',
            'email' => 'kitchen@cancellation.test',
            'role' => 'kitchen',
            'restaurant_id' => $context['restaurant_id'],
        ]);

        $this->post(route('customer.order.cancel', $context['qr_code']), [
            'order_number' => 'SS-CANCEL-003',
            'cancellation_reason' => 'Guest had a medical emergency.',
        ])->assertRedirect(route('customer.menu', $context['qr_code']));

        $this->actingAs($staff)
            ->get(route('kitchen'))
            ->assertOk()
            ->assertSee('Cancelled today')
            ->assertSee('Guest had a medical emergency.');
    }

    public function test_customer_cannot_cancel_order_placed_at_another_table(): void
    {
        $context = $this->createRestaurantContext();
        $otherTableId = DB::table('restaurant_tables')->insertGetId([
            'restaurant_id' => $context['restaurant_id'],
            'table_number' => 'T02',
            'qr_code' => 'other-table-qr',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $orderId = $this->createOrder(
            ['restaurant_id' => $context['restaurant_id'], 'table_id' => $otherTableId],
            'SS-OTHER-TABLE',
            'pending'
        );

        $this->post(route('customer.order.cancel', $context['qr_code']), [
            'order_number' => 'SS-OTHER-TABLE',
            'cancellation_reason' => 'Should not work.',
        ])->assertSessionHasErrors([
            'order_number' => 'No order with that number was found for this table.',
        ]);

        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'pending']);
        $this->assertDatabaseMissing('order_status_events', [
            'order_id' => $orderId,
            'to_status' => 'cancelled',
        ]);
    }

    public function test_customer_cannot_cancel_completed_order(): void
    {
        $context = $this->createRestaurantContext();
        $orderId = $this->createOrder($context, 'SS-COMPLETED-001', 'completed');

        $this->post(route('customer.order.cancel', $context['qr_code']), [
            'order_number' => 'SS-COMPLETED-001',
            'cancellation_reason' => 'Too late.',
        ])->assertSessionHasErrors([
            'order_number' => 'This order can no longer be cancelled. Please ask staff for help.',
        ]);

        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'completed']);
        $this->assertDatabaseMissing('order_status_events', [
            'order_id' => $orderId,
            'to_status' => 'cancelled',
        ]);
    }

    public function test_cancellation_requires_order_number_and_reason(): void
    {
        $context = $this->createRestaurantContext();

        $this->post(route('customer.order.cancel', $context['qr_code']), [])
            ->assertSessionHasErrors([
                'order_number' => 'Enter the order number you want to cancel.',
                'cancellation_reason' => 'Tell us why you need to cancel this order.',
            ]);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_cancellation_returns_404_for_unknown_table_qr_code(): void
    {
        $this->post(route('customer.order.cancel', 'unknown-qr-code'), [
            'order_number' => 'SS-ANY-001',
            'cancellation_reason' => 'Emergency.',
        ])->assertNotFound();
    }

    public function test_menu_shows_recent_order_number_after_submission(): void
    {
        $context = $this->createRestaurantContext();
        $itemId = DB::table('menu_items')->insertGetId([
            'restaurant_id' => $context['restaurant_id'],
            'menu_category_id' => $context['category_id'],
            'name' => 'Coffee',
            'price' => 6000,
            'is_available' => true,
            'ingredients_verified' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('customer.order', $context['qr_code']), [
            'cart_json' => json_encode([['id' => $itemId, 'quantity' => 1]], JSON_THROW_ON_ERROR),
        ])->assertRedirect(route('customer.menu', $context['qr_code']));

        $orderNumber = DB::table('orders')->value('order_number');

        $this->get(route('customer.menu', $context['qr_code']))
            ->assertOk()
            ->assertSee('Emergency? Cancel an order')
            ->assertSee($orderNumber);
    }

    private function createRestaurantContext(
        string $slug = 'safeserve-cancel-test',
        string $qrCode = 'cancel-table-qr'
    ): array {
        $restaurantId = DB::table('restaurants')->insertGetId([
            'name' => 'SafeServe Cancellation Test Restaurant',
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
            'name' => 'Drinks',
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

    private function createOrder(array $context, string $orderNumber, string $status): int
    {
        return DB::table('orders')->insertGetId([
            'restaurant_id' => $context['restaurant_id'],
            'restaurant_table_id' => $context['table_id'],
            'order_number' => $orderNumber,
            'customer_name' => 'Amina',
            'language' => 'en',
            'subtotal' => 12000,
            'tax' => 0,
            'total' => 12000,
            'status' => $status,
            'allergy_review_status' => 'not_required',
            'payment_status' => 'unpaid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
