<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffTableQrTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_manager_can_view_printable_table_qr_codes(): void
    {
        $context = $this->createRestaurantContext();
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager');

        $this->actingAs($manager)
            ->get(route('staff.tables.qr'))
            ->assertOk()
            ->assertSee('Table QR codes')
            ->assertSee('T01')
            ->assertSee(config('app.url').'/table/'.$context['qr_code'], false);
    }

    public function test_generated_menu_url_uses_configured_app_url(): void
    {
        config()->set('app.url', 'http://192.168.1.50:8000');

        $context = $this->createRestaurantContext();
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager');

        $this->actingAs($manager)
            ->get(route('staff.tables.qr'))
            ->assertOk()
            ->assertSee('http://192.168.1.50:8000/table/'.$context['qr_code'], false);
    }

    public function test_qr_image_returns_svg_for_own_restaurant_table(): void
    {
        $context = $this->createRestaurantContext();
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager');

        $response = $this->actingAs($manager)
            ->get(route('staff.tables.qr.image', $context['table_id']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', (string) $response->getContent());
    }

    public function test_kitchen_staff_cannot_view_table_qr_codes(): void
    {
        $context = $this->createRestaurantContext();
        $kitchenStaff = $this->createStaffUser($context['restaurant_id'], 'kitchen');

        $this->actingAs($kitchenStaff)
            ->get(route('staff.tables.qr'))
            ->assertForbidden();
    }

    public function test_manager_cannot_view_qr_for_another_restaurant_table(): void
    {
        $context = $this->createRestaurantContext();
        $otherContext = $this->createRestaurantContext('other-safeserve', 'other-table-qr');
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager');

        $this->actingAs($manager)
            ->get(route('staff.tables.qr.image', $otherContext['table_id']))
            ->assertNotFound();
    }

    private function createRestaurantContext(
        string $slug = 'safeserve-test',
        string $qrCode = 'test-table-qr'
    ): array {
        $restaurantId = DB::table('restaurants')->insertGetId([
            'name' => 'SafeServe QR Test Restaurant',
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

    private function createStaffUser(int $restaurantId, string $role): User
    {
        return User::factory()->create([
            'name' => 'QR Staff Member',
            'email' => 'qr-staff@safeserve.test',
            'role' => $role,
            'restaurant_id' => $restaurantId,
        ]);
    }
}
