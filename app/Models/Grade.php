<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = ['grade_code', 'grade_name', 'classroom_count', 'school_year_code'];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_code', 'school_year_code');
    }

    public function classrooms()
    {
        return $this->hasMany(Classroom::class, 'grade_code', 'grade_code');
    }
}
