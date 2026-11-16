
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // RESTAURANTS
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('currency', 3)->default('TZS');
            $table->timestamps();
        });

        // RESTAURANT TABLES
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('table_number');
            $table->string('qr_code')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['restaurant_id', 'table_number']);
        });

        // MENU CATEGORIES
        Schema::create('menu_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ALLERGENS
        Schema::create('allergens', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // INGREDIENTS
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->unique(['restaurant_id', 'name']);
        });

        // MENU ITEMS
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_category_id')
                ->constrained('menu_categories')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 12, 2);
            $table->boolean('is_available')->default(true);
            $table->boolean('ingredients_verified')->default(false);
            $table->timestamps();
        });

        // INGREDIENTS USED IN EACH MENU ITEM
        Schema::create('menu_item_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['menu_item_id', 'ingredient_id']);
        });

        // INGREDIENT-ALLERGEN RELATIONSHIP
        Schema::create('ingredient_allergens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('allergen_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['ingredient_id', 'allergen_id']);
        });

        // CUSTOMER ORDERS
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_table_id')
                ->nullable()->constrained('restaurant_tables')->nullOnDelete();

            $table->string('order_number')->unique();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('language', 5)->default('en');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->string('status')->default('pending');
            $table->string('allergy_review_status')->default('not_required');
            $table->string('payment_status')->default('unpaid');

            $table->text('customer_notes')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'status']);
        });

        // ORDER ITEMS
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_item_id')
                ->nullable()->constrained()->nullOnDelete();

            // Preserve the item details even if menu changes later
            $table->string('item_name');
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->text('special_instructions')->nullable();
            $table->timestamps();
        });

        // CUSTOMER ALLERGY DECLARATIONS AND SAFETY FLAGS
        Schema::create('order_allergens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')
                ->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('allergen_id')
                ->nullable()->constrained()->nullOnDelete();

            // Snapshot keeps the warning understandable if records change
            $table->string('allergen_name');
            $table->string('source')->default('declared');
            $table->string('risk_status')->default('requires_review');

            $table->timestamp('staff_acknowledged_at')->nullable();
            $table->text('staff_note')->nullable();
            $table->timestamps();
        });

        // ORDER STATUS HISTORY
        Schema::create('order_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('note')->nullable();
            $table->foreignId('changed_by')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_events');
        Schema::dropIfExists('order_allergens');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('ingredient_allergens');
        Schema::dropIfExists('menu_item_ingredients');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('allergens');
        Schema::dropIfExists('menu_categories');
        Schema::dropIfExists('restaurant_tables');
        Schema::dropIfExists('restaurants');
    }
};
