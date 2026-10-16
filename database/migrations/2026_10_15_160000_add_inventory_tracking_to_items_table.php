<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('name');
            $table->boolean('track_inventory')->default(false)->after('tax_per_item');
            $table->decimal('initial_quantity', 15, 2)->default(0)->after('track_inventory');
            $table->decimal('quantity', 15, 2)->default(0)->after('initial_quantity');
            $table->decimal('reorder_level', 15, 2)->nullable()->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn([
                'sku',
                'track_inventory',
                'initial_quantity',
                'quantity',
                'reorder_level',
            ]);
        });
    }
};
