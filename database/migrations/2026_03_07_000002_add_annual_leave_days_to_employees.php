<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Manually configured annual leave entitlement (days/year).
            // When null, the auto-calculation based on Labor Standards Act §38 is used.
            $table->integer('annualLeaveDays')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('annualLeaveDays');
        });
    }
};
