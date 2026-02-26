<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->date('date');
            $table->text('description')->nullable();
            $table->json('entries')->nullable();
            $table->string('reference')->nullable();
            $table->string('createdBy')->nullable();
            $table->timestamp('createdAt')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
