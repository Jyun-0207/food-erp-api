<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('employeeId');
            $table->string('employeeName')->nullable();
            $table->date('date');
            $table->string('shiftTypeId')->nullable();
            $table->string('shiftTypeName')->nullable();
            $table->timestamp('checkInTime')->nullable();
            $table->timestamp('checkOutTime')->nullable();
            $table->float('workHours')->nullable();
            $table->string('status')->default('present');
            $table->text('notes')->nullable();
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();

            $table->foreign('employeeId')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('shiftTypeId')->references('id')->on('shift_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
