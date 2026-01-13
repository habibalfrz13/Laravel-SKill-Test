<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder; 

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'content', 'is_draft', 'published_at'];

    protected $casts = [
        'published_at' => 'datetime',
        'is_draft' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_draft', false)
              ->whereNotNull('published_at')
              ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return !$this->is_draft 
            && $this->published_at 
            && $this->published_at->lte(now());
    }

    public function isDraft(): bool
    {
        return $this->is_draft === true;
    }

    public function isScheduled(): bool
    {
        return !$this->is_draft 
            && $this->published_at 
            && $this->published_at->gt(now());
    }
}
