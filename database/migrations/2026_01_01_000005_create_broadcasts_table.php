<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('body');
            $table->string('audience', 30)->default('all');
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();

            // Filled in as the queued jobs report back, so the compose screen
            // can show progress while a long send is still running.
            $table->unsignedInteger('queued_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcasts');
    }
};
