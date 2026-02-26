<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('orderNumber')->unique();
            $table->string('customerId')->nullable();
            $table->string('customerName')->nullable();
            $table->string('status')->default('pending');
            $table->json('items')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('shipping', 15, 2)->default(0);
            $table->decimal('totalAmount', 15, 2)->default(0);
            $table->json('shippingAddress')->nullable();
            $table->string('paymentMethod')->nullable();
            $table->string('paymentStatus')->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamp('returnedDate')->nullable();
            $table->text('returnReason')->nullable();
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
            $table->timestamp('deletedAt')->nullable();

            $table->foreign('customerId')->references('id')->on('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
