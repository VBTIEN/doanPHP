<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentTermAverage extends Model
{
    protected $table = 'student_term_averages';

    protected $fillable = [
        'student_code',
        'term_code',
        'term_average',
        'classroom_rank',
        'grade_rank',
        'academic_performance',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_code', 'student_code');
    }

    public function term()
    {
        return $this->belongsTo(Term::class, 'term_code', 'term_code');
    }
}