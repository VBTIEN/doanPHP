<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use Illuminate\Http\Request;
use App\Services\StudentService;

class StudentController extends Controller
{
    protected $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    /**
     * Lấy danh sách điểm của học sinh đã xác thực.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getScores(Request $request)
    {
        // Kiểm tra người dùng có phải là học sinh không
        $user = $request->user();
        if (!$user || $user->role_code !== 'R2') {
            return ResponseFormatter::fail(
                'Không có quyền truy cập. Chỉ học sinh mới được phép.',
                null,
                403
            );
        }

        // Lấy subject_code và term_code từ request body
        $subjectCode = $request->input('subject_code');
        $termCode = $request->input('term_code');

        try {
            // Lấy danh sách điểm của học sinh
            $scores = $this->studentService->getStudentScores($user->student_code, $subjectCode, $termCode);

            if (empty($scores)) {
                return ResponseFormatter::success(
                    [],
                    'Không tìm thấy điểm nào phù hợp với bộ lọc'
                );
            }

            return ResponseFormatter::success(
                $scores,
                'Lấy danh sách điểm thành công'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::fail(
                $e->getMessage(),
                null,
                400
            );
        }
    }
}