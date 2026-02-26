<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('employeeNumber')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('pinCode')->nullable();
            $table->string('departmentId')->nullable();
            $table->string('departmentName')->nullable();
            $table->string('position')->nullable();
            $table->date('hireDate')->nullable();
            $table->date('resignDate')->nullable();
            $table->string('status')->default('active');
            $table->string('shiftTypeId')->nullable();
            $table->string('shiftTypeName')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
            $table->timestamp('deletedAt')->nullable();

            $table->foreign('departmentId')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('shiftTypeId')->references('id')->on('shift_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
