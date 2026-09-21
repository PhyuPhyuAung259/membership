<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The register of members, before it became a register of companies.
 *
 * Guarded with hasTable(): every environment this project has actually run
 * in already has this table — it predates migration tracking here — so this
 * only does real work on a fresh database (a new dev setup, or the test
 * database that RefreshDatabase rebuilds from nothing). Running it against an
 * existing database is a no-op, never a collision.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('members')) {
            return;
        }

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 40)->nullable();
            $table->string('member_type', 50)->default('standard')->nullable();

            // active -> lapsed -> cancelled. cancelled is a human decision and
            // is never set by anything but an explicit admin action.
            $table->string('status', 20)->default('active');

            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->date('join_date');

            // Maintained by the paid_through trigger, not written to directly.
            $table->date('paid_through')->nullable();

            $table->boolean('marketing_opt_in')->default(true);
            $table->timestamp('unsubscribed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('paid_through');
        });

        DB::statement("ALTER TABLE members ADD CONSTRAINT members_status_check CHECK (status IN ('active', 'lapsed', 'cancelled'))");
        DB::statement('ALTER TABLE members ADD CONSTRAINT members_monthly_fee_positive CHECK (monthly_fee >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
