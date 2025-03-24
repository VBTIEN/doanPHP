<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectYearlyAverage extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_code',
        'subject_code',
        'school_year_code',
        'yearly_average',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_code', 'student_code');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_code', 'subject_code');
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_code', 'school_year_code');
    }
}