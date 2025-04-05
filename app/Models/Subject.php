<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model////////////////////////////////seeders////////////////////////////////
{
    use HasFactory;

    protected $fillable = ['subject_code', 'subject_name'];

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subject', 'subject_code', 'teacher_code')
                    ->withTimestamps();
    }
}
