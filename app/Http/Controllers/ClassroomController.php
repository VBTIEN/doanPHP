<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\Classroom;
use Illuminate\Http\JsonResponse;

class ClassroomController extends Controller
{
    /**
     * Lấy danh sách tất cả các lớp học.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $classrooms = Classroom::all([
                'classroom_code',
                'classroom_name',
                'grade_code',
                'student_count',
                'homeroom_teacher_code'
            ]);
            return ResponseFormatter::success(
                $classrooms,
                'Lấy danh sách lớp học thành công'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::fail(
                'Không thể lấy danh sách lớp học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }
}