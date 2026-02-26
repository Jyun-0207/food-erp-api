<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('employeeId');
            $table->string('employeeName')->nullable();
            $table->string('leaveTypeId');
            $table->string('leaveTypeName')->nullable();
            $table->date('startDate');
            $table->date('endDate');
            $table->decimal('days', 5, 1)->default(0);
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->string('approvedBy')->nullable();
            $table->timestamp('approvedAt')->nullable();
            $table->text('rejectedReason')->nullable();
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();

            $table->foreign('employeeId')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('leaveTypeId')->references('id')->on('leave_types')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
    }
};
