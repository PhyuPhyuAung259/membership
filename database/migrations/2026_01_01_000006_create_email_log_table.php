<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every outbound message, recorded BEFORE it is handed to the mailer.
 *
 * The unique index on dedupe_key is the whole safety mechanism against
 * duplicate email. It matters more here than it would in a plain script,
 * because Laravel's queue retries failed jobs automatically: without this
 * constraint, one timeout from your mail provider turns into two identical
 * reminders landing in a member's inbox.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('to_email');
            $table->string('kind', 40);

            $table->string('dedupe_key')->unique();

            $table->string('subject');
            $table->foreignId('broadcast_id')->nullable()->constrained()->nullOnDelete();

            // queued -> sent | failed. 'skipped' records a deliberate decision
            // not to send, so the audit trail explains the gap.
            $table->string('status', 20)->default('queued');

            $table->string('mailer', 30)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'created_at']);
            $table->index(['kind', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_log');
    }
};
