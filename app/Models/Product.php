<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Something a member company sells.
 *
 * Carries one file, which may be an image or a PDF. file_kind records which,
 * so the directory can show a thumbnail for one and a download link for the
 * other without inspecting the extension at render time.
 */
class Product extends Model
{
    public const KIND_IMAGE = 'image';
    public const KIND_DOCUMENT = 'document';

    /** Extensions accepted on upload, mapped to the kind they record. */
    public const ACCEPTED = [
        'jpg' => self::KIND_IMAGE,
        'jpeg' => self::KIND_IMAGE,
        'png' => self::KIND_IMAGE,
        'webp' => self::KIND_IMAGE,
        'gif' => self::KIND_IMAGE,
        'pdf' => self::KIND_DOCUMENT,
    ];

    protected $fillable = [
        'member_id', 'product_name', 'description',
        'file_path', 'file_kind', 'file_original_name', 'sort_order',
    ];

    protected $casts = ['sort_order' => 'integer'];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function isImage(): bool
    {
        return $this->file_kind === self::KIND_IMAGE;
    }

    /** Public URL for the file, or null when the product has none. */
    public function fileUrl(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    /** Work out the kind from a filename. Null means the type is not allowed. */
    public static function kindFor(string $filename): ?string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return self::ACCEPTED[$ext] ?? null;
    }
}
