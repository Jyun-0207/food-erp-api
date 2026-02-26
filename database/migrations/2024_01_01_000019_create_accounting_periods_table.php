<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('periodType');
            $table->integer('year');
            $table->integer('month')->nullable();
            $table->integer('quarter')->nullable();
            $table->date('startDate');
            $table->date('endDate');
            $table->string('status')->default('open');
            $table->timestamp('closedAt')->nullable();
            $table->string('closedBy')->nullable();
            $table->json('trialBalance')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('createdAt')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};
