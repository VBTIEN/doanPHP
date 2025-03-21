<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Support\Facades\DB;

class Teacher extends Model implements AuthenticatableContract
{
    use Authenticatable, HasApiTokens;

    protected $fillable = ['teacher_code', 'email', 'password', 'name', 'role_code', 'google_id', 'email_verified_at'];
    protected $hidden = ['password'];
    protected $casts = ['email_verified_at' => 'datetime'];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_code', 'role_code');
    }

    public function classrooms()
    {
        return $this->belongsToMany(Classroom::class, 'classroom_teacher', 'teacher_code', 'classroom_code')
                    ->withPivot('subject_code')
                    ->withTimestamps();
    }

    public function subjects()
    {
        return Subject::select('subjects.*')
            ->join('teacher_subject', 'subjects.subject_code', '=', 'teacher_subject.subject_code')
            ->join('teachers', 'teachers.teacher_code', '=', 'teacher_subject.teacher_code')
            ->where('teachers.teacher_code', $this->teacher_code);
    }
    public function scores()
    {
        return $this->hasManyThrough(Score::class, Subject::class, 'subject_code', 'subject_code');
    }

    public function homeroomClass()
    {
        return $this->hasOne(Classroom::class, 'homeroom_teacher_code', 'teacher_code');
    }
}