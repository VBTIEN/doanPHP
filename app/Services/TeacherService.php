<?php

namespace App\Services;

use App\Models\Teacher;
use App\Models\Classroom;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class TeacherService
{
    /**
     * Kiểm tra và gán giáo viên nhận dạy lớp.
     *
     * @param Teacher $teacher Giáo viên đang xác thực
     * @param string $classroomCode Mã lớp học
     * @return array Danh sách môn được gán
     * @throws \Exception
     */
    public function assignTeachingClassroom(Teacher $teacher, string $classroomCode): array
    {
        // Tìm lớp học
        $classroom = Classroom::where('classroom_code', $classroomCode)->first();
        if (!$classroom) {
            throw new \Exception('Không tìm thấy lớp học');
        }

        // Lấy danh sách tất cả môn học
        $allSubjects = Subject::pluck('subject_code')->toArray();
        if (empty($allSubjects)) {
            throw new \Exception('Chưa có môn học nào trong hệ thống');
        }

        // Lấy danh sách môn mà giáo viên này có thể dạy (dựa trên teacher_subject)
        $teacherSubjectsQuery = $teacher->subjects();
        \Log::info('SQL Query for teacher subjects: ' . $teacherSubjectsQuery->toSql());
        \Log::info('Bindings: ' . json_encode($teacherSubjectsQuery->getBindings()));
        $teacherSubjects = $teacherSubjectsQuery->pluck('subject_code')->toArray();
        \Log::info('Teacher subjects for teacher ' . $teacher->teacher_code . ': ' . json_encode($teacherSubjects));
        if (empty($teacherSubjects)) {
            throw new \Exception('Giáo viên này chưa được gán môn học nào');
        }

        // Xử lý dựa trên việc lớp có giáo viên chủ nhiệm hay không
        if ($classroom->homeroom_teacher_code) {
            return $this->handleWithHomeroomTeacher($teacher, $classroom, $allSubjects, $teacherSubjects);
        } else {
            return $this->handleWithoutHomeroomTeacher($teacher, $classroom, $allSubjects, $teacherSubjects);
        }
    }

    /**
     * Lấy danh sách môn còn lại mà lớp cần.
     *
     * @param string $classroomCode
     * @param array $allSubjects
     * @return array
     */
    private function getRemainingSubjects(string $classroomCode, array $allSubjects): array
    {
        $assignedSubjects = DB::table('classroom_teacher')
            ->where('classroom_code', $classroomCode)
            ->pluck('subject_code')
            ->toArray();

        return array_diff($allSubjects, $assignedSubjects);
    }

    /**
     * Xử lý trường hợp lớp đã có giáo viên chủ nhiệm.
     *
     * @param Teacher $teacher
     * @param Classroom $classroom
     * @param array $allSubjects
     * @param array $teacherSubjects
     * @return array
     * @throws \Exception
     */
    private function handleWithHomeroomTeacher(Teacher $teacher, Classroom $classroom, array $allSubjects, array $teacherSubjects): array
    {
        $remainingSubjects = $this->getRemainingSubjects($classroom->classroom_code, $allSubjects);

        // Trường hợp lớp đã đủ giáo viên dạy tất cả các môn
        if (empty($remainingSubjects)) {
            throw new \Exception('Lớp đã đủ giáo viên dạy tất cả các môn');
        }

        // Lấy danh sách giáo viên bộ môn khác đã dạy lớp này
        $otherTeachersSubjects = DB::table('classroom_teacher')
            ->where('classroom_code', $classroom->classroom_code)
            ->where('teacher_code', '!=', $teacher->teacher_code)
            ->pluck('subject_code')
            ->toArray();

        // Nếu giáo viên này là giáo viên chủ nhiệm
        if ($classroom->homeroom_teacher_code === $teacher->teacher_code) {
            $subjectsToAssign = array_intersect($teacherSubjects, $remainingSubjects);
            if (empty($subjectsToAssign)) {
                throw new \Exception('Giáo viên không có môn nào phù hợp để nhận dạy');
            }
            return $this->assignSubjectsToTeacher($teacher, $classroom->classroom_code, $subjectsToAssign);
        }

        // Nếu đã có giáo viên bộ môn khác
        if (!empty($otherTeachersSubjects)) {
            // Tính các môn trùng và không trùng
            $overlappingSubjects = array_intersect($teacherSubjects, $otherTeachersSubjects);
            $nonOverlappingSubjects = array_diff($teacherSubjects, $otherTeachersSubjects);

            // Nếu các môn giáo viên xin dạy trùng hết với các môn đã được gán
            if (empty($nonOverlappingSubjects)) {
                throw new \Exception('Các môn giáo viên xin dạy đã được gán hết bởi các giáo viên khác');
            }

            // Lấy các môn không trùng và nằm trong danh sách môn còn lại của lớp
            $subjectsToAssign = array_intersect($nonOverlappingSubjects, $remainingSubjects);
            if (empty($subjectsToAssign)) {
                throw new \Exception('Không có môn nào phù hợp để nhận dạy');
            }

            return $this->assignSubjectsToTeacher($teacher, $classroom->classroom_code, $subjectsToAssign);
        }

        // Nếu chưa có giáo viên bộ môn khác
        $subjectsToAssign = array_intersect($teacherSubjects, $remainingSubjects);
        if (empty($subjectsToAssign)) {
            throw new \Exception('Giáo viên không có môn nào phù hợp để nhận dạy');
        }

        return $this->assignSubjectsToTeacher($teacher, $classroom->classroom_code, $subjectsToAssign);
    }

    /**
     * Xử lý trường hợp lớp chưa có giáo viên chủ nhiệm.
     *
     * @param Teacher $teacher
     * @param Classroom $classroom
     * @param array $allSubjects
     * @param array $teacherSubjects
     * @return array
     * @throws \Exception
     */
    private function handleWithoutHomeroomTeacher(Teacher $teacher, Classroom $classroom, array $allSubjects, array $teacherSubjects): array
    {
        $remainingSubjects = $this->getRemainingSubjects($classroom->classroom_code, $allSubjects);

        // Trường hợp lớp đã đủ giáo viên dạy tất cả các môn
        if (empty($remainingSubjects)) {
            throw new \Exception('Lớp đã đủ giáo viên dạy tất cả các môn');
        }

        // Lấy danh sách giáo viên bộ môn khác đã dạy lớp này
        $otherTeachersSubjects = DB::table('classroom_teacher')
            ->where('classroom_code', $classroom->classroom_code)
            ->where('teacher_code', '!=', $teacher->teacher_code)
            ->pluck('subject_code')
            ->toArray();

        // Nếu đã có giáo viên bộ môn khác
        if (!empty($otherTeachersSubjects)) {
            // Tính các môn trùng và không trùng
            $overlappingSubjects = array_intersect($teacherSubjects, $otherTeachersSubjects);
            $nonOverlappingSubjects = array_diff($teacherSubjects, $otherTeachersSubjects);

            // Nếu các môn giáo viên xin dạy trùng hết với các môn đã được gán
            if (empty($nonOverlappingSubjects)) {
                throw new \Exception('Các môn giáo viên xin dạy đã được gán hết bởi các giáo viên khác');
            }

            // Lấy các môn không trùng và nằm trong danh sách môn còn lại của lớp
            $subjectsToAssign = array_intersect($nonOverlappingSubjects, $remainingSubjects);
            if (empty($subjectsToAssign)) {
                throw new \Exception('Không có môn nào phù hợp để nhận dạy');
            }

            return $this->assignSubjectsToTeacher($teacher, $classroom->classroom_code, $subjectsToAssign);
        }

        // Nếu chưa có giáo viên bộ môn khác
        $subjectsToAssign = array_intersect($teacherSubjects, $remainingSubjects);
        if (empty($subjectsToAssign)) {
            throw new \Exception('Giáo viên không có môn nào phù hợp để nhận dạy');
        }

        return $this->assignSubjectsToTeacher($teacher, $classroom->classroom_code, $subjectsToAssign);
    }

    /**
     * Gán các môn cho giáo viên trong bảng classroom_teacher.
     *
     * @param Teacher $teacher
     * @param string $classroomCode
     * @param array $subjectsToAssign
     * @return array
     */
    private function assignSubjectsToTeacher(Teacher $teacher, string $classroomCode, array $subjectsToAssign): array
    {
        $insertData = array_map(function ($subjectCode) use ($classroomCode, $teacher) {
            return [
                'classroom_code' => $classroomCode,
                'teacher_code' => $teacher->teacher_code,
                'subject_code' => $subjectCode,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $subjectsToAssign);

        DB::table('classroom_teacher')->insert($insertData);

        return $subjectsToAssign;
    }

    /**
     * Lấy danh sách giáo viên dạy trong một lớp dựa trên classroom_code.
     *
     * @param string $classroomCode Mã lớp học
     * @return array Danh sách giáo viên và các môn họ dạy
     * @throws \Exception
     */
    public function getTeachersInClassroom(string $classroomCode): array
    {
        // Kiểm tra xem lớp có tồn tại không
        $classroom = Classroom::where('classroom_code', $classroomCode)->first();
        if (!$classroom) {
            throw new \Exception('Không tìm thấy lớp học');
        }

        // Lấy danh sách giáo viên dạy trong lớp từ bảng classroom_teacher
        $teachersInClass = DB::table('classroom_teacher')
            ->select('classroom_teacher.teacher_code', 'classroom_teacher.subject_code')
            ->where('classroom_teacher.classroom_code', $classroomCode)
            ->get()
            ->groupBy('teacher_code');

        // Nếu không có giáo viên nào dạy lớp này
        if ($teachersInClass->isEmpty()) {
            return [];
        }

        // Lấy thông tin giáo viên và môn học
        $teacherCodes = $teachersInClass->keys()->toArray();
        $teachers = Teacher::whereIn('teacher_code', $teacherCodes)->get()->keyBy('teacher_code');

        $subjectCodes = $teachersInClass->flatten()->pluck('subject_code')->unique()->toArray();
        $subjects = Subject::whereIn('subject_code', $subjectCodes)
            ->get()
            ->keyBy('subject_code')
            ->map(function ($subject) {
                return [
                    'subject_code' => $subject->subject_code,
                    'subject_name' => $subject->subject_name,
                ];
            })->toArray();

        // Xây dựng dữ liệu trả về
        $result = [];
        foreach ($teachersInClass as $teacherCode => $subjectsTaught) {
            $teacher = $teachers->get($teacherCode);
            if (!$teacher) {
                continue; // Bỏ qua nếu không tìm thấy giáo viên
            }

            $teacherData = [
                'teacher_code' => $teacher->teacher_code,
                'name' => $teacher->name,
                'email' => $teacher->email,
                'subjects' => $subjectsTaught->map(function ($item) use ($subjects) {
                    $subject = $subjects[$item->subject_code] ?? null;
                    return $subject ? [
                        'subject_code' => $subject['subject_code'],
                        'subject_name' => $subject['subject_name'],
                    ] : null;
                })->filter()->values()->toArray(),
            ];

            $result[] = $teacherData;
        }

        return $result;
    }
}