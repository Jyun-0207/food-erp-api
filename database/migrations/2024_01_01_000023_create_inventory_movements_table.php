<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('productId');
            $table->string('productName')->nullable();
            $table->string('type');
            $table->integer('quantity')->default(0);
            $table->integer('beforeStock')->default(0);
            $table->integer('afterStock')->default(0);
            $table->text('reason')->nullable();
            $table->string('reference')->nullable();
            $table->string('createdBy')->nullable();
            $table->timestamp('createdAt')->nullable();

            $table->foreign('productId')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
