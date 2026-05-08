<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id', 
        'title', 
        'total_questions', 
        'passing_score', 
        'is_published', // <--- This MUST be here
        'time_limit'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'time_limit' => 'integer'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}