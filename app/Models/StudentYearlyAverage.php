<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentYearlyAverage extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_code',
        'school_year_code',
        'yearly_average',
        'classroom_rank',
        'grade_rank',
        'academic_performance',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_code', 'student_code');
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_code', 'school_year_code');
    }
}