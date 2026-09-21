<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What industry a member company is in. Staff-maintained reference data.
 *
 * Additive: creates one new table and touches nothing that already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->timestamps();
        });

        // Case-insensitive, so "Manufacturing" and "manufacturing" cannot
        // become two categories that split the directory in half.
        DB::statement('CREATE UNIQUE INDEX business_types_name_lower_idx ON business_types (lower(name))');
    }

    public function down(): void
    {
        Schema::dropIfExists('business_types');
    }
};
