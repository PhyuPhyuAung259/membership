<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turns `members` into a register of COMPANIES.
 *
 * This is the only migration that touches an existing table, and it touches
 * exactly one: `members`. Nothing here goes near payments, events,
 * broadcasts, email_log, or the paid_through trigger — the billing and email
 * flow is left completely alone.
 *
 * Existing rows survive. `name` is renamed rather than dropped and recreated,
 * and whatever is in the old `member_type` column is lifted into the new
 * member_types table and linked, so a register that already has companies in
 * it comes out the other side intact.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. New columns. All nullable, so existing rows stay valid.
        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('business_type_id')->nullable()->after('email')
                ->constrained()->nullOnDelete();

            // Who to speak to at the company.
            $table->string('contact_person')->nullable()->after('phone');
            $table->string('contact_person_position', 120)->nullable()->after('contact_person');

            // restrictOnDelete: a tier that companies are on cannot be
            // deleted out from under them. Reassign them first.
            $table->foreignId('member_type_id')->nullable()->after('contact_person_position')
                ->constrained()->restrictOnDelete();

            // Relative paths within their storage disk, not URLs.
            // logo_path                  -> public disk, shown in the directory
            // registration_document_path -> PRIVATE disk, staff eyes only
            $table->string('logo_path')->nullable()->after('notes');
            $table->string('registration_document_path')->nullable()->after('logo_path');
        });

        // 2. Rename, in its own statement. Postgres is happier with a rename
        //    separated from column additions.
        Schema::table('members', function (Blueprint $table) {
            $table->renameColumn('name', 'company_name');
        });

        // 3. The fee moves to the tier. NULL on a member now means "use the
        //    tier's fee"; a value means this company is on a negotiated rate.
        Schema::table('members', function (Blueprint $table) {
            $table->decimal('monthly_fee', 10, 2)->nullable()->change();
        });

        // 4. Lift whatever is in the old varchar column into the new table
        //    and link it. A no-op on an empty register.
        $this->migrateExistingTypes();

        // 5. The varchar is now redundant. Two sources of truth for a
        //    member's tier is how they drift apart.
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('member_type');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->index('member_type_id');
            $table->index('business_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('member_type', 50)->default('standard')->nullable();
        });

        // Put the tier names back into the varchar before the FK goes.
        DB::statement(<<<'SQL'
            UPDATE members
               SET member_type = mt.name
              FROM member_types mt
             WHERE mt.id = members.member_type_id
        SQL);

        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['member_type_id']);
            $table->dropIndex(['business_type_id']);
            $table->dropConstrainedForeignId('member_type_id');
            $table->dropConstrainedForeignId('business_type_id');
            $table->dropColumn([
                'contact_person',
                'contact_person_position',
                'logo_path',
                'registration_document_path',
            ]);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->renameColumn('company_name', 'name');
        });

        // The column was NOT NULL before; give the nulls a value first or the
        // change fails.
        DB::table('members')->whereNull('monthly_fee')->update(['monthly_fee' => 0]);

        Schema::table('members', function (Blueprint $table) {
            $table->decimal('monthly_fee', 10, 2)->default(0)->nullable(false)->change();
        });
    }

    /**
     * Turn each distinct value of the old `member_type` varchar into a
     * member_types row, then point the member at it.
     *
     * Fees come across as zero — the old column carried no price. Set the
     * real tier fees afterwards, either in the admin screen or by running
     * ReferenceDataSeeder and reassigning.
     */
    private function migrateExistingTypes(): void
    {
        $names = DB::table('members')
            ->whereNotNull('member_type')
            ->distinct()
            ->pluck('member_type');

        foreach ($names as $name) {
            $name = trim((string) $name);

            if ($name === '') {
                continue;
            }

            $lower = mb_strtolower($name);

            $id = DB::table('member_types')->whereRaw('lower(name) = ?', [$lower])->value('id');

            if (! $id) {
                $id = DB::table('member_types')->insertGetId([
                    'name' => $name,
                    'monthly_fee' => 0,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('members')
                ->whereRaw('lower(member_type) = ?', [$lower])
                ->update(['member_type_id' => $id]);
        }
    }
};
