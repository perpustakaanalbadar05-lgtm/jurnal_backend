<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaperAuthor extends Model
{
    use HasFactory;

    protected $fillable = [
        'paper_id', 'name', 'email', 'institution', 'is_corresponding', 'order',
    ];

    protected $casts = [
        'is_corresponding' => 'boolean',
        'order' => 'integer',
    ];

    public function paper()
    {
        return $this->belongsTo(Paper::class);
    }
}
