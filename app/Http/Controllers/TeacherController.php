<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\Classroom;
use App\Models\Student;
use App\Services\AuthService;
use App\Services\TeacherService;

class TeacherController extends Controller
{
    protected $authService;
    protected $teacherService;

    public function __construct(AuthService $authService, TeacherService $teacherService)
    {
        $this->authService = $authService;
        $this->teacherService = $teacherService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role_code !== 'R1') {
            return ResponseFormatter::fail(
                'Không có quyền truy cập. Chỉ giáo viên mới được phép.',
                null,
                403
            );
        }

        $teachers = Teacher::all();
        $teachersData = $teachers->map(function ($teacher) {
            return $this->removeSensitiveData($teacher->toArray());
        });

        return ResponseFormatter::success(
            $teachersData,
            'Lấy danh sách giáo viên thành công'
        );
    }

    public function getStudentsByClassroom(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role_code !== 'R1') {
            return ResponseFormatter::fail(
                'Không có quyền truy cập. Chỉ giáo viên mới được phép.',
                null,
                403
            );
        }

        $classroomCode = $request->query('classroom_code');
        if (!$classroomCode) {
            return ResponseFormatter::fail(
                'classroom_code là bắt buộc',
                null,
                422
            );
        }

        $classroom = Classroom::where('classroom_code', $classroomCode)->first();
        if (!$classroom) {
            return ResponseFormatter::fail(
                'Không tìm thấy lớp học',
                null,
                404
            );
        }

        $students = Student::where('classroom_code', $classroomCode)->get();
        $studentsData = $students->map(function ($student) {
            return $this->removeSensitiveData($student->toArray());
        });

        return ResponseFormatter::success(
            $studentsData,
            'Lấy danh sách học sinh thành công'
        );
    }

    public function assignHomeroomClassroom(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role_code !== 'R1') {
            return ResponseFormatter::fail(
                'Không có quyền thực hiện. Chỉ giáo viên mới được phép.',
                null,
                403
            );
        }

        $classroomCode = $request->input('classroom_code');
        if (!$classroomCode) {
            return ResponseFormatter::fail(
                'classroom_code là bắt buộc',
                null,
                422
            );
        }

        try {
            $classroom = Classroom::where('classroom_code', $classroomCode)->first();
            if (!$classroom) {
                return ResponseFormatter::fail(
                    'Không tìm thấy lớp học',
                    null,
                    404
                );
            }

            $this->authService->assignHomeroomTeacher($user, $classroomCode);

            return ResponseFormatter::success(
                $classroom->toArray(),
                'Gán giáo viên chủ nhiệm thành công'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::fail(
                $e->getMessage(),
                null,
                400
            );
        }
    }

    /**
     * Giáo viên nhận dạy lớp, hệ thống tự tính toán các môn có thể dạy.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignTeachingClassroom(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role_code !== 'R1') {
            return ResponseFormatter::fail(
                'Không có quyền thực hiện. Chỉ giáo viên mới được phép.',
                null,
                403
            );
        }

        $classroomCode = $request->input('classroom_code');
        if (!$classroomCode) {
            return ResponseFormatter::fail(
                'classroom_code là bắt buộc',
                null,
                422
            );
        }

        try {
            $assignedSubjects = $this->teacherService->assignTeachingClassroom($user, $classroomCode);

            return ResponseFormatter::success(
                ['assigned_subjects' => $assignedSubjects],
                'Nhận dạy lớp thành công'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::fail(
                $e->getMessage(),
                null,
                400
            );
        }
    }

    /**
     * Lấy danh sách giáo viên dạy trong một lớp dựa trên classroom_code.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTeachersInClassroom(Request $request)
    {
        $classroomCode = $request->query('classroom_code');
        if (!$classroomCode) {
            return ResponseFormatter::fail(
                'classroom_code là bắt buộc',
                null,
                422
            );
        }

        try {
            $teachers = $this->teacherService->getTeachersInClassroom($classroomCode);

            return ResponseFormatter::success(
                $teachers,
                'Lấy danh sách giáo viên dạy trong lớp thành công'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::fail(
                $e->getMessage(),
                null,
                400
            );
        }
    }

    private function removeSensitiveData(array $data)
    {
        unset($data['password']);
        return $data;
    }
}