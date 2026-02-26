<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('orderNumber')->unique();
            $table->string('supplierId')->nullable();
            $table->string('supplierName')->nullable();
            $table->string('status')->default('pending');
            $table->json('items')->nullable();
            $table->decimal('totalAmount', 15, 2)->default(0);
            $table->string('paymentMethod')->nullable();
            $table->date('expectedDate')->nullable();
            $table->date('receivedDate')->nullable();
            $table->date('returnedDate')->nullable();
            $table->text('returnReason')->nullable();
            $table->boolean('refundReceived')->default(false);
            $table->text('notes')->nullable();
            $table->string('createdBy')->nullable();
            $table->string('approvedBy')->nullable();
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
            $table->timestamp('deletedAt')->nullable();

            $table->foreign('supplierId')->references('id')->on('suppliers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
