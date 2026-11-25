<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffReportsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login_from_sales_report(): void
    {
        $this->get(route('staff.reports.sales'))
            ->assertRedirect(route('staff.login'));
    }

    public function test_kitchen_staff_cannot_view_sales_report(): void
    {
        $context = $this->createRestaurantContext();
        $kitchenStaff = $this->createStaffUser($context['restaurant_id'], 'kitchen', 'kitchen@sales.test');

        $this->actingAs($kitchenStaff)
            ->get(route('staff.reports.sales'))
            ->assertForbidden();
    }

    public function test_manager_sees_only_their_restaurant_revenue(): void
    {
        $context = $this->createRestaurantContext();
        $otherContext = $this->createRestaurantContext('other-sales-test', 'other-sales-qr');
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager', 'manager@sales.test');
        $this->createOrder($context, 'SS-SALES-001', 10000, 'completed');
        $this->createOrder($otherContext, 'SS-OTHER-001', 99000, 'completed');

        $this->actingAs($manager)
            ->get(route('staff.reports.sales'))
            ->assertOk()
            ->assertSee('Sales report')
            ->assertSee('TZS 10,000')
            ->assertDontSee('TZS 99,000');
    }

    public function test_cancelled_orders_are_excluded_from_revenue(): void
    {
        $this->travelTo('2026-11-25 12:00:00');

        $context = $this->createRestaurantContext();
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager', 'manager@sales.test');
        $this->createOrder($context, 'SS-SALES-002', 10000, 'completed');
        $this->createOrder($context, 'SS-CANCELLED-002', 5000, 'cancelled');

        $this->actingAs($manager)
            ->get(route('staff.reports.sales'))
            ->assertOk()
            ->assertSee('TZS 10,000')
            ->assertDontSee('TZS 15,000');
    }

    public function test_monthly_comparison_highlights_the_best_sales_month(): void
    {
        $this->travelTo('2026-06-15 12:00:00');

        $context = $this->createRestaurantContext();
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager', 'manager@sales.test');
        $this->createOrder($context, 'SS-APR-001', 5000, 'completed', '2026-04-10 12:00:00');
        $this->createOrder($context, 'SS-MAY-001', 25000, 'completed', '2026-05-10 12:00:00');
        $this->createOrder($context, 'SS-JUN-001', 9000, 'completed', '2026-06-10 12:00:00');

        $this->actingAs($manager)
            ->get(route('staff.reports.sales'))
            ->assertOk()
            ->assertSee('TZS 25,000')
            ->assertSeeInOrder(['May 2026', 'Best month']);
    }

    public function test_month_range_shows_thirty_days_and_invalid_range_falls_back_to_week(): void
    {
        $context = $this->createRestaurantContext();
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager', 'manager@sales.test');

        $this->actingAs($manager)
            ->get(route('staff.reports.sales', ['range' => 'month']))
            ->assertOk()
            ->assertSee('Last 30 days')
            ->assertSee('Across 30 day(s)');

        $this->get(route('staff.reports.sales', ['range' => 'nonsense']))
            ->assertOk()
            ->assertSee('Last 7 days')
            ->assertSee('Across 7 day(s)');
    }

    private function createRestaurantContext(
        string $slug = 'safeserve-sales-test',
        string $qrCode = 'sales-table-qr'
    ): array {
        $restaurantId = DB::table('restaurants')->insertGetId([
            'name' => 'SafeServe Sales Test Restaurant',
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

    private function createStaffUser(int $restaurantId, string $role, string $email): User
    {
        return User::factory()->create([
            'name' => 'Sales Report Staff',
            'email' => $email,
            'role' => $role,
            'restaurant_id' => $restaurantId,
        ]);
    }

    private function createOrder(
        array $context,
        string $orderNumber,
        float $total,
        string $status,
        ?string $createdAt = null
    ): int {
        $timestamp = $createdAt ? Carbon::parse($createdAt) : now();

        return DB::table('orders')->insertGetId([
            'restaurant_id' => $context['restaurant_id'],
            'restaurant_table_id' => $context['table_id'],
            'order_number' => $orderNumber,
            'customer_name' => 'Amina',
            'language' => 'en',
            'subtotal' => $total,
            'tax' => 0,
            'total' => $total,
            'status' => $status,
            'allergy_review_status' => 'not_required',
            'payment_status' => 'unpaid',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
}
