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
            // Lấy tất cả student_code hiện có để tìm mã lớn nhất
            $students = Student::pluck('student_code')->toArray();
            $maxNumber = 0;

            foreach ($students as $code) {
                $number = (int) substr($code, 1); // Lấy phần số từ student_code (S1 -> 1)
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }

            $nextNumber = $maxNumber + 1;
            return 'S' . $nextNumber;
        }

        throw new \Exception('Invalid role_code');
    }

    public function createUser($roleCode, array $data)
    {
        $code = isset($data['student_code']) ? $data['student_code'] : $this->generateCode($roleCode);

        $userData = [
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'name' => $data['name'],
            'role_code' => $roleCode,
        ];

        if ($roleCode === 'R1') {
            $userData['teacher_code'] = $code;
        } elseif ($roleCode === 'R2') {
            $userData['student_code'] = $code;
            $classroom = $this->assignClassroomForStudent($data['grade_code']);
            $userData['classroom_code'] = $classroom->classroom_code;
        }

        $user = $roleCode === 'R1'
            ? Teacher::create($userData)
            : Student::create($userData);

        if ($roleCode === 'R1') {
            // Gán các môn học cho giáo viên (bảng teacher_subject)
            if (isset($data['subject_codes']) && is_array($data['subject_codes'])) {
                $teacherSubjects = [];
                foreach ($data['subject_codes'] as $subjectCode) {
                    $teacherSubjects[] = [
                        'teacher_code' => $code,
                        'subject_code' => $subjectCode,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('teacher_subject')->insert($teacherSubjects);
            }

            // Chỉ gán giáo viên làm chủ nhiệm nếu classroom_code không rỗng
            if (!empty($data['classroom_code'])) {
                $this->assignHomeroomTeacher($user, $data['classroom_code']);

                // Tự động gán các môn mà giáo viên đăng ký vào bảng classroom_teacher
                if (isset($data['subject_codes']) && is_array($data['subject_codes'])) {
                    $classroomTeacherData = [];
                    foreach ($data['subject_codes'] as $subjectCode) {
                        $classroomTeacherData[] = [
                            'classroom_code' => $data['classroom_code'],
                            'teacher_code' => $code,
                            'subject_code' => $subjectCode,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    DB::table('classroom_teacher')->insert($classroomTeacherData);
                }
            }
        }

        return $user;
    }

    /**
     * Tự động xếp học sinh vào lớp dựa trên grade_code.
     * Lớp được chọn theo thứ tự tăng dần của classroom_code, với giới hạn 10 học sinh mỗi lớp.
     */
    private function assignClassroomForStudent($gradeCode)
    {
        // Tìm lớp thuộc khối grade_code, có dưới 10 học sinh, sắp xếp tăng dần theo classroom_code
        $classroom = Classroom::where('grade_code', $gradeCode)
            ->where('student_count', '<', 10)
            ->orderBy('classroom_code', 'asc')
            ->first();

        if (!$classroom) {
            // Nếu không có lớp nào trống, tạo lớp mới (tuỳ thuộc yêu cầu)
            throw new \Exception('No available classroom in this grade');
        }

        // Tăng student_count
        $classroom->student_count += 1;
        $classroom->save();

        return $classroom;
    }

    public function assignHomeroomTeacher($teacher, $classroomCode)
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