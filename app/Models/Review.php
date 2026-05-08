<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'paper_id', 'reviewer_id', 'comment', 'private_comment', 'decision', 'status', 'file_path', 'file_name', 'word_file_path', 'word_file_name',
    ];

    const DECISION_ACCEPT = 'accept';
    const DECISION_MINOR = 'minor_revision';
    const DECISION_MAJOR = 'major_revision';
    const DECISION_REJECT = 'reject';

    public function paper()
    {
        return $this->belongsTo(Paper::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
