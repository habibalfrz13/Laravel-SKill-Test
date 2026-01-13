<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'is_draft',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_draft' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active (published) posts.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_draft', false)
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    /**
     * Determine if the post is published.
     */
    public function isPublished(): bool
    {
        return !$this->is_draft
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    /**
     * Determine if the post is a draft.
     */
    public function isDraft(): bool
    {
        return $this->is_draft === true;
    }

    /**
     * Determine if the post is scheduled.
     */
    public function isScheduled(): bool
    {
        return !$this->is_draft
            && $this->published_at !== null
            && $this->published_at->gt(now());
    }
}

