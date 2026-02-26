<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_vouchers', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('voucherNumber')->unique();
            $table->string('voucherType');
            $table->date('voucherDate');
            $table->json('lines')->nullable();
            $table->decimal('totalDebit', 15, 2)->default(0);
            $table->decimal('totalCredit', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->json('attachments')->nullable();
            $table->string('reference')->nullable();
            $table->string('status')->default('draft');
            $table->string('preparedBy')->nullable();
            $table->timestamp('preparedAt')->nullable();
            $table->string('reviewedBy')->nullable();
            $table->timestamp('reviewedAt')->nullable();
            $table->string('approvedBy')->nullable();
            $table->timestamp('approvedAt')->nullable();
            $table->text('rejectedReason')->nullable();
            $table->string('voidedBy')->nullable();
            $table->timestamp('voidedAt')->nullable();
            $table->text('voidedReason')->nullable();
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_vouchers');
    }
};
