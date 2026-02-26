<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('countNumber')->unique();
            $table->string('status')->default('draft');
            $table->json('items')->nullable();
            $table->text('notes')->nullable();
            $table->string('createdBy')->nullable();
            $table->timestamp('completedAt')->nullable();
            $table->timestamp('createdAt')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_counts');
    }
};
