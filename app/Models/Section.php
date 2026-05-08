<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'order',
    ];

    // Relationship: A Section belongs to a Course
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // Relationship: A Section has many Materials (PDFs/Videos)
    public function materials()
    {
        return $this->hasMany(Material::class);
    }
}