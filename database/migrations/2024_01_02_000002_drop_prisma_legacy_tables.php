<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('nextauth_accounts');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('verification_tokens');
    }

    public function down(): void
    {
        // These tables were Prisma/NextAuth leftovers with no further use — not recreated.
    }
};
