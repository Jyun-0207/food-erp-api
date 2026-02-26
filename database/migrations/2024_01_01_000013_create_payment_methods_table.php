<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->boolean('isActive')->default(true);
            $table->boolean('requiresOnlinePayment')->default(false);
            $table->string('type')->nullable();
            $table->string('cvsType')->nullable();
            $table->string('accountId')->nullable();
            $table->timestamp('createdAt')->nullable();

            $table->foreign('accountId')->references('id')->on('chart_of_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
