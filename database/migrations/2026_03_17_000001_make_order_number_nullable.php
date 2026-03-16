<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('orderNumber')->nullable()->change();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('orderNumber')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('orderNumber')->nullable(false)->change();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('orderNumber')->nullable(false)->change();
        });
    }
};
