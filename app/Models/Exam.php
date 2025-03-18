<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model////////////////////////////////seeders////////////////////////////////
{
    use HasFactory;

    protected $fillable = ['exam_code', 'exam_name', 'subject_code', 'term_code', 'date'];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_code', 'subject_code');
    }

    public function term()
    {
        return $this->belongsTo(Term::class, 'term_code', 'term_code');
    }

    public function scores()
    {
        return $this->hasMany(Score::class, 'exam_code', 'exam_code');
    }
}
