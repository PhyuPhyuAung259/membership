<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('paid_on');

            // The period this money buys. Coverage is derived from these
            // rows, which is why paying three months up front is one row
            // spanning three months and needs no special handling.
            $table->date('period_start');
            $table->date('period_end');

            $table->string('method', 30)->default('bank_transfer');
            $table->string('reference', 120)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['member_id', 'period_end']);
            $table->index('paid_on');
        });

        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_period_order CHECK (period_end >= period_start)');
        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_amount_positive CHECK (amount >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
