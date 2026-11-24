<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffOrderWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_staff_login_from_protected_pages(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('staff.login'));

        $this->get(route('kitchen'))
            ->assertRedirect(route('staff.login'));
    }

    public function test_customer_role_cannot_access_staff_dashboard(): void
    {
        $user = $this->createStaffUser(null, 'customer');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_staff_can_sign_in_and_view_real_dashboard_data(): void
    {
        $context = $this->createRestaurantContext();
        $this->createStaffUser($context['restaurant_id'], 'manager');
        $orderId = $this->createOrder($context['restaurant_id'], $context['table_id'], 'SS-DASH-001');
        $this->createAlert($orderId, 'Peanut', 'matched');

        $this->post(route('staff.login.store'), [
            'email' => $this->staffEmail(),
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('SS-DASH-001')
            ->assertSee('Peanut')
            ->assertSee('Restaurant overview');
    }

    public function test_kitchen_board_shows_only_the_staff_restaurant_orders(): void
    {
        $context = $this->createRestaurantContext();
        $otherContext = $this->createRestaurantContext('other-safeserve', 'other-table-qr');
        $this->createOrder($context['restaurant_id'], $context['table_id'], 'SS-KITCHEN-001');
        $this->createOrder($otherContext['restaurant_id'], $otherContext['table_id'], 'SS-OTHER-001');

        $this->actingAs($this->createStaffUser($context['restaurant_id'], 'kitchen'))
            ->get(route('kitchen'))
            ->assertOk()
            ->assertSee('SS-KITCHEN-001')
            ->assertDontSee('SS-OTHER-001');
    }

    public function test_kitchen_can_acknowledge_high_priority_alert_and_mark_order_reviewed(): void
    {
        $context = $this->createRestaurantContext();
        $staff = $this->createStaffUser($context['restaurant_id'], 'kitchen');
        $orderId = $this->createOrder($context['restaurant_id'], $context['table_id'], 'SS-ACK-001', 'requires_review');
        $alertId = $this->createAlert($orderId, 'Shellfish', 'matched');

        $this->actingAs($staff)
            ->from(route('kitchen'))
            ->post(route('kitchen.alerts.acknowledge', $alertId), [
                'staff_note' => 'Recipe checked with chef; substituted before preparation.',
            ])
            ->assertRedirect(route('kitchen'))
            ->assertSessionHas('success', 'Allergy alert acknowledged.');

        $alert = DB::table('order_allergens')->where('id', $alertId)->first();
        $this->assertNotNull($alert->staff_acknowledged_at);
        $this->assertSame('Recipe checked with chef; substituted before preparation.', $alert->staff_note);
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'allergy_review_status' => 'reviewed',
        ]);
    }

    public function test_kitchen_can_update_order_status_and_records_status_event(): void
    {
        $context = $this->createRestaurantContext();
        $staff = $this->createStaffUser($context['restaurant_id'], 'kitchen');
        $orderId = $this->createOrder($context['restaurant_id'], $context['table_id'], 'SS-STATUS-001');

        $this->actingAs($staff)
            ->from(route('kitchen'))
            ->post(route('kitchen.orders.status', $orderId), [
                'status' => 'preparing',
                'note' => 'Started in the kitchen.',
            ])
            ->assertRedirect(route('kitchen'))
            ->assertSessionHas('success', 'Order status updated.');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'preparing',
        ]);
        $this->assertDatabaseHas('order_status_events', [
            'order_id' => $orderId,
            'from_status' => 'pending',
            'to_status' => 'preparing',
            'note' => 'Started in the kitchen.',
            'changed_by' => $staff->id,
        ]);
    }

    public function test_staff_cannot_acknowledge_alert_from_another_restaurant(): void
    {
        $context = $this->createRestaurantContext();
        $otherContext = $this->createRestaurantContext('other-safeserve', 'other-table-qr');
        $staff = $this->createStaffUser($context['restaurant_id'], 'kitchen');
        $otherOrderId = $this->createOrder($otherContext['restaurant_id'], $otherContext['table_id'], 'SS-OTHER-002');
        $alertId = $this->createAlert($otherOrderId, 'Peanut', 'matched');

        $this->actingAs($staff)
            ->post(route('kitchen.alerts.acknowledge', $alertId), [
                'staff_note' => 'Should not be allowed.',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('order_allergens', [
            'id' => $alertId,
            'staff_note' => null,
        ]);
        $this->assertNull(DB::table('order_allergens')->where('id', $alertId)->value('staff_acknowledged_at'));
    }

    private function createRestaurantContext(
        string $slug = 'safeserve-test',
        string $qrCode = 'test-table-qr'
    ): array {
        $restaurantId = DB::table('restaurants')->insertGetId([
            'name' => 'SafeServe Staff Test Restaurant',
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
        ];
    }

    private function createStaffUser(?int $restaurantId, string $role): User
    {
        return User::factory()->create([
            'name' => $role === 'customer' ? 'Test Customer' : 'Test Staff Member',
            'email' => $this->staffEmail(),
            'role' => $role,
            'restaurant_id' => $restaurantId,
        ]);
    }

    private function staffEmail(): string
    {
        return 'staff-workflow@safeserve.test';
    }

    private function createOrder(
        int $restaurantId,
        int $tableId,
        string $orderNumber,
        string $reviewStatus = 'requires_review'
    ): int {
        return DB::table('orders')->insertGetId([
            'restaurant_id' => $restaurantId,
            'restaurant_table_id' => $tableId,
            'order_number' => $orderNumber,
            'customer_name' => 'Amina',
            'language' => 'en',
            'subtotal' => 12000,
            'tax' => 0,
            'total' => 12000,
            'status' => 'pending',
            'allergy_review_status' => $reviewStatus,
            'payment_status' => 'unpaid',
            'customer_notes' => 'Please check all ingredients.',
            'created_at' => now()->subMinutes(4),
            'updated_at' => now()->subMinutes(4),
        ]);
    }

    private function createAlert(int $orderId, string $allergenName, string $riskStatus): int
    {
        return DB::table('order_allergens')->insertGetId([
            'order_id' => $orderId,
            'order_item_id' => null,
            'allergen_id' => null,
            'allergen_name' => $allergenName,
            'source' => 'declared',
            'risk_status' => $riskStatus,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
