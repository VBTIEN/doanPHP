<?php
namespace App\Services;

use App\Models\Teacher;
use App\Models\Student;
use App\Models\Classroom;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthService
{
    public function authenticateUser($email, $password)
    {
        $teacher = Teacher::where('email', $email)->first();
        if ($teacher && password_verify($password, $teacher->password)) {
            return $teacher;
        }

        $student = Student::where('email', $email)->first();
        if ($student && password_verify($password, $student->password)) {
            return $student;
        }

        return null;
    }

    public function generateCode($roleCode)
    {
        if ($roleCode === 'R1') {
            $latest = Teacher::orderBy('teacher_code', 'desc')->first();
            $number = $latest ? (int) substr($latest->teacher_code, 1) + 1 : 1;
            return 'T' . $number;
        } elseif ($roleCode === 'R2') {
            $latest = Student::orderBy('student_code', 'desc')->first();
            $number = $latest ? (int) substr($latest->teacher_code, 1) + 1 : 1;
            return 'S' . $number;
        }

        throw new \Exception('Invalid role_code');
    }

    public function createUser($roleCode, array $data)
    {
        $code = $this->generateCode($roleCode);
        $userData = [
            'teacher_code' => $code,
            'student_code' => $code,
            'email' => $data['email'],
            'password' => bcrypt($data['password']), // Mã hóa mật khẩu
            'name' => $data['name'],
            'role_code' => $roleCode,
        ];

        $user = $roleCode === 'R1'
            ? Teacher::create($userData)
            : Student::create($userData);

        if ($roleCode === 'R1' && isset($data['classroom_code'])) {
            $this->assignHomeroomTeacher($user, $data['classroom_code']);
        }

        return $user;
    }

    private function assignHomeroomTeacher($teacher, $classroomCode)
    {
        $classroom = Classroom::where('classroom_code', $classroomCode)->first();

        if (!$classroom) {
            throw new \Exception('Lớp không tồn tại');
        }

        if ($classroom->homeroom_teacher_code !== null) {
            throw new \Exception('Lớp này đã có giáo viên chủ nhiệm');
        }

        $classroom->homeroom_teacher_code = $teacher->teacher_code;
        $classroom->save();
    }

    /**
     * Send password reset link to user's email.
     *
     * @param string $email
     * @return bool
     */
    public function sendPasswordResetLink($email)
    {
        $user = Teacher::where('email', $email)->first() ?? Student::where('email', $email)->first();
        if (!$user) {
            Log::info("Email not found: {$email}");
            Log::info("Teacher check: " . json_encode(Teacher::where('email', $email)->first()));
            Log::info("Student check: " . json_encode(Student::where('email', $email)->first()));
            return false;
        }

        $token = Str::random(60);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => $token, 'created_at' => now()]
        );

        try {
            $resetUrl = "http://localhost:5173/reset-password?email=" . urlencode($email);
            Log::info("Đã copy mail tới FE");
            Mail::to($email)->send(new ResetPasswordMail($resetUrl));
            Log::info("Reset link sent to: {$email}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send reset link to {$email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reset user's password using token.
     *
     * @param string $email
     * @param string $token
     * @param string $password
     * @return bool
     */
    public function resetPassword($email, $token, $password)
    {
        $reset = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $token)
            ->first();

        if (!$reset || now()->subHour()->gt($reset->created_at)) {
            return false; // Token không tồn tại hoặc hết hạn (1 giờ)
        }

        $user = Teacher::where('email', $email)->first() ?? Student::where('email', $email)->first();
        if (!$user) {
            return false;
        }

        $user->password = bcrypt($password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return true;
    }
}