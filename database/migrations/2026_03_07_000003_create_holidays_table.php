<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->date('date');
            $table->integer('year');
            $table->string('description')->nullable();
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();

            $table->index('year');
            $table->unique(['date', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
