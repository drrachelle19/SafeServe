<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 20)->default('customer')->after('email');
            $table->foreignId('restaurant_id')
                ->nullable()
                ->after('role')
                ->constrained()
                ->nullOnDelete();
            $table->index(['role', 'restaurant_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role', 'restaurant_id']);
            $table->dropForeign(['restaurant_id']);
            $table->dropColumn(['restaurant_id', 'role']);
        });
    }
};
