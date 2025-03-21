<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\Exam;
use Illuminate\Http\JsonResponse;

class ExamController extends Controller
{
    /**
     * Lấy danh sách tất cả các kỳ thi.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $exams = Exam::all([
                'exam_code',
                'exam_name',
                'subject_code',
                'term_code',
                'date'
            ]);
            return ResponseFormatter::success(
                $exams,
                'Lấy danh sách kỳ thi thành công'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::fail(
                'Không thể lấy danh sách kỳ thi: ' . $e->getMessage(),
                null,
                500
            );
        }
    }
}