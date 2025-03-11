<?php
namespace App\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\EloquentUserProvider;
use App\Models\Teacher;
use App\Models\Student;

class CustomUserProvider extends EloquentUserProvider {
    public function retrieveById($identifier) {
        // Tìm trong bảng teachers
        $teacher = Teacher::where('teacher_code', $identifier)->first();
        if ($teacher) {
            return $teacher;
        }

        // Tìm trong bảng students
        $student = Student::where('student_code', $identifier)->first();
        if ($student) {
            return $student;
        }

        return null;
    }

    public function retrieveByCredentials(array $credentials) {
        // Tìm user bằng email trong cả hai bảng
        $teacher = Teacher::where('email', $credentials['email'])->first();
        if ($teacher && $this->validateCredentials($teacher, $credentials)) {
            return $teacher;
        }

        $student = Student::where('email', $credentials['email'])->first();
        if ($student && $this->validateCredentials($student, $credentials)) {
            return $student;
        }

        return null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials) {
        return password_verify($credentials['password'], $user->getAuthPassword());
    }
}