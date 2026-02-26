<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix AR/AP default status from 'unpaid' to 'pending'
        Schema::table('accounts_receivable', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });

        Schema::table('accounts_payable', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });

        // Add missing voucher columns: rejectedBy, rejectedAt
        Schema::table('accounting_vouchers', function (Blueprint $table) {
            $table->string('rejectedBy')->nullable()->after('rejectedReason');
            $table->timestamp('rejectedAt')->nullable()->after('rejectedBy');
        });

        // Fix work_orders default status from 'pending' to 'draft'
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
        });
    }

    public function down(): void
    {
        Schema::table('accounts_receivable', function (Blueprint $table) {
            $table->string('status')->default('unpaid')->change();
        });

        Schema::table('accounts_payable', function (Blueprint $table) {
            $table->string('status')->default('unpaid')->change();
        });

        Schema::table('accounting_vouchers', function (Blueprint $table) {
            $table->dropColumn(['rejectedBy', 'rejectedAt']);
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }
};
