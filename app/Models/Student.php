<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class Student extends Model implements AuthenticatableContract {
    use Authenticatable, HasApiTokens;

    protected $fillable = ['student_code', 'email', 'password', 'name', 'role_code'];
    protected $hidden = ['password'];

    public function role() {
        return $this->belongsTo(Role::class, 'role_code', 'role_code');
    }

    public static function boot() {
        parent::boot();
        static::creating(function ($model) {
            $model->password = bcrypt($model->password);
        });
    }
}