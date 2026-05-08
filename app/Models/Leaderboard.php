<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leaderboard extends Model
{
    use HasFactory;

    protected $table = 'leaderboard'; // Explicitly set table name

    protected $fillable = ['student_id', 'total_score', 'rank'];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}