<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body')->default('');
            $table->date('event_date')->nullable();
            $table->string('event_time', 60)->nullable();
            $table->string('location', 300)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('event_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
