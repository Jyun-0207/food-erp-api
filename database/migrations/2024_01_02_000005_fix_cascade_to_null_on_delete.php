<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Accounts receivable: preserve records when customer is deleted
        Schema::table('accounts_receivable', function (Blueprint $table) {
            $table->dropForeign(['customerId']);
            $table->foreign('customerId')->references('id')->on('customers')->nullOnDelete();
        });

        // Make customerId nullable (required for nullOnDelete to work)
        Schema::table('accounts_receivable', function (Blueprint $table) {
            $table->string('customerId')->nullable()->change();
        });

        // Accounts payable: preserve records when supplier is deleted
        Schema::table('accounts_payable', function (Blueprint $table) {
            $table->dropForeign(['supplierId']);
            $table->foreign('supplierId')->references('id')->on('suppliers')->nullOnDelete();
        });

        Schema::table('accounts_payable', function (Blueprint $table) {
            $table->string('supplierId')->nullable()->change();
        });

        // Attendance records: preserve records when employee is deleted
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['employeeId']);
            $table->foreign('employeeId')->references('id')->on('employees')->nullOnDelete();
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->string('employeeId')->nullable()->change();
        });

        // Leave applications: preserve records when employee is deleted
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropForeign(['employeeId']);
            $table->foreign('employeeId')->references('id')->on('employees')->nullOnDelete();
        });

        Schema::table('leave_applications', function (Blueprint $table) {
            $table->string('employeeId')->nullable()->change();
        });

        // Leave applications: preserve records when leave type is deleted
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropForeign(['leaveTypeId']);
            $table->foreign('leaveTypeId')->references('id')->on('leave_types')->nullOnDelete();
        });

        Schema::table('leave_applications', function (Blueprint $table) {
            $table->string('leaveTypeId')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Revert to cascadeOnDelete (original behavior)
        Schema::table('accounts_receivable', function (Blueprint $table) {
            $table->dropForeign(['customerId']);
            $table->foreign('customerId')->references('id')->on('customers')->cascadeOnDelete();
        });

        Schema::table('accounts_payable', function (Blueprint $table) {
            $table->dropForeign(['supplierId']);
            $table->foreign('supplierId')->references('id')->on('suppliers')->cascadeOnDelete();
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['employeeId']);
            $table->foreign('employeeId')->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropForeign(['employeeId']);
            $table->foreign('employeeId')->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropForeign(['leaveTypeId']);
            $table->foreign('leaveTypeId')->references('id')->on('leave_types')->cascadeOnDelete();
        });
    }
};
