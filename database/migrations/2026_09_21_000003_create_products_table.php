<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a member company sells. One company, many products.
 *
 * A product carries one file, which may be an image or a PDF — file_kind
 * records which, so the directory can show a thumbnail for one and a download
 * link for the other without sniffing the extension at render time.
 *
 * Additive: creates one new table. The foreign key points AT members; the
 * members table itself is not modified here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();

            $table->string('product_name');
            $table->text('description')->nullable();

            // Relative path on the public disk, not a URL.
            $table->string('file_path')->nullable();

            // 'image' | 'document'
            $table->string('file_kind', 20)->nullable();

            // What the member called the file, so a download keeps its name.
            $table->string('file_original_name')->nullable();

            // Lets staff order a company's catalogue deliberately.
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['member_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
