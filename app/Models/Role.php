<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model {////////////////////////////////seeders////////////////////////////////
    protected $fillable = ['role_code', 'role_name'];

    public function teachers() {
        return $this->hasMany(Teacher::class, 'role_code', 'role_code');
    }

    public function students() {
        return $this->hasMany(Student::class, 'role_code', 'role_code');
    }
}