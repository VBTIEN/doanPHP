<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    use HasFactory;

    protected $fillable = ['school_year_code', 'school_year_name'];

    public function terms()
    {
        return $this->hasMany(Term::class, 'school_year_code', 'school_year_code');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'school_year_code', 'school_year_code');
    }
}
