<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('productId');
            $table->string('productName')->nullable();
            $table->string('batchNumber');
            $table->date('manufacturingDate')->nullable();
            $table->date('expirationDate')->nullable();
            $table->date('receivedDate')->nullable();
            $table->integer('initialQuantity')->default(0);
            $table->integer('currentQuantity')->default(0);
            $table->integer('reservedQuantity')->default(0);
            $table->string('supplierId')->nullable();
            $table->string('supplierName')->nullable();
            $table->string('purchaseOrderId')->nullable();
            $table->string('purchaseOrderNumber')->nullable();
            $table->decimal('costPrice', 15, 2)->default(0);
            $table->string('location')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();

            $table->foreign('productId')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('supplierId')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('purchaseOrderId')->references('id')->on('purchase_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
