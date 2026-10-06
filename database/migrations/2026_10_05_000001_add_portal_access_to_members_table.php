<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a member company log into its own portal. password stays null until
 * someone actually sets one — staff sends an invite (reusing the standard
 * password-reset broker, see Member::sendPasswordResetNotification), the
 * member clicks through and sets it. A null password means "no portal
 * access yet", not "locked out" — there's no separate active/inactive flag
 * for this.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
            $table->rememberToken()->after('password');

            // Set whenever the registration document is (re)uploaded, by
            // staff or by the member — this is what the document-reminder
            // schedule checks to decide a document has gone stale.
            $table->timestamp('registration_document_updated_at')->nullable()->after('registration_document_path');
        });

        Schema::create('member_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_password_reset_tokens');

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['password', 'remember_token', 'registration_document_updated_at']);
        });
    }
};
