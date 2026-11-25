<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerTableCodeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_table_code_form_renders(): void
    {
        $this->get(route('customer.table-code'))
            ->assertOk()
            ->assertSee('Enter your table code');
    }

    public function test_table_code_requires_a_value(): void
    {
        $this->from(route('customer.table-code'))
            ->post(route('customer.table-code.store'), ['table_code' => ''])
            ->assertRedirect(route('customer.table-code'))
            ->assertSessionHasErrors(['table_code' => 'Enter the table code shown on your table.']);
    }

    public function test_table_number_opens_the_canonical_menu(): void
    {
        $context = $this->createRestaurantContext();

        $this->from(route('customer.table-code'))
            ->post(route('customer.table-code.store'), ['table_code' => 't01'])
            ->assertRedirect(route('customer.menu', $context['qr_code']));

        $this->get(route('customer.menu', $context['qr_code']))
            ->assertOk()
            ->assertSee('Table T01');
    }

    public function test_full_code_selects_the_right_restaurant_when_table_numbers_overlap(): void
    {
        $this->createRestaurantContext('first-cafe', 'first-cafe-qr', 'T01');
        $second = $this->createRestaurantContext('second-cafe', 'second-cafe-qr', 'T01');

        $this->post(route('customer.table-code.store'), ['table_code' => 'second-cafe:t01'])
            ->assertRedirect(route('customer.menu', $second['qr_code']));
    }

    public function test_ambiguous_table_number_is_rejected(): void
    {
        $this->createRestaurantContext('first-cafe', 'first-cafe-qr', 'T01');
        $this->createRestaurantContext('second-cafe', 'second-cafe-qr', 'T01');

        $this->from(route('customer.table-code'))
            ->post(route('customer.table-code.store'), ['table_code' => 'T01'])
            ->assertRedirect(route('customer.table-code'))
            ->assertSessionHasErrors([
                'table_code' => 'This table number is used by more than one restaurant. Enter the full code shown on the table pad.',
            ]);
    }

    public function test_unknown_table_code_is_rejected(): void
    {
        $this->from(route('customer.table-code'))
            ->post(route('customer.table-code.store'), ['table_code' => 'T99'])
            ->assertRedirect(route('customer.table-code'))
            ->assertSessionHasErrors([
                'table_code' => 'No active table matches that code. Check the code on your table.',
            ]);
    }

    public function test_inactive_table_code_is_rejected(): void
    {
        $this->createRestaurantContext('inactive-cafe', 'inactive-cafe-qr', 'T01', false);

        $this->from(route('customer.table-code'))
            ->post(route('customer.table-code.store'), ['table_code' => 'T01'])
            ->assertRedirect(route('customer.table-code'))
            ->assertSessionHasErrors([
                'table_code' => 'No active table matches that code. Check the code on your table.',
            ]);
    }

    public function test_table_without_a_qr_code_is_rejected(): void
    {
        $this->createRestaurantContext('no-qr-cafe', null, 'T01');

        $this->from(route('customer.table-code'))
            ->post(route('customer.table-code.store'), ['table_code' => 'T01'])
            ->assertRedirect(route('customer.table-code'))
            ->assertSessionHasErrors([
                'table_code' => 'This table is not ready for ordering yet.',
            ]);
    }

    private function createRestaurantContext(
        string $slug = 'safeserve-code-test',
        ?string $qrCode = 'table-code-qr',
        string $tableNumber = 'T01',
        bool $isActive = true
    ): array {
        $restaurantId = DB::table('restaurants')->insertGetId([
            'name' => 'SafeServe Table Code Test Restaurant',
            'slug' => $slug,
            'currency' => 'TZS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tableId = DB::table('restaurant_tables')->insertGetId([
            'restaurant_id' => $restaurantId,
            'table_number' => $tableNumber,
            'qr_code' => $qrCode,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'restaurant_id' => $restaurantId,
            'table_id' => $tableId,
            'qr_code' => $qrCode,
        ];
    }
}
