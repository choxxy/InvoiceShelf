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
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('width', 15, 2)->nullable()->after('quantity');
            $table->decimal('length', 15, 2)->nullable()->after('width');
            $table->unsignedInteger('count')->nullable()->after('length');
            $table->decimal('area', 15, 2)->nullable()->after('count');
        });

        Schema::table('estimate_items', function (Blueprint $table) {
            $table->decimal('width', 15, 2)->nullable()->after('quantity');
            $table->decimal('length', 15, 2)->nullable()->after('width');
            $table->unsignedInteger('count')->nullable()->after('length');
            $table->decimal('area', 15, 2)->nullable()->after('count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['width', 'length', 'count', 'area']);
        });

        Schema::table('estimate_items', function (Blueprint $table) {
            $table->dropColumn(['width', 'length', 'count', 'area']);
        });
    }
};
