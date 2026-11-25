<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerOrderProgressTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_menu_shows_live_progress_for_the_recent_order(): void
    {
        $context = $this->createRestaurantContext();
        $this->createOrder($context, 'SS-PROGRESS-001', 'preparing');

        $this->withSession([
            'customer_recent_orders' => [$context['qr_code'] => 'SS-PROGRESS-001'],
        ])->get(route('customer.menu', $context['qr_code']))
            ->assertOk()
            ->assertSee('Order progress')
            ->assertSee('SS-PROGRESS-001')
            ->assertSee('The kitchen is preparing your food.')
            ->assertSee('Live updates are on. Your cart and order form will stay as they are.')
            ->assertSee('Step 5')
            ->assertSee('Served');
    }

    public function test_status_endpoint_returns_current_progress_and_stops_at_served(): void
    {
        $context = $this->createRestaurantContext();
        $this->createOrder($context, 'SS-PROGRESS-002', 'preparing');

        $this->withSession([
            'customer_recent_orders' => [$context['qr_code'] => 'SS-PROGRESS-002'],
        ])->get(route('customer.order.status', $context['qr_code']))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('order.order_number', 'SS-PROGRESS-002')
            ->assertJsonPath('order.status', 'preparing')
            ->assertJsonPath('order.status_label', 'Preparing')
            ->assertJsonPath('order.message', 'The kitchen is preparing your food.')
            ->assertJsonPath('order.step', 2)
            ->assertJsonPath('order.is_cancellable', true)
            ->assertJsonPath('order.is_final', false);

        DB::table('orders')
            ->where('order_number', 'SS-PROGRESS-002')
            ->update(['status' => 'completed', 'updated_at' => now()]);

        $this->get(route('customer.order.status', $context['qr_code']))
            ->assertOk()
            ->assertJsonPath('order.status', 'completed')
            ->assertJsonPath('order.status_label', 'Served')
            ->assertJsonPath('order.message', 'Enjoy your meal — your order has been served.')
            ->assertJsonPath('order.step', 4)
            ->assertJsonPath('order.is_cancellable', false)
            ->assertJsonPath('order.is_final', true);
    }

    public function test_status_endpoint_marks_cancelled_orders_as_final(): void
    {
        $context = $this->createRestaurantContext();
        $this->createOrder($context, 'SS-PROGRESS-003', 'cancelled');

        $this->withSession([
            'customer_recent_orders' => [$context['qr_code'] => 'SS-PROGRESS-003'],
        ])->get(route('customer.order.status', $context['qr_code']))
            ->assertOk()
            ->assertJsonPath('order.status', 'cancelled')
            ->assertJsonPath('order.status_label', 'Cancelled')
            ->assertJsonPath('order.step', -1)
            ->assertJsonPath('order.is_cancellable', false)
            ->assertJsonPath('order.is_final', true);
    }

    public function test_status_endpoint_returns_null_without_a_recent_order(): void
    {
        $context = $this->createRestaurantContext();

        $this->get(route('customer.order.status', $context['qr_code']))
            ->assertOk()
            ->assertJson(['order' => null]);
    }

    public function test_status_endpoint_does_not_expose_an_order_from_another_table(): void
    {
        $context = $this->createRestaurantContext();
        $otherTableId = DB::table('restaurant_tables')->insertGetId([
            'restaurant_id' => $context['restaurant_id'],
            'table_number' => 'T99',
            'qr_code' => 'other-progress-table-qr',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createOrder(
            ['restaurant_id' => $context['restaurant_id'], 'table_id' => $otherTableId],
            'SS-OTHER-TABLE',
            'preparing'
        );

        $this->withSession([
            'customer_recent_orders' => [$context['qr_code'] => 'SS-OTHER-TABLE'],
        ])->get(route('customer.order.status', $context['qr_code']))
            ->assertOk()
            ->assertJson(['order' => null]);
    }

    public function test_status_endpoint_returns_404_for_unknown_table_qr_code(): void
    {
        $this->get(route('customer.order.status', 'unknown-progress-qr'))
            ->assertNotFound();
    }

    private function createRestaurantContext(
        string $slug = 'safeserve-progress-test',
        string $qrCode = 'progress-table-qr'
    ): array {
        $restaurantId = DB::table('restaurants')->insertGetId([
            'name' => 'SafeServe Progress Test Restaurant',
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

        return [
            'restaurant_id' => $restaurantId,
            'table_id' => $tableId,
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
