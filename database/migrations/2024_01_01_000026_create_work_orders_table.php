<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('workOrderNumber')->unique();
            $table->string('productId');
            $table->string('productName')->nullable();
            $table->integer('quantity')->default(0);
            $table->string('status')->default('pending');
            $table->date('scheduledDate')->nullable();
            $table->date('completedDate')->nullable();
            $table->string('bomId')->nullable();
            $table->json('materialUsage')->nullable();
            $table->json('qualityCheck')->nullable();
            $table->text('notes')->nullable();
            $table->string('createdBy')->nullable();
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();

            $table->foreign('productId')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('bomId')->references('id')->on('boms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
