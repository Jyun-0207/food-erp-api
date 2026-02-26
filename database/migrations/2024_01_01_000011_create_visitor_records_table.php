<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_records', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('sessionId');
            $table->string('page');
            $table->string('referrer')->nullable();
            $table->string('userAgent')->nullable();
            $table->timestamp('timestamp')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_records');
    }
};
