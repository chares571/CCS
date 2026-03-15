<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'author_id',
        'updated_by',
        'deleted_by',
        'restored_by',
        'title',
        'content',
        'content_format',
        'is_draft',
        'image_path',
        'image_caption',
        'publish_at',
        'is_pinned',
        'pinned_at',
    ];

    protected function casts(): array
    {
        return [
            'publish_at' => 'datetime',
            'pinned_at' => 'datetime',
            'is_draft' => 'boolean',
            'is_pinned' => 'boolean',
        ];
    }

    public function author() { return $this->belongsTo(User::class, 'author_id'); }
    public function updatedBy() { return $this->belongsTo(User::class, 'updated_by'); }
    public function deletedBy() { return $this->belongsTo(User::class, 'deleted_by'); }
    public function restoredBy() { return $this->belongsTo(User::class, 'restored_by'); }

    public function statusLabel(): string
    {
        if ($this->trashed()) {
            return 'DELETED';
        }

        if ((bool) $this->is_draft) {
            return 'DRAFT';
        }

        if ($this->publish_at && $this->publish_at->isFuture()) {
            return 'SCHEDULED';
        }

        return 'PUBLISHED';
    }

    public function renderedContent(): HtmlString
    {
        $content = (string) ($this->content ?? '');
        $format = (string) ($this->content_format ?? 'plain');

        if ($format === 'markdown' && method_exists(Str::class, 'markdown')) {
            $html = Str::markdown($content, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);

            return new HtmlString((string) $html);
        }

        return new HtmlString(nl2br(e($content)));
    }
}
