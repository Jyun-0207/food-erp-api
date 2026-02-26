<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts_payable', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('supplierId');
            $table->string('supplierName')->nullable();
            $table->string('orderId')->nullable();
            $table->string('orderNumber')->nullable();
            $table->string('invoiceNumber')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('paidAmount', 15, 2)->default(0);
            $table->date('dueDate')->nullable();
            $table->string('status')->default('unpaid');
            $table->timestamp('createdAt')->nullable();

            $table->foreign('supplierId')->references('id')->on('suppliers')->cascadeOnDelete();
            $table->foreign('orderId')->references('id')->on('purchase_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_payable');
    }
};
