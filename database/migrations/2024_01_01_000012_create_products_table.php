<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('costPrice', 15, 2)->default(0);
            $table->string('categoryId')->nullable();
            $table->string('purchaseAccountId')->nullable();
            $table->string('salesAccountId')->nullable();
            $table->string('sku')->nullable();
            $table->integer('stock')->default(0);
            $table->integer('minStock')->default(0);
            $table->string('unit')->nullable();
            $table->json('images')->nullable();
            $table->boolean('taxable')->default(false);
            $table->boolean('purchasable')->default(false);
            $table->json('supplierIds')->nullable();
            $table->json('allergenIds')->nullable();
            $table->json('categoryIds')->nullable();
            $table->boolean('requiresBatch')->default(false);
            $table->integer('shelfLife')->nullable();
            $table->string('shelfLifeUnit')->nullable();
            $table->boolean('isActive')->default(true);
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
            $table->timestamp('deletedAt')->nullable();

            $table->foreign('categoryId')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('purchaseAccountId')->references('id')->on('chart_of_accounts')->nullOnDelete();
            $table->foreign('salesAccountId')->references('id')->on('chart_of_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
