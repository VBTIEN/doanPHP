<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use Illuminate\Http\Request;
use App\Services\StudentService;
use Illuminate\Support\Facades\Log;

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
        $user = $request->user();
        if (!$user || $user->role_code !== 'R2') {
            return ResponseFormatter::fail(
                'Không có quyền truy cập. Chỉ học sinh mới được phép.',
                null,
                403
            );
        }

        $subjectCode = $request->input('subject_code');
        $termCode = $request->input('term_code');

        try {
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

    /**
     * Cập nhật thông tin của học sinh đã xác thực.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role_code !== 'R2') {
            return ResponseFormatter::fail(
                'Không có quyền truy cập. Chỉ học sinh mới được phép.',
                null,
                403
            );
        }

        try {
            // Gọi service để xử lý logic cập nhật
            $updatedStudent = $this->studentService->updateStudent($request, $user);

            return ResponseFormatter::success(
                $this->removeSensitiveData($updatedStudent->toArray()),
                'Cập nhật thông tin học sinh thành công'
            );
        } catch (\Exception $e) {
            Log::error("Error in update student: " . $e->getMessage());
            return ResponseFormatter::fail(
                $e->getMessage(),
                null,
                400
            );
        }
    }

    /**
     * Loại bỏ dữ liệu nhạy cảm trước khi trả về.
     *
     * @param array $data
     * @return array
     */
    private function removeSensitiveData(array $data)
    {
        unset($data['password']);
        return $data;
    }
}