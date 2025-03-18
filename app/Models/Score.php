<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    use HasFactory;

    protected $fillable = ['student_code', 'exam_code', 'score_value'];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_code', 'student_code');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_code', 'exam_code');
    }
}
