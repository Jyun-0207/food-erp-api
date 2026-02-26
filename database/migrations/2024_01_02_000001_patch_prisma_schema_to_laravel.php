<?php

/**
 * Patch migration: align Prisma-created tables with Laravel migration definitions.
 *
 * Changes:
 * 1. Add missing `deletedAt` column to 6 SoftDeletes tables
 * 2. Widen decimal precision from (10,2)/(12,2) to (15,2) on monetary columns
 * 3. Make columns nullable where Laravel migrations expect nullable
 * 4. Fix default value mismatches
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add deletedAt to SoftDeletes tables ──
        $softDeleteTables = [
            'products',
            'customers',
            'suppliers',
            'employees',
            'sales_orders',
            'purchase_orders',
        ];

        foreach ($softDeleteTables as $table) {
            if (!Schema::hasColumn($table, 'deletedAt')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->timestamp('deletedAt')->nullable()->after('updatedAt');
                });
            }
        }

        // ── 2. products: fix precision, nullable, defaults ──
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->default(0)->change();
            $table->decimal('costPrice', 15, 2)->default(0)->change();
            $table->string('sku')->nullable()->change();
            $table->string('unit')->nullable()->change();
            $table->json('images')->nullable()->change();
            $table->json('supplierIds')->nullable()->change();
            $table->json('allergenIds')->nullable()->change();
            $table->json('categoryIds')->nullable()->change();
            $table->boolean('taxable')->default(false)->change();
            $table->boolean('purchasable')->default(false)->change();
            $table->boolean('requiresBatch')->default(false)->change();
        });

        // ── 3. customers: fix precision, nullable ──
        Schema::table('customers', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone')->nullable()->change();
            $table->json('address')->nullable()->change();
            $table->decimal('creditLimit', 15, 2)->default(0)->change();
        });

        // ── 4. suppliers: fix nullable, type ──
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone')->nullable()->change();
            $table->string('address')->nullable()->change(); // text → varchar, nullable
        });

        // ── 5. employees: fix nullable ──
        Schema::table('employees', function (Blueprint $table) {
            $table->date('hireDate')->nullable()->change();
        });

        // ── 6. sales_orders: fix precision, nullable, defaults ──
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('customerName')->nullable()->change();
            $table->json('items')->nullable()->change();
            $table->json('shippingAddress')->nullable()->change();
            $table->string('paymentMethod')->nullable()->change();
            $table->decimal('subtotal', 15, 2)->default(0)->change();
            $table->decimal('tax', 15, 2)->default(0)->change();
            $table->decimal('shipping', 15, 2)->default(0)->change();
            $table->decimal('totalAmount', 15, 2)->default(0)->change();
            $table->string('paymentStatus')->default('unpaid')->change();
        });

        // ── 7. purchase_orders: fix precision, nullable, defaults ──
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('supplierName')->nullable()->change();
            $table->json('items')->nullable()->change();
            $table->decimal('totalAmount', 15, 2)->default(0)->change();
            $table->string('status')->default('pending')->change();
        });
    }

    public function down(): void
    {
        // Remove deletedAt columns
        $softDeleteTables = [
            'products',
            'customers',
            'suppliers',
            'employees',
            'sales_orders',
            'purchase_orders',
        ];

        foreach ($softDeleteTables as $table) {
            if (Schema::hasColumn($table, 'deletedAt')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('deletedAt');
                });
            }
        }

        // Revert precision, nullable, defaults back to Prisma originals
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->change();
            $table->decimal('costPrice', 10, 2)->change();
            $table->string('sku')->nullable(false)->change();
            $table->string('unit')->nullable(false)->change();
            $table->json('images')->nullable(false)->change();
            $table->json('supplierIds')->nullable(false)->change();
            $table->json('allergenIds')->nullable(false)->change();
            $table->json('categoryIds')->nullable(false)->change();
            $table->boolean('taxable')->default(true)->change();
            $table->boolean('purchasable')->default(true)->change();
            $table->boolean('requiresBatch')->default(true)->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
            $table->json('address')->nullable(false)->change();
            $table->decimal('creditLimit', 12, 2)->nullable()->change();
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
            $table->text('address')->nullable()->change();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->date('hireDate')->nullable(false)->change();
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('customerName')->nullable(false)->change();
            $table->json('items')->nullable(false)->change();
            $table->json('shippingAddress')->nullable(false)->change();
            $table->string('paymentMethod')->nullable(false)->change();
            $table->decimal('subtotal', 10, 2)->change();
            $table->decimal('tax', 10, 2)->default(0)->change();
            $table->decimal('shipping', 10, 2)->default(0)->change();
            $table->decimal('totalAmount', 10, 2)->change();
            $table->string('paymentStatus')->default('pending')->change();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('supplierName')->nullable(false)->change();
            $table->json('items')->nullable(false)->change();
            $table->decimal('totalAmount', 10, 2)->change();
            $table->string('status')->default('draft')->change();
        });
    }
};
