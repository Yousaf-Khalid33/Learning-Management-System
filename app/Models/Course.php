<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'teacher_id',
    ];

    // Relationship: A Course belongs to a Teacher (User)
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Relationship: A Course has many Sections
    public function sections()
    {
        return $this->hasMany(Section::class)->orderBy('order');
    }

    // Relationship: A Course has many Quizzes
    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    // NEW: Relationship: A Course has many Enrollments (For counting students)
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}