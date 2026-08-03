<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // 客戶編號 (e.g. C00008) — printed as 請款單位 on 客戶應收對帳明細表.
            // Nullable because every existing customer predates the field.
            $table->string('code', 50)->nullable()->unique()->after('id');
            // 客戶傳真 — printed on the same statement.
            $table->string('fax', 50)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'fax']);
        });
    }
};
