<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffUserSeeder extends Seeder
{
    public function run(): void
    {
        $restaurantId = DB::table('restaurants')
            ->where('slug', 'safeserve-demo')
            ->value('id');

        $users = [
            [
                'name' => 'SafeServe Manager',
                'email' => 'manager@safeserve.test',
                'phone' => '+255700000001',
                'role' => 'manager',
            ],
            [
                'name' => 'Head Chef',
                'email' => 'kitchen@safeserve.test',
                'phone' => '+255700000002',
                'role' => 'kitchen',
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'phone' => $user['phone'],
                    'password' => Hash::make('password'),
                    'role' => $user['role'],
                    'restaurant_id' => $restaurantId,
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
