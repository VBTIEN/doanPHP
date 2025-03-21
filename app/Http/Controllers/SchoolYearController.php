<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\SchoolYear;
use Illuminate\Http\JsonResponse;

class SchoolYearController extends Controller
{
    /**
     * Lấy danh sách tất cả các năm học.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $schoolYears = SchoolYear::all(['school_year_code', 'school_year_name']);
            return ResponseFormatter::success(
                $schoolYears,
                'Lấy danh sách năm học thành công'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::fail(
                'Không thể lấy danh sách năm học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }
}