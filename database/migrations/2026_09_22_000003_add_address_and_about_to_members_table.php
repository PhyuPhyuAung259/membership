<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->text('address')->nullable()->after('contact_person_position');

            // A short company/member profile shown on the public directory
            // page. Word-count bounds (not just a max length) are enforced
            // in the form, not here — this column just holds the text.
            $table->text('about')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['address', 'about']);
        });
    }
};
