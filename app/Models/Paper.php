<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paper extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'abstract', 'category', 'keywords', 'file_path', 'file_name',
        'word_file_path', 'word_file_name',
        'author_id', 'assigned_reviewer_id', 'status', 'version', 'admin_notes',
    ];

    protected $casts = [
        'version' => 'integer',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REVISION = 'revision';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PUBLISHED = 'published';

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function assignedReviewer()
    {
        return $this->belongsTo(User::class, 'assigned_reviewer_id');
    }

    public function coAuthors()
    {
        return $this->hasMany(PaperAuthor::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function latestReview()
    {
        return $this->hasOne(Review::class)->latestOfMany();
    }
}
