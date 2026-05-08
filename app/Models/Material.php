<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = ['section_id', 'title', 'content', 'file_path', 'type', 'due_date']; // <--- Added due_date

    protected $appends = ['file_url'];

    protected $casts = [
        'due_date' => 'datetime'
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function getFileUrlAttribute()
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }
}