<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->date('numberingDate')->nullable()->after('orderDate');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->date('numberingDate')->nullable()->after('orderDate');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('numberingDate');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('numberingDate');
        });
    }
};
