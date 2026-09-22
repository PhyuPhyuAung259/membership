<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Members can now self-register through the public registration form. A
 * self-registered row lands as 'pending' — not yet vetted by staff — rather
 * than 'active', so it never appears as a real, billable member until
 * someone reviews it.
 */
return new class extends Migration
{
    public function up(): void
    {
        // IF EXISTS: some environments never got the original constraint
        // (their `members` table pre-dates migration tracking, so the
        // creating migration's guard skipped it). Safe either way.
        DB::statement('ALTER TABLE members DROP CONSTRAINT IF EXISTS members_status_check');
        DB::statement("ALTER TABLE members ADD CONSTRAINT members_status_check CHECK (status IN ('active', 'lapsed', 'cancelled', 'pending'))");
    }

    public function down(): void
    {
        DB::table('members')->where('status', 'pending')->update(['status' => 'active']);

        DB::statement('ALTER TABLE members DROP CONSTRAINT IF EXISTS members_status_check');
        DB::statement("ALTER TABLE members ADD CONSTRAINT members_status_check CHECK (status IN ('active', 'lapsed', 'cancelled'))");
    }
};
