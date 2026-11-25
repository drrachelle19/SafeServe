<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffWorkersTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login_from_workers_page(): void
    {
        $this->get(route('staff.workers.index'))
            ->assertRedirect(route('staff.login'));
    }

    public function test_kitchen_staff_cannot_manage_workers(): void
    {
        $context = $this->createRestaurantContext();
        $kitchenStaff = $this->createStaffUser($context['restaurant_id'], 'kitchen', 'blocked-kitchen@workers.test');

        $this->actingAs($kitchenStaff)
            ->get(route('staff.workers.index'))
            ->assertForbidden();

        $this->post(route('staff.workers.store'), [
            'name' => 'Blocked Worker',
            'email' => 'blocked-worker@workers.test',
            'phone' => '+255700000009',
        ])->assertForbidden();
    }

    public function test_manager_can_add_one_worker(): void
    {
        $context = $this->createRestaurantContext();
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager', 'manager@workers.test');

        $this->actingAs($manager)
            ->from(route('staff.workers.index'))
            ->post(route('staff.workers.store'), [
                'name' => 'Asha Mwangi',
                'email' => 'asha@workers.test',
                'phone' => '+255712345678',
            ])
            ->assertRedirect(route('staff.workers.index'))
            ->assertSessionHas('success', 'Asha Mwangi added to the worker list.');

        $worker = DB::table('users')->where('email', 'asha@workers.test')->first();

        $this->assertNotNull($worker);
        $this->assertSame('Asha Mwangi', $worker->name);
        $this->assertSame('+255712345678', $worker->phone);
        $this->assertSame('kitchen', $worker->role);
        $this->assertSame($context['restaurant_id'], (int) $worker->restaurant_id);
        $this->assertTrue((bool) $worker->is_active);
        $this->assertNotSame('asha@workers.test', $worker->password);

        $this->get(route('staff.workers.index'))
            ->assertOk()
            ->assertSee('Asha Mwangi')
            ->assertSee('asha@workers.test')
            ->assertSee('+255712345678');
    }

    public function test_kitchen_staff_cannot_edit_or_delete_workers(): void
    {
        $context = $this->createRestaurantContext();
        $kitchenStaff = $this->createStaffUser($context['restaurant_id'], 'kitchen', 'blocked-editor@workers.test');
        $worker = User::factory()->create([
            'name' => 'Protected Worker',
            'email' => 'protected-worker@workers.test',
            'role' => 'kitchen',
            'restaurant_id' => $context['restaurant_id'],
            'is_active' => true,
        ]);

        $this->actingAs($kitchenStaff)
            ->get(route('staff.workers.edit', $worker->id))
            ->assertForbidden();

        $this->put(route('staff.workers.update', $worker->id), [
            'name' => 'Changed Worker',
            'email' => 'changed-worker@workers.test',
        ])->assertForbidden();

        $this->delete(route('staff.workers.destroy', $worker->id))
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $worker->id,
            'name' => 'Protected Worker',
            'email' => 'protected-worker@workers.test',
        ]);
    }

    public function test_manager_can_edit_a_worker(): void
    {
        $context = $this->createRestaurantContext();
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager', 'edit-manager@workers.test');
        $worker = User::factory()->create([
            'name' => 'Old Worker Name',
            'email' => 'editable-worker@workers.test',
            'phone' => '+255700000010',
            'role' => 'kitchen',
            'restaurant_id' => $context['restaurant_id'],
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->get(route('staff.workers.edit', $worker->id))
            ->assertOk()
            ->assertSee('Edit worker details')
            ->assertSee('editable-worker@workers.test');

        $this->from(route('staff.workers.index'))
            ->put(route('staff.workers.update', $worker->id), [
                'name' => 'New Worker Name',
                'email' => 'editable-worker@workers.test',
                'phone' => '+255700000020',
            ])
            ->assertRedirect(route('staff.workers.index'))
            ->assertSessionHas('success', 'New Worker Name updated.');

        $this->assertDatabaseHas('users', [
            'id' => $worker->id,
            'name' => 'New Worker Name',
            'email' => 'editable-worker@workers.test',
            'phone' => '+255700000020',
            'role' => 'kitchen',
            'restaurant_id' => $context['restaurant_id'],
            'is_active' => true,
        ]);

        $this->get(route('staff.workers.index'))
            ->assertOk()
            ->assertSee('New Worker Name')
            ->assertSee('+255700000020');
    }

    public function test_update_rejects_email_used_by_another_worker(): void
    {
        $context = $this->createRestaurantContext();
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager', 'duplicate-email-manager@workers.test');
        $worker = User::factory()->create([
            'name' => 'Worker To Edit',
            'email' => 'worker-to-edit@workers.test',
            'role' => 'kitchen',
            'restaurant_id' => $context['restaurant_id'],
            'is_active' => true,
        ]);
        $otherWorker = User::factory()->create([
            'name' => 'Other Worker',
            'email' => 'other-worker@workers.test',
            'role' => 'kitchen',
            'restaurant_id' => $context['restaurant_id'],
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->from(route('staff.workers.index'))
            ->put(route('staff.workers.update', $worker->id), [
                'name' => 'Worker To Edit',
                'email' => $otherWorker->email,
            ])
            ->assertRedirect(route('staff.workers.index'))
            ->assertSessionHasErrors(['email' => 'The email has already been taken.']);

        $this->assertDatabaseHas('users', [
            'id' => $worker->id,
            'name' => 'Worker To Edit',
            'email' => 'worker-to-edit@workers.test',
        ]);
    }

    public function test_manager_can_delete_a_worker(): void
    {
        $context = $this->createRestaurantContext();
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager', 'delete-manager@workers.test');
        $worker = User::factory()->create([
            'name' => 'Worker To Delete',
            'email' => 'delete-worker@workers.test',
            'role' => 'kitchen',
            'restaurant_id' => $context['restaurant_id'],
            'is_active' => true,
        ]);
        DB::table('restaurant_worker_rotations')->insert([
            'restaurant_id' => $context['restaurant_id'],
            'last_worker_id' => $worker->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($manager)
            ->from(route('staff.workers.index'))
            ->delete(route('staff.workers.destroy', $worker->id))
            ->assertRedirect(route('staff.workers.index'))
            ->assertSessionHas('success', 'Worker To Delete removed from the worker list.');

        $this->assertDatabaseMissing('users', ['id' => $worker->id]);
        $this->assertDatabaseHas('restaurant_worker_rotations', [
            'restaurant_id' => $context['restaurant_id'],
            'last_worker_id' => null,
        ]);

        $this->get(route('staff.workers.index'))
            ->assertOk()
            ->assertDontSee('delete-worker@workers.test');
    }

    public function test_manager_cannot_edit_or_delete_worker_from_another_restaurant(): void
    {
        $context = $this->createRestaurantContext();
        $otherContext = $this->createRestaurantContext('other-edit-workers', 'other-edit-workers-qr');
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager', 'scoped-editor@workers.test');
        $otherWorker = User::factory()->create([
            'name' => 'Other Worker',
            'email' => 'other-edit-worker@workers.test',
            'role' => 'kitchen',
            'restaurant_id' => $otherContext['restaurant_id'],
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->get(route('staff.workers.edit', $otherWorker->id))
            ->assertNotFound();

        $this->put(route('staff.workers.update', $otherWorker->id), [
            'name' => 'Changed Other Worker',
            'email' => $otherWorker->email,
        ])->assertNotFound();

        $this->delete(route('staff.workers.destroy', $otherWorker->id))
            ->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $otherWorker->id,
            'name' => 'Other Worker',
            'email' => 'other-edit-worker@workers.test',
        ]);
    }

    public function test_manager_cannot_add_worker_with_invalid_phone(): void
    {
        $context = $this->createRestaurantContext();
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager', 'invalid-phone-manager@workers.test');

        $this->actingAs($manager)
            ->from(route('staff.workers.index'))
            ->post(route('staff.workers.store'), [
                'name' => 'Invalid Phone Worker',
                'email' => 'invalid-phone@workers.test',
                'phone' => ['not-a-string'],
            ])
            ->assertRedirect(route('staff.workers.index'))
            ->assertSessionHasErrors(['phone' => 'The phone field must be a string.']);

        $this->assertDatabaseMissing('users', ['email' => 'invalid-phone@workers.test']);
    }

    public function test_manager_can_upload_registered_workers_from_csv(): void
    {
        $context = $this->createRestaurantContext();
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager', 'upload-manager@workers.test');
        $csv = UploadedFile::fake()->createWithContent('workers.csv', implode("\n", [
            'name,email,phone',
            'Juma Ali,juma@workers.test,+255711111111',
            'Neema John,neema@workers.test,',
        ]));

        $this->actingAs($manager)
            ->from(route('staff.workers.index'))
            ->post(route('staff.workers.upload'), ['workers_csv' => $csv])
            ->assertRedirect(route('staff.workers.index'))
            ->assertSessionHas('success', '2 worker(s) uploaded from the CSV.');

        $this->assertDatabaseHas('users', [
            'email' => 'juma@workers.test',
            'name' => 'Juma Ali',
            'phone' => '+255711111111',
            'role' => 'kitchen',
            'restaurant_id' => $context['restaurant_id'],
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'neema@workers.test',
            'name' => 'Neema John',
            'phone' => null,
            'role' => 'kitchen',
            'restaurant_id' => $context['restaurant_id'],
            'is_active' => true,
        ]);
    }

    public function test_csv_with_wrong_columns_is_rejected_without_creating_workers(): void
    {
        $context = $this->createRestaurantContext();
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager', 'bad-csv-manager@workers.test');
        $csv = UploadedFile::fake()->createWithContent('workers.csv', "full_name,mail\nJuma Ali,juma@workers.test\n");

        $this->actingAs($manager)
            ->from(route('staff.workers.index'))
            ->post(route('staff.workers.upload'), ['workers_csv' => $csv])
            ->assertRedirect(route('staff.workers.index'))
            ->assertSessionHasErrors('workers_csv');

        $this->assertDatabaseMissing('users', ['email' => 'juma@workers.test']);
    }

    public function test_csv_with_invalid_phone_is_rejected_without_creating_workers(): void
    {
        $context = $this->createRestaurantContext();
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager', 'invalid-csv-phone-manager@workers.test');
        $csv = UploadedFile::fake()->createWithContent('workers.csv', implode("\n", [
            'name,email,phone',
            'Juma Ali,juma@workers.test,'.str_repeat('1', 31),
        ]));

        $this->actingAs($manager)
            ->from(route('staff.workers.index'))
            ->post(route('staff.workers.upload'), ['workers_csv' => $csv])
            ->assertRedirect(route('staff.workers.index'))
            ->assertSessionHasErrors(['workers_csv' => 'The worker phone field must not be greater than 30 characters.']);

        $this->assertDatabaseMissing('users', ['email' => 'juma@workers.test']);
    }

    public function test_manager_can_pause_and_resume_a_worker(): void
    {
        $context = $this->createRestaurantContext();
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager', 'toggle-manager@workers.test');
        $worker = User::factory()->create([
            'name' => 'Toggle Worker',
            'email' => 'toggle-worker@workers.test',
            'role' => 'kitchen',
            'restaurant_id' => $context['restaurant_id'],
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->from(route('staff.workers.index'))
            ->post(route('staff.workers.toggle', $worker->id))
            ->assertRedirect(route('staff.workers.index'))
            ->assertSessionHas('success', 'Toggle Worker removed from the rotation.');

        $this->assertDatabaseHas('users', [
            'id' => $worker->id,
            'is_active' => false,
        ]);

        $this->post(route('staff.workers.toggle', $worker->id))
            ->assertRedirect(route('staff.workers.index'))
            ->assertSessionHas('success', 'Toggle Worker returned to the rotation.');

        $this->assertDatabaseHas('users', [
            'id' => $worker->id,
            'is_active' => true,
        ]);
    }

    public function test_manager_cannot_pause_worker_from_another_restaurant(): void
    {
        $context = $this->createRestaurantContext();
        $otherContext = $this->createRestaurantContext('other-workers', 'other-workers-qr');
        $manager = $this->createStaffUser($context['restaurant_id'], 'manager', 'scoped-manager@workers.test');
        $otherWorker = User::factory()->create([
            'name' => 'Other Worker',
            'email' => 'other-worker@workers.test',
            'role' => 'kitchen',
            'restaurant_id' => $otherContext['restaurant_id'],
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->post(route('staff.workers.toggle', $otherWorker->id))
            ->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $otherWorker->id,
            'is_active' => true,
        ]);
    }

    private function createRestaurantContext(
        string $slug = 'workers-safeserve',
        string $qrCode = 'workers-table-qr'
    ): array {
        $restaurantId = DB::table('restaurants')->insertGetId([
            'name' => 'SafeServe Workers Test Restaurant',
            'slug' => $slug,
            'currency' => 'TZS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['restaurant_id' => $restaurantId];
    }

    private function createStaffUser(int $restaurantId, string $role, string $email): User
    {
        return User::factory()->create([
            'name' => 'Workers Test Manager',
            'email' => $email,
            'role' => $role,
            'restaurant_id' => $restaurantId,
        ]);
    }
}
