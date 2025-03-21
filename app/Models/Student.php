<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class Student extends Model implements AuthenticatableContract
{
    use Authenticatable, HasApiTokens;

    protected $fillable = ['student_code', 'email', 'password', 'name', 'role_code', 'google_id', 'email_verified_at', 'classroom_code'];
    protected $hidden = ['password'];
    protected $casts = ['email_verified_at' => 'datetime'];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_code', 'role_code');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_code', 'classroom_code');
    }

    public function scores()
    {
        return $this->hasMany(Score::class, 'student_code', 'student_code');
    }
}