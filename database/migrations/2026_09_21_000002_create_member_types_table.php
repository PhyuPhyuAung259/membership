<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membership tiers, and what each one costs per month.
 *
 * The fee lives here rather than on each member, so raising the Gold rate is
 * one edit rather than an update across every Gold company. A company can
 * still override it — members.monthly_fee becomes nullable in the next
 * migration, and null means "use this tier's fee".
 *
 * Additive: creates one new table and touches nothing that already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->text('description')->nullable();

            $table->decimal('monthly_fee', 10, 2)->default(0);

            // Tiers have a rank, and alphabetical order gets it wrong
            // (Bronze, Gold, Platinum, Silver). Lower sorts first.
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
        });

        DB::statement('CREATE UNIQUE INDEX member_types_name_lower_idx ON member_types (lower(name))');
        DB::statement('ALTER TABLE member_types ADD CONSTRAINT member_types_fee_positive CHECK (monthly_fee >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('member_types');
    }
};
