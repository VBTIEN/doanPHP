<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use App\Models\Classroom;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Lấy tất cả lớp học
        $classrooms = Classroom::all();

        if ($classrooms->isEmpty()) {
            echo "No classrooms found.\n";
            return;
        }

        // Lấy tất cả môn học
        $allSubjects = Subject::pluck('subject_code')->toArray();

        // Nếu không có môn học, báo lỗi
        if (empty($allSubjects)) {
            throw new \Exception('Chưa có môn học nào trong hệ thống. Vui lòng seed bảng subjects trước.');
        }

        // Lấy số giáo viên hiện có để tạo teacher_code tăng dần
        $lastTeacher = Teacher::orderBy('teacher_code', 'desc')->first();
        $teacherCounter = $lastTeacher ? (int) str_replace('T', '', $lastTeacher->teacher_code) : 0;

        echo "Starting teacher seeding...\n";

        // Duyệt qua từng lớp để kiểm tra và gán giáo viên
        foreach ($classrooms as $classroom) {
            echo "Processing classroom {$classroom->classroom_code}...\n";

            // Lấy danh sách môn đã được gán cho lớp này
            $assignedSubjects = DB::table('classroom_teacher')
                ->where('classroom_code', $classroom->classroom_code)
                ->pluck('subject_code')
                ->toArray();

            // Tính các môn còn thiếu
            $remainingSubjects = array_diff($allSubjects, $assignedSubjects);

            // Kiểm tra xem lớp đã đủ giáo viên dạy tất cả các môn chưa
            $needsSubjects = !empty($remainingSubjects);

            // Kiểm tra xem lớp có giáo viên bộ môn nào chưa
            $hasSubjectTeachers = !empty($assignedSubjects);

            // Kiểm tra xem lớp có giáo viên chủ nhiệm chưa
            $needsHomeroomTeacher = is_null($classroom->homeroom_teacher_code);

            if ($needsHomeroomTeacher) {
                // Tạo giáo viên chủ nhiệm mới
                $teacherCounter++;
                $teacherCode = 'T' . $teacherCounter;

                $teacher = Teacher::create([
                    'teacher_code' => $teacherCode,
                    'email' => "teacher{$teacherCounter}@example.com",
                    'password' => Hash::make('password'), // Mật khẩu mặc định
                    'name' => "Teacher {$teacherCounter}",
                    'role_code' => 'R1', // Sử dụng role_code R1 (Teacher)
                    'google_id' => null,
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                echo "Created teacher: {$teacher->email} with code {$teacherCode}\n";

                // Gán giáo viên này làm giáo viên chủ nhiệm
                $classroom->homeroom_teacher_code = $teacherCode;
                $classroom->save();

                echo "Assigned teacher {$teacherCode} as homeroom teacher for classroom {$classroom->classroom_code}\n";

                // Nếu lớp có môn chưa được gán, gán giáo viên chủ nhiệm dạy các môn còn lại
                if ($needsSubjects) {
                    $subjectsToAssign = $remainingSubjects;

                    if ($hasSubjectTeachers) {
                        echo "Classroom {$classroom->classroom_code} already has some subject teachers. Assigning remaining subjects to homeroom teacher {$teacherCode}: " . implode(', ', $subjectsToAssign) . "\n";
                    } else {
                        echo "Classroom {$classroom->classroom_code} has no subject teachers. Assigning all subjects to homeroom teacher {$teacherCode}: " . implode(', ', $subjectsToAssign) . "\n";
                    }

                    // Thêm vào bảng teacher_subject
                    $teacherSubjectData = array_map(function ($subjectCode) use ($teacherCode) {
                        return [
                            'teacher_code' => $teacherCode,
                            'subject_code' => $subjectCode,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }, $subjectsToAssign);
                    DB::table('teacher_subject')->insert($teacherSubjectData);

                    // Thêm vào bảng classroom_teacher
                    $classroomTeacherData = array_map(function ($subjectCode) use ($classroom, $teacherCode) {
                        return [
                            'classroom_code' => $classroom->classroom_code,
                            'teacher_code' => $teacherCode,
                            'subject_code' => $subjectCode,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }, $subjectsToAssign);
                    DB::table('classroom_teacher')->insert($classroomTeacherData);

                    echo "Assigned subjects to teacher {$teacherCode} for classroom {$classroom->classroom_code}\n";
                } else {
                    echo "Classroom {$classroom->classroom_code} already has all subjects assigned, no additional subjects needed for homeroom teacher.\n";
                }
            } elseif ($needsSubjects) {
                // Lớp đã có giáo viên chủ nhiệm hoặc đã có giáo viên bộ môn nhưng chưa đủ môn
                // Tìm giáo viên hiện có từ các lớp khác có thể dạy các môn còn thiếu
                $existingTeacher = $this->findExistingTeacherForSubjects($remainingSubjects, $classroom->classroom_code);

                if ($existingTeacher) {
                    $teacherCode = $existingTeacher['teacher_code'];
                    $subjectsToAssign = array_intersect($remainingSubjects, $existingTeacher['subjects']);

                    echo "Found existing teacher {$teacherCode} to teach remaining subjects in classroom {$classroom->classroom_code}: " . implode(', ', $subjectsToAssign) . "\n";

                    // Thêm vào bảng classroom_teacher
                    $classroomTeacherData = array_map(function ($subjectCode) use ($classroom, $teacherCode) {
                        return [
                            'classroom_code' => $classroom->classroom_code,
                            'teacher_code' => $teacherCode,
                            'subject_code' => $subjectCode,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }, $subjectsToAssign);
                    DB::table('classroom_teacher')->insert($classroomTeacherData);

                    echo "Assigned subjects to existing teacher {$teacherCode} for classroom {$classroom->classroom_code}\n";
                } else {
                    // Nếu không tìm thấy giáo viên hiện có, tạo giáo viên mới
                    $teacherCounter++;
                    $teacherCode = 'T' . $teacherCounter;

                    $teacher = Teacher::create([
                        'teacher_code' => $teacherCode,
                        'email' => "teacher{$teacherCounter}@example.com",
                        'password' => Hash::make('password'), // Mật khẩu mặc định
                        'name' => "Teacher {$teacherCounter}",
                        'role_code' => 'R1', // Sử dụng role_code R1 (Teacher)
                        'google_id' => null,
                        'email_verified_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    echo "No existing teacher found. Created new teacher: {$teacher->email} with code {$teacherCode}\n";

                    // Gán giáo viên này dạy các môn còn lại
                    $subjectsToAssign = $remainingSubjects;

                    echo "Assigning remaining subjects to new teacher {$teacherCode} for classroom {$classroom->classroom_code}: " . implode(', ', $subjectsToAssign) . "\n";

                    // Thêm vào bảng teacher_subject
                    $teacherSubjectData = array_map(function ($subjectCode) use ($teacherCode) {
                        return [
                            'teacher_code' => $teacherCode,
                            'subject_code' => $subjectCode,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }, $subjectsToAssign);
                    DB::table('teacher_subject')->insert($teacherSubjectData);

                    // Thêm vào bảng classroom_teacher
                    $classroomTeacherData = array_map(function ($subjectCode) use ($classroom, $teacherCode) {
                        return [
                            'classroom_code' => $classroom->classroom_code,
                            'teacher_code' => $teacherCode,
                            'subject_code' => $subjectCode,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }, $subjectsToAssign);
                    DB::table('classroom_teacher')->insert($classroomTeacherData);

                    echo "Assigned subjects to new teacher {$teacherCode} for classroom {$classroom->classroom_code}\n";
                }
            } else {
                echo "Classroom {$classroom->classroom_code} already has a homeroom teacher and all subjects assigned, skipping...\n";
            }
        }

        echo "Teacher seeding completed.\n";
    }

    /**
     * Tìm giáo viên hiện có từ các lớp khác có thể dạy các môn còn thiếu.
     *
     * @param array $requiredSubjects Các môn còn thiếu
     * @param string $currentClassroomCode Mã lớp hiện tại
     * @return array|null Thông tin giáo viên phù hợp hoặc null nếu không tìm thấy
     */
    private function findExistingTeacherForSubjects(array $requiredSubjects, string $currentClassroomCode): ?array
    {
        // Lấy tất cả giáo viên hiện có
        $teachers = Teacher::all();

        if ($teachers->isEmpty()) {
            return null;
        }

        // Lấy danh sách môn học mà từng giáo viên có thể dạy
        $teacherSubjects = DB::table('teacher_subject')
            ->whereIn('teacher_code', $teachers->pluck('teacher_code'))
            ->get()
            ->groupBy('teacher_code')
            ->map(function ($subjects) {
                return $subjects->pluck('subject_code')->toArray();
            })->toArray();

        // Lấy danh sách lớp mà từng giáo viên đang dạy
        $teacherClassrooms = DB::table('classroom_teacher')
            ->whereIn('teacher_code', $teachers->pluck('teacher_code'))
            ->get()
            ->groupBy('teacher_code')
            ->map(function ($classrooms) {
                return $classrooms->pluck('classroom_code')->unique()->toArray();
            })->toArray();

        // Tìm giáo viên phù hợp
        $bestTeacher = null;
        $minClassrooms = PHP_INT_MAX;

        foreach ($teacherSubjects as $teacherCode => $subjects) {
            // Kiểm tra xem giáo viên có thể dạy ít nhất một môn trong danh sách môn còn thiếu không
            $canTeachSubjects = array_intersect($subjects, $requiredSubjects);
            if (empty($canTeachSubjects)) {
                continue; // Bỏ qua nếu giáo viên không thể dạy môn nào trong danh sách cần
            }

            // Kiểm tra xem giáo viên đã dạy lớp hiện tại chưa
            $classroomsTaught = $teacherClassrooms[$teacherCode] ?? [];
            if (in_array($currentClassroomCode, $classroomsTaught)) {
                continue; // Bỏ qua nếu giáo viên đã dạy lớp này
            }

            // Ưu tiên giáo viên dạy ít lớp nhất
            $classroomCount = count($classroomsTaught);
            if ($classroomCount < $minClassrooms) {
                $minClassrooms = $classroomCount;
                $bestTeacher = [
                    'teacher_code' => $teacherCode,
                    'subjects' => $subjects,
                ];
            }
        }

        return $bestTeacher;
    }
}