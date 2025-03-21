<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\Grade;
use Illuminate\Http\JsonResponse;

class GradeController extends Controller
{
    /**
     * Lấy danh sách tất cả các khối.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $grades = Grade::all([
                'grade_code',
                'grade_name',
                'classroom_count',
                'school_year_code'
            ]);
            return ResponseFormatter::success(
                $grades,
                'Lấy danh sách khối thành công'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::fail(
                'Không thể lấy danh sách khối: ' . $e->getMessage(),
                null,
                500
            );
        }
    }
}