<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectAverage extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_code',
        'subject_code',
        'term_code',
        'term_average',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_code', 'student_code');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_code', 'subject_code');
    }

    public function term()
    {
        return $this->belongsTo(Term::class, 'term_code', 'term_code');
    }
}