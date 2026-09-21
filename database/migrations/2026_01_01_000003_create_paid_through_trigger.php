<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Keeps members.paid_through in step with the payment rows.
 *
 * This lives in the database rather than an Eloquent observer on purpose.
 * Payments are entered by hand and hand entry goes wrong, so corrections get
 * made in all sorts of ways: the admin screen, a tinker session, a bulk
 * UPDATE during a data fix, a seeder. An observer only fires for Eloquent
 * model events, so `Payment::where(...)->delete()` or a raw query would
 * silently leave a member's coverage wrong, and nothing would ever tell you.
 *
 * A trigger cannot be bypassed. The invariant holds no matter what writes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION refresh_paid_through(p_member_id BIGINT)
            RETURNS VOID AS $$
            BEGIN
                UPDATE members
                   SET paid_through = (
                           SELECT MAX(period_end) FROM payments WHERE member_id = p_member_id
                       ),
                       updated_at = now()
                 WHERE id = p_member_id;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION payments_touch_member()
            RETURNS TRIGGER AS $$
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    PERFORM refresh_paid_through(OLD.member_id);
                    RETURN OLD;
                END IF;

                PERFORM refresh_paid_through(NEW.member_id);

                IF TG_OP = 'UPDATE' AND NEW.member_id <> OLD.member_id THEN
                    PERFORM refresh_paid_through(OLD.member_id);
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared('DROP TRIGGER IF EXISTS payments_touch_member_trg ON payments');

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER payments_touch_member_trg
            AFTER INSERT OR UPDATE OR DELETE ON payments
            FOR EACH ROW EXECUTE FUNCTION payments_touch_member();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS payments_touch_member_trg ON payments');
        DB::unprepared('DROP FUNCTION IF EXISTS payments_touch_member()');
        DB::unprepared('DROP FUNCTION IF EXISTS refresh_paid_through(BIGINT)');
    }
};
