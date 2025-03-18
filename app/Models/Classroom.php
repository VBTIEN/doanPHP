<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model////////////////////////////////seeders////////////////////////////////
{
    use HasFactory;

    protected $fillable = ['classroom_code', 'classroom_name', 'grade_code', 'student_count', 'homeroom_teacher_code'];

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'classroom_teacher', 'classroom_code', 'teacher_code')
                    ->withPivot('subject_code')
                    ->withTimestamps();
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'classroom_code', 'classroom_code');
    }

    public function homeroomTeacher()
    {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_code', 'teacher_code');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_code', 'grade_code');
    }
}
