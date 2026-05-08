<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    // Added 'status' to the list
    protected $fillable = ['student_id', 'quiz_id', 'score', 'submitted_at', 'started_at', 'status'];

    protected $casts = [
        'submitted_at' => 'datetime',
        'started_at' => 'datetime'
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers()
    {
        return $this->hasMany(SubmissionAnswer::class);
    }
}