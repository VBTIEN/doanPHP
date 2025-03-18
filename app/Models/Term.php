<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Term extends Model////////////////////////////////seeders////////////////////////////////
{
    use HasFactory;

    protected $fillable = ['term_code', 'term_name', 'start_date', 'end_date', 'school_year_code'];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_code', 'school_year_code');
    }

    public function scores()
    {
        return $this->hasMany(Score::class, 'term_code', 'term_code');
    }
}
