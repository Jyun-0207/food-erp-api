<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_types', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('startTime');
            $table->string('endTime');
            $table->string('breakStartTime')->nullable();
            $table->string('breakEndTime')->nullable();
            $table->integer('graceMinutes')->default(0);
            $table->boolean('isActive')->default(true);
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_types');
    }
};
